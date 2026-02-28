<?php
/**
 * Timezone Diagnostic Tool
 * Check PHP and MySQL timezone settings
 */

require_once __DIR__ . '/includes/db.php';

echo "<h1>Timezone Diagnostic</h1>";

// PHP Timezone
echo "<h2>PHP Settings</h2>";
echo "<strong>PHP Timezone:</strong> " . date_default_timezone_get() . "<br>";
echo "<strong>Current PHP Time:</strong> " . date('Y-m-d H:i:s') . "<br>";
echo "<strong>Current PHP Time (with timezone):</strong> " . date('Y-m-d H:i:s T') . "<br>";

// MySQL Timezone
echo "<h2>MySQL Settings</h2>";
$db = Database::getInstance();

$systemTimezone = $db->fetchOne("SELECT @@system_time_zone as tz");
echo "<strong>MySQL System Timezone:</strong> " . ($systemTimezone['tz'] ?? 'Unknown') . "<br>";

$globalTimezone = $db->fetchOne("SELECT @@global.time_zone as tz");
echo "<strong>MySQL Global Timezone:</strong> " . ($globalTimezone['tz'] ?? 'Unknown') . "<br>";

$sessionTimezone = $db->fetchOne("SELECT @@session.time_zone as tz");
echo "<strong>MySQL Session Timezone:</strong> " . ($sessionTimezone['tz'] ?? 'Unknown') . "<br>";

$currentTime = $db->fetchOne("SELECT NOW() as current_time");
echo "<strong>MySQL NOW():</strong> " . ($currentTime['current_time'] ?? 'Unknown') . "<br>";

$utcTime = $db->fetchOne("SELECT UTC_TIMESTAMP() as utc_time");
echo "<strong>MySQL UTC_TIMESTAMP():</strong> " . ($utcTime['utc_time'] ?? 'Unknown') . "<br>";

// Recommendations
echo "<h2>Analysis</h2>";
$phpTz = date_default_timezone_get();
$mysqlTz = $sessionTimezone['tz'] ?? '';

if ($mysqlTz === 'SYSTEM') {
    echo "<p style='color: orange;'>⚠️ MySQL is using SYSTEM timezone. This might differ from PHP.</p>";
    echo "<p>Recommendation: Set MySQL timezone to match PHP timezone (Europe/London)</p>";
} elseif ($mysqlTz !== $phpTz && $mysqlTz !== '+00:00' && $mysqlTz !== '+01:00') {
    echo "<p style='color: red;'>❌ Timezone mismatch detected!</p>";
    echo "<p>PHP: $phpTz<br>MySQL: $mysqlTz</p>";
} else {
    echo "<p style='color: green;'>✓ Timezones appear to be aligned</p>";
}

echo "<h2>Fix</h2>";
echo "<p>If timezones are mismatched, add this to config/database.php after connection:</p>";
echo "<pre>\$pdo->exec(\"SET time_zone = '+00:00'\"); // Store in UTC
// OR
\$pdo->exec(\"SET time_zone = 'Europe/London'\"); // Store in London time</pre>";
