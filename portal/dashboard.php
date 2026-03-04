<?php
/**
 * Customer Portal Dashboard
 * View booking details, payments, and GDPR tools
 */

// Initialize
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/customer-auth.php';
require_once __DIR__ . '/../classes/Booking.php';

// Require authentication
requireCustomerAuth();

$customerId = currentCustomerId();

// Load booking
try {
    $booking = new Booking($customerId);
    $bookingData = $booking->getData();
    $attendees = $booking->getAttendees();
    $payments = $booking->getPayments();
    $paymentSchedule = $booking->getPaymentSchedule();
} catch (Exception $e) {
    customerLogout();
    redirect(url('portal/login.php'));
}

$welcome = isset($_GET['welcome']);
$success = $_SESSION['success'] ?? null;
$error = $_SESSION['error'] ?? null;
unset($_SESSION['success'], $_SESSION['error']);

// Check for overdue payments
$overduePayments = [];
$upcomingPayments = [];
$today = date('Y-m-d');

foreach ($paymentSchedule as $schedule) {
    if ($schedule['status'] === 'pending' || $schedule['status'] === 'failed') {
        if ($schedule['due_date'] < $today) {
            $overduePayments[] = $schedule;
        } else {
            $upcomingPayments[] = $schedule;
        }
    }
}

$hasOverdue = !empty($overduePayments);
$totalOverdue = array_sum(array_column($overduePayments, 'amount'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Booking - <?php echo e(EVENT_NAME); ?></title>
    <link rel="stylesheet" href="<?php echo basePath('public/assets/css/admin.css'); ?>">
    <style>
        body {
            background: #f9fafb;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
        }

        .portal-header {
            background: linear-gradient(135deg, #1f2937 0%, #d40080 100%);
            color: white;
            padding: 30px 20px;
            margin-bottom: 30px;
            border-color: #eb008b;
            border-top-style: solid;
            border-width: 5px;
        }

        .portal-header-content {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .portal-logo {
            height: 50px;
        }

        .portal-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px 40px;
        }

        @media (max-width: 768px) {
            .portal-header-content {
                flex-direction: column;
                gap: 15px;
            }
        }

        /* Hero Section */
        .hero-section {
            background: url(/public/assets/images/ECHO-wide-background.png);
            border-radius: 16px;
            padding: 40px;
            margin-bottom: 30px;
            color: white;
            text-align: center;
            box-shadow: 0 10px 30px rgba(235, 0, 139, 0.3);
            position: relative;
            overflow: hidden;
            background-size: cover;
            background-position: bottom;
        }

        .hero-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg width="100" height="100" xmlns="http://www.w3.org/2000/svg"><circle cx="50" cy="50" r="40" fill="rgba(255,255,255,0.05)"/></svg>');
            opacity: 0.3;
        }

        .hero-content {
            position: relative;
            z-index: 1;
        }

        .hero-title {
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 10px;
            text-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .hero-subtitle {
            font-size: 18px;
            opacity: 0.95;
            margin-bottom: 30px;
        }

        .countdown-container {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin: 30px 0;
            flex-wrap: wrap;
        }

        .countdown-item {
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(10px);
            border-radius: 12px;
            padding: 20px;
            min-width: 100px;
        }

        .countdown-number {
            font-size: 36px;
            font-weight: 700;
            display: block;
        }

        .countdown-label {
            font-size: 12px;
            text-transform: uppercase;
            opacity: 0.9;
            margin-top: 5px;
        }

        .hero-btn {
            background: white;
            color: #eb008b;
            padding: 14px 32px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            font-size: 16px;
            display: inline-block;
            transition: all 0.3s;
            cursor: pointer;
            border: none;
        }

        .hero-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
        }

        /* Video Modal */
        .video-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 10000;
        }

        .video-modal.active {
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .video-modal-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.9);
        }

        .video-modal-content {
            position: relative;
            z-index: 10001;
            width: 90%;
            max-width: 900px;
        }

        .video-modal-close {
            position: absolute;
            top: -40px;
            right: 0;
            background: transparent;
            border: none;
            color: white;
            font-size: 30px;
            cursor: pointer;
            padding: 10px;
            z-index: 10002;
        }

        .video-container {
            position: relative;
            padding-bottom: 56.25%;
            height: 0;
            background: #000;
            border-radius: 8px;
            overflow: hidden;
        }

        .video-container iframe {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
        }

        @media (max-width: 768px) {
            .hero-section {
                padding: 30px 20px;
            }

            .hero-title {
                font-size: 24px;
            }

            .hero-subtitle {
                font-size: 16px;
            }

            .countdown-number {
                font-size: 28px;
            }

            .countdown-item {
                min-width: 70px;
                padding: 15px;
            }
        }
    </style>
</head>
<body>
    <div class="portal-header">
        <div class="portal-header-content">
            <div>
                <img src="<?php echo basePath('public/assets/images/echo-logo.png'); ?>" alt="ECHO2026" class="portal-logo" >
            </div>
            <div style="display: flex; gap: 15px; align-items: center;">
                <span><?php echo e($bookingData['booker_name']); ?></span>
                <a href="<?php echo url('portal/logout.php'); ?>" class="btn-logout">Logout</a>
            </div>
        </div>
    </div>

    <div class="portal-container">
        <!-- Hero Section -->
        <div class="hero-section">
            <div class="hero-content">
                <h1 class="hero-title">YOU'RE COMING TO ECHO2026! 🎉</h1>
                <p class="hero-subtitle">The call has been answered. Your journey begins...</p>

                <!-- Countdown Timer -->
                <div class="countdown-container">
                    <div class="countdown-item">
                        <span class="countdown-number" id="days">-</span>
                        <span class="countdown-label">Days</span>
                    </div>
                    <div class="countdown-item">
                        <span class="countdown-number" id="hours">-</span>
                        <span class="countdown-label">Hours</span>
                    </div>
                    <div class="countdown-item">
                        <span class="countdown-number" id="minutes">-</span>
                        <span class="countdown-label">Minutes</span>
                    </div>
                    <div class="countdown-item">
                        <span class="countdown-number" id="seconds">-</span>
                        <span class="countdown-label">Seconds</span>
                    </div>
                </div>

                <button type="button" class="hero-btn" id="watch-promo-btn">
                    📺 Watch the Promo Again
                </button>
            </div>
        </div>

        <!-- Video Modal -->
        <div id="video-modal" class="video-modal">
            <div class="video-modal-overlay" id="video-overlay"></div>
            <div class="video-modal-content">
                <button type="button" class="video-modal-close" id="close-video-btn">✕</button>
                <div class="video-container">
                    <iframe
                        id="promo-video"
                        width="100%"
                        height="100%"
                        src=""
                        frameborder="0"
                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                        allowfullscreen>
                    </iframe>
                </div>
            </div>
        </div>

        <?php if ($hasOverdue): ?>
            <div style="background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); color: white; padding: 20px; border-radius: 12px; margin-bottom: 20px; box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);">
                <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;">
                    <div>
                        <h3 style="margin: 0 0 5px 0; font-size: 20px; font-weight: 700;">⚠️ Payment Overdue</h3>
                        <p style="margin: 0; opacity: 0.95;">You have <?php echo count($overduePayments); ?> overdue payment(s) totaling <?php echo formatCurrency($totalOverdue); ?></p>
                    </div>
                    <a href="<?php echo url('portal/pay-now.php'); ?>" class="btn" style="background: white; color: #ef4444; font-weight: 600; padding: 12px 24px; border-radius: 8px; text-decoration: none; white-space: nowrap;">
                        Pay Now
                    </a>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($welcome): ?>
            <div class="alert alert-success">
                <strong>Welcome to your portal!</strong> You now have access to view and manage your ECHO2026 booking.
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo e($success); ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo e($error); ?></div>
        <?php endif; ?>

        <!-- Booking Overview -->
        <div class="content-card">
            <div class="card-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
                <div style="display: flex; align-items: center; gap: 15px; flex-wrap: wrap;">
                    <h2 class="card-title" style="margin: 0;">Booking Overview</h2>
                    <span class="badge badge-info">Ref: <?php echo e($bookingData['booking_reference']); ?></span>
                </div>
                <a href="<?php echo url('portal/edit-booking.php'); ?>" class="btn btn-primary btn-sm">
                    ✏️ Edit Details
                </a>
            </div>

            <div class="detail-grid">
                <div class="detail-item">
                    <div class="detail-label">Status</div>
                    <div class="detail-value">
                        <?php echo getBookingStatusBadge($bookingData['booking_status']); ?>
                    </div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Payment Status</div>
                    <div class="detail-value">
                        <?php echo getPaymentStatusBadge($bookingData['payment_status']); ?>
                    </div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Total Amount</div>
                    <div class="detail-value"><?php echo formatCurrency($bookingData['total_amount']); ?></div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Amount Paid</div>
                    <div class="detail-value" style="color: var(--success-color);">
                        <?php echo formatCurrency($bookingData['amount_paid']); ?>
                    </div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Outstanding</div>
                    <div class="detail-value" style="color: var(--danger-color);">
                        <?php echo formatCurrency($bookingData['amount_outstanding']); ?>
                    </div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Booking Date</div>
                    <div class="detail-value"><?php echo formatDate($bookingData['created_at'], 'd M Y'); ?></div>
                </div>
            </div>
        </div>

        <!-- Contact Information -->
        <div class="content-card">
            <div class="card-header">
                <h2 class="card-title">Your Contact Details</h2>
            </div>

            <div class="detail-grid">
                <div class="detail-item">
                    <div class="detail-label">Name</div>
                    <div class="detail-value"><?php echo e($bookingData['booker_name']); ?></div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Email</div>
                    <div class="detail-value"><?php echo e($bookingData['booker_email']); ?></div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Phone</div>
                    <div class="detail-value"><?php echo e($bookingData['booker_phone']); ?></div>
                </div>
            </div>
        </div>

        <!-- Camping Requirements -->
        <div class="content-card">
            <div class="card-header">
                <h2 class="card-title">Camping Requirements</h2>
            </div>

            <div class="detail-grid">
                <div class="detail-item">
                    <div class="detail-label">Number of Tents</div>
                    <div class="detail-value"><?php echo $bookingData['num_tents'] ?? 0; ?></div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Bringing Caravan/Campervan</div>
                    <div class="detail-value"><?php echo $bookingData['has_caravan'] ? 'Yes' : 'No'; ?></div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Need Tent Provided</div>
                    <div class="detail-value"><?php echo $bookingData['needs_tent_provided'] ? 'Yes' : 'No'; ?></div>
                </div>
            </div>

            <?php if (!empty($bookingData['special_requirements'])): ?>
                <div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid var(--border-color);">
                    <div class="detail-label" style="margin-bottom: 10px;">Special Requirements</div>
                    <div style="background: var(--bg-light); padding: 15px; border-radius: 8px; white-space: pre-wrap;"><?php echo e($bookingData['special_requirements']); ?></div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Attendees -->
        <div class="content-card">
            <div class="card-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
                <h2 class="card-title" style="margin: 0;">Attendees (<?php echo count($attendees); ?>)</h2>
                <a href="<?php echo url('portal/add-attendee.php'); ?>" class="btn btn-primary btn-sm">
                    ➕ Add Attendee
                </a>
            </div>

            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 15px;">
                <?php foreach ($attendees as $attendee): ?>
                    <div style="background: var(--bg-light); padding: 15px; border-radius: 8px; position: relative;">
                        <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 5px;">
                            <div style="font-weight: 600;"><?php echo e($attendee['name']); ?></div>
                            <div style="display: flex; gap: 8px;">
                                <a href="<?php echo url('portal/edit-attendee.php?id=' . $attendee['id']); ?>" style="color: var(--primary-color); font-size: 12px; text-decoration: none;">✏️ Edit</a>
                                <?php if (count($attendees) > 1): ?>
                                    <a href="<?php echo url('portal/delete-attendee.php?id=' . $attendee['id']); ?>"
                                       style="color: #dc2626; font-size: 12px; text-decoration: none;"
                                       onclick="return confirm('Are you sure you want to remove <?php echo e($attendee['name']); ?> from your booking? This will update your total amount.');">🗑️ Delete</a>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div style="font-size: 13px; color: var(--text-medium);">
                            Age: <?php echo $attendee['age']; ?> years<br>
                            <?php echo ucwords(str_replace('_', ' ', $attendee['ticket_type'])); ?><br>
                            <strong><?php echo formatCurrency($attendee['ticket_price']); ?></strong>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Payment History -->
        <?php if (!empty($payments)): ?>
        <div class="content-card">
            <div class="card-header">
                <h2 class="card-title">Payment History</h2>
            </div>

            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Amount</th>
                            <th>Method</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($payments as $payment): ?>
                            <tr>
                                <td><?php echo formatDate($payment['payment_date'], 'd M Y H:i'); ?></td>
                                <td><strong><?php echo formatCurrency($payment['amount']); ?></strong></td>
                                <td><?php echo ucwords(str_replace('_', ' ', $payment['payment_method'])); ?></td>
                                <td>
                                    <?php if ($payment['status'] === 'succeeded' || $payment['status'] === 'completed'): ?>
                                        <span class="badge badge-success">Paid</span>
                                    <?php elseif ($payment['status'] === 'failed'): ?>
                                        <span class="badge badge-danger">Failed</span>
                                    <?php else: ?>
                                        <span class="badge badge-secondary"><?php echo ucfirst($payment['status']); ?></span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>

        <!-- Payment Schedule -->
        <?php if (!empty($paymentSchedule) && $bookingData['amount_outstanding'] > 0): ?>
        <div class="content-card">
            <div class="card-header">
                <h2 class="card-title">Upcoming Payments</h2>
            </div>

            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Due Date</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($paymentSchedule as $schedule): ?>
                            <?php if ($schedule['status'] !== 'paid'): ?>
                                <?php
                                $isOverdue = ($schedule['status'] === 'pending' || $schedule['status'] === 'failed') && $schedule['due_date'] < $today;
                                $isFailed = $schedule['status'] === 'failed';
                                ?>
                                <tr style="<?php echo $isOverdue ? 'background: #fef2f2;' : ''; ?>">
                                    <td><?php echo $schedule['installment_number']; ?></td>
                                    <td>
                                        <?php echo formatDate($schedule['due_date'], 'd M Y'); ?>
                                        <?php if ($isOverdue): ?>
                                            <span style="color: #ef4444; font-weight: 600; font-size: 11px; display: block;">OVERDUE</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><strong><?php echo formatCurrency($schedule['amount']); ?></strong></td>
                                    <td>
                                        <?php if ($schedule['status'] === 'pending'): ?>
                                            <span class="badge badge-warning">Pending</span>
                                        <?php elseif ($schedule['status'] === 'failed'): ?>
                                            <span class="badge badge-danger">Failed</span>
                                        <?php else: ?>
                                            <span class="badge badge-secondary"><?php echo ucfirst($schedule['status']); ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($isOverdue || $isFailed): ?>
                                            <a href="<?php echo url('portal/pay-now.php?schedule_id=' . $schedule['id']); ?>" class="btn btn-danger btn-sm">
                                                Pay Now
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>

        <!-- GDPR Tools -->
        <div class="content-card">
            <div class="card-header">
                <h2 class="card-title">Privacy & Data Management</h2>
            </div>

            <div style="display: grid; gap: 15px;">
                <div style="background: var(--bg-light); padding: 20px; border-radius: 8px;">
                    <h3 style="margin: 0 0 10px 0; font-size: 16px;">📄 Download Booking Confirmation</h3>
                    <p style="margin: 0 0 15px 0; color: var(--text-medium); font-size: 14px;">
                        Download a PDF with all your booking details. Perfect for printing and bringing to check-in!
                    </p>
                    <a href="<?php echo url('portal/export-data.php'); ?>" class="btn btn-secondary btn-sm">
                        📥 Download PDF
                    </a>
                </div>

                <div style="background: var(--bg-light); padding: 20px; border-radius: 8px;">
                    <h3 style="margin: 0 0 10px 0; font-size: 16px;">Request Data Deletion</h3>
                    <p style="margin: 0 0 15px 0; color: var(--text-medium); font-size: 14px;">
                        Request permanent deletion of your booking and personal data
                    </p>
                    <?php if ($bookingData['data_deletion_requested']): ?>
                        <span class="badge badge-warning">Deletion Requested on <?php echo formatDate($bookingData['data_deletion_requested_at'], 'd M Y'); ?></span>
                    <?php else: ?>
                        <a href="<?php echo url('portal/delete-request.php'); ?>" class="btn btn-danger btn-sm"
                           onclick="return confirm('Are you sure you want to request deletion of your data? This cannot be undone.');">
                            Request Deletion
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Help -->
        <div class="content-card">
            <div class="card-header">
                <h2 class="card-title">Need Help?</h2>
            </div>
            <p>If you have questions about your booking or need assistance, please contact us:</p>
            <p><strong>Email:</strong> <a href="mailto:<?php echo e(env('SMTP_FROM_EMAIL')); ?>"><?php echo e(env('SMTP_FROM_EMAIL')); ?></a></p>
        </div>
    </div>

    <script>
        // Countdown Timer to May 29, 2026
        function updateCountdown() {
            const eventDate = new Date('2026-05-29T00:00:00').getTime();
            const now = new Date().getTime();
            const distance = eventDate - now;

            if (distance < 0) {
                // Event has passed
                document.querySelector('.countdown-container').innerHTML = '<div style="font-size: 24px; font-weight: 700;">THE EVENT IS HERE! 🎉</div>';
                return;
            }

            const days = Math.floor(distance / (1000 * 60 * 60 * 24));
            const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
            const seconds = Math.floor((distance % (1000 * 60)) / 1000);

            document.getElementById('days').textContent = days;
            document.getElementById('hours').textContent = hours.toString().padStart(2, '0');
            document.getElementById('minutes').textContent = minutes.toString().padStart(2, '0');
            document.getElementById('seconds').textContent = seconds.toString().padStart(2, '0');
        }

        // Update countdown every second
        updateCountdown();
        setInterval(updateCountdown, 1000);

        // Video Modal
        const videoModal = document.getElementById('video-modal');
        const watchPromoBtn = document.getElementById('watch-promo-btn');
        const closeVideoBtn = document.getElementById('close-video-btn');
        const videoOverlay = document.getElementById('video-overlay');
        const promoVideo = document.getElementById('promo-video');

        // YouTube video ID (replace with actual ECHO promo video ID)
        const videoId = 'VRr_5ZLL2gg'; // Replace this with the actual YouTube video ID

        watchPromoBtn.addEventListener('click', function() {
            videoModal.classList.add('active');
            promoVideo.src = 'https://www.youtube.com/embed/' + videoId + '?autoplay=1';
            document.body.style.overflow = 'hidden';
        });

        function closeModal() {
            videoModal.classList.remove('active');
            promoVideo.src = '';
            document.body.style.overflow = 'auto';
        }

        closeVideoBtn.addEventListener('click', closeModal);
        videoOverlay.addEventListener('click', closeModal);

        // Close on escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && videoModal.classList.contains('active')) {
                closeModal();
            }
        });
    </script>
</body>
</html>
