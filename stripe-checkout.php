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

// Check if returning from Stripe redirect
$setupIntentId = $_GET['setup_intent'] ?? null;
$paymentIntentId = $_GET['payment_intent'] ?? null;
$redirectStatus = $_GET['redirect_status'] ?? null;

// If returning from Stripe with success, redirect to success page
if (($setupIntentId || $paymentIntentId) && $redirectStatus === 'succeeded') {
    $bookingRef = $_SESSION['booking_reference'] ?? null;
    if ($bookingRef) {
        redirect(url('payment-success.php?booking=' . $bookingRef .
            ($setupIntentId ? '&setup_intent=' . $setupIntentId : '&payment_intent=' . $paymentIntentId) .
            '&redirect_status=' . $redirectStatus));
    }
}

// Get booking reference from session
$bookingReference = $_SESSION['booking_reference'] ?? null;
$clientSecret = $_SESSION['stripe_client_secret'] ?? null;
$isSetupIntent = $_SESSION['stripe_is_setup_intent'] ?? false;

if (!$bookingReference || !$clientSecret) {
    redirect(url(''));
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
    redirect(url(''));
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
    <link rel="stylesheet" href="<?php echo basePath('public/assets/css/main.css'); ?>">
    <script src="https://js.stripe.com/v3/"></script>
    <style>
        body {
            background: #121214;
            padding: 40px 20px;
            margin: 0;
        }

        .payment-container {
            max-width: 600px;
            margin: 0 auto;
            background: #f0f0f2;
            border-radius: 12px;
            padding: 40px;
            position: relative;
            overflow: hidden;
        }

        .payment-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, #00e5ff, #eb008b);
        }

        .payment-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .payment-header h1 {
            color: #1f2937;
            margin-bottom: 10px;
            font-size: 26px;
            font-weight: 700;
            letter-spacing: 1px;
        }

        .payment-header p {
            color: #6b7280;
            margin: 0;
        }

        .payment-header p strong {
            color: #eb008b;
            font-family: 'Courier New', monospace;
        }

        .payment-summary {
            background: #121214;
            border-radius: 8px;
            padding: 24px;
            margin-bottom: 30px;
            color: white;
            position: relative;
        }

        .payment-summary h2 {
            font-size: 14px;
            margin-bottom: 15px;
            color: rgba(255, 255, 255, 0.6);
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            color: rgba(255, 255, 255, 0.7);
        }

        .summary-row:last-child {
            border-bottom: none;
            font-weight: 700;
            font-size: 22px;
            padding-top: 15px;
            margin-top: 5px;
            border-top: 1px solid rgba(255, 255, 255, 0.15);
        }

        .summary-row:last-child span:last-child {
            background: linear-gradient(90deg, #00e5ff, #eb008b);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
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
            background: #fef2f2;
            color: #991b1b;
            border: 1px solid #fecaca;
            border-radius: 8px;
            padding: 16px 20px;
            margin: 20px 0;
            font-size: 15px;
            font-weight: 500;
            line-height: 1.5;
        }

        .payment-actions {
            margin-top: 30px;
        }

        #stripe-submit-btn {
            width: 100%;
            padding: 18px;
            background: linear-gradient(135deg, #eb008b 0%, #d40080 100%);
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            font-weight: 700;
            letter-spacing: 2px;
            text-transform: uppercase;
            cursor: pointer;
            transition: all 0.3s ease;
            font-family: inherit;
        }

        #stripe-submit-btn:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 0 30px rgba(235, 0, 139, 0.4);
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
            color: #00e5ff;
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
                    $planCount = (int)$bookingData['payment_plan'];
                    echo $planCount <= 1 ? 'Pay in Full' : $planCount . ' Split Payments';
                    ?>
                </span>
            </div>
            <div class="summary-row">
                <span>Total Amount:</span>
                <span><?php echo formatCurrency($bookingData['total_amount']); ?></span>
            </div>
            <?php if ($isSetupIntent): ?>
                <div style="margin-top: 15px; padding: 12px 15px; background: rgba(0, 229, 255, 0.08); border-left: 3px solid #00e5ff; border-radius: 0 6px 6px 0; font-size: 14px; color: rgba(255, 255, 255, 0.8);">
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

        <a href="<?php echo basePath('payment-cancel.php'); ?>" class="cancel-link">Cancel and choose a different payment method</a>
    </div>

    <script src="<?php echo basePath('public/assets/js/stripe-handler.js'); ?>"></script>
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
