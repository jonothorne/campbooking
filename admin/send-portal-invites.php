<?php
/**
 * Send Portal Invites to Customers
 * Bulk send password setup emails to existing bookings
 */

// Initialize
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/customer-auth.php';
require_once __DIR__ . '/../classes/Email.php';

// Require authentication
requireAuth();

$db = Database::getInstance();
$success = null;
$error = null;
$sendResults = [];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrfToken();

    $bookingIds = $_POST['booking_ids'] ?? [];

    if (empty($bookingIds)) {
        $error = 'Please select at least one booking to send invites to.';
    } else {
        $email = new Email();
        $successCount = 0;
        $failCount = 0;

        foreach ($bookingIds as $bookingId) {
            $bookingId = (int)$bookingId;

            try {
                // Check if booking exists and doesn't have password
                $booking = $db->fetchOne(
                    "SELECT id, booker_name, booker_email, password_hash FROM bookings WHERE id = ?",
                    [$bookingId]
                );

                if (!$booking) {
                    $sendResults[] = [
                        'booking_id' => $bookingId,
                        'status' => 'error',
                        'message' => 'Booking not found'
                    ];
                    $failCount++;
                    continue;
                }

                if (!empty($booking['password_hash'])) {
                    $sendResults[] = [
                        'booking_id' => $bookingId,
                        'name' => $booking['booker_name'],
                        'email' => $booking['booker_email'],
                        'status' => 'skipped',
                        'message' => 'Already has password'
                    ];
                    continue;
                }

                // Generate password setup token
                $token = generatePasswordSetupToken($bookingId);

                // Generate setup link for display
                $setupLink = url('portal/setup-password.php?token=' . $token);

                // Try to send email
                $emailSent = $email->sendPasswordSetup($bookingId, $token);

                if ($emailSent || isDevelopment()) {
                    // In development, show link even if email fails
                    $message = $emailSent
                        ? 'Invite sent successfully'
                        : 'Email failed - Use link below';

                    $sendResults[] = [
                        'booking_id' => $bookingId,
                        'name' => $booking['booker_name'],
                        'email' => $booking['booker_email'],
                        'status' => $emailSent ? 'success' : 'warning',
                        'message' => $message,
                        'setup_link' => $setupLink
                    ];
                    $successCount++;
                } else {
                    $sendResults[] = [
                        'booking_id' => $bookingId,
                        'name' => $booking['booker_name'],
                        'email' => $booking['booker_email'],
                        'status' => 'error',
                        'message' => 'Failed to send email'
                    ];
                    $failCount++;
                }

            } catch (Exception $e) {
                $sendResults[] = [
                    'booking_id' => $bookingId,
                    'status' => 'error',
                    'message' => 'Error: ' . $e->getMessage()
                ];
                $failCount++;
            }
        }

        $success = "Sent {$successCount} invite(s). {$failCount} failed.";
    }
}

// Get all bookings without passwords
$bookingsWithoutPassword = $db->fetchAll(
    "SELECT
        b.id,
        b.booking_reference,
        b.booker_name,
        b.booker_email,
        b.created_at,
        (SELECT COUNT(*) FROM password_setup_tokens WHERE booking_id = b.id) as token_count,
        (SELECT MAX(created_at) FROM password_setup_tokens WHERE booking_id = b.id) as last_invite_sent
    FROM bookings b
    WHERE b.password_hash IS NULL OR b.password_hash = ''
    ORDER BY b.created_at DESC"
);

// Get bookings that already have passwords
$bookingsWithPassword = $db->fetchAll(
    "SELECT
        b.id,
        b.booking_reference,
        b.booker_name,
        b.booker_email,
        b.last_portal_login
    FROM bookings b
    WHERE b.password_hash IS NOT NULL AND b.password_hash != ''
    ORDER BY b.last_portal_login IS NULL, b.last_portal_login DESC"
);

$csrfToken = generateCsrfToken();
$pageTitle = 'Send Portal Invites';

// Include admin header
include __DIR__ . '/../templates/admin/header.php';
?>

<style>
    .status-badge {
        display: inline-block;
        padding: 4px 12px;
        border-radius: 12px;
        font-size: 12px;
        font-weight: 600;
    }
    .status-success {
        background: #d4edda;
        color: #155724;
    }
    .status-error {
        background: #f8d7da;
        color: #721c24;
    }
    .status-skipped {
        background: #fff3cd;
        color: #856404;
    }
    .status-warning {
        background: #fff3cd;
        color: #856404;
    }
    .select-all-container {
        margin: 15px 0;
        padding: 15px;
        background: #f0f9ff;
        border-radius: 8px;
    }
    .send-results {
        margin: 20px 0;
    }
    .result-item {
        padding: 12px;
        margin: 8px 0;
        border-radius: 6px;
        background: #f9f9f9;
        border-left: 4px solid #ddd;
    }
    .result-item.success {
        border-left-color: #28a745;
        background: #d4edda;
    }
    .result-item.error {
        border-left-color: #dc3545;
        background: #f8d7da;
    }
    .result-item.skipped {
        border-left-color: #ffc107;
        background: #fff3cd;
    }
    .result-item.warning {
        border-left-color: #ffc107;
        background: #fff3cd;
    }
</style>

<div class="content-header">
                    <div>
                        <h1>Send Portal Invites</h1>
                        <p style="color: var(--text-medium); margin: 5px 0;">Send password setup emails to customers for portal access</p>
                    </div>
                    <div>
                        <a href="<?php echo url('admin/'); ?>" class="btn btn-secondary">
                            ← Back to Dashboard
                        </a>
                    </div>
                </div>

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

                <?php if (!empty($sendResults)): ?>
                    <div class="content-card send-results">
                        <div class="card-header">
                            <h2 class="card-title">Send Results</h2>
                        </div>
                        <?php foreach ($sendResults as $result): ?>
                            <div class="result-item <?php echo e($result['status']); ?>">
                                <strong><?php echo e($result['name'] ?? 'Booking #' . $result['booking_id']); ?></strong>
                                <?php if (isset($result['email'])): ?>
                                    - <?php echo e($result['email']); ?>
                                <?php endif; ?>
                                <br>
                                <span class="status-badge status-<?php echo e($result['status']); ?>">
                                    <?php echo e(ucfirst($result['status'])); ?>: <?php echo e($result['message']); ?>
                                </span>
                                <?php if (isset($result['setup_link'])): ?>
                                    <div style="margin-top: 10px; padding: 10px; background: #f0f9ff; border-radius: 4px; font-size: 12px;">
                                        <strong>Setup Link (for testing):</strong><br>
                                        <a href="<?php echo e($result['setup_link']); ?>" target="_blank" style="color: #eb008b; word-break: break-all;">
                                            <?php echo e($result['setup_link']); ?>
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <!-- Bookings Without Password -->
                <div class="content-card">
                    <div class="card-header">
                        <h2 class="card-title">
                            Customers Without Portal Access (<?php echo count($bookingsWithoutPassword); ?>)
                        </h2>
                    </div>

                    <?php if (empty($bookingsWithoutPassword)): ?>
                        <div style="text-align: center; padding: 40px; color: var(--text-medium);">
                            <p>✅ All customers have been sent portal access invites!</p>
                        </div>
                    <?php else: ?>
                        <form method="POST" action="">
                            <input type="hidden" name="csrf_token" value="<?php echo e($csrfToken); ?>">

                            <div class="select-all-container">
                                <label style="display: flex; align-items: center; gap: 10px; cursor: pointer; font-weight: 600;">
                                    <input type="checkbox" id="select-all" style="width: auto;">
                                    <span>Select All (<?php echo count($bookingsWithoutPassword); ?> bookings)</span>
                                </label>
                                <p style="margin: 10px 0 0 0; font-size: 14px; color: var(--text-medium);">
                                    This will send password setup emails to all selected customers, allowing them to access their portal.
                                </p>
                            </div>

                            <div class="table-container">
                                <table class="data-table">
                                    <thead>
                                        <tr>
                                            <th style="width: 40px;">
                                                <input type="checkbox" id="select-all-header" style="width: auto;">
                                            </th>
                                            <th>Booking Ref</th>
                                            <th>Name</th>
                                            <th>Email</th>
                                            <th>Booking Date</th>
                                            <th>Invites Sent</th>
                                            <th>Last Invite</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($bookingsWithoutPassword as $booking): ?>
                                            <tr>
                                                <td>
                                                    <input
                                                        type="checkbox"
                                                        name="booking_ids[]"
                                                        value="<?php echo $booking['id']; ?>"
                                                        class="booking-checkbox"
                                                        style="width: auto;"
                                                    >
                                                </td>
                                                <td>
                                                    <a href="<?php echo url('admin/booking-detail.php?id=' . $booking['id']); ?>">
                                                        <?php echo e($booking['booking_reference']); ?>
                                                    </a>
                                                </td>
                                                <td><?php echo e($booking['booker_name']); ?></td>
                                                <td><?php echo e($booking['booker_email']); ?></td>
                                                <td><?php echo formatDate($booking['created_at'], 'd M Y'); ?></td>
                                                <td style="text-align: center;">
                                                    <?php if ($booking['token_count'] > 0): ?>
                                                        <span class="badge badge-warning"><?php echo $booking['token_count']; ?></span>
                                                    <?php else: ?>
                                                        <span style="color: var(--text-light);">None</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if ($booking['last_invite_sent']): ?>
                                                        <?php echo formatDate($booking['last_invite_sent'], 'd M Y H:i'); ?>
                                                    <?php else: ?>
                                                        <span style="color: var(--text-light);">Never</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>

                            <div style="margin-top: 20px; padding: 20px; background: var(--bg-light); border-radius: 8px;">
                                <button type="submit" class="btn btn-primary" style="width: auto;">
                                    📧 Send Invites to Selected Customers
                                </button>
                                <p style="margin: 15px 0 0 0; font-size: 13px; color: var(--text-medium);">
                                    <strong>Note:</strong> Each customer will receive an email with a secure link to set up their portal password. The link expires in 7 days.
                                </p>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>

                <!-- Bookings With Password -->
                <div class="content-card">
                    <div class="card-header">
                        <h2 class="card-title">
                            Customers With Portal Access (<?php echo count($bookingsWithPassword); ?>)
                        </h2>
                    </div>

                    <?php if (empty($bookingsWithPassword)): ?>
                        <div style="text-align: center; padding: 40px; color: var(--text-medium);">
                            <p>No customers have set up portal access yet.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-container">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Booking Ref</th>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Last Portal Login</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($bookingsWithPassword as $booking): ?>
                                        <tr>
                                            <td>
                                                <a href="<?php echo url('admin/booking-detail.php?id=' . $booking['id']); ?>">
                                                    <?php echo e($booking['booking_reference']); ?>
                                                </a>
                                            </td>
                                            <td><?php echo e($booking['booker_name']); ?></td>
                                            <td><?php echo e($booking['booker_email']); ?></td>
                                            <td>
                                                <?php if ($booking['last_portal_login']): ?>
                                                    <?php echo formatDate($booking['last_portal_login'], 'd M Y H:i'); ?>
                                                <?php else: ?>
                                                    <span style="color: var(--text-light);">Never logged in</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>

<script>
        // Select all checkboxes
        const selectAllCheckbox = document.getElementById('select-all');
        const selectAllHeader = document.getElementById('select-all-header');
        const bookingCheckboxes = document.querySelectorAll('.booking-checkbox');

        function updateSelectAll() {
            const allChecked = Array.from(bookingCheckboxes).every(cb => cb.checked);
            const someChecked = Array.from(bookingCheckboxes).some(cb => cb.checked);

            selectAllCheckbox.checked = allChecked;
            selectAllHeader.checked = allChecked;
            selectAllCheckbox.indeterminate = someChecked && !allChecked;
            selectAllHeader.indeterminate = someChecked && !allChecked;
        }

        function toggleAllCheckboxes(checked) {
            bookingCheckboxes.forEach(cb => {
                cb.checked = checked;
            });
            updateSelectAll();
        }

        selectAllCheckbox.addEventListener('change', (e) => {
            toggleAllCheckboxes(e.target.checked);
        });

        selectAllHeader.addEventListener('change', (e) => {
            toggleAllCheckboxes(e.target.checked);
        });

        bookingCheckboxes.forEach(cb => {
            cb.addEventListener('change', updateSelectAll);
        });

        // Initialize
        updateSelectAll();
    </script>

<?php include __DIR__ . '/../templates/admin/footer.php'; ?>
