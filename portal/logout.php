<?php
/**
 * Customer Portal Logout
 */

// Initialize
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/customer-auth.php';

// Logout the customer
customerLogout();

// Redirect to login page
redirect(url('portal/login.php'));
