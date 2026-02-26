<?php
/**
 * Payment Cancelled Page
 * Redirects back to booking form with data preserved
 */

// Initialize
require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set a flag that payment was cancelled
$_SESSION['payment_cancelled'] = true;

// Redirect back to booking form
redirect('/book/?payment_cancelled=1');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Cancelled - <?php echo e(EVENT_NAME); ?></title>
    <link rel="stylesheet" href="/book/public/assets/css/main.css">
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Helvetica Neue', sans-serif;
            font-size: 16px;
            line-height: 1.6;
            color: #1f2937;
            background: #1a1a1a;
            min-height: 100vh;
            padding: 40px 20px;
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .container {
            max-width: 600px;
            width: 100%;
            background: #f5f5f5;
            border-radius: 12px;
            padding: 40px;
        }
        .form-section {
            background: white;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            margin-bottom: 25px;
        }
        .cancel-icon {
            width: 80px;
            height: 80px;
            margin: 0 auto 20px;
            background: #f59e0b;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 40px;
            color: white;
        }
        h1 {
            color: #1f2937;
            margin-bottom: 15px;
        }
        h2 {
            color: #1f2937;
            font-size: 20px;
            margin-bottom: 15px;
        }
        p {
            color: #6b7280;
            line-height: 1.6;
            margin-bottom: 15px;
        }
        .btn {
            display: inline-block;
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 600;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            font-family: inherit;
            text-decoration: none;
            background: linear-gradient(135deg, #eb008b 0%, #d40080 100%);
            color: white;
        }
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
        }
        a {
            color: #eb008b;
            text-decoration: none;
        }
        a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="form-section" style="text-align: center;">
            <div class="cancel-icon">✕</div>
            <h1>Payment Cancelled</h1>
            <p style="font-size: 18px; margin-bottom: 30px;">
                Your payment was cancelled. No charges have been made to your card.
            </p>

            <p>
                Your booking has <strong>not been completed</strong>. If this was a mistake or you'd like to try a different payment method, you can return to the booking form.
            </p>

            <div style="margin-top: 30px;">
                <a href="/book/" class="btn">Return to Booking Form</a>
            </div>
        </div>

        <div class="form-section">
            <h2>Alternative Payment Methods</h2>
            <p>
                If you prefer not to pay online, we also accept:
            </p>
            <ul style="text-align: left; color: #6b7280; line-height: 2;">
                <li><strong>Bank Transfer</strong> - Direct transfer to our account</li>
                <li><strong>Cash Payment</strong> - Pay in person or via post</li>
            </ul>
            <p style="margin-top: 20px;">
                When you complete the booking form, you can select your preferred payment method.
            </p>
        </div>

        <div class="form-section">
            <h2>Need Help?</h2>
            <p>
                If you're experiencing issues with payment or have questions about the booking process,
                please contact us:
            </p>
            <p style="margin-top: 15px;">
                <strong>Email:</strong> <a href="mailto:<?php echo e(SMTP_FROM_EMAIL); ?>"><?php echo e(SMTP_FROM_EMAIL); ?></a>
            </p>
        </div>
    </div>
</body>
</html>
