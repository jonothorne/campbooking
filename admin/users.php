<?php
/**
 * Admin Users List
 * View and manage admin users
 */

// Initialize
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/sanitize.php';

// Check authentication
requireAuth();

// Set page title
$pageTitle = 'Admin Users';

// Get all users
$db = Database::getInstance();
$users = $db->fetchAll(
    "SELECT id, username, email, is_active, last_login, created_at
    FROM users
    ORDER BY created_at DESC"
);

// Get success/error messages
$success = $_SESSION['success'] ?? null;
$error = $_SESSION['error'] ?? null;
unset($_SESSION['success'], $_SESSION['error']);

// Include header
include __DIR__ . '/../templates/admin/header.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Admin Users</h1>
        <p class="page-subtitle"><?php echo count($users); ?> user<?php echo count($users) !== 1 ? 's' : ''; ?></p>
    </div>
    <a href="<?php echo url('admin/user-add.php'); ?>" class="btn btn-primary">
        Add User
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

<!-- Users Table -->
<div class="content-card">
    <?php if (empty($users)): ?>
        <div class="empty-state">
            <div class="empty-state-icon">👥</div>
            <h3 class="empty-state-title">No Users Found</h3>
            <p class="empty-state-text">Create the first admin user to get started.</p>
            <a href="<?php echo url('admin/user-add.php'); ?>" class="btn btn-primary">
                Create First User
            </a>
        </div>
    <?php else: ?>
        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Status</th>
                        <th>Last Login</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td>
                                <strong><?php echo e($user['username']); ?></strong>
                                <?php if ($user['id'] == currentAdminId()): ?>
                                    <span class="badge badge-primary" style="margin-left: 8px;">You</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo e($user['email']); ?></td>
                            <td>
                                <?php if ($user['is_active']): ?>
                                    <span class="badge badge-success">Active</span>
                                <?php else: ?>
                                    <span class="badge badge-secondary">Inactive</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($user['last_login']): ?>
                                    <?php echo formatDateTime($user['last_login']); ?>
                                <?php else: ?>
                                    <span style="color: var(--text-medium);">Never</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo formatDate($user['created_at'], 'd/m/Y H:i'); ?></td>
                            <td>
                                <div class="table-actions">
                                    <a href="<?php echo url('admin/user-edit.php?id=' . $user['id']); ?>"
                                       class="btn btn-secondary btn-sm">
                                        Edit
                                    </a>

                                    <?php if ($user['id'] == currentAdminId()): ?>
                                        <a href="<?php echo url('admin/change-password.php'); ?>"
                                           class="btn btn-primary btn-sm">
                                            Change Password
                                        </a>
                                    <?php else: ?>
                                        <form method="POST"
                                              action="<?php echo url('admin/user-delete.php'); ?>"
                                              style="display: inline;">
                                            <input type="hidden" name="csrf_token" value="<?php echo csrfToken(); ?>">
                                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                            <button type="submit" class="btn btn-danger btn-sm confirm-delete-user">
                                                Delete
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<script>
// Confirmation for user deletion
document.querySelectorAll('.confirm-delete-user').forEach(button => {
    button.addEventListener('click', function(e) {
        if (!confirm('Are you sure you want to delete this user? This action cannot be undone.')) {
            e.preventDefault();
        }
    });
});
</script>

<?php include __DIR__ . '/../templates/admin/footer.php'; ?>
