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
        .reminder-badge {
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
        .payment-due {
            background: #fff3cd;
            border: 2px solid #ffc107;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }
        .amount-due {
            font-size: 32px;
            font-weight: bold;
            color: #856404;
            text-align: center;
            margin: 20px 0;
        }
        .due-date {
            background: #dc3545;
            color: white;
            padding: 12px;
            border-radius: 8px;
            text-align: center;
            font-size: 16px;
            font-weight: bold;
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
            <h1>Payment Reminder</h1>
            <p style="color: #666; margin: 10px 0 0 0;"><?php echo env('EVENT_NAME', 'Alive Church Camp 2026'); ?></p>
        </div>

        <div class="reminder-badge">
            ⏰ Payment Due Soon
        </div>

        <p>Dear <?php echo e($booker_name); ?>,</p>

        <p>This is a friendly reminder that your next installment payment for booking <strong><?php echo e($booking_reference); ?></strong> is due soon.</p>

        <!-- Payment Due Information -->
        <div class="payment-due">
            <h3 style="margin-top: 0; color: #856404;">Upcoming Payment</h3>
            <div class="detail-row">
                <span class="detail-label">Installment Number:</span>
                <span class="detail-value"><strong>#<?php echo $installment_number; ?></strong></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Due Date:</span>
                <span class="detail-value"><strong><?php echo formatDate($due_date, 'd/m/Y'); ?></strong></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Days Until Due:</span>
                <span class="detail-value">
                    <strong style="color: <?php echo $days_until_due <= 1 ? '#dc3545' : '#856404'; ?>;">
                        <?php echo $days_until_due; ?> day<?php echo $days_until_due != 1 ? 's' : ''; ?>
                    </strong>
                </span>
            </div>
        </div>

        <div class="amount-due">
            <?php echo formatCurrency($payment_amount); ?>
        </div>

        <!-- Payment Method Info -->
        <div class="section">
            <h2>Payment Information</h2>

            <?php if ($booking['payment_method'] === 'stripe'): ?>
                <div style="background: #f0f9ff; padding: 15px; border-radius: 8px;">
                    <p style="margin: 0;">
                        <strong>Good news!</strong> Your payment will be automatically charged to your saved card on <strong><?php echo formatDate($due_date, 'd/m/Y'); ?></strong>.
                    </p>
                    <p style="margin: 10px 0 0 0; font-size: 14px; color: #666;">
                        Please ensure your card has sufficient funds. You'll receive a receipt once the payment is processed.
                    </p>
                </div>
            <?php elseif ($booking['payment_method'] === 'bank_transfer'): ?>
                <div style="background: #f0f9ff; border: 2px solid #667eea; padding: 15px; border-radius: 8px;">
                    <strong>Bank Transfer Details:</strong>
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
                    <p style="margin: 15px 0 0 0; font-size: 14px; color: #666;">
                        Please make your payment by <strong><?php echo formatDate($due_date, 'd/m/Y'); ?></strong>.
                    </p>
                </div>
            <?php else: ?>
                <div style="background: #fffbf0; padding: 15px; border-radius: 8px;">
                    <p style="margin: 0;">
                        Please arrange to pay <strong><?php echo formatCurrency($payment_amount); ?></strong> to Jon at church by <strong><?php echo formatDate($due_date, 'd/m/Y'); ?></strong>.
                    </p>
                </div>
            <?php endif; ?>
        </div>

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

        <div class="footer">
            <p><strong>Questions?</strong> Contact us at <?php echo e(env('SMTP_FROM_EMAIL')); ?></p>
            <p style="margin-top: 15px; color: #999; font-size: 12px;">
                Booking Reference: <?php echo e($booking_reference); ?>
            </p>
        </div>
    </div>
</body>
</html>
