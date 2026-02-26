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
        .booking-ref {
            background: linear-gradient(135deg, #eb008b 0%, #d40080 100%);
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
        .attendee-list {
            background: #f9f9f9;
            padding: 15px;
            border-radius: 8px;
            margin: 10px 0;
        }
        .attendee-item {
            padding: 8px 0;
            border-bottom: 1px solid #e0e0e0;
        }
        .attendee-item:last-child {
            border-bottom: none;
        }
        .total-amount {
            background: #f0f7ff;
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
            font-size: 20px;
            font-weight: bold;
            text-align: center;
            color: #667eea;
        }
        .payment-info {
            background: #fffbf0;
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .bank-details {
            background: #f0f9ff;
            border: 2px solid #667eea;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }
        .bank-details .detail-row {
            border-bottom: 1px solid #d0e0f0;
        }
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 2px solid #f0f0f0;
            text-align: center;
            color: #666;
            font-size: 14px;
        }
        .button {
            display: inline-block;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 12px 30px;
            text-decoration: none;
            border-radius: 8px;
            margin: 20px 0;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="header">
            <h1><?php echo env('EVENT_NAME', 'Alive Church Camp 2026'); ?></h1>
            <p style="color: #666; margin: 10px 0 0 0;">May 29-31, 2026</p>
        </div>

        <p>Dear <?php echo e($booker_name); ?>,</p>

        <p>Thank you for your booking! We're excited to confirm your registration for Alive Church Camp 2026.</p>

        <div class="booking-ref">
            Booking Reference: <?php echo e($booking_reference); ?>
        </div>

        <!-- Attendees -->
        <div class="section">
            <h2>Attendees (<?php echo count($attendees); ?>)</h2>
            <div class="attendee-list">
                <?php foreach ($attendees as $attendee): ?>
                    <?php
                    // Get ticket description
                    $ticketDesc = '';
                    switch ($attendee['ticket_type']) {
                        case 'adult_weekend':
                            $ticketDesc = 'Adult Weekend Ticket';
                            break;
                        case 'adult_sponsor':
                            $ticketDesc = 'Adult Sponsor Ticket (Help fund a young person)';
                            break;
                        case 'child_weekend':
                            $ticketDesc = 'Child Weekend Ticket';
                            break;
                        case 'free_child':
                            $ticketDesc = 'Free (Ages 0-4)';
                            break;
                        case 'adult_day':
                            $ticketDesc = 'Adult Day Ticket';
                            break;
                        case 'child_day':
                            $ticketDesc = 'Child Day Ticket';
                            break;
                        default:
                            $ticketDesc = ucwords(str_replace('_', ' ', $attendee['ticket_type']));
                    }
                    ?>
                    <div class="attendee-item">
                        <strong><?php echo e($attendee['name']); ?></strong>
                        (<?php echo e($attendee['age']); ?> years) -
                        <?php echo e($ticketDesc); ?> -
                        <strong><?php echo formatCurrency($attendee['ticket_price']); ?></strong>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Camping Requirements -->
        <?php if ($booking['num_tents'] || $booking['has_caravan'] || $booking['needs_tent_provided']): ?>
        <div class="section">
            <h2>Camping Requirements</h2>
            <div class="detail-row">
                <span class="detail-label">Number of Tents:</span>
                <span class="detail-value"><?php echo $booking['num_tents'] ?: 'None'; ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Caravan/Campervan:</span>
                <span class="detail-value"><?php echo $booking['has_caravan'] ? 'Yes' : 'No'; ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Needs Tent Provided:</span>
                <span class="detail-value"><?php echo $booking['needs_tent_provided'] ? 'Yes' : 'No'; ?></span>
            </div>
            <?php if (!empty($booking['special_requirements'])): ?>
                <div style="margin-top: 15px;">
                    <strong>Special Requirements:</strong>
                    <p style="margin: 5px 0; color: #666;"><?php echo nl2br(e($booking['special_requirements'])); ?></p>
                </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- Total Amount -->
        <div class="total-amount">
            Total Amount: <?php echo formatCurrency($total_amount); ?>
        </div>

        <!-- Payment Instructions -->
        <div class="section">
            <h2>Payment Information</h2>

            <?php if ($payment_method === 'bank_transfer'): ?>
                <div class="bank-details">
                    <h3 style="margin-top: 0; color: #667eea;">Bank Transfer Details</h3>
                    <div class="detail-row">
                        <span class="detail-label">Bank:</span>
                        <span class="detail-value"><?php echo env('BANK_NAME'); ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Account Number:</span>
                        <span class="detail-value"><?php echo env('BANK_ACCOUNT'); ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Sort Code:</span>
                        <span class="detail-value"><?php echo env('BANK_SORT_CODE'); ?></span>
                    </div>
                    <div class="detail-row" style="border-bottom: none;">
                        <span class="detail-label">Reference:</span>
                        <span class="detail-value" style="font-weight: bold; color: #667eea;"><?php echo e($bank_reference); ?></span>
                    </div>
                    <p style="margin: 15px 0 0 0; font-size: 14px; color: #666;">
                        <strong>Important:</strong> Please use the reference above so we can match your payment.
                    </p>
                </div>

                <?php if ($payment_plan !== 'full'): ?>
                    <div class="payment-info">
                        <strong>Payment Plan:</strong> <?php
                            $plans = [
                                'monthly' => 'Monthly Installments',
                                'three_payments' => '3 Equal Payments'
                            ];
                            echo $plans[$payment_plan] ?? 'Pay in Full';
                        ?>
                        <p style="margin: 10px 0 0 0;">You'll receive reminders before each payment is due.</p>
                    </div>
                <?php endif; ?>

            <?php elseif ($payment_method === 'cash'): ?>
                <div class="payment-info">
                    <strong>Payment Method:</strong> Cash
                    <p style="margin: 10px 0 0 0;">Please hand your payment to Jon at church. He will confirm receipt and mark your booking as paid.</p>
                </div>

            <?php else: ?>
                <div class="payment-info">
                    <strong>Payment Method:</strong> Card (Stripe)
                    <p style="margin: 10px 0 0 0;">
                        <?php if ($payment_plan === 'full'): ?>
                            Your payment has been processed successfully.
                        <?php else: ?>
                            Your card has been saved securely. Payments will be automatically charged according to your payment plan.
                        <?php endif; ?>
                    </p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Contact Info -->
        <div class="section">
            <h2>Your Contact Details</h2>
            <div class="detail-row">
                <span class="detail-label">Email:</span>
                <span class="detail-value"><?php echo e($booking['booker_email']); ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Phone:</span>
                <span class="detail-value"><?php echo e($booking['booker_phone']); ?></span>
            </div>
        </div>

        <div class="footer">
            <p><strong>Questions?</strong> Contact us at <?php echo e(env('SMTP_FROM_EMAIL')); ?></p>
            <p style="margin-top: 15px; color: #999; font-size: 12px;">
                This is an automated email. Please keep this confirmation for your records.
            </p>
        </div>
    </div>
</body>
</html>
