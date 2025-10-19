#!/usr/bin/env php
<?php
declare(strict_types=1);

/**
 * Bootstrap Debug Script
 * 
 * This script tests each bootstrap component individually to find the hanging issue
 */

// Enable comprehensive error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', '/tmp/bootstrap_debug.log');

// Disable output buffering
if (ob_get_level()) {
    ob_end_flush();
}

function logMessage($message) {
    $logFile = '/tmp/bootstrap_debug.log';
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$timestamp] $message\n", FILE_APPEND);
    echo "[$timestamp] $message\n";
    flush();
}

logMessage("🚀 Starting bootstrap debug...");

// Test 1: Check if we can include basic files
logMessage("1. Testing basic file includes...");

try {
    logMessage("   Including helpers.php...");
    require_once __DIR__ . '/app/lib/helpers.php';
    logMessage("   ✅ helpers.php loaded");
} catch (\Exception $e) {
    logMessage("   ❌ helpers.php failed: " . $e->getMessage());
    exit(1);
}

try {
    logMessage("   Including Config.php...");
    require_once __DIR__ . '/app/lib/Config.php';
    logMessage("   ✅ Config.php loaded");
} catch (\Exception $e) {
    logMessage("   ❌ Config.php failed: " . $e->getMessage());
    exit(1);
}

try {
    logMessage("   Including DatabaseInterface.php...");
    require_once __DIR__ . '/app/lib/DatabaseInterface.php';
    logMessage("   ✅ DatabaseInterface.php loaded");
} catch (\Exception $e) {
    logMessage("   ❌ DatabaseInterface.php failed: " . $e->getMessage());
    exit(1);
}

try {
    logMessage("   Including SchemaMigrator.php...");
    require_once __DIR__ . '/app/lib/SchemaMigrator.php';
    logMessage("   ✅ SchemaMigrator.php loaded");
} catch (\Exception $e) {
    logMessage("   ❌ SchemaMigrator.php failed: " . $e->getMessage());
    exit(1);
}

try {
    logMessage("   Including DataMigrator.php...");
    require_once __DIR__ . '/app/lib/DataMigrator.php';
    logMessage("   ✅ DataMigrator.php loaded");
} catch (\Exception $e) {
    logMessage("   ❌ DataMigrator.php failed: " . $e->getMessage());
    exit(1);
}

try {
    logMessage("   Including MigrationController.php...");
    require_once __DIR__ . '/app/lib/MigrationController.php';
    logMessage("   ✅ MigrationController.php loaded");
} catch (\Exception $e) {
    logMessage("   ❌ MigrationController.php failed: " . $e->getMessage());
    exit(1);
}

// Test 2: Check if we can include the rest of bootstrap
logMessage("2. Testing remaining bootstrap files...");

$bootstrapFiles = [
    'Router.php',
    'DB.php',
    'Logger.php',
    'Mailer.php',
    'Cache.php',
    'DatabaseService.php',
    'SecurityService.php',
    'PaginationService.php',
    'ErrorHandler.php',
    'FrontendOptimizer.php'
];

foreach ($bootstrapFiles as $file) {
    try {
        logMessage("   Including {$file}...");
        require_once __DIR__ . '/app/lib/' . $file;
        logMessage("   ✅ {$file} loaded");
    } catch (\Exception $e) {
        logMessage("   ❌ {$file} failed: " . $e->getMessage());
        exit(1);
    }
}

// Test 3: Check controller files
logMessage("3. Testing controller files...");

$controllerFiles = [
    'controllers/UserController.php',
    'controllers/BaseController.php',
    'controllers/ContestController.php',
    'controllers/AdminController.php',
    'routes/controllers.php',
    'routes/AuditorController.php',
    'routes/BoardController.php'
];

foreach ($controllerFiles as $file) {
    try {
        logMessage("   Including {$file}...");
        require_once __DIR__ . '/app/' . $file;
        logMessage("   ✅ {$file} loaded");
    } catch (\Exception $e) {
        logMessage("   ❌ {$file} failed: " . $e->getMessage());
        exit(1);
    }
}

// Test 4: Test initialization (this is where it might hang)
logMessage("4. Testing initialization...");

try {
    logMessage("   Testing Config::init()...");
    \App\Config::init();
    logMessage("   ✅ Config::init() completed");
} catch (\Exception $e) {
    logMessage("   ❌ Config::init() failed: " . $e->getMessage());
    exit(1);
}

try {
    logMessage("   Testing database configuration...");
    $dbConfig = \App\Config::getDatabaseConfig();
    logMessage("   ✅ Database config retrieved");
} catch (\Exception $e) {
    logMessage("   ❌ Database config failed: " . $e->getMessage());
    exit(1);
}

try {
    logMessage("   Testing DB::initialize()...");
    \App\DB::initialize($dbConfig);
    logMessage("   ✅ DB::initialize() completed");
} catch (\Exception $e) {
    logMessage("   ❌ DB::initialize() failed: " . $e->getMessage());
    exit(1);
}

try {
    logMessage("   Testing Cache::init()...");
    \App\Cache::init();
    logMessage("   ✅ Cache::init() completed");
} catch (\Exception $e) {
    logMessage("   ❌ Cache::init() failed: " . $e->getMessage());
    exit(1);
}

try {
    logMessage("   Testing SecurityService::setSecurityHeaders()...");
    \App\SecurityService::setSecurityHeaders();
    logMessage("   ✅ SecurityService::setSecurityHeaders() completed");
} catch (\Exception $e) {
    logMessage("   ❌ SecurityService::setSecurityHeaders() failed: " . $e->getMessage());
    exit(1);
}

try {
    logMessage("   Testing SecurityService::startSecureSession()...");
    \App\SecurityService::startSecureSession();
    logMessage("   ✅ SecurityService::startSecureSession() completed");
} catch (\Exception $e) {
    logMessage("   ❌ SecurityService::startSecureSession() failed: " . $e->getMessage());
    exit(1);
}

try {
    logMessage("   Testing FrontendOptimizer::init()...");
    \App\FrontendOptimizer::init();
    logMessage("   ✅ FrontendOptimizer::init() completed");
} catch (\Exception $e) {
    logMessage("   ❌ FrontendOptimizer::init() failed: " . $e->getMessage());
    exit(1);
}

logMessage("🎉 Bootstrap debug completed successfully!");
logMessage("💡 The issue was likely in one of the initialization steps above.");
