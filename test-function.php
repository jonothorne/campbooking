<?php
// Test if e() function loads properly
require_once __DIR__ . '/includes/functions.php';

echo "Function e() exists: " . (function_exists('e') ? 'YES' : 'NO') . "<br>";

if (function_exists('e')) {
    echo "Test output: " . e('<script>alert("test")</script>') . "<br>";
} else {
    echo "ERROR: e() function not found!<br>";
    echo "functions.php path: " . __DIR__ . '/includes/functions.php<br>';
    echo "File exists: " . (file_exists(__DIR__ . '/includes/functions.php') ? 'YES' : 'NO') . "<br>";
}

// List all defined functions with 'e' in name
echo "<br>Functions containing 'e':<br>";
$functions = get_defined_functions()['user'];
foreach ($functions as $func) {
    if (strpos($func, 'e') !== false && strlen($func) <= 3) {
        echo "- $func<br>";
    }
}
