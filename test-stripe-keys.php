<?php
/**
 * Stripe Keys Diagnostic
 * Checks if Stripe keys are loaded from .env
 * DELETE THIS FILE after testing!
 */

require_once __DIR__ . '/config/constants.php';

echo "<h1>Stripe Keys Diagnostic</h1>";

$publicKey = env('STRIPE_PUBLIC_KEY');
$secretKey = env('STRIPE_SECRET_KEY');

echo "<p><strong>Public Key:</strong> ";
if (empty($publicKey)) {
    echo "<span style='color: red;'>❌ NOT SET or EMPTY</span>";
} else {
    echo "<span style='color: green;'>✓ Loaded (" . substr($publicKey, 0, 10) . "...)</span>";
}
echo "</p>";

echo "<p><strong>Secret Key:</strong> ";
if (empty($secretKey)) {
    echo "<span style='color: red;'>❌ NOT SET or EMPTY</span>";
} else {
    echo "<span style='color: green;'>✓ Loaded (" . substr($secretKey, 0, 10) . "...)</span>";
}
echo "</p>";

echo "<p><strong>Environment:</strong> " . env('APP_ENV', 'not set') . "</p>";

echo "<hr>";
echo "<p style='color: orange;'><strong>⚠ DELETE THIS FILE after testing!</strong></p>";
?>
