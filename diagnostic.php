<?php
/**
 * Diagnostic Tool
 * Upload this to your server and access it to check for issues
 * DELETE THIS FILE after troubleshooting!
 */

// Turn on error display
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>Server Diagnostic</h1>";

// Check PHP version
echo "<h2>PHP Version</h2>";
echo "<p>" . phpversion() . " (Minimum required: 7.4)</p>";
if (version_compare(phpversion(), '7.4.0', '<')) {
    echo "<p style='color: red;'>❌ PHP version too old!</p>";
} else {
    echo "<p style='color: green;'>✓ PHP version OK</p>";
}

// Check required extensions
echo "<h2>PHP Extensions</h2>";
$required = ['pdo', 'pdo_mysql', 'curl', 'mbstring', 'openssl'];
foreach ($required as $ext) {
    if (extension_loaded($ext)) {
        echo "<p style='color: green;'>✓ $ext loaded</p>";
    } else {
        echo "<p style='color: red;'>❌ $ext NOT loaded</p>";
    }
}

// Check file permissions
echo "<h2>File Permissions</h2>";
$files_to_check = [
    __DIR__ . '/.env' => 'Config file',
    __DIR__ . '/vendor' => 'Vendor directory',
    __DIR__ . '/logs' => 'Logs directory',
    __DIR__ . '/index.php' => 'Main index file',
];

foreach ($files_to_check as $path => $name) {
    if (file_exists($path)) {
        $perms = substr(sprintf('%o', fileperms($path)), -4);
        echo "<p style='color: green;'>✓ $name exists (permissions: $perms)</p>";
    } else {
        echo "<p style='color: red;'>❌ $name not found: $path</p>";
    }
}

// Check .env file
echo "<h2>.env File Check</h2>";
if (file_exists(__DIR__ . '/.env')) {
    echo "<p style='color: green;'>✓ .env file exists</p>";
    // Don't display contents for security
    $env_size = filesize(__DIR__ . '/.env');
    echo "<p>File size: $env_size bytes</p>";
    if ($env_size < 100) {
        echo "<p style='color: orange;'>⚠ File seems very small, might be incomplete</p>";
    }
} else {
    echo "<p style='color: red;'>❌ .env file NOT found! Copy .env.example to .env</p>";
}

// Try to load includes
echo "<h2>Core Files Test</h2>";
$core_files = [
    'includes/db.php',
    'includes/functions.php',
    'includes/sanitize.php',
    'config/database.php',
];

foreach ($core_files as $file) {
    $path = __DIR__ . '/' . $file;
    if (file_exists($path)) {
        echo "<p style='color: green;'>✓ $file exists</p>";
    } else {
        echo "<p style='color: red;'>❌ $file NOT found</p>";
    }
}

// Check document root
echo "<h2>Server Info</h2>";
echo "<p>Document Root: " . $_SERVER['DOCUMENT_ROOT'] . "</p>";
echo "<p>Script Filename: " . $_SERVER['SCRIPT_FILENAME'] . "</p>";
echo "<p>Current Directory: " . __DIR__ . "</p>";
echo "<p>Server Software: " . ($_SERVER['SERVER_SOFTWARE'] ?? 'Unknown') . "</p>";

// Check .htaccess
echo "<h2>.htaccess Check</h2>";
if (file_exists(__DIR__ . '/.htaccess')) {
    echo "<p style='color: green;'>✓ .htaccess exists</p>";
    if (function_exists('apache_get_modules')) {
        $modules = apache_get_modules();
        if (in_array('mod_rewrite', $modules)) {
            echo "<p style='color: green;'>✓ mod_rewrite is loaded</p>";
        } else {
            echo "<p style='color: red;'>❌ mod_rewrite NOT loaded</p>";
        }
    } else {
        echo "<p style='color: orange;'>⚠ Cannot detect Apache modules (might be using different server)</p>";
    }
} else {
    echo "<p style='color: red;'>❌ .htaccess NOT found</p>";
}

echo "<hr>";
echo "<p><strong>⚠ IMPORTANT: Delete this file after troubleshooting!</strong></p>";
?>
