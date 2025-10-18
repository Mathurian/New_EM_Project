<?php
/**
 * Fix Judge Certifications Migration Script (Robust Rebuild + Lock Handling)
 *
 * Recreates judge_certifications with UNIQUE(subcategory_id, contestant_id, judge_id),
 * migrating existing data safely. Adds WAL mode and busy_timeout with retries to
 * mitigate SQLite locking errors on busy servers.
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once __DIR__ . '/app/lib/DB.php';

use App\DB;

function uuid_hex(): string { return bin2hex(random_bytes(16)); }

echo "Starting robust judge_certifications migration...\n";

$pdo = DB::pdo();
$pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

// Improve resilience on busy SQLite DBs
try {
	$pdo->exec('PRAGMA journal_mode=WAL');       // better concurrency
	$pdo->exec('PRAGMA synchronous=NORMAL');     // safer + faster in WAL
	$pdo->exec('PRAGMA busy_timeout=10000');     // wait up to 10s on locks
} catch (\Throwable $e) {
	// Non-fatal
}

$maxAttempts = 8;
$attempt = 1;
$lastError = null;

while ($attempt <= $maxAttempts) {
	try {
		// Inspect current table SQL
		$stmt = $pdo->prepare("SELECT sql FROM sqlite_master WHERE type='table' AND name='judge_certifications'");
		$stmt->execute();
		$tableSql = (string)($stmt->fetchColumn() ?: '');
		if ($tableSql === '') {
			echo "Table judge_certifications not found. Nothing to do.\n";
			break;
		}

		$hasContestantId = false;
		$oldUniqueTwoCol = false;
		
		// Check columns
		$cols = $pdo->query('PRAGMA table_info(judge_certifications)')->fetchAll(\PDO::FETCH_ASSOC);
		foreach ($cols as $col) {
			if (($col['name'] ?? '') === 'contestant_id') { $hasContestantId = true; }
		}
		
		// Detect old UNIQUE(subcategory_id, judge_id)
		$normalized = strtolower(preg_replace('/\s+/', ' ', $tableSql));
		if (strpos($normalized, 'unique (subcategory_id, judge_id)') !== false) {
			$oldUniqueTwoCol = true;
		}
		
		if ($hasContestantId && !$oldUniqueTwoCol) {
			echo "✓ judge_certifications already has contestant_id and correct UNIQUE. No action needed.\n";
			break;
		}

		echo "Rebuilding judge_certifications table with contestant_id and 3-column UNIQUE... (attempt $attempt)\n";

		$pdo->exec('PRAGMA foreign_keys = OFF');
		$pdo->beginTransaction();

		// 1) Create new table with desired schema
		$pdo->exec(
			"CREATE TABLE IF NOT EXISTS judge_certifications_new (
				id TEXT PRIMARY KEY,
				subcategory_id TEXT NOT NULL,
				contestant_id TEXT NOT NULL,
				judge_id TEXT NOT NULL,
				signature_name TEXT NOT NULL,
				certified_at TEXT NOT NULL,
				UNIQUE (subcategory_id, contestant_id, judge_id)
			)"
		);

		// 2) Read all existing certifications from old table
		$old = $pdo->query('SELECT id, subcategory_id, judge_id, signature_name, certified_at,'
			. ($hasContestantId ? ' contestant_id' : ' NULL AS contestant_id')
			. ' FROM judge_certifications')->fetchAll(\PDO::FETCH_ASSOC);

		$insert = $pdo->prepare('INSERT OR IGNORE INTO judge_certifications_new 
			(id, subcategory_id, contestant_id, judge_id, signature_name, certified_at)
			VALUES (?, ?, ?, ?, ?, ?)');

		$migrated = 0; $expanded = 0; $copied = 0;

		foreach ($old as $row) {
			$subcategoryId = $row['subcategory_id'];
			$judgeId = $row['judge_id'];
			$certId = $row['id'];
			$signature = $row['signature_name'];
			$certAt = $row['certified_at'];
			$contestantId = $row['contestant_id'];

			if ($contestantId) {
				// Already per-contestant: copy as-is
				$insert->execute([$certId ?: uuid_hex(), $subcategoryId, $contestantId, $judgeId, $signature, $certAt]);
				$copied++;
				continue;
			}

			// Old style certification: expand to all contestants in subcategory
			$cs = $pdo->prepare('SELECT contestant_id FROM subcategory_contestants WHERE subcategory_id = ?');
			$cs->execute([$subcategoryId]);
			$contestants = $cs->fetchAll(\PDO::FETCH_COLUMN);
			if (!$contestants) {
				// If no mapping exists, skip (cannot infer contestants)
				continue;
			}
			foreach ($contestants as $cid) {
				$insert->execute([uuid_hex(), $subcategoryId, $cid, $judgeId, $signature, $certAt]);
				$expanded++;
			}
			$migrated++;
		}

		// 3) Replace the old table
		$pdo->exec('DROP TABLE judge_certifications');
		$pdo->exec('ALTER TABLE judge_certifications_new RENAME TO judge_certifications');

		// 4) Create explicit unique index (redundant with table UNIQUE but explicit)
		$pdo->exec('CREATE UNIQUE INDEX IF NOT EXISTS judge_certifications_unique ON judge_certifications (subcategory_id, contestant_id, judge_id)');

		$pdo->commit();
		$pdo->exec('PRAGMA foreign_keys = ON');

		echo "✓ Rebuild complete. Copied: $copied, Expanded: $expanded from $migrated old certifications.\n";
		echo "Verification...\n";
		$dups = $pdo->query("SELECT COUNT(*) FROM (SELECT subcategory_id, contestant_id, judge_id, COUNT(*) c FROM judge_certifications GROUP BY 1,2,3 HAVING c>1)")->fetchColumn();
		if ((int)$dups > 0) {
			echo "⚠ Found duplicate groups after rebuild. Please inspect database.\n";
		} else {
			echo "✓ No duplicate groups detected.\n";
		}
		echo "Done.\n";
		$lastError = null;
		break;
	} catch (\Throwable $e) {
		$lastError = $e;
		if ($pdo->inTransaction()) { $pdo->rollBack(); }
		$pdo->exec('PRAGMA foreign_keys = ON');

		$locked = stripos($e->getMessage(), 'locked') !== false || stripos($e->getMessage(), 'database is locked') !== false;
		if ($locked && $attempt < $maxAttempts) {
			$delay = min(10, 1 * (1 << ($attempt - 1))); // 1,2,4,8,10,10,10...
			echo "Lock detected; retrying in {$delay}s (attempt $attempt of $maxAttempts)...\n";
			sleep($delay);
			$attempt++;
			continue;
		}

		echo '❌ Error: ' . $e->getMessage() . "\n";
		echo $e->getTraceAsString() . "\n";
		break;
	}
}

if ($lastError) {
	echo "Migration failed after retries.\n";
	exit(1);
}
