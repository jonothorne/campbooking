<?php
/**
 * Customer Portal - Pay Now
 * Process overdue or pending payments
 */

// Initialize
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/customer-auth.php';
require_once __DIR__ . '/../classes/Booking.php';
require_once __DIR__ . '/../classes/StripeHandler.php';

// Require authentication
requireCustomerAuth();

$customerId = currentCustomerId();
$db = Database::getInstance();

// Load booking
try {
    $booking = new Booking($customerId);
    $bookingData = $booking->getData();
    $paymentSchedule = $booking->getPaymentSchedule();
} catch (Exception $e) {
    customerLogout();
    redirect(url('portal/login.php'));
}

// Get specific schedule ID if provided, otherwise show all overdue
$scheduleId = isset($_GET['schedule_id']) ? (int)$_GET['schedule_id'] : null;
$today = date('Y-m-d');

// Find payments to process
$paymentsToPay = [];
if ($scheduleId) {
    // Specific payment
    foreach ($paymentSchedule as $schedule) {
        if ($schedule['id'] == $scheduleId && ($schedule['status'] === 'pending' || $schedule['status'] === 'failed')) {
            $paymentsToPay[] = $schedule;
            break;
        }
    }
} else {
    // All overdue payments
    foreach ($paymentSchedule as $schedule) {
        if (($schedule['status'] === 'pending' || $schedule['status'] === 'failed') && $schedule['due_date'] < $today) {
            $paymentsToPay[] = $schedule;
        }
    }
}

if (empty($paymentsToPay)) {
    $_SESSION['error'] = 'No payments to process.';
    redirect(url('portal/dashboard.php'));
}

$totalAmount = array_sum(array_column($paymentsToPay, 'amount'));

// Handle payment method selection
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCustomerCsrfToken();

    $paymentMethod = $_POST['payment_method'] ?? '';

    if ($paymentMethod === 'stripe') {
        // Process Stripe payment
        try {
            $stripe = new StripeHandler();

            // Check if customer has saved payment method
            if (!empty($bookingData['stripe_customer_id']) && !empty($bookingData['stripe_payment_method_id'])) {
                // Use saved payment method - charge all schedules in one payment
                $scheduleIds = array_column($paymentsToPay, 'id');
                $amountInPence = (int)round($totalAmount * 100);

                // Create payment intent with saved payment method
                $paymentIntent = \Stripe\PaymentIntent::create([
                    'amount' => $amountInPence,
                    'currency' => 'gbp',
                    'customer' => $bookingData['stripe_customer_id'],
                    'payment_method' => $bookingData['stripe_payment_method_id'],
                    'off_session' => false, // Customer is present
                    'confirm' => true,
                    'receipt_email' => $bookingData['booker_email'],
                    'description' => 'Portal Payment - ' . $bookingData['booking_reference'],
                    'metadata' => [
                        'booking_id' => $customerId,
                        'payment_type' => 'portal_payment',
                        'schedule_ids' => json_encode($scheduleIds)
                    ],
                ]);

                // In development mode, process payment synchronously (webhook won't work locally)
                if (isDevelopment() && $paymentIntent->status === 'succeeded') {
                    // Record payment
                    $amount = $totalAmount;
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
                            [$paymentId, $scheduleId, $customerId]
                        );
                    }
                }

                // Payment will be processed by webhook (or already processed above in dev mode)
                // Show success and redirect to dashboard
                $_SESSION['success'] = 'Payment processed successfully! Your payment schedule has been updated.';
                redirect(url('portal/dashboard.php'));
            } else {
                // Create new payment intent and redirect to checkout
                // Include schedule IDs in metadata so webhook knows which schedules to mark as paid
                $scheduleIds = array_column($paymentsToPay, 'id');

                $stripe = new StripeHandler();
                $amountInPence = (int)round($totalAmount * 100);

                // Create payment intent with custom metadata
                $paymentIntent = \Stripe\PaymentIntent::create([
                    'amount' => $amountInPence,
                    'currency' => 'gbp',
                    'receipt_email' => $bookingData['booker_email'],
                    'description' => 'Portal Payment - ' . $bookingData['booking_reference'],
                    'metadata' => [
                        'booking_id' => $customerId,
                        'payment_type' => 'portal_payment',
                        'schedule_ids' => json_encode($scheduleIds)
                    ],
                    'automatic_payment_methods' => [
                        'enabled' => true,
                    ],
                ]);

                // Store in session for stripe-checkout.php
                $_SESSION['stripe_client_secret'] = $paymentIntent->client_secret;
                $_SESSION['stripe_payment_intent_id'] = $paymentIntent->id;
                $_SESSION['stripe_is_setup_intent'] = false;
                $_SESSION['booking_reference'] = $bookingData['booking_reference'];
                $_SESSION['portal_payment_schedules'] = $scheduleIds;
                $_SESSION['is_portal_payment'] = true;

                // Redirect to Stripe checkout page
                redirect(url('stripe-checkout.php'));
            }
        } catch (Exception $e) {
            $error = 'Payment failed: ' . $e->getMessage();
            error_log("Portal payment error: " . $e->getMessage());
        }
    } else {
        $error = 'Please select a payment method.';
    }
}

$csrfToken = generateCustomerCsrfToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pay Now - <?php echo e(EVENT_NAME); ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            background: #f9fafb;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            line-height: 1.6;
            color: #1f2937;
        }
        .portal-header {
            color: white;
            padding: 30px 20px;
            margin-bottom: 30px;
            background: linear-gradient(135deg, #1f2937 0%, #d40080 100%);
            border-color:#eb008b;
            border-top-style: solid;
            border-width: 5px;

        }
        .portal-header-content {
            max-width: 800px;
            margin: 0 auto;
            text-align: center;
        }
        .portal-logo {
            height: 50px;
            margin-bottom: 15px;
        }
        .portal-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 0 20px 40px;
        }
        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .alert-danger {
            background: #fee;
            color: #c33;
            border: 1px solid #fcc;
        }
        .payment-summary {
            background: #fff;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 25px;
        }
        .payment-summary h2 {
            margin: 0 0 20px 0;
            color: #111827;
        }
        .payment-item {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid #e5e7eb;
        }
        .payment-item:last-child {
            border-bottom: none;
            font-weight: 700;
            font-size: 18px;
            color: #eb008b;
        }
        .content-card {
            background: white;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .card-header {
            margin-bottom: 20px;
        }
        .card-title {
            margin: 0;
            color: #111827;
            font-size: 20px;
        }
        .payment-method-card {
            background: white;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            padding: 20px;
            margin: 15px 0;
            cursor: pointer;
            transition: all 0.3s;
            display: block;
        }
        .payment-method-card:hover {
            border-color: #eb008b;
            background: #fef2f8;
        }
        .payment-method-card input[type="radio"] {
            margin-right: 12px;
        }
        .btn {
            display: inline-block;
            padding: 12px 24px;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            border: none;
            transition: all 0.3s;
        }
        .btn-primary {
            background: linear-gradient(135deg, #eb008b 0%, #d40080 100%);
            color: white;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(235, 0, 139, 0.4);
        }
        .btn-secondary {
            background: #6b7280;
            color: white;
        }
        .btn-secondary:hover {
            background: #4b5563;
        }
    </style>
</head>
<body>
    <div class="portal-header">
        <div class="portal-header-content">
            <img src="<?php echo basePath('public/assets/images/echo-logo.png'); ?>" alt="ECHO2026" class="portal-logo">
            <h1 style="margin: 0; font-size: 28px;">Process Payment</h1>
            <p style="margin: 10px 0 0 0; opacity: 0.9;">Complete your outstanding payment(s)</p>
        </div>
    </div>

    <div class="portal-container">
        <?php if (isset($error)): ?>
            <div class="alert alert-danger">
                <?php echo e($error); ?>
            </div>
        <?php endif; ?>

        <!-- Payment Summary -->
        <div class="payment-summary">
            <h2 style="margin: 0 0 20px 0;">Payment Summary</h2>
            <?php foreach ($paymentsToPay as $schedule): ?>
                <div class="payment-item">
                    <div>
                        <strong>Payment #<?php echo $schedule['installment_number']; ?></strong>
                        <span style="color: #6b7280; font-size: 14px; margin-left: 10px;">
                            Due: <?php echo formatDate($schedule['due_date'], 'd M Y'); ?>
                        </span>
                    </div>
                    <div><?php echo formatCurrency($schedule['amount']); ?></div>
                </div>
            <?php endforeach; ?>
            <div class="payment-item">
                <div>Total to Pay</div>
                <div><?php echo formatCurrency($totalAmount); ?></div>
            </div>
        </div>

        <!-- Payment Method Selection -->
        <div class="content-card">
            <div class="card-header">
                <h2 class="card-title">Select Payment Method</h2>
            </div>

            <form method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?php echo e($csrfToken); ?>">

                <label class="payment-method-card">
                    <input type="radio" name="payment_method" value="stripe" required>
                    <strong style="font-size: 16px;">💳 Card Payment</strong>
                    <p style="margin: 8px 0 0 28px; color: #6b7280; font-size: 14px;">
                        Pay securely with credit/debit card via Stripe
                    </p>
                </label>

                <div style="margin-top: 30px; display: flex; gap: 15px;">
                    <button type="submit" class="btn btn-primary" style="flex: 1;">
                        Continue to Payment
                    </button>
                    <a href="<?php echo url('portal/dashboard.php'); ?>" class="btn btn-secondary" style="flex: 1; text-align: center;">
                        Cancel
                    </a>
                </div>
            </form>

            <div style="margin-top: 25px; padding: 20px; background: #f0f9ff; border-radius: 8px; font-size: 14px;">
                <strong>💡 Other Payment Methods:</strong>
                <p style="margin: 10px 0 0 0; color: #6b7280;">
                    To pay by bank transfer or cash, please contact us at
                    <a href="mailto:<?php echo e(env('SMTP_FROM_EMAIL')); ?>" style="color: var(--primary-color);">
                        <?php echo e(env('SMTP_FROM_EMAIL')); ?>
                    </a>
                </p>
            </div>
        </div>
    </div>
</body>
</html>
