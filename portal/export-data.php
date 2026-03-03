<?php
/**
 * Customer Data Export (GDPR Compliance)
 * Downloads all customer data in JSON format
 */

// Initialize
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/customer-auth.php';

// Require authentication
requireCustomerAuth();

$customerId = currentCustomerId();

// Export all customer data
$data = exportCustomerData($customerId);

// Set headers for JSON download
header('Content-Type: application/json');
header('Content-Disposition: attachment; filename="my-echo2026-data-' . date('Y-m-d') . '.json"');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// Output JSON
echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
exit;
