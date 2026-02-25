<?php
/**
 * Admin Dashboard
 * Overview with statistics and recent bookings
 */

// Initialize
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/sanitize.php';
require_once __DIR__ . '/../classes/Booking.php';
require_once __DIR__ . '/../classes/Attendee.php';

// Set page title
$pageTitle = 'Dashboard';

// Get statistics
$stats = Booking::getStatistics();
$attendeeStats = Attendee::getStatistics();

// Get recent bookings
$recentBookings = Booking::getAll(['limit' => 10]);

// Include header
include __DIR__ . '/../templates/admin/header.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Dashboard</h1>
        <p class="page-subtitle">Overview of <?php echo e(EVENT_NAME); ?> bookings</p>
    </div>
    <a href="/book/" class="btn btn-primary" target="_blank">
        New Booking
    </a>
</div>

<!-- Statistics Cards -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-header">
            <span class="stat-label">Total Bookings</span>
            <span class="stat-icon">📋</span>
        </div>
        <div class="stat-value"><?php echo number_format($stats['total_bookings']); ?></div>
    </div>

    <div class="stat-card">
        <div class="stat-header">
            <span class="stat-label">Total Attendees</span>
            <span class="stat-icon">👥</span>
        </div>
        <div class="stat-value"><?php echo number_format($stats['total_attendees']); ?></div>
    </div>

    <div class="stat-card">
        <div class="stat-header">
            <span class="stat-label">Total Revenue</span>
            <span class="stat-icon">💰</span>
        </div>
        <div class="stat-value"><?php echo formatCurrency($stats['total_revenue']); ?></div>
    </div>

    <div class="stat-card">
        <div class="stat-header">
            <span class="stat-label">Outstanding</span>
            <span class="stat-icon">⏳</span>
        </div>
        <div class="stat-value"><?php echo formatCurrency($stats['outstanding_amount']); ?></div>
    </div>
</div>

<!-- Payment Status Overview -->
<div class="content-card">
    <div class="card-header">
        <h2 class="card-title">Payment Status</h2>
    </div>

    <div class="stats-grid">
        <div class="detail-item">
            <div class="detail-label">Paid Bookings</div>
            <div class="detail-value" style="color: var(--success-color);">
                <?php echo $stats['paid_bookings']; ?>
            </div>
        </div>

        <div class="detail-item">
            <div class="detail-label">Partial Payments</div>
            <div class="detail-value" style="color: var(--warning-color);">
                <?php echo $stats['partial_bookings']; ?>
            </div>
        </div>

        <div class="detail-item">
            <div class="detail-label">Unpaid Bookings</div>
            <div class="detail-value" style="color: var(--danger-color);">
                <?php echo $stats['unpaid_bookings']; ?>
            </div>
        </div>
    </div>
</div>

<!-- Attendee Statistics -->
<div class="content-card">
    <div class="card-header">
        <h2 class="card-title">Ticket Breakdown</h2>
    </div>

    <div class="detail-grid">
        <div class="detail-item">
            <div class="detail-label">Adult Weekend</div>
            <div class="detail-value"><?php echo $attendeeStats['adult_weekend']; ?></div>
        </div>

        <div class="detail-item">
            <div class="detail-label">Adult Sponsor</div>
            <div class="detail-value"><?php echo $attendeeStats['adult_sponsor']; ?></div>
        </div>

        <div class="detail-item">
            <div class="detail-label">Child Weekend</div>
            <div class="detail-value"><?php echo $attendeeStats['child_weekend']; ?></div>
        </div>

        <div class="detail-item">
            <div class="detail-label">Free Children (0-4)</div>
            <div class="detail-value"><?php echo $attendeeStats['free_child']; ?></div>
        </div>

        <div class="detail-item">
            <div class="detail-label">Adult Day Tickets</div>
            <div class="detail-value"><?php echo $attendeeStats['adult_day']; ?></div>
        </div>

        <div class="detail-item">
            <div class="detail-label">Child Day Tickets</div>
            <div class="detail-value"><?php echo $attendeeStats['child_day']; ?></div>
        </div>
    </div>
</div>

<!-- Recent Bookings -->
<div class="content-card">
    <div class="card-header">
        <h2 class="card-title">Recent Bookings</h2>
        <a href="<?php echo url('admin/bookings.php'); ?>" class="btn btn-secondary btn-sm">
            View All
        </a>
    </div>

    <?php if (empty($recentBookings)): ?>
        <div class="empty-state">
            <div class="empty-state-icon">📋</div>
            <h3 class="empty-state-title">No Bookings Yet</h3>
            <p class="empty-state-text">Bookings will appear here once people start registering for camp.</p>
            <a href="/book/" class="btn btn-primary" target="_blank">
                Create First Booking
            </a>
        </div>
    <?php else: ?>
        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Reference</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Attendees</th>
                        <th>Amount</th>
                        <th>Paid</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach (array_slice($recentBookings, 0, 10) as $booking): ?>
                        <tr>
                            <td>
                                <strong><?php echo e($booking['booking_reference']); ?></strong>
                            </td>
                            <td><?php echo e($booking['booker_name']); ?></td>
                            <td><?php echo e($booking['booker_email']); ?></td>
                            <td><?php echo $booking['attendee_count']; ?></td>
                            <td><?php echo formatCurrency($booking['total_amount']); ?></td>
                            <td><?php echo formatCurrency($booking['amount_paid']); ?></td>
                            <td><?php echo getPaymentStatusBadge($booking['payment_status']); ?></td>
                            <td><?php echo formatDateTime($booking['created_at'], 'd/m/Y H:i'); ?></td>
                            <td>
                                <div class="table-actions">
                                    <a href="<?php echo url('admin/booking-detail.php?id=' . $booking['id']); ?>"
                                       class="btn btn-secondary btn-sm">
                                        View
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../templates/admin/footer.php'; ?>
