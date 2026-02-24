<?php
/**
 * Delete User Handler
 * Processes user deletion requests
 */

// Initialize
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/sanitize.php';

// Check authentication
requireAuth();

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error'] = 'Invalid request method';
    redirect(url('admin/users.php'));
}

// Verify CSRF token
requireCsrfToken();

$userId = sanitizeInt($_POST['user_id'] ?? 0);

if (!$userId) {
    $_SESSION['error'] = 'Invalid user ID';
    redirect(url('admin/users.php'));
}

// Prevent users from deleting themselves
if ($userId == currentAdminId()) {
    $_SESSION['error'] = 'You cannot delete your own account';
    redirect(url('admin/users.php'));
}

// Get user data
$db = Database::getInstance();
$user = $db->fetchOne(
    "SELECT id, username FROM users WHERE id = ?",
    [$userId]
);

if (!$user) {
    $_SESSION['error'] = 'User not found';
    redirect(url('admin/users.php'));
}

try {
    // Delete user
    $db->execute(
        "DELETE FROM users WHERE id = ?",
        [$userId]
    );

    $_SESSION['success'] = "User '{$user['username']}' deleted successfully";
} catch (Exception $e) {
    error_log("Error deleting user: " . $e->getMessage());
    $_SESSION['error'] = 'Failed to delete user. Please try again.';
}

redirect(url('admin/users.php'));
