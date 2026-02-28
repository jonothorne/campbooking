<?php
/**
 * Email Testing Tool
 * Test email sending and view email templates
 *
 * DELETE THIS FILE AFTER TESTING
 */

// Load dependencies
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/includes/functions.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Handle AJAX requests FIRST (before any output)
if (isset($_GET['action'])) {
    // Session already started by constants.php

    // Check authentication for AJAX requests
    if (!isset($_SESSION['email_test_auth'])) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'output' => 'Not authenticated']);
        exit;
    }

    header('Content-Type: application/json');

    switch ($_GET['action']) {
        case 'send':
            sendTestEmail($_GET['type'] ?? '', $_GET['email'] ?? '');
            break;
        case 'preview':
            previewEmail($_GET['type'] ?? '');
            break;
        default:
            echo json_encode(['success' => false, 'output' => 'Invalid action']);
    }
    exit;
}

// Session already started by constants.php
$password = 'test123'; // Change this to something secure

// Handle login
if (isset($_POST['password'])) {
    if ($_POST['password'] === $password) {
        $_SESSION['email_test_auth'] = true;
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    } else {
        $loginError = 'Incorrect password';
    }
}

// Show login page if not authenticated
if (!isset($_SESSION['email_test_auth'])) {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Email Test - Authentication</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                background: #1a1a1a;
                color: #f5f5f5;
                display: flex;
                justify-content: center;
                align-items: center;
                height: 100vh;
                margin: 0;
            }
            .auth-box {
                background: #2a2a2a;
                padding: 40px;
                border-radius: 8px;
                box-shadow: 0 4px 20px rgba(0,0,0,0.5);
                max-width: 400px;
                width: 100%;
            }
            h2 { color: #eb008b; margin-top: 0; }
            input {
                padding: 10px;
                width: 100%;
                margin: 10px 0;
                border: 1px solid #444;
                background: #1a1a1a;
                color: #f5f5f5;
                border-radius: 4px;
                box-sizing: border-box;
            }
            button {
                padding: 12px 24px;
                background: #eb008b;
                color: white;
                border: none;
                border-radius: 4px;
                cursor: pointer;
                width: 100%;
                font-size: 16px;
            }
            button:hover { background: #d40080; }
            .error {
                background: #7f1d1d;
                color: #fca5a5;
                padding: 10px;
                border-radius: 4px;
                margin-bottom: 15px;
            }
        </style>
    </head>
    <body>
        <div class="auth-box">
            <h2>🔒 Email Test Authentication</h2>
            <?php if (isset($loginError)): ?>
                <div class="error"><?php echo htmlspecialchars($loginError); ?></div>
            <?php endif; ?>
            <form method="POST">
                <input type="password" name="password" placeholder="Enter password" required autofocus>
                <button type="submit">Login</button>
            </form>
            <p style="font-size: 12px; color: #888; margin-top: 20px;">Default password: test123</p>
        </div>
    </body>
    </html>
    <?php
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Testing Tool</title>
    <style>
        body { font-family: 'Courier New', monospace; background: #1a1a1a; color: #f5f5f5; padding: 40px; max-width: 1200px; margin: 0 auto; }
        h1 { color: #eb008b; border-bottom: 2px solid #eb008b; padding-bottom: 10px; }
        h2 { color: #ff6b9d; margin-top: 30px; }
        .warning-box { background: #7f1d1d; border: 2px solid #dc2626; padding: 20px; margin: 30px 0; border-radius: 8px; text-align: center; }
        .warning-box strong { color: #fca5a5; font-size: 18px; }
        .info-box { background: #1e3a5f; border-left: 4px solid #3b82f6; padding: 15px 20px; margin: 20px 0; border-radius: 4px; }
        .info-box h3 { color: #60a5fa; margin-top: 0; }
        .info-box code { background: #1a1a1a; padding: 2px 6px; border-radius: 3px; color: #4ade80; }
        .test-section { background: #2a2a2a; border-left: 4px solid #eb008b; padding: 20px; margin: 20px 0; border-radius: 4px; }
        .test-button { padding: 12px 24px; background: linear-gradient(135deg, #eb008b 0%, #d40080 100%); color: white; border: none; border-radius: 6px; cursor: pointer; font-size: 16px; font-weight: 600; margin: 10px 10px 10px 0; transition: all 0.3s ease; }
        .test-button:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(235, 0, 139, 0.4); }
        .test-button:disabled { opacity: 0.5; cursor: not-allowed; }
        .preview-button { background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); }
        .output-box { background: #1a1a1a; border: 1px solid #444; padding: 15px; margin: 15px 0; border-radius: 4px; font-family: 'Courier New', monospace; font-size: 13px; max-height: 400px; overflow-y: auto; white-space: pre-wrap; word-wrap: break-word; }
        .preview-box { background: white; border: 1px solid #444; padding: 20px; margin: 15px 0; border-radius: 4px; max-height: 500px; overflow-y: auto; }
        .success { color: #4ade80; }
        .error { color: #f87171; }
        .loading { display: inline-block; width: 16px; height: 16px; border: 3px solid rgba(235, 0, 139, 0.3); border-radius: 50%; border-top-color: #eb008b; animation: spin 1s ease-in-out infinite; }
        @keyframes spin { to { transform: rotate(360deg); } }
        input[type="email"] { padding: 10px; background: #1a1a1a; border: 1px solid #444; color: #f5f5f5; border-radius: 4px; font-size: 14px; width: 300px; margin-right: 10px; }
        .logout-btn { position: absolute; top: 20px; right: 20px; padding: 8px 16px; background: #444; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 14px; text-decoration: none; }
        .logout-btn:hover { background: #555; }
    </style>
</head>
<body>
    <a href="?logout=1" class="logout-btn">Logout</a>
    <h1>📧 Email Testing Tool</h1>

    <div class="warning-box">
        <strong>⚠️ SECURITY WARNING ⚠️</strong><br>
        This is a testing tool. DELETE THIS FILE after testing your emails!
    </div>

    <div class="info-box">
        <h3>📋 Email Authentication Status</h3>
        <p>After adding SPF and DMARC DNS records, test sending an email below to verify it doesn't go to spam.</p>
        <p><strong>DNS propagation can take 15-30 minutes.</strong></p>
    </div>

    <h2>1. Test Email Sending</h2>

    <div class="test-section">
        <h3>Booking Confirmation Email</h3>
        <p>Test the email sent when a booking is completed.</p>
        <input type="email" id="email-booking-confirmation" placeholder="your.email@example.com">
        <button class="test-button" onclick="sendEmail('booking-confirmation')">Send Test Email</button>
        <button class="preview-button test-button" onclick="previewEmail('booking-confirmation')">Preview</button>
        <div id="output-booking-confirmation" class="output-box" style="display: none;"></div>
        <div id="preview-booking-confirmation" class="preview-box" style="display: none;"></div>
    </div>

    <div class="test-section">
        <h3>Payment Receipt Email</h3>
        <p>Test the email sent when a payment is received.</p>
        <input type="email" id="email-payment-receipt" placeholder="your.email@example.com">
        <button class="test-button" onclick="sendEmail('payment-receipt')">Send Test Email</button>
        <button class="preview-button test-button" onclick="previewEmail('payment-receipt')">Preview</button>
        <div id="output-payment-receipt" class="output-box" style="display: none;"></div>
        <div id="preview-payment-receipt" class="preview-box" style="display: none;"></div>
    </div>

    <div class="test-section">
        <h3>Payment Reminder Email</h3>
        <p>Test the reminder email sent 3 days before a payment is due.</p>
        <input type="email" id="email-payment-reminder" placeholder="your.email@example.com">
        <button class="test-button" onclick="sendEmail('payment-reminder')">Send Test Email</button>
        <button class="preview-button test-button" onclick="previewEmail('payment-reminder')">Preview</button>
        <div id="output-payment-reminder" class="output-box" style="display: none;"></div>
        <div id="preview-payment-reminder" class="preview-box" style="display: none;"></div>
    </div>

    <div class="test-section">
        <h3>Payment Failed Email</h3>
        <p>Test the email sent when a payment fails.</p>
        <input type="email" id="email-payment-failed" placeholder="your.email@example.com">
        <button class="test-button" onclick="sendEmail('payment-failed')">Send Test Email</button>
        <button class="preview-button test-button" onclick="previewEmail('payment-failed')">Preview</button>
        <div id="output-payment-failed" class="output-box" style="display: none;"></div>
        <div id="preview-payment-failed" class="preview-box" style="display: none;"></div>
    </div>

    <h2>2. DNS Records Reference</h2>

    <div class="info-box">
        <h3>🔐 DNS Records (GoDaddy)</h3>
        
        <h4 style="margin-top: 20px;">✅ SPF Record:</h4>
        <div style="background: #1a1a1a; padding: 15px; border-radius: 4px; margin: 10px 0;">
            <strong>Type:</strong> TXT<br>
            <strong>Name:</strong> @<br>
            <strong>Value:</strong> <code>v=spf1 a mx include:secureserver.net ~all</code><br>
            <strong>TTL:</strong> 3600
        </div>

        <h4 style="margin-top: 20px;">✅ DMARC Record:</h4>
        <div style="background: #1a1a1a; padding: 15px; border-radius: 4px; margin: 10px 0;">
            <strong>Type:</strong> TXT<br>
            <strong>Name:</strong> _dmarc<br>
            <strong>Value:</strong> <code>v=DMARC1; p=none; rua=mailto:office@alive.me.uk</code><br>
            <strong>TTL:</strong> 3600
        </div>

        <p style="margin-top: 20px;"><strong>📧 DKIM:</strong> Check cPanel → Email → Email Deliverability, or contact GoDaddy support to enable DKIM signing.</p>
    </div>

    <div class="warning-box" style="margin-top: 40px;">
        <strong>🗑️ DELETE THIS FILE AFTER TESTING</strong><br>
        File: test-email.php
    </div>

    <script>
        async function sendEmail(type) {
            const outputDiv = document.getElementById('output-' + type);
            const emailInput = document.getElementById('email-' + type);
            const button = event.target;
            const email = emailInput.value.trim();
            if (!email) { alert('Please enter an email address'); return; }
            outputDiv.style.display = 'block';
            outputDiv.innerHTML = '<span class="loading"></span> Sending email to ' + email + '...';
            button.disabled = true;
            try {
                const response = await fetch('?action=send&type=' + encodeURIComponent(type) + '&email=' + encodeURIComponent(email));
                const text = await response.text();
                try {
                    const data = JSON.parse(text);
                    if (data.success) {
                        outputDiv.innerHTML = '<span class="success">✓ SUCCESS</span>\n\n' + escapeHtml(data.output);
                    } else {
                        outputDiv.innerHTML = '<span class="error">✗ ERROR</span>\n\n' + escapeHtml(data.output);
                    }
                } catch (parseError) {
                    outputDiv.innerHTML = '<span class="error">✗ FAILED - Invalid JSON</span>\n\n' + escapeHtml(text);
                }
            } catch (error) {
                outputDiv.innerHTML = '<span class="error">✗ FAILED</span>\n\n' + escapeHtml(error.message);
            } finally {
                button.disabled = false;
            }
        }

        async function previewEmail(type) {
            const previewDiv = document.getElementById('preview-' + type);
            const button = event.target;
            previewDiv.style.display = 'block';
            previewDiv.innerHTML = '<div style="color: #666;"><span class="loading"></span> Loading preview...</div>';
            button.disabled = true;
            try {
                const response = await fetch('?action=preview&type=' + encodeURIComponent(type));
                const data = await response.json();
                if (data.success) {
                    previewDiv.innerHTML = data.html;
                } else {
                    previewDiv.innerHTML = '<div style="color: #f87171;">✗ ERROR: ' + escapeHtml(data.output) + '</div>';
                }
            } catch (error) {
                previewDiv.innerHTML = '<div style="color: #f87171;">✗ FAILED: ' + escapeHtml(error.message) + '</div>';
            } finally {
                button.disabled = false;
            }
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    </script>
</body>
</html>

<?php
if (isset($_GET['logout'])) { session_destroy(); header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?')); exit; }

function sendTestEmail($type, $email) {
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'output' => 'Invalid email address']);
        return;
    }
    try {
        $testData = getTestEmailData($type);
        if (!$testData) {
            echo json_encode(['success' => false, 'output' => 'Invalid email type']);
            return;
        }

        // Create PHPMailer instance
        $mail = new PHPMailer(true);

        // SMTP configuration
        $smtpHost = env('SMTP_HOST');
        $smtpPort = env('SMTP_PORT', 587);
        $smtpAuthRequired = env('SMTP_AUTH_REQUIRED', 'true') === 'true';

        $mail->isSMTP();
        $mail->Host = $smtpHost;
        $mail->Port = $smtpPort;

        if ($smtpAuthRequired) {
            $mail->SMTPAuth = true;
            $mail->Username = env('SMTP_USER');
            $mail->Password = env('SMTP_PASS');

            if ($smtpHost !== 'localhost' && $smtpHost !== '127.0.0.1') {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            }
        } else {
            $mail->SMTPAuth = false;
            $mail->SMTPAutoTLS = false;
        }

        // Email settings
        $mail->setFrom(
            env('SMTP_FROM_EMAIL', 'noreply@example.com'),
            env('SMTP_FROM_NAME', 'Alive Church Camp')
        );
        $mail->addAddress($email);
        $mail->CharSet = 'UTF-8';
        $mail->isHTML(true);
        $mail->Subject = $testData['subject'];
        $mail->Body = $testData['body'];

        // Send
        if ($mail->send()) {
            $output = "✓ Email sent successfully to: {$email}\n\n";
            $output .= "Subject: {$testData['subject']}\n\n";
            $output .= "Check your inbox (and spam folder).\n\n";
            $output .= "If it's in spam, wait 15-30 min for DNS to propagate after adding SPF/DMARC records.";
            echo json_encode(['success' => true, 'output' => $output]);
        } else {
            echo json_encode(['success' => false, 'output' => 'Failed to send email.']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'output' => 'Exception: ' . $e->getMessage()]);
    }
}

function previewEmail($type) {
    try {
        require_once __DIR__ . '/config/constants.php';
        require_once __DIR__ . '/includes/functions.php';
        $testData = getTestEmailData($type);
        if (!$testData) {
            echo json_encode(['success' => false, 'output' => 'Invalid email type']);
            return;
        }
        echo json_encode(['success' => true, 'html' => $testData['body']]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'output' => 'Exception: ' . $e->getMessage()]);
    }
}

function getTestEmailData($type) {
    $data = [
        'booking-confirmation' => ['subject' => 'Booking Confirmation - ' . EVENT_NAME, 'body' => getBookingConfirmationEmail()],
        'payment-receipt' => ['subject' => 'Payment Receipt - ' . EVENT_NAME, 'body' => getPaymentReceiptEmail()],
        'payment-reminder' => ['subject' => 'Payment Reminder - ' . EVENT_NAME, 'body' => getPaymentReminderEmail()],
        'payment-failed' => ['subject' => 'Payment Failed - ' . EVENT_NAME, 'body' => getPaymentFailedEmail()]
    ];
    return $data[$type] ?? null;
}

function getBookingConfirmationEmail() {
    require_once __DIR__ . '/config/constants.php';
    require_once __DIR__ . '/includes/functions.php';
    ob_start();

    // Extract variables for template
    $booking_reference = 'CAMP-TEST-1234';
    $booker_name = 'Test User';
    $booker_email = 'test@example.com';
    $total_amount = 250.00;
    $payment_method = 'stripe';
    $payment_plan = 'three_payments';
    $attendees = [
        ['name' => 'John Doe', 'age' => 30, 'ticket_type' => 'adult_weekend', 'ticket_price' => 85.00],
        ['name' => 'Jane Doe', 'age' => 28, 'ticket_type' => 'adult_weekend', 'ticket_price' => 85.00],
        ['name' => 'Jimmy Doe', 'age' => 12, 'ticket_type' => 'child_weekend', 'ticket_price' => 55.00],
        ['name' => 'Jenny Doe', 'age' => 3, 'ticket_type' => 'free_child', 'ticket_price' => 0.00]
    ];
    $booking = [
        'num_tents' => 2,
        'has_caravan' => 0,
        'needs_tent_provided' => 0,
        'special_requirements' => 'Vegetarian meals for Jane Doe',
        'booker_email' => 'test@example.com',
        'booker_phone' => '07700 900123'
    ];

    include __DIR__ . '/templates/emails/booking-confirmation.php';
    return ob_get_clean();
}

function getPaymentReceiptEmail() {
    require_once __DIR__ . '/config/constants.php';
    require_once __DIR__ . '/includes/functions.php';
    ob_start();

    // Extract variables for template
    $booking_reference = 'CAMP-TEST-1234';
    $booker_name = 'Test User';
    $payment_amount = 83.33;
    $payment_date = date('Y-m-d H:i:s');
    $payment = [
        'payment_method' => 'stripe',
        'transaction_id' => 'pi_test_1234567890'
    ];
    $booking = [
        'total_amount' => 250.00,
        'payment_plan' => 'three_payments'
    ];
    $amount_paid = 166.66; // 2 payments of 83.33
    $amount_outstanding = 83.34; // 1 payment remaining

    include __DIR__ . '/templates/emails/payment-receipt.php';
    return ob_get_clean();
}

function getPaymentReminderEmail() {
    require_once __DIR__ . '/config/constants.php';
    require_once __DIR__ . '/includes/functions.php';
    ob_start();

    // Extract variables for template
    $booking_reference = 'CAMP-TEST-1234';
    $booker_name = 'Test User';
    $payment_amount = 83.33;
    $due_date = date('Y-m-d', strtotime('+3 days'));
    $installment_number = 2;
    $days_until_due = 3;
    $booking = [
        'payment_method' => 'stripe',
        'payment_plan' => 'three_payments',
        'total_amount' => 250.00,
        'amount_paid' => 83.33,
        'amount_outstanding' => 166.67
    ];

    include __DIR__ . '/templates/emails/payment-reminder.php';
    return ob_get_clean();
}

function getPaymentFailedEmail() {
    require_once __DIR__ . '/config/constants.php';
    require_once __DIR__ . '/includes/functions.php';
    ob_start();

    // Extract variables for template
    $booking_reference = 'CAMP-TEST-1234';
    $booker_name = 'Test User';
    $payment_amount = 83.33;
    $due_date = date('Y-m-d');
    $installment_number = 2;
    $retry_date = date('Y-m-d', strtotime('+2 days'));
    $next_retry_date = date('Y-m-d', strtotime('+2 days'));
    $attempt_count = 1;
    $booking = [
        'payment_method' => 'stripe',
        'payment_plan' => 'three_payments',
        'total_amount' => 250.00,
        'amount_paid' => 83.33,
        'amount_outstanding' => 166.67
    ];

    include __DIR__ . '/templates/emails/payment-failed.php';
    return ob_get_clean();
}
?>
