<?php
/**
 * Set Admin Event Year
 * Handles the year switcher form submission
 */

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requireAuth();

$year = (int)($_GET['year'] ?? $_POST['event_year'] ?? EVENT_YEAR);
setAdminEventYear($year);

// Redirect back to the referring page, or dashboard
$redirect = $_SERVER['HTTP_REFERER'] ?? url('admin/dashboard.php');
header('Location: ' . $redirect);
exit;
