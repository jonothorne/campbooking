<?php
/**
 * Payment Cancelled Page
 * Shows when user cancels Stripe payment
 */

// Initialize
require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';
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
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f5f5f5;
            margin: 0;
            padding: 40px 20px;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .container {
            max-width: 600px;
            width: 100%;
        }
        .form-section {
            background: white;
            border-radius: 12px;
            padding: 40px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            margin-bottom: 20px;
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
            padding: 14px 32px;
            background: linear-gradient(135deg, #eb008b, #d40080);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(235, 0, 139, 0.3);
        }
        a {
            color: #eb008b;
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
