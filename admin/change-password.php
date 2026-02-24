<?php
/**
 * Change Password Page
 * Allows users to change their own password
 */

// Initialize
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/sanitize.php';

// Check authentication
requireAuth();

// Set page title
$pageTitle = 'Change Password';

$error = null;
$success = null;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    requireCsrfToken();

    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    // Validation
    if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
        $error = 'All fields are required';
    } elseif ($newPassword !== $confirmPassword) {
        $error = 'New passwords do not match';
    } elseif (strlen($newPassword) < 8) {
        $error = 'New password must be at least 8 characters long';
    } else {
        // Verify current password
        $db = Database::getInstance();
        $user = $db->fetchOne(
            "SELECT id, password_hash FROM users WHERE id = ?",
            [currentAdminId()]
        );

        if (!password_verify($currentPassword, $user['password_hash'])) {
            $error = 'Current password is incorrect';
        } else {
            // Update password
            $newHash = password_hash($newPassword, PASSWORD_BCRYPT);
            $db->execute(
                "UPDATE users SET password_hash = ? WHERE id = ?",
                [$newHash, currentAdminId()]
            );

            $success = 'Password changed successfully!';

            // Clear form
            $_POST = [];
        }
    }
}

// Include header
include __DIR__ . '/../templates/admin/header.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Change Password</h1>
        <p class="page-subtitle">Update your account password</p>
    </div>
    <a href="<?php echo url('admin/users.php'); ?>" class="btn btn-secondary">
        ← Back to Users
    </a>
</div>

<!-- Success/Error Messages -->
<?php if ($success): ?>
    <div class="alert alert-success">
        <?php echo e($success); ?>
    </div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-danger">
        <?php echo e($error); ?>
    </div>
<?php endif; ?>

<!-- Change Password Form -->
<div class="content-card">
    <form method="POST" action="">
        <input type="hidden" name="csrf_token" value="<?php echo csrfToken(); ?>">

        <div class="form-row">
            <div class="form-group">
                <label for="current_password">Current Password *</label>
                <input
                    type="password"
                    id="current_password"
                    name="current_password"
                    class="form-control"
                    required
                    autocomplete="current-password"
                >
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="new_password">New Password *</label>
                <input
                    type="password"
                    id="new_password"
                    name="new_password"
                    class="form-control"
                    required
                    minlength="8"
                    autocomplete="new-password"
                >
                <small style="color: var(--text-medium); display: block; margin-top: 5px;">
                    Minimum 8 characters
                </small>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="confirm_password">Confirm New Password *</label>
                <input
                    type="password"
                    id="confirm_password"
                    name="confirm_password"
                    class="form-control"
                    required
                    minlength="8"
                    autocomplete="new-password"
                >
            </div>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">
                Change Password
            </button>
            <a href="<?php echo url('admin/users.php'); ?>" class="btn btn-secondary">
                Cancel
            </a>
        </div>
    </form>
</div>

<!-- Password Requirements -->
<div class="content-card">
    <h2 style="font-size: 18px; margin-bottom: 15px;">Password Requirements</h2>
    <ul style="margin: 0; padding-left: 20px;">
        <li>At least 8 characters long</li>
        <li>Include a mix of letters, numbers, and symbols for better security</li>
        <li>Avoid common words or personal information</li>
        <li>Don't reuse passwords from other accounts</li>
    </ul>
</div>

<?php include __DIR__ . '/../templates/admin/footer.php'; ?>
