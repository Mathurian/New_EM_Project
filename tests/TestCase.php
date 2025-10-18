<?php
declare(strict_types=1);

namespace Tests;

use App\{DB, Cache, Logger};
use PDO;

/**
 * Base test case with common testing utilities
 */
abstract class TestCase
{
    protected PDO $pdo;
    protected string $testDbPath;

    protected function setUp(): void
    {
        // Create test database
        $this->testDbPath = __DIR__ . '/../storage/test_contest.sqlite';
        
        // Remove existing test database
        if (file_exists($this->testDbPath)) {
            unlink($this->testDbPath);
        }
        
        // Create new test database
        $this->pdo = new PDO("sqlite:{$this->testDbPath}");
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Initialize cache
        Cache::init();
        
        // Run migrations
        $this->runMigrations();
    }

    protected function tearDown(): void
    {
        // Clean up test database
        if (file_exists($this->testDbPath)) {
            unlink($this->testDbPath);
        }
        
        // Clear cache
        Cache::flush();
    }

    protected function runMigrations(): void
    {
        // Basic table creation for testing
        $sql = <<<'SQL'
        CREATE TABLE IF NOT EXISTS users (
            id TEXT PRIMARY KEY,
            name TEXT NOT NULL,
            email TEXT,
            password_hash TEXT,
            role TEXT NOT NULL,
            created_at TEXT NOT NULL
        );
        
        CREATE TABLE IF NOT EXISTS contests (
            id TEXT PRIMARY KEY,
            name TEXT NOT NULL,
            start_date TEXT NOT NULL,
            end_date TEXT NOT NULL,
            archived INTEGER DEFAULT 0
        );
        
        CREATE TABLE IF NOT EXISTS categories (
            id TEXT PRIMARY KEY,
            contest_id TEXT NOT NULL,
            name TEXT NOT NULL,
            FOREIGN KEY (contest_id) REFERENCES contests(id) ON DELETE CASCADE
        );
        
        CREATE TABLE IF NOT EXISTS activity_logs (
            id TEXT PRIMARY KEY,
            level TEXT NOT NULL,
            entity_type TEXT,
            entity_id TEXT,
            message TEXT NOT NULL,
            context TEXT,
            created_at TEXT NOT NULL
        );
        SQL;
        
        $this->pdo->exec($sql);
    }

    protected function createUser(array $data = []): string
    {
        $id = bin2hex(random_bytes(16));
        $defaults = [
            'id' => $id,
            'name' => 'Test User',
            'email' => 'test@example.com',
            'role' => 'organizer',
            'created_at' => date('c')
        ];
        
        $userData = array_merge($defaults, $data);
        
        $stmt = $this->pdo->prepare('
            INSERT INTO users (id, name, email, password_hash, role, created_at) 
            VALUES (?, ?, ?, ?, ?, ?)
        ');
        $stmt->execute([
            $userData['id'],
            $userData['name'],
            $userData['email'],
            $userData['password_hash'] ?? null,
            $userData['role'],
            $userData['created_at']
        ]);
        
        return $id;
    }

    protected function createContest(array $data = []): string
    {
        $id = bin2hex(random_bytes(16));
        $defaults = [
            'id' => $id,
            'name' => 'Test Contest',
            'start_date' => date('Y-m-d'),
            'end_date' => date('Y-m-d', strtotime('+1 week')),
            'archived' => 0
        ];
        
        $contestData = array_merge($defaults, $data);
        
        $stmt = $this->pdo->prepare('
            INSERT INTO contests (id, name, start_date, end_date, archived) 
            VALUES (?, ?, ?, ?, ?)
        ');
        $stmt->execute([
            $contestData['id'],
            $contestData['name'],
            $contestData['start_date'],
            $contestData['end_date'],
            $contestData['archived']
        ]);
        
        return $id;
    }

    protected function assertDatabaseHas(string $table, array $conditions): void
    {
        $whereClause = '';
        $params = [];
        
        foreach ($conditions as $column => $value) {
            if ($whereClause) {
                $whereClause .= ' AND ';
            }
            $whereClause .= "{$column} = ?";
            $params[] = $value;
        }
        
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM {$table} WHERE {$whereClause}");
        $stmt->execute($params);
        $count = $stmt->fetchColumn();
        
        $this->assertTrue($count > 0, "Expected to find record in {$table} with conditions: " . json_encode($conditions));
    }

    protected function assertDatabaseMissing(string $table, array $conditions): void
    {
        $whereClause = '';
        $params = [];
        
        foreach ($conditions as $column => $value) {
            if ($whereClause) {
                $whereClause .= ' AND ';
            }
            $whereClause .= "{$column} = ?";
            $params[] = $value;
        }
        
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM {$table} WHERE {$whereClause}");
        $stmt->execute($params);
        $count = $stmt->fetchColumn();
        
        $this->assertTrue($count === 0, "Expected not to find record in {$table} with conditions: " . json_encode($conditions));
    }

    protected function assertTrue(bool $condition, string $message = ''): void
    {
        if (!$condition) {
            throw new \AssertionError($message ?: 'Assertion failed');
        }
    }

    protected function assertFalse(bool $condition, string $message = ''): void
    {
        $this->assertTrue(!$condition, $message ?: 'Assertion failed');
    }

    protected function assertEquals($expected, $actual, string $message = ''): void
    {
        if ($expected !== $actual) {
            throw new \AssertionError($message ?: "Expected {$expected}, got {$actual}");
        }
    }

    protected function assertArrayHasKey($key, array $array, string $message = ''): void
    {
        if (!array_key_exists($key, $array)) {
            throw new \AssertionError($message ?: "Array does not have key {$key}");
        }
    }

    protected function assertCount(int $expectedCount, $countable, string $message = ''): void
    {
        $actualCount = is_array($countable) ? count($countable) : count($countable);
        if ($actualCount !== $expectedCount) {
            throw new \AssertionError($message ?: "Expected count {$expectedCount}, got {$actualCount}");
        }
    }
}