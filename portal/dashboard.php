<?php
/**
 * Customer Portal Dashboard
 * View booking details, payments, and GDPR tools
 */

// Initialize
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/customer-auth.php';
require_once __DIR__ . '/../classes/Booking.php';

// Require authentication
requireCustomerAuth();

$customerId = currentCustomerId();

// Load booking
try {
    $booking = new Booking($customerId);
    $bookingData = $booking->getData();
    $attendees = $booking->getAttendees();
    $payments = $booking->getPayments();
    $paymentSchedule = $booking->getPaymentSchedule();
} catch (Exception $e) {
    customerLogout();
    redirect(url('portal/login.php'));
}

$welcome = isset($_GET['welcome']);
$success = $_SESSION['success'] ?? null;
$error = $_SESSION['error'] ?? null;
unset($_SESSION['success'], $_SESSION['error']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Booking - <?php echo e(EVENT_NAME); ?></title>
    <link rel="stylesheet" href="<?php echo basePath('public/assets/css/admin.css'); ?>">
    <style>
        body {
            background: #f9fafb;
        }

        .portal-header {
            background: linear-gradient(135deg, #eb008b 0%, #d40080 100%);
            color: white;
            padding: 30px 20px;
            margin-bottom: 30px;
        }

        .portal-header-content {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .portal-logo {
            height: 50px;
        }

        .portal-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px 40px;
        }

        @media (max-width: 768px) {
            .portal-header-content {
                flex-direction: column;
                gap: 15px;
            }
        }
    </style>
</head>
<body>
    <div class="portal-header">
        <div class="portal-header-content">
            <div>
                <img src="<?php echo basePath('public/assets/images/echo-logo.png'); ?>" alt="ECHO2026" class="portal-logo" style="filter: brightness(0) invert(1);">
            </div>
            <div style="display: flex; gap: 15px; align-items: center;">
                <span><?php echo e($bookingData['booker_name']); ?></span>
                <a href="<?php echo url('portal/logout.php'); ?>" class="btn-logout">Logout</a>
            </div>
        </div>
    </div>

    <div class="portal-container">
        <?php if ($welcome): ?>
            <div class="alert alert-success">
                <strong>Welcome to your portal!</strong> You now have access to view and manage your ECHO2026 booking.
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo e($success); ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo e($error); ?></div>
        <?php endif; ?>

        <!-- Booking Overview -->
        <div class="content-card">
            <div class="card-header">
                <h2 class="card-title">Booking Overview</h2>
                <span class="badge badge-info">Ref: <?php echo e($bookingData['booking_reference']); ?></span>
            </div>

            <div class="detail-grid">
                <div class="detail-item">
                    <div class="detail-label">Status</div>
                    <div class="detail-value">
                        <?php echo getBookingStatusBadge($bookingData['booking_status']); ?>
                    </div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Payment Status</div>
                    <div class="detail-value">
                        <?php echo getPaymentStatusBadge($bookingData['payment_status']); ?>
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
                    <div class="detail-label">Booking Date</div>
                    <div class="detail-value"><?php echo formatDate($bookingData['created_at'], 'd M Y'); ?></div>
                </div>
            </div>
        </div>

        <!-- Contact Information -->
        <div class="content-card">
            <div class="card-header">
                <h2 class="card-title">Your Contact Details</h2>
            </div>

            <div class="detail-grid">
                <div class="detail-item">
                    <div class="detail-label">Name</div>
                    <div class="detail-value"><?php echo e($bookingData['booker_name']); ?></div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Email</div>
                    <div class="detail-value"><?php echo e($bookingData['booker_email']); ?></div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Phone</div>
                    <div class="detail-value"><?php echo e($bookingData['booker_phone']); ?></div>
                </div>
            </div>
        </div>

        <!-- Attendees -->
        <div class="content-card">
            <div class="card-header">
                <h2 class="card-title">Attendees (<?php echo count($attendees); ?>)</h2>
            </div>

            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 15px;">
                <?php foreach ($attendees as $attendee): ?>
                    <div style="background: var(--bg-light); padding: 15px; border-radius: 8px;">
                        <div style="font-weight: 600; margin-bottom: 5px;"><?php echo e($attendee['name']); ?></div>
                        <div style="font-size: 13px; color: var(--text-medium);">
                            Age: <?php echo $attendee['age']; ?> years<br>
                            <?php echo ucwords(str_replace('_', ' ', $attendee['ticket_type'])); ?><br>
                            <strong><?php echo formatCurrency($attendee['ticket_price']); ?></strong>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Payment History -->
        <?php if (!empty($payments)): ?>
        <div class="content-card">
            <div class="card-header">
                <h2 class="card-title">Payment History</h2>
            </div>

            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Amount</th>
                            <th>Method</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($payments as $payment): ?>
                            <tr>
                                <td><?php echo formatDate($payment['payment_date'], 'd M Y H:i'); ?></td>
                                <td><strong><?php echo formatCurrency($payment['amount']); ?></strong></td>
                                <td><?php echo ucwords(str_replace('_', ' ', $payment['payment_method'])); ?></td>
                                <td>
                                    <?php if ($payment['status'] === 'succeeded' || $payment['status'] === 'completed'): ?>
                                        <span class="badge badge-success">Paid</span>
                                    <?php elseif ($payment['status'] === 'failed'): ?>
                                        <span class="badge badge-danger">Failed</span>
                                    <?php else: ?>
                                        <span class="badge badge-secondary"><?php echo ucfirst($payment['status']); ?></span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>

        <!-- Payment Schedule -->
        <?php if (!empty($paymentSchedule) && $bookingData['amount_outstanding'] > 0): ?>
        <div class="content-card">
            <div class="card-header">
                <h2 class="card-title">Upcoming Payments</h2>
            </div>

            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Due Date</th>
                            <th>Amount</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($paymentSchedule as $schedule): ?>
                            <?php if ($schedule['status'] !== 'paid'): ?>
                                <tr>
                                    <td><?php echo $schedule['installment_number']; ?></td>
                                    <td><?php echo formatDate($schedule['due_date'], 'd M Y'); ?></td>
                                    <td><strong><?php echo formatCurrency($schedule['amount']); ?></strong></td>
                                    <td>
                                        <?php if ($schedule['status'] === 'pending'): ?>
                                            <span class="badge badge-warning">Pending</span>
                                        <?php elseif ($schedule['status'] === 'failed'): ?>
                                            <span class="badge badge-danger">Failed</span>
                                        <?php else: ?>
                                            <span class="badge badge-secondary"><?php echo ucfirst($schedule['status']); ?></span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>

        <!-- GDPR Tools -->
        <div class="content-card">
            <div class="card-header">
                <h2 class="card-title">Privacy & Data Management</h2>
            </div>

            <div style="display: grid; gap: 15px;">
                <div style="background: var(--bg-light); padding: 20px; border-radius: 8px;">
                    <h3 style="margin: 0 0 10px 0; font-size: 16px;">Download Your Data</h3>
                    <p style="margin: 0 0 15px 0; color: var(--text-medium); font-size: 14px;">
                        Export all your personal data in JSON format (GDPR compliance)
                    </p>
                    <a href="<?php echo url('portal/export-data.php'); ?>" class="btn btn-secondary btn-sm">
                        Download Data Export
                    </a>
                </div>

                <div style="background: var(--bg-light); padding: 20px; border-radius: 8px;">
                    <h3 style="margin: 0 0 10px 0; font-size: 16px;">Request Data Deletion</h3>
                    <p style="margin: 0 0 15px 0; color: var(--text-medium); font-size: 14px;">
                        Request permanent deletion of your booking and personal data
                    </p>
                    <?php if ($bookingData['data_deletion_requested']): ?>
                        <span class="badge badge-warning">Deletion Requested on <?php echo formatDate($bookingData['data_deletion_requested_at'], 'd M Y'); ?></span>
                    <?php else: ?>
                        <a href="<?php echo url('portal/delete-request.php'); ?>" class="btn btn-danger btn-sm"
                           onclick="return confirm('Are you sure you want to request deletion of your data? This cannot be undone.');">
                            Request Deletion
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Help -->
        <div class="content-card">
            <div class="card-header">
                <h2 class="card-title">Need Help?</h2>
            </div>
            <p>If you have questions about your booking or need assistance, please contact us:</p>
            <p><strong>Email:</strong> <a href="mailto:<?php echo e(env('SMTP_FROM_EMAIL')); ?>"><?php echo e(env('SMTP_FROM_EMAIL')); ?></a></p>
        </div>
    </div>
</body>
</html>
