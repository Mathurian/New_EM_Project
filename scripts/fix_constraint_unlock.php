<?php
// Unlock & Rebuild for judge_certifications (inspired by previous unlock scripts)
// Run via: php scripts/fix_constraint_unlock.php

require_once __DIR__ . '/../app/lib/DB.php';

use App\DB;

function uuid_hex(): string { return bin2hex(random_bytes(16)); }

$pdo = DB::pdo();
$pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

function try_pragmas(\PDO $pdo): void {
	try { $pdo->exec('PRAGMA wal_checkpoint(TRUNCATE)'); } catch (\Throwable $e) {}
	try { $pdo->exec('PRAGMA journal_mode=WAL'); } catch (\Throwable $e) {}
	try { $pdo->exec('PRAGMA synchronous=NORMAL'); } catch (\Throwable $e) {}
	try { $pdo->exec('PRAGMA busy_timeout=20000'); } catch (\Throwable $e) {}
}

function ensure_transaction_cleared(\PDO $pdo): void {
	try { $pdo->exec('ROLLBACK'); } catch (\Throwable $e) {}
	try { $pdo->exec('END'); } catch (\Throwable $e) {}
}

function rebuild(\PDO $pdo): void {
	// Quick state checks
	$s = $pdo->query("SELECT sql FROM sqlite_master WHERE type='table' AND name='judge_certifications'");
	$sql = (string)($s->fetchColumn() ?: '');
	if ($sql === '') { echo "No judge_certifications table, nothing to do.\n"; return; }
	$hasContestant = false; $cols = $pdo->query('PRAGMA table_info(judge_certifications)')->fetchAll(\PDO::FETCH_ASSOC);
	foreach ($cols as $c) { if (($c['name'] ?? '') === 'contestant_id') { $hasContestant = true; } }
	$oldTwoCol = strpos(strtolower(preg_replace('/\s+/', ' ', $sql)), 'unique (subcategory_id, judge_id)') !== false;
	if ($hasContestant && !$oldTwoCol) { echo "Already correct schema.\n"; return; }

	$pdo->exec('PRAGMA foreign_keys=OFF');
	// Clear any dangling transactions from previous attempts
	ensure_transaction_cleared($pdo);
	if (!$pdo->inTransaction()) {
		$pdo->exec('BEGIN IMMEDIATE');
	}

	$pdo->exec("CREATE TABLE IF NOT EXISTS judge_certifications_new (
		id TEXT PRIMARY KEY,
		subcategory_id TEXT NOT NULL,
		contestant_id TEXT NOT NULL,
		judge_id TEXT NOT NULL,
		signature_name TEXT NOT NULL,
		certified_at TEXT NOT NULL,
		UNIQUE (subcategory_id, contestant_id, judge_id)
	)");

	$old = $pdo->query('SELECT id, subcategory_id, judge_id, signature_name, certified_at,'
		. ($hasContestant ? ' contestant_id' : ' NULL AS contestant_id')
		. ' FROM judge_certifications')->fetchAll(\PDO::FETCH_ASSOC);
	$ins = $pdo->prepare('INSERT OR IGNORE INTO judge_certifications_new (id, subcategory_id, contestant_id, judge_id, signature_name, certified_at) VALUES (?,?,?,?,?,?)');
	foreach ($old as $row) {
		if (!empty($row['contestant_id'])) {
			$ins->execute([$row['id'] ?: uuid_hex(), $row['subcategory_id'], $row['contestant_id'], $row['judge_id'], $row['signature_name'], $row['certified_at']]);
			continue;
		}
		$cs = $pdo->prepare('SELECT contestant_id FROM subcategory_contestants WHERE subcategory_id=?');
		$cs->execute([$row['subcategory_id']]);
		foreach ($cs->fetchAll(\PDO::FETCH_COLUMN) as $cid) {
			$ins->execute([uuid_hex(), $row['subcategory_id'], $cid, $row['judge_id'], $row['signature_name'], $row['certified_at']]);
		}
	}
	$pdo->exec('DROP TABLE judge_certifications');
	$pdo->exec('ALTER TABLE judge_certifications_new RENAME TO judge_certifications');
	$pdo->exec('CREATE UNIQUE INDEX IF NOT EXISTS judge_certifications_unique ON judge_certifications (subcategory_id, contestant_id, judge_id)');
	$pdo->exec('COMMIT');
	$pdo->exec('PRAGMA foreign_keys=ON');
}

$max = 8; $i = 1;
while ($i <= $max) {
	try_pragmas($pdo);
	try {
		rebuild($pdo);
		echo "Done.\n";
		exit(0);
	} catch (\Throwable $e) {
		// Ensure the tx is closed before retry to avoid nested BEGINs
		ensure_transaction_cleared($pdo);
		$locked = stripos($e->getMessage(), 'locked') !== false || stripos($e->getMessage(), 'transaction') !== false;
		fwrite(STDERR, "Attempt $i failed: " . $e->getMessage() . "\n");
		if (!$locked || $i === $max) {
			throw $e;
		}
		sleep(min(10, 1 << ($i - 1)));
		$i++;
	}
}
