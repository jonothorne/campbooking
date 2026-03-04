<?php
/**
 * Customer Portal - Edit Booking
 * Allow customers to update their booking information
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
$success = null;

// Load booking
try {
    $booking = new Booking($customerId);
    $bookingData = $booking->getData();
} catch (Exception $e) {
    customerLogout();
    redirect(url('portal/login.php'));
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCustomerCsrfToken();

    $bookerName = trim($_POST['booker_name'] ?? '');
    $bookerEmail = trim($_POST['booker_email'] ?? '');
    $bookerPhone = trim($_POST['booker_phone'] ?? '');
    $specialRequirements = trim($_POST['special_requirements'] ?? '');
    $numTents = (int)($_POST['num_tents'] ?? 0);
    $hasCaravan = isset($_POST['has_caravan']) ? 1 : 0;
    $needsTentProvided = isset($_POST['needs_tent_provided']) ? 1 : 0;

    if (empty($bookerName) || empty($bookerEmail) || empty($bookerPhone)) {
        $error = 'Please fill in all required fields.';
    } elseif (!filter_var($bookerEmail, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        // Update booking
        $db = Database::getInstance();
        $updated = $db->execute(
            "UPDATE bookings SET
                booker_name = ?,
                booker_email = ?,
                booker_phone = ?,
                special_requirements = ?,
                num_tents = ?,
                has_caravan = ?,
                needs_tent_provided = ?
            WHERE id = ?",
            [$bookerName, $bookerEmail, $bookerPhone, $specialRequirements, $numTents, $hasCaravan, $needsTentProvided, $customerId]
        );

        if ($updated) {
            logGDPRAction($customerId, 'privacy_update', 'Customer updated booking details');
            $_SESSION['success'] = 'Your booking details have been updated successfully!';
            redirect(url('portal/dashboard.php'));
        } else {
            $error = 'Failed to update booking. Please try again.';
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
    <title>Edit Booking - <?php echo e(EVENT_NAME); ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            background: #f9fafb;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            line-height: 1.6;
            color: #1f2937;
        }
        .portal-header {
            background: linear-gradient(135deg, #eb008b 0%, #d40080 100%);
            color: white;
            padding: 30px 20px;
            margin-bottom: 30px;
        }
        .portal-header-content {
            max-width: 800px;
            margin: 0 auto;
            text-align: center;
        }
        .portal-logo {
            height: 50px;
            margin-bottom: 15px;
        }
        .portal-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 0 20px 40px;
        }
        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .alert-danger {
            background: #fee;
            color: #c33;
            border: 1px solid #fcc;
        }
        .content-card {
            background: white;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        h3 {
            margin: 20px 0 15px 0;
            color: #111827;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #374151;
        }
        .form-control {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            font-size: 16px;
            font-family: inherit;
            transition: border-color 0.3s;
        }
        .form-control:focus {
            outline: none;
            border-color: #eb008b;
        }
        textarea.form-control {
            resize: vertical;
        }
        .btn {
            display: inline-block;
            padding: 12px 24px;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            border: none;
            transition: all 0.3s;
        }
        .btn-primary {
            background: linear-gradient(135deg, #eb008b 0%, #d40080 100%);
            color: white;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(235, 0, 139, 0.4);
        }
        .btn-secondary {
            background: #6b7280;
            color: white;
        }
        .btn-secondary:hover {
            background: #4b5563;
        }
    </style>
</head>
<body>
    <div class="portal-header">
        <div class="portal-header-content">
            <img src="<?php echo basePath('public/assets/images/echo-logo.png'); ?>" alt="ECHO2026" class="portal-logo" style="filter: brightness(0) invert(1);">
            <h1 style="margin: 0; font-size: 28px;">Edit Your Booking</h1>
            <p style="margin: 10px 0 0 0; opacity: 0.9;">Update your contact and camping information</p>
        </div>
    </div>

    <div class="portal-container">
        <?php if ($error): ?>
            <div class="alert alert-danger">
                <?php echo e($error); ?>
            </div>
        <?php endif; ?>

        <div class="content-card">
            <form method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?php echo e($csrfToken); ?>">

                <h3>Contact Information</h3>

                <div class="form-group">
                    <label for="booker_name">Full Name *</label>
                    <input type="text" id="booker_name" name="booker_name" class="form-control" value="<?php echo e($bookingData['booker_name']); ?>" required>
                </div>

                <div class="form-group">
                    <label for="booker_email">Email Address *</label>
                    <input type="email" id="booker_email" name="booker_email" class="form-control" value="<?php echo e($bookingData['booker_email']); ?>" required>
                </div>

                <div class="form-group">
                    <label for="booker_phone">Phone Number *</label>
                    <input type="tel" id="booker_phone" name="booker_phone" class="form-control" value="<?php echo e($bookingData['booker_phone']); ?>" required>
                </div>

                <h3 style="margin-top: 30px;">Camping Requirements</h3>

                <div class="form-group">
                    <label for="num_tents">Number of Tents</label>
                    <input type="number" id="num_tents" name="num_tents" class="form-control" value="<?php echo $bookingData['num_tents']; ?>" min="0">
                </div>

                <div class="form-group">
                    <label style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
                        <input type="checkbox" name="has_caravan" value="1" <?php echo $bookingData['has_caravan'] ? 'checked' : ''; ?> style="width: auto;">
                        <span>We're bringing a caravan/campervan</span>
                    </label>
                </div>

                <div class="form-group">
                    <label style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
                        <input type="checkbox" name="needs_tent_provided" value="1" <?php echo $bookingData['needs_tent_provided'] ? 'checked' : ''; ?> style="width: auto;">
                        <span>We need a tent provided</span>
                    </label>
                </div>

                <div class="form-group">
                    <label for="special_requirements">Special Requirements</label>
                    <textarea id="special_requirements" name="special_requirements" class="form-control" rows="4" placeholder="Dietary requirements, accessibility needs, etc."><?php echo e($bookingData['special_requirements']); ?></textarea>
                </div>

                <div style="margin-top: 30px; display: flex; gap: 15px;">
                    <button type="submit" class="btn btn-primary" style="flex: 1;">
                        💾 Save Changes
                    </button>
                    <a href="<?php echo url('portal/dashboard.php'); ?>" class="btn btn-secondary" style="flex: 1; text-align: center;">
                        Cancel
                    </a>
                </div>
            </form>

            <div style="margin-top: 25px; padding: 20px; background: #fff3cd; border-radius: 8px; font-size: 14px;">
                <strong>ℹ️ Note:</strong>
                <p style="margin: 10px 0 0 0; color: #666;">
                    To modify attendees or change your booking dates, please use the "Add Attendee" button on your dashboard or contact us at
                    <a href="mailto:<?php echo e(env('SMTP_FROM_EMAIL')); ?>" style="color: var(--primary-color);">
                        <?php echo e(env('SMTP_FROM_EMAIL')); ?>
                    </a>
                </p>
            </div>
        </div>
    </div>
</body>
</html>
