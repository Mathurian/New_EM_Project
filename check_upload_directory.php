<?php
/**
 * Check file upload directory permissions and create if needed
 */

echo "=== File Upload Directory Check ===\n";

// The upload directory path
$uploadDir = '/var/www/html/public/uploads/emcee-scripts/';

echo "1. Checking upload directory: $uploadDir\n";

// Check if directory exists
if (!is_dir($uploadDir)) {
    echo "   ❌ Directory does not exist\n";
    echo "   🔧 Creating directory...\n";
    
    if (mkdir($uploadDir, 0755, true)) {
        echo "   ✅ Directory created successfully\n";
    } else {
        echo "   ❌ Failed to create directory\n";
        exit(1);
    }
} else {
    echo "   ✅ Directory exists\n";
}

// Check directory permissions
echo "2. Checking directory permissions...\n";
$perms = fileperms($uploadDir);
$permsOct = sprintf('%o', $perms);
echo "   Current permissions: $permsOct\n";

// Check if directory is writable
if (is_writable($uploadDir)) {
    echo "   ✅ Directory is writable\n";
} else {
    echo "   ❌ Directory is not writable\n";
    echo "   🔧 Attempting to fix permissions...\n";
    
    if (chmod($uploadDir, 0755)) {
        echo "   ✅ Permissions updated to 755\n";
    } else {
        echo "   ❌ Failed to update permissions\n";
    }
}

// Test file creation
echo "3. Testing file creation...\n";
$testFile = $uploadDir . 'test_' . time() . '.txt';
$testContent = 'This is a test file';

if (file_put_contents($testFile, $testContent)) {
    echo "   ✅ File creation test successful\n";
    
    // Clean up test file
    if (unlink($testFile)) {
        echo "   ✅ Test file cleaned up\n";
    } else {
        echo "   ⚠️  Could not clean up test file: $testFile\n";
    }
} else {
    echo "   ❌ File creation test failed\n";
}

// Check parent directories
echo "4. Checking parent directories...\n";
$parentDirs = [
    '/var/www/html',
    '/var/www/html/public',
    '/var/www/html/public/uploads'
];

foreach ($parentDirs as $dir) {
    if (is_dir($dir)) {
        $perms = fileperms($dir);
        $permsOct = sprintf('%o', $perms);
        echo "   ✅ $dir exists (permissions: $permsOct)\n";
    } else {
        echo "   ❌ $dir does not exist\n";
    }
}

echo "\n=== Directory Check Complete ===\n";
echo "If all tests passed, the upload directory should be working correctly.\n";
echo "If any tests failed, you may need to:\n";
echo "1. Create missing directories\n";
echo "2. Fix directory permissions\n";
echo "3. Check web server user permissions\n";
