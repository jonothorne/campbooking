<?php
/**
 * Admin Booking Detail
 * View and edit single booking with all details
 */

// Initialize
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/sanitize.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../classes/Booking.php';
require_once __DIR__ . '/../classes/Attendee.php';

// Check authentication
requireAuth();

// Get booking ID
$bookingId = isset($_GET['id']) ? sanitizeInt($_GET['id']) : 0;

if (!$bookingId) {
    redirect(url('admin/bookings.php'));
}

// Load booking
try {
    $booking = new Booking($bookingId);
    $bookingData = $booking->getData();
    $attendees = $booking->getAttendees();
    $payments = $booking->getPayments();
    $paymentSchedule = $booking->getPaymentSchedule();
} catch (Exception $e) {
    $_SESSION['error'] = 'Booking not found';
    redirect(url('admin/bookings.php'));
}

// Set page title
$pageTitle = 'Booking #' . $bookingData['booking_reference'];

// Get success/error messages
$success = $_SESSION['success'] ?? null;
$error = $_SESSION['error'] ?? null;
unset($_SESSION['success'], $_SESSION['error']);

// Include header
include __DIR__ . '/../templates/admin/header.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Booking Details</h1>
        <p class="page-subtitle">Reference: <?php echo e($bookingData['booking_reference']); ?></p>
    </div>
    <a href="<?php echo url('admin/bookings.php'); ?>" class="btn btn-secondary">
        ← Back to Bookings
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

<!-- Booking Status Overview -->
<div class="content-card">
    <div class="detail-grid">
        <div class="detail-item">
            <div class="detail-label">Payment Status</div>
            <div class="detail-value">
                <?php echo getPaymentStatusBadge($bookingData['payment_status']); ?>
            </div>
        </div>

        <div class="detail-item">
            <div class="detail-label">Booking Status</div>
            <div class="detail-value">
                <?php echo getBookingStatusBadge($bookingData['booking_status']); ?>
            </div>
        </div>

        <div class="detail-item">
            <div class="detail-label">Total Amount</div>
            <div class="detail-value"><?php echo formatCurrency($bookingData['total_amount']); ?></div>
        </div>

        <div class="detail-item">
            <div class="detail-label">Amount Paid</div>
            <div class="detail-value" style="color: var(--success-color);">
                <?php echo formatCurrency($bookingData['amount_paid']); ?>
            </div>
        </div>

        <div class="detail-item">
            <div class="detail-label">Outstanding</div>
            <div class="detail-value" style="color: var(--danger-color);">
                <?php echo formatCurrency($bookingData['amount_outstanding']); ?>
            </div>
        </div>

        <div class="detail-item">
            <div class="detail-label">Payment Completion</div>
            <div class="detail-value">
                <?php echo calculatePaymentPercentage($bookingData['total_amount'], $bookingData['amount_paid']); ?>%
            </div>
        </div>
    </div>
</div>

<!-- Booker Information -->
<div class="content-card">
    <div class="card-header">
        <h2 class="card-title">Booker Information</h2>
    </div>

    <div class="detail-grid">
        <div class="detail-item">
            <div class="detail-label">Name</div>
            <div class="detail-value"><?php echo e($bookingData['booker_name']); ?></div>
        </div>

        <div class="detail-item">
            <div class="detail-label">Email</div>
            <div class="detail-value">
                <a href="mailto:<?php echo e($bookingData['booker_email']); ?>">
                    <?php echo e($bookingData['booker_email']); ?>
                </a>
            </div>
        </div>

        <div class="detail-item">
            <div class="detail-label">Phone</div>
            <div class="detail-value">
                <a href="tel:<?php echo e($bookingData['booker_phone']); ?>">
                    <?php echo e($bookingData['booker_phone']); ?>
                </a>
            </div>
        </div>

        <div class="detail-item">
            <div class="detail-label">Booking Date</div>
            <div class="detail-value"><?php echo formatDateTime($bookingData['created_at']); ?></div>
        </div>
    </div>
</div>

<!-- Attendees -->
<div class="content-card">
    <div class="card-header">
        <h2 class="card-title">Attendees (<?php echo count($attendees); ?>)</h2>
    </div>

    <div class="table-container">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Age</th>
                    <th>Ticket Type</th>
                    <th>Price</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($attendees as $attendee): ?>
                    <?php $att = new Attendee($attendee['id']); ?>
                    <tr>
                        <td><strong><?php echo e($attendee['name']); ?></strong></td>
                        <td><?php echo e($attendee['age']); ?> years</td>
                        <td><?php echo e($att->getTicketDescription()); ?></td>
                        <td><?php echo formatCurrency($attendee['ticket_price']); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr style="font-weight: bold; background: var(--bg-light);">
                    <td colspan="3" class="text-right">Total:</td>
                    <td><?php echo formatCurrency($bookingData['total_amount']); ?></td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

<!-- Camping Requirements -->
<div class="content-card">
    <div class="card-header">
        <h2 class="card-title">Camping Requirements</h2>
    </div>

    <div class="detail-grid">
        <div class="detail-item">
            <div class="detail-label">Number of Tents</div>
            <div class="detail-value"><?php echo $bookingData['num_tents'] ?: 'None'; ?></div>
        </div>

        <div class="detail-item">
            <div class="detail-label">Caravan/Campervan</div>
            <div class="detail-value"><?php echo $bookingData['has_caravan'] ? 'Yes' : 'No'; ?></div>
        </div>

        <div class="detail-item">
            <div class="detail-label">Needs Tent Provided</div>
            <div class="detail-value"><?php echo $bookingData['needs_tent_provided'] ? 'Yes' : 'No'; ?></div>
        </div>
    </div>

    <?php if (!empty($bookingData['special_requirements'])): ?>
        <div style="margin-top: 20px;">
            <div class="detail-label">Special Requirements</div>
            <div style="padding: 15px; background: var(--bg-light); border-radius: 8px; margin-top: 10px;">
                <?php echo nl2br(e($bookingData['special_requirements'])); ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Payment Information -->
<div class="content-card">
    <div class="card-header">
        <h2 class="card-title">Payment Information</h2>
        <?php if ($bookingData['payment_status'] !== 'paid' && ($bookingData['payment_method'] === 'cash' || $bookingData['payment_method'] === 'bank_transfer')): ?>
            <form method="POST" action="<?php echo url('admin/mark-paid.php'); ?>" style="display: inline;">
                <input type="hidden" name="csrf_token" value="<?php echo csrfToken(); ?>">
                <input type="hidden" name="booking_id" value="<?php echo $bookingId; ?>">
                <button type="submit" class="btn btn-success btn-sm confirm-paid">
                    Mark as Paid
                </button>
            </form>
        <?php endif; ?>
    </div>

    <div class="detail-grid">
        <div class="detail-item">
            <div class="detail-label">Payment Method</div>
            <div class="detail-value"><?php echo ucwords(str_replace('_', ' ', $bookingData['payment_method'])); ?></div>
        </div>

        <div class="detail-item">
            <div class="detail-label">Payment Plan</div>
            <div class="detail-value">
                <?php
                $plans = [
                    'full' => 'Pay in Full',
                    'monthly' => 'Monthly Installments',
                    'three_payments' => '3 Equal Payments'
                ];
                echo $plans[$bookingData['payment_plan']] ?? 'Unknown';
                ?>
            </div>
        </div>

        <?php if ($bookingData['payment_method'] === 'bank_transfer'): ?>
            <div class="detail-item">
                <div class="detail-label">Bank Reference</div>
                <div class="detail-value"><?php echo e(getBankTransferReference($bookingData['booker_name'])); ?></div>
            </div>
        <?php endif; ?>

        <?php if ($bookingData['stripe_customer_id']): ?>
            <div class="detail-item">
                <div class="detail-label">Stripe Customer ID</div>
                <div class="detail-value" style="font-family: monospace; font-size: 12px;">
                    <?php echo e($bookingData['stripe_customer_id']); ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Payment History -->
<?php if (!empty($payments)): ?>
    <div class="content-card">
        <div class="card-header">
            <h2 class="card-title">Payment History (<?php echo count($payments); ?>)</h2>
        </div>

        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Amount</th>
                        <th>Method</th>
                        <th>Type</th>
                        <th>Status</th>
                        <th>Notes</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($payments as $payment): ?>
                        <tr>
                            <td><?php echo formatDateTime($payment['payment_date']); ?></td>
                            <td><strong><?php echo formatCurrency($payment['amount']); ?></strong></td>
                            <td><?php echo ucwords(str_replace('_', ' ', $payment['payment_method'])); ?></td>
                            <td><?php echo ucwords($payment['payment_type']); ?></td>
                            <td>
                                <?php
                                $statusBadges = [
                                    'succeeded' => '<span class="badge badge-success">Succeeded</span>',
                                    'pending' => '<span class="badge badge-warning">Pending</span>',
                                    'failed' => '<span class="badge badge-danger">Failed</span>',
                                    'refunded' => '<span class="badge badge-secondary">Refunded</span>'
                                ];
                                echo $statusBadges[$payment['status']] ?? e($payment['status']);
                                ?>
                            </td>
                            <td><?php echo e($payment['admin_notes'] ?: '-'); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<!-- Payment Schedule (for installments) -->
<?php if (!empty($paymentSchedule)): ?>
    <div class="content-card">
        <div class="card-header">
            <h2 class="card-title">Payment Schedule</h2>
        </div>

        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Installment #</th>
                        <th>Amount</th>
                        <th>Due Date</th>
                        <th>Status</th>
                        <th>Attempts</th>
                        <th>Last Attempt</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($paymentSchedule as $schedule): ?>
                        <tr>
                            <td><?php echo $schedule['installment_number']; ?></td>
                            <td><strong><?php echo formatCurrency($schedule['amount']); ?></strong></td>
                            <td>
                                <?php echo formatDate($schedule['due_date']); ?>
                                <?php if (isDatePast($schedule['due_date']) && $schedule['status'] === 'pending'): ?>
                                    <span class="badge badge-danger">OVERDUE</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php
                                $statusBadges = [
                                    'pending' => '<span class="badge badge-warning">Pending</span>',
                                    'paid' => '<span class="badge badge-success">Paid</span>',
                                    'failed' => '<span class="badge badge-danger">Failed</span>',
                                    'cancelled' => '<span class="badge badge-secondary">Cancelled</span>'
                                ];
                                echo $statusBadges[$schedule['status']] ?? e($schedule['status']);
                                ?>
                            </td>
                            <td><?php echo $schedule['attempt_count']; ?></td>
                            <td><?php echo $schedule['last_attempt_date'] ? formatDateTime($schedule['last_attempt_date']) : '-'; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<!-- Actions -->
<div class="content-card">
    <div class="card-header">
        <h2 class="card-title">Actions</h2>
    </div>

    <div style="display: flex; gap: 10px; flex-wrap: wrap;">
        <?php if ($bookingData['payment_status'] !== 'paid' && ($bookingData['payment_method'] === 'cash' || $bookingData['payment_method'] === 'bank_transfer')): ?>
            <form method="POST" action="<?php echo url('admin/mark-paid.php'); ?>" style="display: inline;">
                <input type="hidden" name="csrf_token" value="<?php echo csrfToken(); ?>">
                <input type="hidden" name="booking_id" value="<?php echo $bookingId; ?>">
                <button type="submit" class="btn btn-success confirm-paid">
                    💰 Mark as Fully Paid
                </button>
            </form>
        <?php endif; ?>

        <a href="mailto:<?php echo e($bookingData['booker_email']); ?>"
           class="btn btn-secondary">
            ✉️ Email Booker
        </a>

        <form method="POST" action="<?php echo url('admin/delete-booking.php'); ?>" style="display: inline;">
            <input type="hidden" name="csrf_token" value="<?php echo csrfToken(); ?>">
            <input type="hidden" name="booking_id" value="<?php echo $bookingId; ?>">
            <button type="submit" class="btn btn-danger confirm-delete">
                🗑️ Delete Booking
            </button>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../templates/admin/footer.php'; ?>
