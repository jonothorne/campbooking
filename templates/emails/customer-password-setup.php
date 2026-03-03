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
        .section {
            margin: 25px 0;
        }
        .info-box {
            background: #f0f9ff;
            border-left: 4px solid #eb008b;
            padding: 20px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .info-box h3 {
            margin: 0 0 10px 0;
            color: #eb008b;
            font-size: 18px;
        }
        .button {
            display: inline-block;
            background: linear-gradient(135deg, #eb008b 0%, #d40080 100%);
            color: white;
            padding: 15px 40px;
            text-decoration: none;
            border-radius: 8px;
            margin: 20px 0;
            font-weight: 600;
            font-size: 16px;
            text-align: center;
        }
        .button:hover {
            background: linear-gradient(135deg, #d40080 0%, #c00070 100%);
        }
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 2px solid #f0f0f0;
            text-align: center;
            color: #666;
            font-size: 14px;
        }
        .expiry-notice {
            background: #fff3cd;
            border: 2px solid #ffc107;
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
            font-size: 14px;
            color: #856404;
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

            <p>We're excited to give you access to your ECHO2026 customer portal! This secure portal allows you to:</p>

            <ul style="color: #666; line-height: 1.8;">
                <li>View your booking details and attendees</li>
                <li>Track your payment history and schedule</li>
                <li>Download your booking data</li>
                <li>Manage your privacy preferences</li>
            </ul>

            <div class="info-box">
                <h3>🔐 Set Up Your Password</h3>
                <p style="margin: 0 0 10px 0; color: #666;">
                    Click the button below to create your secure password and access your portal:
                </p>
            </div>

            <div style="text-align: center; margin: 30px 0;">
                <a href="<?php echo e($setup_link); ?>" class="button">
                    Set Up My Password →
                </a>
            </div>

            <div class="expiry-notice">
                ⏱️ <strong>Important:</strong> This link will expire in 7 days. If it expires, please contact us for a new link.
            </div>

            <div style="background: #f9f9f9; padding: 20px; border-radius: 8px; margin: 25px 0;">
                <h3 style="margin: 0 0 10px 0; color: #333; font-size: 16px;">Your Booking Reference</h3>
                <p style="margin: 0; font-size: 20px; font-weight: bold; color: #eb008b;">
                    <?php echo e($booking_reference); ?>
                </p>
            </div>

            <div class="section">
                <h3 style="color: #333; font-size: 16px; margin: 0 0 10px 0;">Need Help?</h3>
                <p style="color: #666; margin: 0;">
                    If you have any questions or didn't request this access, please contact us at
                    <a href="mailto:<?php echo e(env('SMTP_FROM_EMAIL')); ?>" style="color: #eb008b; text-decoration: none;">
                        <?php echo e(env('SMTP_FROM_EMAIL')); ?>
                    </a>
                </p>
            </div>

            <div style="margin-top: 30px; padding: 20px; background: #f0f9ff; border-radius: 8px;">
                <p style="margin: 0; color: #666; font-size: 14px;">
                    <strong style="color: #333;">Privacy & Security:</strong> We take your data seriously. Your portal is protected with secure authentication, and you have full control over your personal information in compliance with GDPR regulations.
                </p>
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
