<?php
/**
 * Customer Data Deletion Request (GDPR Right to be Forgotten)
 */

// Initialize
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/customer-auth.php';
require_once __DIR__ . '/../classes/Booking.php';

// Require authentication
requireCustomerAuth();

$customerId = currentCustomerId();
$error = null;
$success = false;

// Load booking data
try {
    $booking = new Booking($customerId);
    $bookingData = $booking->getData();
} catch (Exception $e) {
    customerLogout();
    redirect(url('portal/login.php'));
}

// Check if already requested
if ($bookingData['data_deletion_requested']) {
    $_SESSION['error'] = 'You have already requested data deletion. We will process your request shortly.';
    redirect(url('portal/dashboard.php'));
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    requireCustomerCsrfToken();

    $reason = $_POST['reason'] ?? '';
    $confirmation = $_POST['confirm'] ?? '';

    if ($confirmation !== 'DELETE') {
        $error = 'Please type DELETE to confirm.';
    } else {
        // Submit deletion request
        if (requestDataDeletion($customerId, $reason)) {
            $_SESSION['success'] = 'Your data deletion request has been submitted. We will process it within 30 days as required by GDPR.';
            redirect(url('portal/dashboard.php'));
        } else {
            $error = 'Failed to submit deletion request. Please try again or contact us.';
        }
    }
}

$csrfToken = generateCustomerCsrfToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Request Data Deletion - <?php echo e(EVENT_NAME); ?></title>
    <link rel="stylesheet" href="<?php echo basePath('public/assets/css/main.css'); ?>">
    <style>
        body {
            background: linear-gradient(135deg, #eb008b 0%, #d40080 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .delete-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            padding: 40px;
            width: 100%;
            max-width: 600px;
        }

        .logo {
            text-align: center;
            margin-bottom: 30px;
        }

        .logo img {
            max-width: 200px;
            height: auto;
            margin-bottom: 20px;
        }

        h1 {
            color: #c33;
            font-size: 24px;
            margin-bottom: 10px;
            text-align: center;
        }

        .subtitle {
            color: #666;
            font-size: 14px;
            margin-bottom: 30px;
            text-align: center;
        }

        .warning-box {
            background: #fff3cd;
            border: 2px solid #ff9800;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 25px;
        }

        .warning-box h2 {
            color: #ff9800;
            font-size: 18px;
            margin: 0 0 10px 0;
        }

        .warning-box ul {
            margin: 10px 0;
            padding-left: 20px;
        }

        .warning-box li {
            color: #666;
            font-size: 14px;
            margin: 5px 0;
        }

        .alert-danger {
            background-color: #fee;
            color: #c33;
            border: 1px solid #fcc;
            padding: 12px 16px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
            font-size: 14px;
        }

        textarea {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
            font-family: inherit;
            min-height: 100px;
            resize: vertical;
        }

        input[type="text"] {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
            transition: border-color 0.3s;
        }

        input:focus, textarea:focus {
            outline: none;
            border-color: #eb008b;
        }

        .help-text {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }

        .btn {
            width: 100%;
            padding: 12px 16px;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .btn-danger {
            background: #dc3545;
            color: white;
        }

        .btn-danger:hover {
            background: #c82333;
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(220, 53, 69, 0.4);
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
            margin-top: 10px;
        }

        .btn-secondary:hover {
            background: #5a6268;
        }

        .back-link {
            text-align: center;
            margin-top: 20px;
        }

        .back-link a {
            color: #eb008b;
            text-decoration: none;
            font-size: 14px;
        }

        .back-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="delete-container">
        <div class="logo">
            <img src="<?php echo basePath('public/assets/images/echo-logo.png'); ?>" alt="ECHO2026">
            <h1>⚠️ Request Data Deletion</h1>
            <p class="subtitle">GDPR Right to be Forgotten</p>
        </div>

        <?php if ($error): ?>
            <div class="alert-danger">
                <?php echo e($error); ?>
            </div>
        <?php endif; ?>

        <div class="warning-box">
            <h2>⚠️ Warning: This Action Cannot be Undone</h2>
            <p style="color: #666; font-size: 14px; margin: 10px 0;">
                Requesting data deletion will permanently remove:
            </p>
            <ul>
                <li>Your booking information</li>
                <li>All attendee details</li>
                <li>Payment history and records</li>
                <li>Email communications</li>
                <li>Your portal access</li>
            </ul>
            <p style="color: #666; font-size: 14px; margin: 10px 0;">
                <strong>Important:</strong> This will cancel your booking for ECHO2026. If you still wish to attend the event, you will need to create a new booking.
            </p>
            <p style="color: #666; font-size: 14px; margin: 10px 0;">
                We will process your request within 30 days as required by GDPR regulations.
            </p>
        </div>

        <form method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?php echo e($csrfToken); ?>">

            <div class="form-group">
                <label for="reason">Reason for Deletion (Optional)</label>
                <textarea
                    id="reason"
                    name="reason"
                    placeholder="Please let us know why you're requesting deletion (optional)"
                ><?php echo isset($_POST['reason']) ? e($_POST['reason']) : ''; ?></textarea>
                <div class="help-text">
                    This helps us improve our service
                </div>
            </div>

            <div class="form-group">
                <label for="confirm">Type "DELETE" to Confirm</label>
                <input
                    type="text"
                    id="confirm"
                    name="confirm"
                    required
                    autocomplete="off"
                    placeholder="DELETE"
                >
                <div class="help-text">
                    You must type DELETE (in capital letters) to confirm this request
                </div>
            </div>

            <button type="submit" class="btn btn-danger">
                🗑️ Submit Deletion Request
            </button>

            <a href="<?php echo url('portal/dashboard.php'); ?>" class="btn btn-secondary">
                Cancel - Go Back to Dashboard
            </a>
        </form>

        <div class="back-link">
            <p style="margin-top: 20px; color: #666; font-size: 13px;">
                Need help? Contact us at <a href="mailto:<?php echo e(env('SMTP_FROM_EMAIL')); ?>"><?php echo e(env('SMTP_FROM_EMAIL')); ?></a>
            </p>
        </div>
    </div>
</body>
</html>
