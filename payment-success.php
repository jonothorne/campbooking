<?php
/**
 * Booking Success Page
 * Shows confirmation after successful booking
 */

// Initialize
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/sanitize.php';
require_once __DIR__ . '/classes/Booking.php';
require_once __DIR__ . '/classes/Attendee.php';
require_once __DIR__ . '/classes/StripeHandler.php';
require_once __DIR__ . '/classes/Email.php';

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Get booking reference from URL
$bookingReference = $_GET['booking'] ?? null;

if (!$bookingReference) {
    redirect(url(''));
}

// Check if this is a redirect from Stripe
$setupIntentId = $_GET['setup_intent'] ?? null;
$paymentIntentId = $_GET['payment_intent'] ?? null;
$redirectStatus = $_GET['redirect_status'] ?? null;

// If returning from Stripe, verify the payment/setup was successful
if ($setupIntentId || $paymentIntentId) {
    try {
        $stripe = new StripeHandler();

        if ($setupIntentId) {
            // Verify Setup Intent succeeded
            $setupIntent = $stripe->retrieveSetupIntent($setupIntentId);

            if ($setupIntent->status !== 'succeeded') {
                error_log("Setup Intent not succeeded: " . $setupIntent->status);
                $_SESSION['booking_error'] = 'Payment method setup was not completed. Please try again.';
                redirect(url('?error=1'));
            }

            // Setup Intent will be processed by webhook
            // We just need to confirm it succeeded here

        } elseif ($paymentIntentId) {
            // Verify Payment Intent succeeded
            $paymentIntent = $stripe->retrievePaymentIntent($paymentIntentId);

            if ($paymentIntent->status !== 'succeeded') {
                error_log("Payment Intent not succeeded: " . $paymentIntent->status);
                $_SESSION['booking_error'] = 'Payment was not completed. Please try again.';
                redirect(url('?error=1'));
            }

            // Payment Intent will be processed by webhook
        }

    } catch (Exception $e) {
        error_log("Error verifying Stripe intent: " . $e->getMessage());
        $_SESSION['booking_error'] = 'Unable to verify payment. Please contact support with your booking reference: ' . $bookingReference;
        redirect(url('?error=1'));
    }
}

// In development mode, process initial booking payment synchronously (webhook won't work locally)
if (isDevelopment() && $paymentIntentId && !isset($_SESSION['is_portal_payment'])) {
    try {
        $db = Database::getInstance();
        $stripe = new StripeHandler();
        $paymentIntent = $stripe->retrievePaymentIntent($paymentIntentId);

        if ($paymentIntent->status === 'succeeded') {
            $bookingId = $paymentIntent->metadata->booking_id ?? null;
            $paymentType = $paymentIntent->metadata->payment_type ?? 'full';

            if ($bookingId) {
                // Check if payment already recorded (prevent duplicates)
                $existingPayment = $db->fetchOne(
                    "SELECT id FROM payments WHERE stripe_payment_intent_id = ?",
                    [$paymentIntent->id]
                );

                if (!$existingPayment) {
                    // Get booking
                    $bookingData = $db->fetchOne("SELECT * FROM bookings WHERE id = ?", [$bookingId]);

                    if ($bookingData) {
                        $booking = new Booking((int)$bookingId);
                        $amount = StripeHandler::formatAmountFromPence($paymentIntent->amount);

                        error_log("Dev mode: Processing payment for booking #{$bookingId}, amount: £{$amount}");

                        // Record payment
                        $paymentId = $booking->addPayment(
                            $amount,
                            'stripe',
                            [
                                'payment_type' => $paymentType,
                                'status' => 'succeeded',
                                'stripe_payment_intent_id' => $paymentIntent->id,
                                'stripe_charge_id' => $paymentIntent->charges->data[0]->id ?? null,
                                'admin_notes' => 'Initial booking payment (dev mode)'
                            ]
                        );

                        // Update booking status to confirmed
                        $booking->update(['booking_status' => 'confirmed']);

                        error_log("Dev mode: Payment recorded successfully. Payment ID: {$paymentId}");

                        // Send confirmation email
                        try {
                            $email = new Email();
                            $email->sendBookingConfirmation($bookingId);
                            error_log("Dev mode: Booking confirmation email sent");
                        } catch (Exception $e) {
                            error_log("Dev mode: Email error - " . $e->getMessage());
                            $_SESSION['email_warning'] = 'We were unable to send your confirmation email. Please contact us if you do not receive it within 24 hours.';
                        }
                    }
                } else {
                    error_log("Dev mode: Payment already recorded (ID: {$existingPayment['id']}), skipping duplicate");
                }
            }
        }
    } catch (Exception $e) {
        error_log("Dev mode payment processing error: " . $e->getMessage());
        $_SESSION['email_warning'] = 'Payment was successful but there was an issue recording it. Please contact support if you have concerns.';
    }
}

// Check if this is a portal payment - redirect to portal dashboard
if (isset($_SESSION['is_portal_payment']) && $_SESSION['is_portal_payment'] === true) {
    // In development mode, process payment synchronously (webhook won't work locally)
    if (isDevelopment() && $paymentIntentId && isset($_SESSION['portal_payment_schedules'])) {
        try {
            $db = Database::getInstance();
            $stripe = new StripeHandler();
            $paymentIntent = $stripe->retrievePaymentIntent($paymentIntentId);

            if ($paymentIntent->status === 'succeeded') {
                $bookingId = $paymentIntent->metadata->booking_id ?? null;
                $scheduleIds = isset($_SESSION['portal_payment_schedules']) ? $_SESSION['portal_payment_schedules'] : [];

                if ($bookingId && !empty($scheduleIds)) {
                    // Check if payment already recorded (prevent duplicates)
                    $existingPayment = $db->fetchOne(
                        "SELECT id FROM payments WHERE stripe_payment_intent_id = ?",
                        [$paymentIntent->id]
                    );

                    if (!$existingPayment) {
                        $booking = new Booking((int)$bookingId);
                        $amount = StripeHandler::formatAmountFromPence($paymentIntent->amount);

                        // Record payment
                        $paymentId = $booking->addPayment(
                            $amount,
                            'stripe',
                            [
                                'payment_type' => 'portal_payment',
                                'status' => 'succeeded',
                                'stripe_payment_intent_id' => $paymentIntent->id,
                                'stripe_charge_id' => $paymentIntent->charges->data[0]->id ?? null,
                                'admin_notes' => 'Portal payment for ' . count($scheduleIds) . ' schedule(s)'
                            ]
                        );

                        // Mark all schedules as paid
                        foreach ($scheduleIds as $scheduleId) {
                            $db->execute(
                                "UPDATE payment_schedules SET status = 'paid', payment_id = ?, last_attempt_date = NOW() WHERE id = ? AND booking_id = ?",
                                [$paymentId, $scheduleId, $bookingId]
                            );
                        }
                    }
                }
            }
        } catch (Exception $e) {
            error_log("Dev mode payment processing error: " . $e->getMessage());
        }
    }

    $_SESSION['success'] = 'Payment processed successfully! Your payment schedule has been updated.';
    unset($_SESSION['is_portal_payment']);
    unset($_SESSION['portal_payment_schedules']);
    unset($_SESSION['booking_reference']);
    unset($_SESSION['stripe_client_secret']);
    unset($_SESSION['stripe_payment_intent_id']);
    unset($_SESSION['stripe_is_setup_intent']);
    redirect(url('portal/dashboard.php'));
}

// Get email warning if present
$emailWarning = $_SESSION['email_warning'] ?? null;

// Clear saved booking form data since payment succeeded
unset($_SESSION['booking_form_data']);
unset($_SESSION['booking_reference']);
unset($_SESSION['pending_booking_id']);
unset($_SESSION['email_warning']);

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
    redirect(url(''));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Confirmed - <?php echo e(EVENT_NAME); ?></title>
    <link rel="stylesheet" href="<?php echo basePath('public/assets/css/main.css'); ?>">
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Helvetica Neue', sans-serif;
            font-size: 16px;
            line-height: 1.6;
            color: #1f2937;
            background: #121214;
            min-height: 100vh;
            padding: 40px 20px;
            margin: 0;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: #f0f0f2;
            border-radius: 12px;
            padding: 40px;
            position: relative;
            overflow: hidden;
        }
        .container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, #00e5ff, #eb008b);
        }
        .form-section {
            background: white;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            margin-bottom: 25px;
        }
        .form-section h2 {
            color: #1f2937;
            margin-bottom: 20px;
            font-size: 20px;
        }
        .success-icon {
            width: 80px;
            height: 80px;
            margin: 0 auto 20px;
            background: linear-gradient(135deg, #00e5ff, #10b981);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 40px;
            color: white;
            box-shadow: 0 0 30px rgba(0, 229, 255, 0.3);
        }
        .booking-reference {
            background: #121214;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            margin: 20px 0;
        }
        .booking-reference p {
            color: rgba(255, 255, 255, 0.6);
        }
        .booking-reference strong {
            font-size: 28px;
            background: linear-gradient(90deg, #00e5ff, #eb008b);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            font-family: 'Courier New', monospace;
        }
        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid #e5e7eb;
        }
        .detail-row:last-child {
            border-bottom: none;
        }
        .detail-label {
            font-weight: 500;
            color: #6b7280;
        }
        .detail-value {
            font-weight: 600;
            color: #1f2937;
        }
        .attendee-list {
            list-style: none;
            padding: 0;
        }
        .attendee-item {
            padding: 12px 15px;
            background: #f9fafb;
            border-radius: 6px;
            margin-bottom: 8px;
            border-left: 3px solid #00e5ff;
        }
        .btn {
            display: inline-block;
            padding: 12px 24px;
            border: none;
            border-radius: 4px;
            font-size: 14px;
            font-weight: 700;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            font-family: inherit;
            text-decoration: none;
            letter-spacing: 1px;
            text-transform: uppercase;
        }
        .btn-primary {
            background: linear-gradient(135deg, #eb008b 0%, #d40080 100%);
            color: white;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 0 30px rgba(235, 0, 139, 0.4);
        }
        .btn-secondary {
            background: transparent;
            color: #00e5ff;
            border: 2px solid #00e5ff;
        }
        .btn-secondary:hover {
            background: #00e5ff;
            color: #121214;
            box-shadow: 0 0 20px rgba(0, 229, 255, 0.3);
        }
        h1 {
            color: #1f2937;
            margin-bottom: 15px;
        }
        p {
            color: #6b7280;
            line-height: 1.6;
        }
        a {
            color: #eb008b;
        }
        a:hover {
            color: #c7006f;
        }
        @media (max-width: 768px) {
            body {
                padding: 20px 15px;
            }
            .container {
                padding: 30px 20px;
            }
            .form-section {
                padding: 20px;
            }
            .detail-row {
                flex-direction: column;
                gap: 5px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <?php if ($emailWarning): ?>
            <div style="background: #fef3c7; border: 1px solid #fde68a; color: #92400e; padding: 16px 20px; border-radius: 8px; margin-bottom: 20px; font-size: 14px;">
                <strong>⚠ Note:</strong> <?php echo e($emailWarning); ?>
            </div>
        <?php endif; ?>

        <div class="form-section" style="text-align: center;">
            <?php if ($bookingData['payment_method'] === 'stripe' && $bookingData['payment_plan'] > 1 && $bookingData['booking_status'] === 'pending'): ?>
                <!-- Installment booking: payment method saved, first charge may still be processing -->
                <div class="success-icon" style="background: #f59e0b;">&#10003;</div>
                <h1>Booking Received!</h1>
                <p style="font-size: 18px; color: var(--text-medium); margin-bottom: 30px;">
                    Your payment method has been saved and your first installment is being processed. You will receive a confirmation email once the payment is confirmed.
                </p>
            <?php elseif ($bookingData['payment_method'] === 'stripe' && $bookingData['booking_status'] === 'pending'): ?>
                <!-- One-time Stripe payment still processing -->
                <div class="success-icon" style="background: #f59e0b;">&#10003;</div>
                <h1>Payment Processing!</h1>
                <p style="font-size: 18px; color: var(--text-medium); margin-bottom: 30px;">
                    Your payment is being processed. You will receive a confirmation email shortly once the payment is confirmed.
                </p>
            <?php elseif (!empty($bookingData['discount_amount']) && $bookingData['discount_amount'] >= $bookingData['total_amount']): ?>
                <div class="success-icon" style="background: linear-gradient(135deg, #10b981, #059669);">&#10003;</div>
                <h1>Booking Confirmed — Fully Funded!</h1>
                <p style="font-size: 18px; color: var(--text-medium); margin-bottom: 30px;">
                    Your booking has been fully funded — no payment is required. We look forward to seeing you at camp!
                </p>
            <?php else: ?>
                <div class="success-icon">&#10003;</div>
                <h1>Booking Confirmed!</h1>
                <p style="font-size: 18px; color: var(--text-medium); margin-bottom: 30px;">
                    Thank you for your booking. We look forward to seeing you at camp!
                </p>
            <?php endif; ?>

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

            <?php if (!empty($bookingData['discount_amount']) && $bookingData['discount_amount'] > 0): ?>
            <div class="detail-row">
                <span class="detail-label">Discount Applied:</span>
                <span class="detail-value" style="color: #10b981;">
                    -<?php echo formatCurrency($bookingData['discount_amount']); ?>
                    <?php if ($bookingData['discount_amount'] >= $bookingData['total_amount']): ?>
                        (Fully Funded)
                    <?php endif; ?>
                </span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Amount Due:</span>
                <span class="detail-value" style="font-weight: 700;"><?php echo formatCurrency($bookingData['amount_outstanding']); ?></span>
            </div>
            <?php endif; ?>

            <?php if (empty($bookingData['discount_amount']) || $bookingData['discount_amount'] < $bookingData['total_amount']): ?>
            <div class="detail-row">
                <span class="detail-label">Payment Method:</span>
                <span class="detail-value"><?php echo ucwords(str_replace('_', ' ', $bookingData['payment_method'])); ?></span>
            </div>

            <div class="detail-row">
                <span class="detail-label">Payment Plan:</span>
                <span class="detail-value">
                    <?php
                    $planCount = (int)$bookingData['payment_plan'];
                    echo $planCount <= 1 ? 'Pay in Full' : $planCount . ' Split Payments';
                    ?>
                </span>
            </div>
            <?php endif; ?>
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

        <!-- Payment Instructions (hide for fully funded bookings) -->
        <?php $isFullyFunded = !empty($bookingData['discount_amount']) && $bookingData['discount_amount'] >= $bookingData['total_amount']; ?>
        <?php if (!$isFullyFunded && $bookingData['payment_method'] === 'bank_transfer'): ?>
            <div class="form-section">
                <h2>Bank Transfer Details</h2>
                <div class="info-box">
                    <p>Please transfer <strong><?php echo formatCurrency($bookingData['amount_outstanding']); ?></strong> to:</p>

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
        <?php elseif (!$isFullyFunded && $bookingData['payment_method'] === 'cash'): ?>
            <div class="form-section">
                <h2>Cash Payment</h2>
                <div class="info-box">
                    <p>Please pay <strong><?php echo formatCurrency($bookingData['amount_outstanding']); ?></strong> in cash to your group leader.</p>
                    <p style="margin-top: 10px;">Reference: <strong><?php echo e($bookingData['booking_reference']); ?></strong></p>
                </div>
            </div>
        <?php endif; ?>

        <!-- What's Next -->
        <div class="form-section">
            <h2>What's Next?</h2>
            <p>You will receive a confirmation email shortly with all your booking details.</p>

            <?php if ($bookingData['payment_plan'] > 1 && !$isFullyFunded): ?>
                <p style="margin-top: 15px;">
                    For installment payments, you will receive payment reminders before each due date.
                </p>
            <?php endif; ?>

            <p style="margin-top: 15px;">
                If you have any questions, please contact us at
                <a href="mailto:<?php echo e(SMTP_FROM_EMAIL); ?>"><?php echo e(SMTP_FROM_EMAIL); ?></a>
            </p>
        </div>

        <!-- Download PDF -->
        <div class="form-section" style="background: #121214; border: 1px solid rgba(0, 229, 255, 0.2); color: white;">
            <h2 style="color: white;">Download Your Booking Confirmation</h2>
            <p style="margin-bottom: 20px; color: rgba(255,255,255,0.7);">Download a PDF with all your booking details. Perfect for printing and bringing to check-in!</p>
            <a href="<?php echo basePath('download-booking-pdf.php?booking=' . $bookingData['booking_reference']); ?>" class="btn btn-primary" style="display: inline-block;">
                Download PDF
            </a>
        </div>

        <div style="text-align: center; margin: 30px 0;">
            <a href="<?php echo basePath(''); ?>" class="btn btn-secondary">Make Another Booking</a>
        </div>
    </div>
</body>
</html>
