<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .email-container {
            background: white;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .header {
            text-align: center;
            padding-bottom: 20px;
            border-bottom: 3px solid #eb008b;
            margin-bottom: 30px;
        }
        .header h1 {
            color: #eb008b;
            margin: 0;
            font-size: 28px;
        }
        .success-badge {
            background: #eb008b;
            color: white;
            padding: 15px;
            border-radius: 8px;
            text-align: center;
            margin: 20px 0;
            font-size: 18px;
            font-weight: bold;
        }
        .section {
            margin: 25px 0;
        }
        .section h2 {
            color: #eb008b;
            font-size: 18px;
            margin-bottom: 15px;
            border-bottom: 2px solid #f0f0f0;
            padding-bottom: 8px;
        }
        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #f0f0f0;
        }
        .detail-label {
            font-weight: 600;
            color: #666;
        }
        .detail-value {
            color: #333;
        }
        .payment-summary {
            background: #fff0f8;
            border: 2px solid #eb008b;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }
        .amount-paid {
            font-size: 32px;
            font-weight: bold;
            color: #eb008b;
            text-align: center;
            margin: 20px 0;
        }
        .balance-info {
            background: #f9f9f9;
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
        }
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 2px solid #f0f0f0;
            text-align: center;
            color: #666;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="header">
            <h1>Payment Received</h1>
            <p style="color: #666; margin: 10px 0 0 0;"><?php echo env('EVENT_NAME', 'Alive Church Camp 2026'); ?></p>
        </div>

        <div class="success-badge">
            ✓ Payment Successfully Received
        </div>

        <p>Dear <?php echo e($booker_name); ?>,</p>

        <p>Thank you! We've received your payment for booking <strong><?php echo e($booking_reference); ?></strong>.</p>

        <div class="amount-paid">
            <?php echo formatCurrency($payment_amount); ?>
        </div>

        <!-- Payment Details -->
        <div class="payment-summary">
            <h3 style="margin-top: 0; color: #eb008b;">Payment Details</h3>
            <div class="detail-row">
                <span class="detail-label">Payment Date:</span>
                <span class="detail-value"><?php echo formatDateTime($payment_date); ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Amount Paid:</span>
                <span class="detail-value" style="font-weight: bold; color: #eb008b;">
                    <?php echo formatCurrency($payment_amount); ?>
                </span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Payment Method:</span>
                <span class="detail-value"><?php echo ucwords(str_replace('_', ' ', $payment['payment_method'])); ?></span>
            </div>
            <?php if (!empty($payment['transaction_id'])): ?>
            <div class="detail-row">
                <span class="detail-label">Transaction ID:</span>
                <span class="detail-value" style="font-family: monospace; font-size: 12px;">
                    <?php echo e($payment['transaction_id']); ?>
                </span>
            </div>
            <?php endif; ?>
        </div>

        <!-- Balance Information -->
        <div class="section">
            <h2>Booking Balance</h2>
            <div class="balance-info">
                <div class="detail-row">
                    <span class="detail-label">Total Booking Amount:</span>
                    <span class="detail-value"><?php echo formatCurrency($booking['total_amount']); ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Total Paid to Date:</span>
                    <span class="detail-value" style="font-weight: bold; color: #eb008b;">
                        <?php echo formatCurrency($amount_paid); ?>
                    </span>
                </div>
                <div class="detail-row" style="border-bottom: 2px solid #eb008b;">
                    <span class="detail-label">Outstanding Balance:</span>
                    <span class="detail-value" style="font-weight: bold; color: <?php echo $amount_outstanding > 0 ? '#dc3545' : '#eb008b'; ?>;">
                        <?php echo formatCurrency($amount_outstanding); ?>
                    </span>
                </div>
            </div>

            <?php if ($amount_outstanding <= 0): ?>
                <div style="background: #fff0f8; border: 2px solid #eb008b; padding: 15px; border-radius: 8px; margin-top: 15px; text-align: center;">
                    <strong style="color: #eb008b; font-size: 18px;">✓ Booking Fully Paid!</strong>
                    <p style="margin: 10px 0 0 0; color: #155724;">Your booking is now confirmed and fully paid. We look forward to seeing you at camp!</p>
                </div>
            <?php else: ?>
                <div style="background: #fff3cd; border: 2px solid #ffc107; padding: 15px; border-radius: 8px; margin-top: 15px;">
                    <strong style="color: #856404;">Remaining Balance:</strong>
                    <p style="margin: 10px 0 0 0; color: #856404;">
                        You have an outstanding balance of <strong><?php echo formatCurrency($amount_outstanding); ?></strong>.
                        <?php if ($booking['payment_plan'] !== 'full'): ?>
                            This will be charged according to your payment plan.
                        <?php else: ?>
                            Please arrange payment at your earliest convenience.
                        <?php endif; ?>
                    </p>
                </div>
            <?php endif; ?>
        </div>

        <div class="footer">
            <p><strong>Questions?</strong> Contact us at <?php echo e(env('SMTP_FROM_EMAIL')); ?></p>
            <p style="margin-top: 15px; color: #999; font-size: 12px;">
                Keep this receipt for your records. Booking Reference: <?php echo e($booking_reference); ?>
            </p>
        </div>
    </div>
</body>
</html>
