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
        .alert-badge {
            background: #dc3545;
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
        .payment-failed {
            background: #f8d7da;
            border: 2px solid #dc3545;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }
        .amount-failed {
            font-size: 32px;
            font-weight: bold;
            color: #721c24;
            text-align: center;
            margin: 20px 0;
        }
        .action-required {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .retry-info {
            background: #d1ecf1;
            border: 2px solid #17a2b8;
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
            <img src="<?php echo url('public/assets/images/echo-logo.png'); ?>" alt="ECHO2026" style="width: 180px; height: auto; margin: 0 auto 15px; display: block; filter: brightness(0) invert(1);">
            <h1>ECHO2026</h1>
            <p style="color: rgba(255,255,255,0.95); margin: 8px 0 0 0; font-size: 14px; letter-spacing: 1px;">Payment Unsuccessful</p>
            <p style="color: rgba(255,255,255,0.9); margin: 20px 0 0 0; font-size: 13px; font-style: italic; border-top: 1px solid rgba(255,255,255,0.3); padding-top: 15px;">"The Spirit and the bride say, 'Come!'" - Revelation 22:17</p>
        </div>

        <div style="padding: 35px 30px;">
        <div class="alert-badge">
            ⚠ Payment Unsuccessful
        </div>

        <p><strong>Dear <?php echo e($booker_name); ?>,</strong></p>

        <p>We attempted to process your scheduled payment for booking <strong><?php echo e($booking_reference); ?></strong>, but unfortunately the payment could not be completed.</p>

        <!-- Failed Payment Information -->
        <div class="payment-failed">
            <h3 style="margin-top: 0; color: #721c24;">Failed Payment Details</h3>
            <div class="detail-row">
                <span class="detail-label">Installment Number:</span>
                <span class="detail-value"><strong>#<?php echo $installment_number; ?></strong></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Due Date:</span>
                <span class="detail-value"><?php echo formatDate($due_date, 'd/m/Y'); ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Attempt Number:</span>
                <span class="detail-value"><?php echo $attempt_count; ?> of 3</span>
            </div>
        </div>

        <div class="amount-failed">
            <?php echo formatCurrency($payment_amount); ?>
        </div>

        <!-- Action Required -->
        <div class="action-required">
            <h3 style="margin-top: 0; color: #856404;">⚡ Action Required</h3>
            <p style="margin: 0;">
                <strong>Please take one of the following actions:</strong>
            </p>
            <ul style="margin: 10px 0; padding-left: 20px;">
                <?php if ($booking['payment_method'] === 'stripe'): ?>
                    <li>Ensure your card has sufficient funds</li>
                    <li>Check your card hasn't expired</li>
                    <li>Contact your bank if the card should be working</li>
                <?php elseif ($booking['payment_method'] === 'bank_transfer'): ?>
                    <li>Complete the bank transfer as soon as possible</li>
                    <li>Use the correct reference: <strong><?php echo getBankTransferReference($booker_name); ?></strong></li>
                <?php else: ?>
                    <li>Contact Jon at church to arrange payment</li>
                <?php endif; ?>
                <li>Contact us if you need to discuss payment options</li>
            </ul>
        </div>

        <!-- Retry Information -->
        <?php if ($attempt_count < 3 && $booking['payment_method'] === 'stripe'): ?>
        <div class="retry-info">
            <h3 style="margin-top: 0; color: #0c5460;">Automatic Retry</h3>
            <p style="margin: 0;">
                We will automatically retry this payment on <strong><?php echo formatDate($next_retry_date, 'd/m/Y'); ?></strong>.
            </p>
            <p style="margin: 10px 0 0 0; font-size: 14px;">
                Please ensure your card is valid and has sufficient funds before the retry date.
            </p>
        </div>
        <?php elseif ($attempt_count >= 3): ?>
        <div style="background: #f8d7da; border: 2px solid #dc3545; padding: 15px; border-radius: 8px; margin: 20px 0;">
            <h3 style="margin-top: 0; color: #721c24;">Maximum Attempts Reached</h3>
            <p style="margin: 0; color: #721c24;">
                We have attempted to process this payment 3 times without success. Please contact us urgently to arrange alternative payment.
            </p>
        </div>
        <?php endif; ?>

        <!-- Payment Method Info -->
        <?php if ($booking['payment_method'] === 'bank_transfer'): ?>
        <div class="section">
            <h2>Bank Transfer Details</h2>
            <div style="background: #f0f9ff; border: 2px solid #667eea; padding: 15px; border-radius: 8px;">
                <div class="detail-row">
                    <span class="detail-label">Bank:</span>
                    <span class="detail-value"><?php echo env('BANK_NAME'); ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Account:</span>
                    <span class="detail-value"><?php echo env('BANK_ACCOUNT'); ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Sort Code:</span>
                    <span class="detail-value"><?php echo env('BANK_SORT_CODE'); ?></span>
                </div>
                <div class="detail-row" style="border-bottom: none;">
                    <span class="detail-label">Reference:</span>
                    <span class="detail-value" style="font-weight: bold; color: #667eea;">
                        <?php echo getBankTransferReference($booker_name); ?>
                    </span>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Booking Summary -->
        <div class="section">
            <h2>Booking Summary</h2>
            <div style="background: #f9f9f9; padding: 15px; border-radius: 8px;">
                <div class="detail-row">
                    <span class="detail-label">Total Booking Amount:</span>
                    <span class="detail-value"><?php echo formatCurrency($booking['total_amount']); ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Total Paid to Date:</span>
                    <span class="detail-value" style="color: #28a745; font-weight: bold;">
                        <?php echo formatCurrency($booking['amount_paid']); ?>
                    </span>
                </div>
                <div class="detail-row" style="border-bottom: none;">
                    <span class="detail-label">Outstanding Balance:</span>
                    <span class="detail-value" style="color: #dc3545; font-weight: bold;">
                        <?php echo formatCurrency($booking['amount_outstanding']); ?>
                    </span>
                </div>
            </div>
        </div>
        </div>

        <div class="footer" style="background: #1a1a1a; color: #9ca3af; padding: 30px; text-align: center; font-size: 13px;">
            <p style="margin: 0;"><strong style="color: #e5e7eb;">ECHO2026</strong> - Respond to the Call</p>
            <p style="margin: 10px 0;"><strong>Need Help?</strong> Contact us immediately at <a href="mailto:<?php echo e(env('SMTP_FROM_EMAIL')); ?>" style="color: #eb008b; text-decoration: none;"><?php echo e(env('SMTP_FROM_EMAIL')); ?></a></p>
            <p style="margin-top: 5px;">We're here to help resolve this issue.</p>
            <p style="margin-top: 20px; font-size: 12px; color: #6b7280;">
                Booking Reference: <?php echo e($booking_reference); ?><br>
                Sizewell Hall, Sizewell, Leiston, Suffolk, IP16 4TX
            </p>
        </div>
    </div>
</body>
</html>
