<?php
/**
 * Admin Index - Redirect to Dashboard or Login
 */

// Initialize
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

// Check if logged in
if (isLoggedIn()) {
    // Redirect to dashboard
    redirect(url('admin/dashboard.php'));
} else {
    // Redirect to login
    redirect(url('admin/login.php'));
}
