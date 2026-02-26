<?php
/**
 * Stripe Checkout Page
 * Displays Stripe payment form for completing payment
 */

// Initialize
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/classes/Booking.php';
require_once __DIR__ . '/classes/StripeHandler.php';

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Get booking reference from session
$bookingReference = $_SESSION['booking_reference'] ?? null;
$clientSecret = $_SESSION['stripe_client_secret'] ?? null;
$isSetupIntent = $_SESSION['stripe_is_setup_intent'] ?? false;

if (!$bookingReference || !$clientSecret) {
    redirect('/book/');
}

// Load booking
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

} catch (Exception $e) {
    redirect('/book/');
}

// Get Stripe public key
$stripe = new StripeHandler();
$stripePublicKey = $stripe->getPublicKey();

// Build return URL
$returnUrl = url('payment-success.php?booking=' . $bookingReference);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Complete Payment - <?php echo e(EVENT_NAME); ?></title>
    <link rel="stylesheet" href="/book/public/assets/css/main.css">
    <script src="https://js.stripe.com/v3/"></script>
    <style>
        body {
            background: #1a1a1a;
            padding: 40px 20px;
            margin: 0;
        }

        .payment-container {
            max-width: 600px;
            margin: 0 auto;
            background: #f5f5f5;
            border-radius: 12px;
            padding: 40px;
        }

        .payment-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .payment-header h1 {
            color: #eb008b;
            margin-bottom: 10px;
            font-size: 28px;
        }

        .payment-header p {
            color: #6b7280;
            margin: 0;
        }

        .payment-summary {
            background: #f9fafb;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 30px;
        }

        .payment-summary h2 {
            font-size: 18px;
            margin-bottom: 15px;
            color: #1f2937;
            font-weight: 600;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #e5e7eb;
            color: #6b7280;
        }

        .summary-row:last-child {
            border-bottom: none;
            font-weight: 600;
            font-size: 20px;
            color: #eb008b;
            padding-top: 15px;
            margin-top: 5px;
            border-top: 2px solid #e5e7eb;
        }

        .payment-form-card {
            background: white;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
            margin-bottom: 20px;
        }

        #stripe-payment-element {
            margin: 30px 0;
        }

        #stripe-loading {
            text-align: center;
            padding: 40px;
            color: #6b7280;
        }

        #stripe-loading.hidden {
            display: none;
        }

        #stripe-errors {
            display: none;
            background: #fee;
            color: #c33;
            border: 1px solid #fcc;
            border-radius: 6px;
            padding: 12px 16px;
            margin: 20px 0;
            font-size: 14px;
        }

        .payment-actions {
            margin-top: 30px;
        }

        #stripe-submit-btn {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, #eb008b 0%, #d40080 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 18px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            font-family: inherit;
        }

        #stripe-submit-btn:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
        }

        #stripe-submit-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        .security-note {
            text-align: center;
            margin-top: 20px;
            font-size: 13px;
            color: #6b7280;
        }

        .security-note svg {
            width: 14px;
            height: 14px;
            vertical-align: middle;
            margin-right: 5px;
        }

        .cancel-link {
            display: block;
            text-align: center;
            margin-top: 20px;
            color: #6b7280;
            text-decoration: none;
            font-size: 14px;
        }

        .cancel-link:hover {
            color: #1f2937;
            text-decoration: underline;
        }

        @media (max-width: 768px) {
            body {
                padding: 20px 15px;
            }

            .payment-container {
                padding: 30px 20px;
            }

            .payment-form-card {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="payment-container">
        <div class="payment-header">
            <h1><?php echo $isSetupIntent ? 'Setup Payment Method' : 'Complete Payment'; ?></h1>
            <p>Booking Reference: <strong><?php echo e($bookingReference); ?></strong></p>
        </div>

        <!-- Payment Summary -->
        <div class="payment-summary">
            <h2>Payment Summary</h2>
            <div class="summary-row">
                <span>Booker:</span>
                <span><?php echo e($bookingData['booker_name']); ?></span>
            </div>
            <div class="summary-row">
                <span>Attendees:</span>
                <span><?php echo count($booking->getAttendees()); ?> people</span>
            </div>
            <div class="summary-row">
                <span>Payment Plan:</span>
                <span>
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
            <div class="summary-row">
                <span>Total Amount:</span>
                <span><?php echo formatCurrency($bookingData['total_amount']); ?></span>
            </div>
            <?php if ($isSetupIntent): ?>
                <div style="margin-top: 15px; padding: 10px; background: #fff3cd; border-radius: 6px; font-size: 14px;">
                    Your payment method will be saved securely. Payments will be automatically charged according to your payment schedule.
                </div>
            <?php endif; ?>
        </div>

        <!-- Stripe Payment Element -->
        <div class="payment-form-card">
            <div id="stripe-loading">
                <p>Loading payment form...</p>
            </div>

            <div id="stripe-errors" role="alert"></div>

            <form id="payment-form">
                <div id="stripe-payment-element">
                    <!-- Stripe.js will inject payment form here -->
                </div>

                <div class="payment-actions">
                    <button type="submit" id="stripe-submit-btn">
                        <?php echo $isSetupIntent ? 'Confirm Payment Method' : 'Pay Now'; ?>
                    </button>
                </div>
            </form>
        </div>

        <div class="security-note">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
            </svg>
            Payments are securely processed by Stripe. Your card details are never stored on our servers.
        </div>

        <a href="payment-cancel.php" class="cancel-link">Cancel Payment</a>
    </div>

    <script src="/book/public/assets/js/stripe-handler.js"></script>
    <script>
        // Debug logging
        console.log('Stripe Public Key:', '<?php echo substr($stripePublicKey, 0, 20); ?>...');
        console.log('Client Secret:', '<?php echo $clientSecret ? substr($clientSecret, 0, 20) . '...' : 'EMPTY'; ?>');
        console.log('Is Setup Intent:', <?php echo $isSetupIntent ? 'true' : 'false'; ?>);
        console.log('Return URL:', '<?php echo $returnUrl; ?>');

        // Initialize Stripe
        const stripeHandler = new StripePaymentHandler('<?php echo $stripePublicKey; ?>');

        // Initialize payment element
        stripeHandler.initializePaymentElement(
            '<?php echo $clientSecret; ?>',
            <?php echo $isSetupIntent ? 'true' : 'false'; ?>
        ).catch(error => {
            console.error('Failed to initialize Stripe:', error);
            document.getElementById('stripe-loading').innerHTML = '<p style="color: red;">Failed to load payment form. Please check console for errors.</p>';
        });

        // Handle form submission
        document.getElementById('payment-form').addEventListener('submit', async (e) => {
            e.preventDefault();

            await stripeHandler.submit('<?php echo $returnUrl; ?>');
        });
    </script>
</body>
</html>
