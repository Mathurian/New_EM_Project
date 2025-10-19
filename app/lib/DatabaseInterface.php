<?php
declare(strict_types=1);

namespace App;

/**
 * Database abstraction layer for SQLite and PostgreSQL compatibility
 */
interface DatabaseInterface {
    public function query(string $sql, array $params = []): array;
    public function execute(string $sql, array $params = []): bool;
    public function fetchOne(string $sql, array $params = []): ?array;
    public function fetchColumn(string $sql, array $params = []): mixed;
    public function lastInsertId(): string;
    public function beginTransaction(): bool;
    public function commit(): bool;
    public function rollback(): bool;
    public function getTableInfo(string $tableName): array;
    public function getTables(): array;
    public function getDatabaseType(): string;
}

/**
 * SQLite Database Implementation
 */
class SQLiteDatabase implements DatabaseInterface {
    private ?\PDO $pdo = null;
    private string $dbPath;

    public function __construct(string $dbPath) {
        $this->dbPath = $dbPath;
    }

    private function getPdo(): \PDO {
        if (!$this->pdo) {
            $this->pdo = new \PDO('sqlite:' . $this->dbPath);
            $this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            
            // SQLite-specific optimizations
            $this->pdo->exec('PRAGMA foreign_keys = ON');
            $this->pdo->exec('PRAGMA journal_mode = WAL');
            $this->pdo->exec('PRAGMA synchronous = NORMAL');
            $this->pdo->exec('PRAGMA cache_size = 10000');
            $this->pdo->exec('PRAGMA temp_store = MEMORY');
            $this->pdo->exec('PRAGMA busy_timeout = 30000');
        }
        return $this->pdo;
    }

    public function query(string $sql, array $params = []): array {
        $stmt = $this->getPdo()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function execute(string $sql, array $params = []): bool {
        $stmt = $this->getPdo()->prepare($sql);
        return $stmt->execute($params);
    }

    public function fetchOne(string $sql, array $params = []): ?array {
        $stmt = $this->getPdo()->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    public function fetchColumn(string $sql, array $params = []): mixed {
        $stmt = $this->getPdo()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn();
    }

    public function lastInsertId(): string {
        return $this->getPdo()->lastInsertId();
    }

    public function beginTransaction(): bool {
        return $this->getPdo()->beginTransaction();
    }

    public function commit(): bool {
        return $this->getPdo()->commit();
    }

    public function rollback(): bool {
        return $this->getPdo()->rollback();
    }

    public function getTableInfo(string $tableName): array {
        $sql = "PRAGMA table_info(`{$tableName}`)";
        return $this->query($sql);
    }

    public function getTables(): array {
        $sql = "SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%' ORDER BY name";
        return array_column($this->query($sql), 'name');
    }

    public function getDatabaseType(): string {
        return 'sqlite';
    }
}

/**
 * PostgreSQL Database Implementation
 */
class PostgreSQLDatabase implements DatabaseInterface {
    private ?\PDO $pdo = null;
    private string $host;
    private string $port;
    private string $dbname;
    private string $username;
    private string $password;

    public function __construct(string $host, string $port, string $dbname, string $username, string $password) {
        $this->host = $host;
        $this->port = $port;
        $this->dbname = $dbname;
        $this->username = $username;
        $this->password = $password;
    }

    private function getPdo(): \PDO {
        if (!$this->pdo) {
            $dsn = "pgsql:host={$this->host};port={$this->port};dbname={$this->dbname}";
            $this->pdo = new \PDO($dsn, $this->username, $this->password);
            $this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            
            // PostgreSQL-specific settings
            $this->pdo->exec('SET timezone = UTC');
            $this->pdo->exec('SET standard_conforming_strings = on');
        }
        return $this->pdo;
    }

    public function query(string $sql, array $params = []): array {
        $stmt = $this->getPdo()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function execute(string $sql, array $params = []): bool {
        $stmt = $this->getPdo()->prepare($sql);
        return $stmt->execute($params);
    }

    public function fetchOne(string $sql, array $params = []): ?array {
        $stmt = $this->getPdo()->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    public function fetchColumn(string $sql, array $params = []): mixed {
        $stmt = $this->getPdo()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn();
    }

    public function lastInsertId(): string {
        return $this->getPdo()->lastInsertId();
    }

    public function beginTransaction(): bool {
        return $this->getPdo()->beginTransaction();
    }

    public function commit(): bool {
        return $this->getPdo()->commit();
    }

    public function rollback(): bool {
        return $this->getPdo()->rollback();
    }

    public function getTableInfo(string $tableName): array {
        $sql = "
            SELECT 
                column_name,
                data_type,
                is_nullable,
                column_default,
                character_maximum_length
            FROM information_schema.columns 
            WHERE table_name = ? 
            ORDER BY ordinal_position
        ";
        return $this->query($sql, [$tableName]);
    }

    public function getTables(): array {
        $sql = "
            SELECT table_name 
            FROM information_schema.tables 
            WHERE table_schema = 'public' 
            ORDER BY table_name
        ";
        return array_column($this->query($sql), 'table_name');
    }

    public function getDatabaseType(): string {
        return 'postgresql';
    }
}

/**
 * Database Factory for creating database instances
 */
class DatabaseFactory {
    public static function createSQLite(string $dbPath): SQLiteDatabase {
        return new SQLiteDatabase($dbPath);
    }

    public static function createPostgreSQL(
        string $host,
        string $port,
        string $dbname,
        string $username,
        string $password
    ): PostgreSQLDatabase {
        return new PostgreSQLDatabase($host, $port, $dbname, $username, $password);
    }

    public static function createFromConfig(array $config): DatabaseInterface {
        $type = $config['type'] ?? 'sqlite';
        
        switch ($type) {
            case 'sqlite':
                return self::createSQLite($config['path']);
            case 'postgresql':
                return self::createPostgreSQL(
                    $config['host'],
                    $config['port'],
                    $config['dbname'],
                    $config['username'],
                    $config['password']
                );
            default:
                throw new \InvalidArgumentException("Unsupported database type: {$type}");
        }
    }
}
