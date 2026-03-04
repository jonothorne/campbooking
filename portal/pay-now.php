<?php
/**
 * Customer Portal - Pay Now
 * Process overdue or pending payments
 */

// Initialize
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
                // Use saved payment method
                foreach ($paymentsToPay as $schedule) {
                    $stripe->chargePaymentMethod(
                        $bookingData['stripe_customer_id'],
                        $bookingData['stripe_payment_method_id'],
                        $schedule['amount'],
                        $schedule['id']
                    );
                }
                $_SESSION['success'] = 'Payment processed successfully!';
                redirect(url('portal/dashboard.php'));
            } else {
                // Create new payment session
                $_SESSION['portal_payment_schedules'] = array_column($paymentsToPay, 'id');
                $checkoutSession = $stripe->createCheckoutSession($customerId, $totalAmount, 'portal_payment');
                redirect($checkoutSession->url);
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
    <link rel="stylesheet" href="<?php echo basePath('public/assets/css/admin.css'); ?>">
    <style>
        body {
            background: #f9fafb;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
        }

        .portal-header {
            background: linear-gradient(135deg, #eb008b 0%, #d40080 100%);
            color: white;
            padding: 30px 20px;
            margin-bottom: 30px;
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

        .payment-summary {
            background: #fff;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 25px;
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
            color: var(--primary-color);
        }

        .payment-method-card {
            background: white;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            padding: 20px;
            margin: 15px 0;
            cursor: pointer;
            transition: all 0.3s;
        }

        .payment-method-card:hover {
            border-color: var(--primary-color);
            background: #fef2f8;
        }

        .payment-method-card input[type="radio"] {
            margin-right: 12px;
        }
    </style>
</head>
<body>
    <div class="portal-header">
        <div class="portal-header-content">
            <img src="<?php echo basePath('public/assets/images/echo-logo.png'); ?>" alt="ECHO2026" class="portal-logo" style="filter: brightness(0) invert(1);">
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
