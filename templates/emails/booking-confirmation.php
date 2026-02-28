<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            line-height: 1.6;
            color: #1f2937;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #1a1a1a;
        }
        .email-container {
            background: white;
            border-radius: 12px;
            padding: 0;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.3);
            overflow: hidden;
        }
        .header {
            background: url('<?php echo url('public/assets/images/poster-background-echo2026.png'); ?>') center center / cover no-repeat;
            text-align: center;
            padding: 60px 30px;
            color: white;
            position: relative;
        }
        .header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(to bottom, rgba(0,0,0,0.3), rgba(0,0,0,0.6));
            z-index: 1;
        }
        .header > * {
            position: relative;
            z-index: 2;
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
            <img src="<?php echo url('public/assets/images/echo-logo.png'); ?>" alt="ECHO2026" style="width: 280px; height: auto; margin: 0 auto 20px; display: block; filter: brightness(0) invert(1);">
            <p style="color: rgba(255,255,255,0.95); margin: 8px 0 0 0; font-size: 15px; letter-spacing: 1px;">May 29-31, 2026 • Sizewell Hall, Suffolk</p>
            <p style="color: rgba(255,255,255,0.9); margin: 20px 0 0 0; font-size: 14px; font-style: italic; border-top: 1px solid rgba(255,255,255,0.3); padding-top: 15px;">"The Spirit and the bride say, 'Come!'" - Revelation 22:17</p>
        </div>

        <div style="padding: 35px 30px;">
        <p><strong>Dear <?php echo e($booker_name); ?>,</strong></p>

        <p>Thank you for answering the call! We're excited to confirm your registration for ECHO2026.</p>

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

            <?php elseif ($payment_method === 'cash'): ?>
                <div class="payment-info">
                    <strong>Payment Method:</strong> Cash
                    <p style="margin: 10px 0 0 0;">Please hand your payment<?php echo $payment_plan !== 'full' ? 's' : ''; ?> to Jon at church. He will confirm receipt and mark your booking as paid.</p>
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

            <!-- Payment Schedule (for installment plans) -->
            <?php if ($payment_plan !== 'full' && !empty($payment_schedule)): ?>
                <div class="section" style="margin-top: 20px;">
                    <h2>Payment Schedule</h2>
                    <div style="background: #f0f9ff; border: 2px solid #eb008b; padding: 20px; border-radius: 8px;">
                        <p style="margin: 0 0 15px 0; color: #666;">
                            <?php
                                $planNames = [
                                    'monthly' => 'Monthly Installments',
                                    'three_payments' => '3 Equal Payments'
                                ];
                                echo '<strong>' . ($planNames[$payment_plan] ?? 'Installment Plan') . '</strong>';
                            ?>
                        </p>

                        <?php foreach ($payment_schedule as $schedule): ?>
                            <div class="detail-row" style="border-bottom: 1px solid #ddd; padding: 12px 0;">
                                <span class="detail-label">
                                    Payment <?php echo $schedule['installment_number']; ?>
                                    <span style="font-weight: normal; color: #666; font-size: 13px;">
                                        (Due: <?php echo formatDate($schedule['due_date'], 'd M Y'); ?>)
                                    </span>
                                </span>
                                <span class="detail-value" style="font-weight: bold; color: #eb008b;">
                                    <?php echo formatCurrency($schedule['amount']); ?>
                                </span>
                            </div>
                        <?php endforeach; ?>

                        <?php if ($payment_method === 'stripe'): ?>
                            <p style="margin: 15px 0 0 0; font-size: 13px; color: #666;">
                                ℹ️ Payments will be automatically charged to your saved card on the due dates above.
                            </p>
                        <?php elseif ($payment_method === 'bank_transfer'): ?>
                            <p style="margin: 15px 0 0 0; font-size: 13px; color: #666;">
                                ℹ️ Please make each transfer by the due date using the bank details above with reference: <strong><?php echo e($bank_reference); ?></strong>
                            </p>
                        <?php else: ?>
                            <p style="margin: 15px 0 0 0; font-size: 13px; color: #666;">
                                ℹ️ Please hand each payment to Jon at church by the due dates above.
                            </p>
                        <?php endif; ?>
                    </div>
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
        </div>

        <div class="footer" style="background: #1a1a1a; color: #9ca3af; padding: 30px; text-align: center; font-size: 13px;">
            <p style="margin: 0;"><strong style="color: #e5e7eb;">ECHO2026</strong> - Respond to the Call</p>
            <p style="margin: 10px 0;">Questions? Contact us at <a href="mailto:<?php echo e(env('SMTP_FROM_EMAIL')); ?>" style="color: #eb008b; text-decoration: none;"><?php echo e(env('SMTP_FROM_EMAIL')); ?></a></p>
            <p style="margin-top: 20px; font-size: 12px; color: #6b7280;">
                This is an automated email. Please keep this for your records.<br>
                Sizewell Hall, Sizewell, Leiston, Suffolk, IP16 4TX
            </p>
        </div>
    </div>
</body>
</html>
