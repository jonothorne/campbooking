<?php
/**
 * Customer Password Setup
 * One-time link for setting up customer portal password
 */

// Initialize
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/customer-auth.php';

$error = null;
$success = false;
$tokenData = null;

// Get token from URL
$token = $_GET['token'] ?? '';

if (empty($token)) {
    $error = 'Invalid or missing setup link.';
} else {
    // Verify token
    $tokenData = verifyPasswordSetupToken($token);

    if (!$tokenData) {
        $error = 'This setup link is invalid or has expired. Please contact us for a new link.';
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $tokenData) {
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if (empty($password) || empty($confirmPassword)) {
        $error = 'Please enter and confirm your password.';
    } elseif ($password !== $confirmPassword) {
        $error = 'Passwords do not match.';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters long.';
    } else {
        // Set password
        if (setBookingPassword($tokenData['booking_id'], $password)) {
            // Mark token as used
            markTokenAsUsed($tokenData['token_id']);

            // Auto-login the customer
            $result = customerLogin($tokenData['booker_email'], $password);

            if ($result['success']) {
                // Log GDPR action
                logGDPRAction($tokenData['booking_id'], 'privacy_update', 'Customer set up portal password');

                // Redirect to dashboard
                redirect(url('portal/dashboard.php?welcome=1'));
            } else {
                $success = true;
            }
        } else {
            $error = 'Failed to set password. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Set Up Password - <?php echo e(EVENT_NAME); ?></title>
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

        .setup-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            padding: 40px;
            width: 100%;
            max-width: 450px;
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
            color: #333;
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

        .welcome-box {
            background: #f0f9ff;
            border: 2px solid #eb008b;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 25px;
        }

        .welcome-box h2 {
            color: #eb008b;
            font-size: 18px;
            margin: 0 0 10px 0;
        }

        .welcome-box p {
            color: #666;
            font-size: 14px;
            margin: 0;
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

        .alert-success {
            background-color: #efe;
            color: #3c3;
            border: 1px solid #cfc;
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

        input[type="password"] {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
            transition: border-color 0.3s;
        }

        input:focus {
            outline: none;
            border-color: #eb008b;
        }

        .password-requirements {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }

        .btn {
            width: 100%;
            padding: 12px 16px;
            background: linear-gradient(135deg, #eb008b 0%, #d40080 100%);
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(235, 0, 139, 0.4);
        }

        .btn-secondary {
            background: #6c757d;
            box-shadow: none;
        }

        .btn-secondary:hover {
            background: #5a6268;
        }
    </style>
</head>
<body>
    <div class="setup-container">
        <div class="logo">
            <img src="<?php echo basePath('public/assets/images/echo-logo.png'); ?>" alt="ECHO2026">
            <h1>Set Up Your Password</h1>
            <p class="subtitle">Create a password to access your booking</p>
        </div>

        <?php if ($error): ?>
            <div class="alert-danger">
                <?php echo e($error); ?>
            </div>
            <?php if (strpos($error, 'expired') !== false): ?>
                <p style="text-align: center; margin-bottom: 20px;">
                    <a href="mailto:<?php echo e(env('SMTP_FROM_EMAIL')); ?>" class="btn btn-secondary">Contact Support</a>
                </p>
            <?php endif; ?>
        <?php elseif ($success): ?>
            <div class="alert-success">
                Password set successfully! You can now login to your portal.
            </div>
            <p style="text-align: center; margin-top: 20px;">
                <a href="<?php echo url('portal/login.php'); ?>" class="btn">Go to Login</a>
            </p>
        <?php elseif ($tokenData): ?>
            <div class="welcome-box">
                <h2>Welcome, <?php echo e($tokenData['booker_name']); ?>!</h2>
                <p>You're setting up access to your ECHO2026 booking. Create a secure password below to continue.</p>
            </div>

            <form method="POST" action="">
                <div class="form-group">
                    <label for="password">New Password</label>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        required
                        minlength="8"
                        autofocus
                    >
                    <div class="password-requirements">
                        Must be at least 8 characters long
                    </div>
                </div>

                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
                    <input
                        type="password"
                        id="confirm_password"
                        name="confirm_password"
                        required
                        minlength="8"
                    >
                </div>

                <button type="submit" class="btn">Set Password & Continue</button>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>
