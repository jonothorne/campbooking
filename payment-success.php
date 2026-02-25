<?php
/**
 * Booking Success Page
 * Shows confirmation after successful booking
 */

// Initialize
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/sanitize.php';
require_once __DIR__ . '/classes/Booking.php';
require_once __DIR__ . '/classes/Attendee.php';

// Get booking reference from URL
$bookingReference = $_GET['booking'] ?? null;

if (!$bookingReference) {
    redirect('/book/');
}

// Fetch booking
try {
    $db = Database::getInstance();
    $bookingData = $db->fetchOne(
        "SELECT * FROM bookings WHERE booking_reference = ?",
        [$bookingReference]
    );

    if (!$bookingData) {
        throw new Exception("Booking not found");
    }

    $booking = new Booking($bookingData['id']);
    $attendees = $booking->getAttendees();

} catch (Exception $e) {
    redirect('/book/');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Confirmed - <?php echo e(EVENT_NAME); ?></title>
    <link rel="stylesheet" href="public/assets/css/main.css">
    <style>
        .success-icon {
            width: 80px;
            height: 80px;
            margin: 0 auto 20px;
            background: #10b981;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 40px;
            color: white;
        }
        .booking-reference {
            background: var(--bg-light);
            padding: 15px;
            border-radius: var(--radius-md);
            text-align: center;
            margin: 20px 0;
        }
        .booking-reference strong {
            font-size: 24px;
            color: var(--primary-color);
            font-family: 'Courier New', monospace;
        }
        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid var(--border-color);
        }
        .detail-row:last-child {
            border-bottom: none;
        }
        .detail-label {
            font-weight: 500;
            color: var(--text-medium);
        }
        .detail-value {
            font-weight: 600;
            color: var(--text-dark);
        }
        .attendee-list {
            list-style: none;
            padding: 0;
        }
        .attendee-item {
            padding: 10px;
            background: var(--bg-light);
            border-radius: var(--radius-sm);
            margin-bottom: 8px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="form-section" style="text-align: center;">
            <div class="success-icon">✓</div>
            <h1>Booking Confirmed!</h1>
            <p style="font-size: 18px; color: var(--text-medium); margin-bottom: 30px;">
                Thank you for your booking. We look forward to seeing you at camp!
            </p>

            <div class="booking-reference">
                <p style="margin-bottom: 8px; color: var(--text-medium);">Your Booking Reference</p>
                <strong><?php echo e($bookingData['booking_reference']); ?></strong>
            </div>
        </div>

        <!-- Booking Details -->
        <div class="form-section">
            <h2>Booking Details</h2>

            <div class="detail-row">
                <span class="detail-label">Booker Name:</span>
                <span class="detail-value"><?php echo e($bookingData['booker_name']); ?></span>
            </div>

            <div class="detail-row">
                <span class="detail-label">Email:</span>
                <span class="detail-value"><?php echo e($bookingData['booker_email']); ?></span>
            </div>

            <div class="detail-row">
                <span class="detail-label">Phone:</span>
                <span class="detail-value"><?php echo e($bookingData['booker_phone']); ?></span>
            </div>

            <div class="detail-row">
                <span class="detail-label">Total Amount:</span>
                <span class="detail-value"><?php echo formatCurrency($bookingData['total_amount']); ?></span>
            </div>

            <div class="detail-row">
                <span class="detail-label">Payment Method:</span>
                <span class="detail-value"><?php echo ucwords(str_replace('_', ' ', $bookingData['payment_method'])); ?></span>
            </div>

            <div class="detail-row">
                <span class="detail-label">Payment Plan:</span>
                <span class="detail-value">
                    <?php
                    $plans = [
                        'full' => 'Pay in Full',
                        'monthly' => 'Monthly Installments',
                        'three_payments' => '3 Equal Payments'
                    ];
                    echo $plans[$bookingData['payment_plan']] ?? 'Unknown';
                    ?>
                </span>
            </div>
        </div>

        <!-- Attendees -->
        <div class="form-section">
            <h2>Attendees (<?php echo count($attendees); ?>)</h2>

            <ul class="attendee-list">
                <?php foreach ($attendees as $attendee): ?>
                    <li class="attendee-item">
                        <strong><?php echo e($attendee['name']); ?></strong> (<?php echo e($attendee['age']); ?> years old)
                        <br>
                        <small><?php
                            $att = new Attendee($attendee['id']);
                            echo e($att->getTicketDescription());
                        ?> - <?php echo formatCurrency($attendee['ticket_price']); ?></small>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>

        <!-- Payment Instructions -->
        <?php if ($bookingData['payment_method'] === 'bank_transfer'): ?>
            <div class="form-section">
                <h2>Bank Transfer Details</h2>
                <div class="info-box">
                    <p>Please transfer <strong><?php echo formatCurrency($bookingData['total_amount']); ?></strong> to:</p>

                    <div class="bank-details" style="margin-top: 15px;">
                        <div class="bank-detail-row">
                            <span class="label">Bank Name:</span>
                            <span class="value"><?php echo e(BANK_NAME); ?></span>
                        </div>
                        <div class="bank-detail-row">
                            <span class="label">Account Number:</span>
                            <span class="value"><?php echo e(BANK_ACCOUNT); ?></span>
                        </div>
                        <div class="bank-detail-row">
                            <span class="label">Sort Code:</span>
                            <span class="value"><?php echo e(BANK_SORT_CODE); ?></span>
                        </div>
                        <div class="bank-detail-row">
                            <span class="label">Reference:</span>
                            <span class="value"><?php echo e(getBankTransferReference($bookingData['booker_name'])); ?></span>
                        </div>
                    </div>

                    <p style="margin-top: 15px; font-size: 14px;">
                        <strong>Important:</strong> Please use the reference shown above to help us identify your payment.
                    </p>
                </div>
            </div>
        <?php elseif ($bookingData['payment_method'] === 'cash'): ?>
            <div class="form-section">
                <h2>Cash Payment</h2>
                <div class="info-box">
                    <p>Please hand <strong><?php echo formatCurrency($bookingData['total_amount']); ?></strong> to Jon at church.</p>
                    <p style="margin-top: 10px;">Reference: <strong><?php echo e($bookingData['booking_reference']); ?></strong></p>
                </div>
            </div>
        <?php endif; ?>

        <!-- What's Next -->
        <div class="form-section">
            <h2>What's Next?</h2>
            <p>You will receive a confirmation email shortly with all your booking details.</p>

            <?php if ($bookingData['payment_plan'] !== 'full'): ?>
                <p style="margin-top: 15px;">
                    For installment payments, you will receive payment reminders before each due date.
                </p>
            <?php endif; ?>

            <p style="margin-top: 15px;">
                If you have any questions, please contact us at
                <a href="mailto:<?php echo e(SMTP_FROM_EMAIL); ?>"><?php echo e(SMTP_FROM_EMAIL); ?></a>
            </p>
        </div>

        <div style="text-align: center; margin: 30px 0;">
            <a href="index.php" class="btn btn-secondary">Make Another Booking</a>
        </div>
    </div>
</body>
</html>
