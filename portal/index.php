<?php
/**
 * Customer Portal Index
 * Redirects to dashboard if logged in, otherwise to login
 */

// Initialize
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/customer-auth.php';

// Check if customer is logged in
if (isCustomerLoggedIn()) {
    // Redirect to dashboard
    redirect(url('portal/dashboard.php'));
} else {
    // Redirect to login
    redirect(url('portal/login.php'));
}
