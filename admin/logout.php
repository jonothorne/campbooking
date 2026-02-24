<?php
/**
 * Admin Logout Handler
 */

// Initialize
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

// Perform logout
logout();

// Redirect to login page
redirect(url('admin/login.php'));
