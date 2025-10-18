<?php
declare(strict_types=1);

/**
 * Simple test runner
 */
require_once __DIR__ . '/../app/bootstrap.php';

class TestRunner
{
    private array $tests = [];
    private int $passed = 0;
    private int $failed = 0;
    private array $failures = [];

    public function addTest(string $testClass): void
    {
        $this->tests[] = $testClass;
    }

    public function run(): void
    {
        echo "Running tests...\n\n";
        
        foreach ($this->tests as $testClass) {
            $this->runTestClass($testClass);
        }
        
        $this->printSummary();
    }

    private function runTestClass(string $testClass): void
    {
        echo "Running {$testClass}:\n";
        
        $reflection = new ReflectionClass($testClass);
        $methods = $reflection->getMethods(ReflectionMethod::IS_PUBLIC);
        
        foreach ($methods as $method) {
            if (strpos($method->getName(), 'test') === 0) {
                $this->runTestMethod($testClass, $method->getName());
            }
        }
        
        echo "\n";
    }

    private function runTestMethod(string $testClass, string $methodName): void
    {
        try {
            $test = new $testClass();
            $test->setUp();
            $test->$methodName();
            $test->tearDown();
            
            echo "  ✓ {$methodName}\n";
            $this->passed++;
            
        } catch (Throwable $e) {
            echo "  ✗ {$methodName} - {$e->getMessage()}\n";
            $this->failed++;
            $this->failures[] = "{$testClass}::{$methodName} - {$e->getMessage()}";
        }
    }

    private function printSummary(): void
    {
        $total = $this->passed + $this->failed;
        
        echo "Test Summary:\n";
        echo "=============\n";
        echo "Total: {$total}\n";
        echo "Passed: {$this->passed}\n";
        echo "Failed: {$this->failed}\n";
        
        if ($this->failed > 0) {
            echo "\nFailures:\n";
            foreach ($this->failures as $failure) {
                echo "  - {$failure}\n";
            }
        }
        
        echo "\n" . ($this->failed === 0 ? "All tests passed! 🎉" : "Some tests failed.") . "\n";
    }
}

// Run tests
$runner = new TestRunner();
$runner->addTest('Tests\DatabaseServiceTest');
$runner->addTest('Tests\SecurityServiceTest');
$runner->run();