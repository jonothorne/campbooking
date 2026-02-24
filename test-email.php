<?php
/**
 * Email Configuration Test Script
 * Tests SMTP settings and sends a test email
 */

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/config/constants.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

echo "Email Configuration Test\n";
echo "========================\n\n";

$authRequired = env('SMTP_AUTH_REQUIRED', 'true') === 'true';

// Display current settings (hide password)
echo "SMTP Settings:\n";
echo "  Host: " . SMTP_HOST . "\n";
echo "  Port: " . SMTP_PORT . "\n";
echo "  Authentication: " . ($authRequired ? 'Yes' : 'No (GoDaddy relay)') . "\n";

if ($authRequired) {
    echo "  Username: " . SMTP_USER . "\n";
    echo "  Password: " . (SMTP_PASS === 'your_app_password_here' || empty(SMTP_PASS) ? '⚠️  NOT CONFIGURED!' : '✓ Set (hidden)') . "\n";
}

echo "  From Email: " . SMTP_FROM_EMAIL . "\n";
echo "  From Name: " . SMTP_FROM_NAME . "\n\n";

// Check if password is configured (only if auth required)
if ($authRequired && (SMTP_PASS === 'your_app_password_here' || empty(SMTP_PASS))) {
    echo "❌ ERROR: SMTP password not configured!\n\n";
    echo "To fix this:\n";
    echo "1. If using Gmail:\n";
    echo "   - Go to https://myaccount.google.com/apppasswords\n";
    echo "   - Enable 2-Step Verification if not already enabled\n";
    echo "   - Generate an 'App Password' for 'Mail'\n";
    echo "   - Copy the 16-character password\n\n";
    echo "2. Update your .env file:\n";
    echo "   SMTP_PASS=your_generated_app_password\n\n";
    echo "3. Run this script again to test\n";
    exit(1);
}

// Ask for test recipient
echo "Enter email address to send test email to: ";
$testRecipient = trim(fgets(STDIN));

if (!filter_var($testRecipient, FILTER_VALIDATE_EMAIL)) {
    echo "❌ Invalid email address\n";
    exit(1);
}

echo "\nSending test email to: {$testRecipient}\n";
echo "Please wait...\n\n";

// Create PHPMailer instance
$mail = new PHPMailer(true);

try {
    $authRequired = env('SMTP_AUTH_REQUIRED', 'true') === 'true';
    $smtpHost = SMTP_HOST;

    // Server settings
    $mail->isSMTP();
    $mail->Host = $smtpHost;
    $mail->Port = SMTP_PORT;

    // Authentication (optional for GoDaddy relay)
    if ($authRequired) {
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USER;
        $mail->Password = SMTP_PASS;

        // Only use encryption if not localhost
        if ($smtpHost !== 'localhost' && $smtpHost !== '127.0.0.1') {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        }
    } else {
        $mail->SMTPAuth = false;
        $mail->SMTPAutoTLS = false;
    }

    // Enable verbose debug output
    $mail->SMTPDebug = 2;

    // Recipients
    $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
    $mail->addAddress($testRecipient);

    // Content
    $mail->isHTML(true);
    $mail->Subject = 'Test Email - Camp Booking System';
    $mail->Body = '
    <!DOCTYPE html>
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; border-radius: 8px; text-align: center; }
            .content { padding: 20px; background: #f9f9f9; border-radius: 8px; margin-top: 20px; }
            .success { color: #28a745; font-weight: bold; }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h1>✅ Email Test Successful!</h1>
            </div>
            <div class="content">
                <p class="success">Your SMTP configuration is working correctly!</p>
                <p>This test email was sent from your Camp Booking System.</p>
                <h3>Configuration Details:</h3>
                <ul>
                    <li><strong>SMTP Host:</strong> ' . SMTP_HOST . '</li>
                    <li><strong>SMTP Port:</strong> ' . SMTP_PORT . '</li>
                    <li><strong>From Email:</strong> ' . SMTP_FROM_EMAIL . '</li>
                    <li><strong>Test Time:</strong> ' . date('Y-m-d H:i:s') . '</li>
                </ul>
                <p>Your booking confirmation emails will now be sent successfully! 🎉</p>
            </div>
        </div>
    </body>
    </html>
    ';

    $mail->AltBody = 'Email Test Successful! Your SMTP configuration is working correctly.';

    $mail->send();

    echo "\n✅ SUCCESS! Test email sent successfully!\n";
    echo "Check {$testRecipient} for the test email.\n";

} catch (Exception $e) {
    echo "\n❌ ERROR: Email could not be sent.\n";
    echo "Error: {$mail->ErrorInfo}\n\n";

    echo "Common issues:\n";
    echo "1. Gmail App Password not created or incorrect\n";
    echo "2. 2-Step Verification not enabled on Gmail account\n";
    echo "3. SMTP blocked by firewall\n";
    echo "4. Incorrect SMTP credentials\n";
    exit(1);
}
