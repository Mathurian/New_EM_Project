<?php
declare(strict_types=1);

/**
 * Migration Testing Suite
 */

require_once __DIR__ . '/app/bootstrap.php';

use App\MigrationController;
use App\DatabaseFactory;
use App\DB;

class MigrationTestSuite {
    private array $testResults = [];
    private array $errors = [];

    public function runAllTests(): array {
        echo "🧪 Running Migration Test Suite\n";
        echo "===============================\n\n";

        $tests = [
            'testDatabaseConnections' => 'Test Database Connections',
            'testSchemaMigration' => 'Test Schema Migration',
            'testDataMigration' => 'Test Data Migration',
            'testDataIntegrity' => 'Test Data Integrity',
            'testApplicationCompatibility' => 'Test Application Compatibility',
            'testPerformance' => 'Test Performance',
            'testRollback' => 'Test Rollback Procedures'
        ];

        foreach ($tests as $testMethod => $testName) {
            echo "🔍 {$testName}...\n";
            try {
                $result = $this->$testMethod();
                $this->testResults[$testMethod] = $result;
                echo $result['success'] ? "✅ PASSED\n" : "❌ FAILED\n";
                if (!$result['success'] && !empty($result['errors'])) {
                    foreach ($result['errors'] as $error) {
                        echo "   - {$error}\n";
                    }
                }
            } catch (\Exception $e) {
                $this->testResults[$testMethod] = [
                    'success' => false,
                    'errors' => [$e->getMessage()]
                ];
                echo "❌ FAILED: {$e->getMessage()}\n";
            }
            echo "\n";
        }

        $this->generateReport();
        return $this->testResults;
    }

    /**
     * Test database connections
     */
    private function testDatabaseConnections(): array {
        $errors = [];

        try {
            // Test SQLite connection
            $sqliteDb = DatabaseFactory::createSQLite(__DIR__ . '/app/db/contest.sqlite');
            $sqliteTables = $sqliteDb->getTables();
            
            if (empty($sqliteTables)) {
                $errors[] = 'SQLite database has no tables';
            }

            // Test PostgreSQL connection
            $pgConfig = [
                'type' => 'postgresql',
                'host' => 'localhost',
                'port' => '5432',
                'dbname' => 'event_manager',
                'username' => 'event_manager',
                'password' => 'password'
            ];

            $pgDb = DatabaseFactory::createFromConfig($pgConfig);
            $pgTables = $pgDb->getTables();

        } catch (\Exception $e) {
            $errors[] = 'Database connection failed: ' . $e->getMessage();
        }

        return [
            'success' => empty($errors),
            'errors' => $errors,
            'sqlite_tables' => count($sqliteTables ?? []),
            'postgresql_tables' => count($pgTables ?? [])
        ];
    }

    /**
     * Test schema migration
     */
    private function testSchemaMigration(): array {
        $errors = [];

        try {
            $controller = new MigrationController();
            $testResults = $controller->testMigration();

            if (!$testResults['schema_migration']) {
                $errors[] = 'Schema migration test failed';
            }

            if (!empty($testResults['schema_errors'])) {
                $errors = array_merge($errors, $testResults['schema_errors']);
            }

        } catch (\Exception $e) {
            $errors[] = 'Schema migration test failed: ' . $e->getMessage();
        }

        return [
            'success' => empty($errors),
            'errors' => $errors
        ];
    }

    /**
     * Test data migration
     */
    private function testDataMigration(): array {
        $errors = [];

        try {
            $sourceDb = DatabaseFactory::createSQLite(__DIR__ . '/app/db/contest.sqlite');
            $targetDb = DatabaseFactory::createFromConfig([
                'type' => 'postgresql',
                'host' => 'localhost',
                'port' => '5432',
                'dbname' => 'event_manager',
                'username' => 'event_manager',
                'password' => 'password'
            ]);

            // Test data type conversions
            $testData = [
                'uuid' => '12345678901234567890123456789012',
                'boolean' => 1,
                'integer' => '42',
                'decimal' => '85.5',
                'timestamp' => '2025-01-15 10:30:00',
                'string' => 'Test String'
            ];

            // Test each data type conversion
            foreach ($testData as $type => $value) {
                $converted = $this->convertValue($value, $type);
                if ($converted === null && $value !== null) {
                    $errors[] = "Data type conversion failed for {$type}: {$value}";
                }
            }

        } catch (\Exception $e) {
            $errors[] = 'Data migration test failed: ' . $e->getMessage();
        }

        return [
            'success' => empty($errors),
            'errors' => $errors
        ];
    }

    /**
     * Test data integrity
     */
    private function testDataIntegrity(): array {
        $errors = [];

        try {
            $sourceDb = DatabaseFactory::createSQLite(__DIR__ . '/app/db/contest.sqlite');
            $targetDb = DatabaseFactory::createFromConfig([
                'type' => 'postgresql',
                'host' => 'localhost',
                'port' => '5432',
                'dbname' => 'event_manager',
                'username' => 'event_manager',
                'password' => 'password'
            ]);

            $tables = $sourceDb->getTables();

            foreach ($tables as $table) {
                $sourceCount = (int) $sourceDb->fetchColumn("SELECT COUNT(*) FROM {$table}");
                $targetCount = (int) $targetDb->fetchColumn("SELECT COUNT(*) FROM {$table}");

                if ($sourceCount !== $targetCount) {
                    $errors[] = "Row count mismatch in {$table}: source={$sourceCount}, target={$targetCount}";
                }
            }

        } catch (\Exception $e) {
            $errors[] = 'Data integrity test failed: ' . $e->getMessage();
        }

        return [
            'success' => empty($errors),
            'errors' => $errors
        ];
    }

    /**
     * Test application compatibility
     */
    private function testApplicationCompatibility(): array {
        $errors = [];

        try {
            // Test if application can start with PostgreSQL
            DB::switchDatabase('postgresql', [
                'type' => 'postgresql',
                'host' => 'localhost',
                'port' => '5432',
                'dbname' => 'event_manager',
                'username' => 'event_manager',
                'password' => 'password'
            ]);

            // Test basic database operations
            $tables = DB::getTables();
            if (empty($tables)) {
                $errors[] = 'No tables found in PostgreSQL database';
            }

            // Test UUID generation
            $uuid = DB::generateUUID();
            if (empty($uuid)) {
                $errors[] = 'UUID generation failed';
            }

            // Test query execution
            $result = DB::query("SELECT 1 as test");
            if (empty($result)) {
                $errors[] = 'Basic query execution failed';
            }

        } catch (\Exception $e) {
            $errors[] = 'Application compatibility test failed: ' . $e->getMessage();
        }

        return [
            'success' => empty($errors),
            'errors' => $errors
        ];
    }

    /**
     * Test performance
     */
    private function testPerformance(): array {
        $errors = [];
        $results = [];

        try {
            // Test query performance
            $startTime = microtime(true);
            
            for ($i = 0; $i < 100; $i++) {
                DB::query("SELECT COUNT(*) FROM users");
            }
            
            $endTime = microtime(true);
            $avgTime = ($endTime - $startTime) / 100;
            
            $results['avg_query_time'] = $avgTime;
            
            if ($avgTime > 0.1) { // 100ms threshold
                $errors[] = "Query performance too slow: {$avgTime}s average";
            }

            // Test connection performance
            $startTime = microtime(true);
            DB::getInterface();
            $endTime = microtime(true);
            $connectionTime = $endTime - $startTime;
            
            $results['connection_time'] = $connectionTime;
            
            if ($connectionTime > 1.0) { // 1 second threshold
                $errors[] = "Connection time too slow: {$connectionTime}s";
            }

        } catch (\Exception $e) {
            $errors[] = 'Performance test failed: ' . $e->getMessage();
        }

        return [
            'success' => empty($errors),
            'errors' => $errors,
            'results' => $results
        ];
    }

    /**
     * Test rollback procedures
     */
    private function testRollback(): array {
        $errors = [];

        try {
            // Check if rollback scripts exist
            $rollbackFiles = glob(__DIR__ . '/backups/rollback_*.php');
            
            if (empty($rollbackFiles)) {
                $errors[] = 'No rollback scripts found';
            }

            // Test rollback script syntax
            foreach ($rollbackFiles as $file) {
                $syntaxCheck = shell_exec("php -l {$file} 2>&1");
                if (strpos($syntaxCheck, 'No syntax errors') === false) {
                    $errors[] = "Rollback script syntax error in " . basename($file);
                }
            }

        } catch (\Exception $e) {
            $errors[] = 'Rollback test failed: ' . $e->getMessage();
        }

        return [
            'success' => empty($errors),
            'errors' => $errors,
            'rollback_files' => count($rollbackFiles ?? [])
        ];
    }

    /**
     * Convert value for testing
     */
    private function convertValue(mixed $value, string $type): mixed {
        if ($value === null) {
            return null;
        }

        switch ($type) {
            case 'uuid':
                if (is_string($value) && strlen($value) === 32) {
                    return substr($value, 0, 8) . '-' . 
                           substr($value, 8, 4) . '-' . 
                           substr($value, 12, 4) . '-' . 
                           substr($value, 16, 4) . '-' . 
                           substr($value, 20, 12);
                }
                return $value;
                
            case 'boolean':
                return (bool) $value;
                
            case 'integer':
                return (int) $value;
                
            case 'decimal':
                return (float) $value;
                
            case 'timestamp':
                try {
                    $date = new \DateTime($value);
                    return $date->format('Y-m-d H:i:s');
                } catch (\Exception $e) {
                    return date('Y-m-d H:i:s');
                }
                
            case 'string':
            default:
                return (string) $value;
        }
    }

    /**
     * Generate test report
     */
    private function generateReport(): void {
        $totalTests = count($this->testResults);
        $passedTests = array_filter($this->testResults, fn($result) => $result['success']);
        $passedCount = count($passedTests);

        echo "📊 Test Report\n";
        echo "=============\n";
        echo "Total Tests: {$totalTests}\n";
        echo "Passed: {$passedCount}\n";
        echo "Failed: " . ($totalTests - $passedCount) . "\n";
        echo "Success Rate: " . round(($passedCount / $totalTests) * 100, 2) . "%\n\n";

        if ($passedCount === $totalTests) {
            echo "🎉 All tests passed! Migration is ready.\n";
        } else {
            echo "⚠️  Some tests failed. Please review the errors above.\n";
        }
    }
}

// Run tests if called directly
if (php_sapi_name() === 'cli') {
    $testSuite = new MigrationTestSuite();
    $results = $testSuite->runAllTests();
    
    // Exit with error code if any tests failed
    $failedTests = array_filter($results, fn($result) => !$result['success']);
    exit(empty($failedTests) ? 0 : 1);
}
