<?php
/**
 * Payment Cancelled Page
 * Shows when user cancels Stripe payment
 */

// Initialize
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
        .cancel-icon {
            width: 80px;
            height: 80px;
            margin: 0 auto 20px;
            background: var(--warning-color);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 40px;
            color: white;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="form-section" style="text-align: center;">
            <div class="cancel-icon">✕</div>
            <h1>Payment Cancelled</h1>
            <p style="font-size: 18px; color: var(--text-medium); margin-bottom: 30px;">
                Your payment was cancelled. No charges have been made.
            </p>

            <p>
                Your booking has not been completed. If this was a mistake, you can try again.
            </p>

            <div style="margin-top: 30px;">
                <a href="/book/" class="btn btn-primary">Try Again</a>
            </div>
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
