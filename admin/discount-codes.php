<?php
/**
 * Admin Discount Codes List
 * View and manage discount codes
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/sanitize.php';
require_once __DIR__ . '/../includes/auth.php';

requireAuth();

$pageTitle = 'Discount Codes';
$eventYear = getAdminEventYear();
$db = Database::getInstance();

// Handle delete action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    requireCsrfToken();

    if ($_POST['action'] === 'delete' && !empty($_POST['code_id'])) {
        $codeId = (int)$_POST['code_id'];
        // Only delete if not used
        $code = $db->fetchOne("SELECT times_used FROM discount_codes WHERE id = ?", [$codeId]);
        if ($code && $code['times_used'] == 0) {
            $db->execute("DELETE FROM discount_codes WHERE id = ?", [$codeId]);
            $_SESSION['admin_success'] = 'Discount code deleted.';
        } else {
            $_SESSION['admin_error'] = 'Cannot delete a code that has been used. Deactivate it instead.';
        }
    } elseif ($_POST['action'] === 'toggle' && !empty($_POST['code_id'])) {
        $codeId = (int)$_POST['code_id'];
        $db->execute("UPDATE discount_codes SET is_active = NOT is_active WHERE id = ?", [$codeId]);
        $_SESSION['admin_success'] = 'Discount code status updated.';
    }

    redirect(url('admin/discount-codes.php'));
}

// Fetch codes for current event year
$codes = $db->fetchAll(
    "SELECT * FROM discount_codes WHERE event_year = ? ORDER BY created_at DESC",
    [$eventYear]
);

include __DIR__ . '/../templates/admin/header.php';
?>

<?php if (!empty($_SESSION['admin_success'])): ?>
    <div class="alert alert-success"><?php echo e($_SESSION['admin_success']); unset($_SESSION['admin_success']); ?></div>
<?php endif; ?>
<?php if (!empty($_SESSION['admin_error'])): ?>
    <div class="alert alert-danger"><?php echo e($_SESSION['admin_error']); unset($_SESSION['admin_error']); ?></div>
<?php endif; ?>

<div class="page-header">
    <div>
        <h1 class="page-title">Discount Codes</h1>
        <p class="page-subtitle"><?php echo count($codes); ?> code<?php echo count($codes) !== 1 ? 's' : ''; ?> for <?php echo $eventYear; ?></p>
    </div>
    <a href="<?php echo url('admin/discount-code-edit.php'); ?>" class="btn btn-primary">
        + New Code
    </a>
</div>

<?php if (empty($codes)): ?>
    <div class="content-card" style="text-align: center; padding: 60px 20px;">
        <p style="font-size: 18px; color: #666; margin: 0;">No discount codes yet.</p>
        <p style="color: #999; margin: 10px 0 20px;">Create a discount code to offer funded or discounted bookings.</p>
        <a href="<?php echo url('admin/discount-code-edit.php'); ?>" class="btn btn-primary">Create First Code</a>
    </div>
<?php else: ?>
    <div class="content-card">
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Description</th>
                        <th>Type</th>
                        <th>Value</th>
                        <th>Usage</th>
                        <th>Expires</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($codes as $code): ?>
                        <?php
                        $isExpired = $code['expires_at'] && strtotime($code['expires_at']) < time();
                        $isMaxed = $code['max_uses'] && $code['times_used'] >= $code['max_uses'];
                        ?>
                        <tr>
                            <td>
                                <strong style="font-family: monospace; font-size: 14px; letter-spacing: 1px;">
                                    <?php echo e($code['code']); ?>
                                </strong>
                            </td>
                            <td><?php echo e($code['description'] ?: '-'); ?></td>
                            <td>
                                <?php
                                switch ($code['discount_type']) {
                                    case 'percentage': echo 'Percentage'; break;
                                    case 'fixed': echo 'Fixed Amount'; break;
                                    case 'full': echo '<strong style="color: #28a745;">Fully Funded</strong>'; break;
                                }
                                ?>
                            </td>
                            <td>
                                <?php
                                switch ($code['discount_type']) {
                                    case 'percentage': echo e($code['discount_value']) . '%'; break;
                                    case 'fixed': echo formatCurrency($code['discount_value']); break;
                                    case 'full': echo 'Full amount'; break;
                                }
                                ?>
                            </td>
                            <td>
                                <?php echo $code['times_used']; ?>
                                <?php if ($code['max_uses']): ?>
                                    / <?php echo $code['max_uses']; ?>
                                <?php else: ?>
                                    <span style="color: #999;">(unlimited)</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($code['expires_at']): ?>
                                    <span style="color: <?php echo $isExpired ? '#dc3545' : '#666'; ?>;">
                                        <?php echo formatDate($code['expires_at'], 'd/m/Y'); ?>
                                        <?php if ($isExpired): ?><br><small>(expired)</small><?php endif; ?>
                                    </span>
                                <?php else: ?>
                                    <span style="color: #999;">Never</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!$code['is_active']): ?>
                                    <span class="status-badge status-cancelled">Inactive</span>
                                <?php elseif ($isExpired): ?>
                                    <span class="status-badge status-failed">Expired</span>
                                <?php elseif ($isMaxed): ?>
                                    <span class="status-badge status-partial">Maxed Out</span>
                                <?php else: ?>
                                    <span class="status-badge status-paid">Active</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div style="display: flex; gap: 8px; align-items: center;">
                                    <a href="<?php echo url('admin/discount-code-edit.php?id=' . $code['id']); ?>" class="btn btn-sm">
                                        Edit
                                    </a>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="csrf_token" value="<?php echo csrfToken(); ?>">
                                        <input type="hidden" name="action" value="toggle">
                                        <input type="hidden" name="code_id" value="<?php echo $code['id']; ?>">
                                        <button type="submit" class="btn btn-sm <?php echo $code['is_active'] ? 'btn-warning' : 'btn-success'; ?>">
                                            <?php echo $code['is_active'] ? 'Deactivate' : 'Activate'; ?>
                                        </button>
                                    </form>
                                    <?php if ($code['times_used'] == 0): ?>
                                        <form method="POST" style="display: inline;" onsubmit="return confirm('Delete this discount code?');">
                                            <input type="hidden" name="csrf_token" value="<?php echo csrfToken(); ?>">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="code_id" value="<?php echo $code['id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<?php include __DIR__ . '/../templates/admin/footer.php'; ?>
