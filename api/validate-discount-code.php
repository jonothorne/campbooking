<?php
/**
 * Validate Discount Code API
 * Returns discount details if code is valid
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/sanitize.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonError('Method not allowed', 405);
}

$code = strtoupper(trim($_POST['code'] ?? ''));

if (empty($code)) {
    jsonError('Please enter a discount code.');
}

$db = Database::getInstance();
$discountCode = $db->fetchOne(
    "SELECT * FROM discount_codes WHERE code = ? AND event_year = ?",
    [$code, EVENT_YEAR]
);

if (!$discountCode) {
    jsonError('Invalid discount code.');
}

if (!$discountCode['is_active']) {
    jsonError('This discount code is no longer active.');
}

if ($discountCode['expires_at'] && strtotime($discountCode['expires_at']) < time()) {
    jsonError('This discount code has expired.');
}

if ($discountCode['max_uses'] && $discountCode['times_used'] >= $discountCode['max_uses']) {
    jsonError('This discount code has reached its maximum uses.');
}

// Return discount info
jsonResponse([
    'valid' => true,
    'code' => $discountCode['code'],
    'discount_type' => $discountCode['discount_type'],
    'discount_value' => (float)$discountCode['discount_value'],
    'description' => $discountCode['description'],
]);
