<?php
/**
 * Admin Discount Code Create/Edit
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/sanitize.php';
require_once __DIR__ . '/../includes/auth.php';

requireAuth();

$db = Database::getInstance();
$eventYear = getAdminEventYear();
$errors = [];
$code = null;

// Load existing code if editing
if (!empty($_GET['id'])) {
    $code = $db->fetchOne("SELECT * FROM discount_codes WHERE id = ?", [(int)$_GET['id']]);
    if (!$code) {
        $_SESSION['admin_error'] = 'Discount code not found.';
        redirect(url('admin/discount-codes.php'));
    }
    $pageTitle = 'Edit Discount Code';
} else {
    $pageTitle = 'New Discount Code';
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrfToken();

    $data = [
        'code' => strtoupper(trim(sanitizeString($_POST['code'] ?? ''))),
        'description' => sanitizeString($_POST['description'] ?? ''),
        'discount_type' => $_POST['discount_type'] ?? '',
        'discount_value' => (float)($_POST['discount_value'] ?? 0),
        'max_uses' => !empty($_POST['max_uses']) ? (int)$_POST['max_uses'] : null,
        'expires_at' => !empty($_POST['expires_at']) ? $_POST['expires_at'] : null,
        'event_year' => $eventYear,
        'is_active' => isset($_POST['is_active']) ? 1 : 0,
    ];

    // Validation
    if (empty($data['code'])) {
        $errors[] = 'Code is required.';
    } elseif (!preg_match('/^[A-Z0-9_-]+$/', $data['code'])) {
        $errors[] = 'Code can only contain letters, numbers, hyphens and underscores.';
    }

    if (!in_array($data['discount_type'], ['percentage', 'fixed', 'full'])) {
        $errors[] = 'Invalid discount type.';
    }

    if ($data['discount_type'] === 'percentage' && ($data['discount_value'] <= 0 || $data['discount_value'] > 100)) {
        $errors[] = 'Percentage must be between 1 and 100.';
    }

    if ($data['discount_type'] === 'fixed' && $data['discount_value'] <= 0) {
        $errors[] = 'Fixed amount must be greater than zero.';
    }

    if ($data['discount_type'] === 'full') {
        $data['discount_value'] = 0;
    }

    // Check uniqueness
    if (empty($errors)) {
        $existingId = $code ? $code['id'] : 0;
        $duplicate = $db->fetchOne(
            "SELECT id FROM discount_codes WHERE code = ? AND event_year = ? AND id != ?",
            [$data['code'], $eventYear, $existingId]
        );
        if ($duplicate) {
            $errors[] = 'A code with this name already exists for this event year.';
        }
    }

    if (empty($errors)) {
        if ($code) {
            // Update
            $db->execute(
                "UPDATE discount_codes SET code = ?, description = ?, discount_type = ?, discount_value = ?, max_uses = ?, expires_at = ?, is_active = ? WHERE id = ?",
                [$data['code'], $data['description'], $data['discount_type'], $data['discount_value'], $data['max_uses'], $data['expires_at'], $data['is_active'], $code['id']]
            );
            $_SESSION['admin_success'] = 'Discount code updated.';
        } else {
            // Insert
            $db->insert(
                "INSERT INTO discount_codes (code, description, discount_type, discount_value, max_uses, expires_at, event_year, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
                [$data['code'], $data['description'], $data['discount_type'], $data['discount_value'], $data['max_uses'], $data['expires_at'], $data['event_year'], $data['is_active']]
            );
            $_SESSION['admin_success'] = 'Discount code created.';
        }
        redirect(url('admin/discount-codes.php'));
    }

    // Keep submitted data on error
    $code = $code ?? [];
    $code = array_merge($code ?: [], $data);
}

include __DIR__ . '/../templates/admin/header.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title"><?php echo $pageTitle; ?></h1>
        <p class="page-subtitle">
            <a href="<?php echo url('admin/discount-codes.php'); ?>">&larr; Back to Discount Codes</a>
        </p>
    </div>
</div>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <?php foreach ($errors as $error): ?>
            <p style="margin: 5px 0;"><?php echo e($error); ?></p>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<form method="POST">
    <input type="hidden" name="csrf_token" value="<?php echo csrfToken(); ?>">

    <div class="content-card">
        <div class="card-header">
            <h2 class="card-title">Code Details</h2>
        </div>

        <div class="form-group">
            <label class="form-label" for="code">Code <span style="color: #dc3545;">*</span></label>
            <input
                type="text"
                id="code"
                name="code"
                class="form-control"
                value="<?php echo e($code['code'] ?? ''); ?>"
                placeholder="e.g. FUNDED2027, HALFTIX, EARLYBIRD"
                style="text-transform: uppercase; font-family: monospace; letter-spacing: 2px;"
                required
            >
            <small style="color: #666;">Letters, numbers, hyphens and underscores only. Will be converted to uppercase.</small>
        </div>

        <div class="form-group">
            <label class="form-label" for="description">Description</label>
            <input
                type="text"
                id="description"
                name="description"
                class="form-control"
                value="<?php echo e($code['description'] ?? ''); ?>"
                placeholder="e.g. Church funded ticket for youth"
            >
        </div>
    </div>

    <div class="content-card">
        <div class="card-header">
            <h2 class="card-title">Discount Settings</h2>
        </div>

        <div class="form-group">
            <label class="form-label">Discount Type <span style="color: #dc3545;">*</span></label>
            <div style="display: flex; flex-direction: column; gap: 12px;">
                <label style="display: flex; align-items: flex-start; gap: 10px; cursor: pointer; padding: 12px; border: 2px solid #e0e0e0; border-radius: 8px;">
                    <input type="radio" name="discount_type" value="full" <?php echo ($code['discount_type'] ?? '') === 'full' ? 'checked' : ''; ?> required>
                    <div>
                        <strong>Fully Funded</strong>
                        <p style="margin: 4px 0 0; color: #666; font-size: 14px;">Entire booking is covered. Booking shows as paid with no outstanding balance.</p>
                    </div>
                </label>
                <label style="display: flex; align-items: flex-start; gap: 10px; cursor: pointer; padding: 12px; border: 2px solid #e0e0e0; border-radius: 8px;">
                    <input type="radio" name="discount_type" value="percentage" <?php echo ($code['discount_type'] ?? '') === 'percentage' ? 'checked' : ''; ?>>
                    <div>
                        <strong>Percentage Off</strong>
                        <p style="margin: 4px 0 0; color: #666; font-size: 14px;">Reduce the total by a percentage (e.g. 50% off).</p>
                    </div>
                </label>
                <label style="display: flex; align-items: flex-start; gap: 10px; cursor: pointer; padding: 12px; border: 2px solid #e0e0e0; border-radius: 8px;">
                    <input type="radio" name="discount_type" value="fixed" <?php echo ($code['discount_type'] ?? '') === 'fixed' ? 'checked' : ''; ?>>
                    <div>
                        <strong>Fixed Amount Off</strong>
                        <p style="margin: 4px 0 0; color: #666; font-size: 14px;">Reduce the total by a fixed amount (e.g. &pound;30 off).</p>
                    </div>
                </label>
            </div>
        </div>

        <div class="form-group" id="discount-value-group" style="display: none;">
            <label class="form-label" for="discount_value">
                <span id="value-label">Discount Value</span> <span style="color: #dc3545;">*</span>
            </label>
            <input
                type="number"
                id="discount_value"
                name="discount_value"
                class="form-control"
                value="<?php echo e($code['discount_value'] ?? ''); ?>"
                min="0"
                step="0.01"
            >
        </div>
    </div>

    <div class="content-card">
        <div class="card-header">
            <h2 class="card-title">Limits</h2>
        </div>

        <div class="form-group">
            <label class="form-label" for="max_uses">Maximum Uses</label>
            <input
                type="number"
                id="max_uses"
                name="max_uses"
                class="form-control"
                value="<?php echo e($code['max_uses'] ?? ''); ?>"
                min="1"
                placeholder="Leave blank for unlimited"
            >
        </div>

        <div class="form-group">
            <label class="form-label" for="expires_at">Expiry Date</label>
            <input
                type="date"
                id="expires_at"
                name="expires_at"
                class="form-control"
                value="<?php echo e($code['expires_at'] ? date('Y-m-d', strtotime($code['expires_at'])) : ''); ?>"
            >
            <small style="color: #666;">Leave blank for no expiry.</small>
        </div>

        <div class="form-group">
            <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                <input
                    type="checkbox"
                    name="is_active"
                    value="1"
                    <?php echo (!isset($code['is_active']) || $code['is_active']) ? 'checked' : ''; ?>
                >
                <span>Active</span>
            </label>
        </div>
    </div>

    <div class="form-actions" style="margin-top: 20px;">
        <button type="submit" class="btn btn-primary">
            <?php echo $code && isset($code['id']) ? 'Update Code' : 'Create Code'; ?>
        </button>
        <a href="<?php echo url('admin/discount-codes.php'); ?>" class="btn">Cancel</a>
    </div>
</form>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const typeRadios = document.querySelectorAll('input[name="discount_type"]');
    const valueGroup = document.getElementById('discount-value-group');
    const valueLabel = document.getElementById('value-label');
    const valueInput = document.getElementById('discount_value');

    function updateValueVisibility() {
        const selected = document.querySelector('input[name="discount_type"]:checked');
        if (!selected) { valueGroup.style.display = 'none'; return; }

        if (selected.value === 'full') {
            valueGroup.style.display = 'none';
            valueInput.removeAttribute('required');
        } else {
            valueGroup.style.display = 'block';
            valueInput.setAttribute('required', 'required');
            if (selected.value === 'percentage') {
                valueLabel.textContent = 'Percentage (1-100)';
                valueInput.setAttribute('max', '100');
                valueInput.setAttribute('min', '1');
            } else {
                valueLabel.textContent = 'Amount (£)';
                valueInput.removeAttribute('max');
                valueInput.setAttribute('min', '0.01');
            }
        }
    }

    typeRadios.forEach(r => r.addEventListener('change', updateValueVisibility));
    updateValueVisibility();
});
</script>

<?php include __DIR__ . '/../templates/admin/footer.php'; ?>
