<?php
/**
 * Admin Bookings List
 * View all bookings with search and filters
 */

// Initialize
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/sanitize.php';
require_once __DIR__ . '/../classes/Booking.php';

// Set page title
$pageTitle = 'Bookings';

// Get filters from query string
$filters = [];

if (!empty($_GET['search'])) {
    $filters['search'] = sanitizeString($_GET['search']);
}

if (!empty($_GET['payment_status']) && $_GET['payment_status'] !== 'all') {
    $filters['payment_status'] = $_GET['payment_status'];
}

if (!empty($_GET['booking_status']) && $_GET['booking_status'] !== 'all') {
    $filters['booking_status'] = $_GET['booking_status'];
}

// Get bookings
$bookings = Booking::getAll($filters);

// Include header
include __DIR__ . '/../templates/admin/header.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">All Bookings</h1>
        <p class="page-subtitle"><?php echo count($bookings); ?> booking<?php echo count($bookings) !== 1 ? 's' : ''; ?> found</p>
    </div>
    <a href="/book/" class="btn btn-primary" target="_blank">
        New Booking
    </a>
</div>

<!-- Search and Filters -->
<div class="content-card">
    <form method="GET" action="">
        <div class="search-bar">
            <input
                type="text"
                name="search"
                class="search-input"
                placeholder="Search by name, email, or booking reference..."
                value="<?php echo isset($_GET['search']) ? e($_GET['search']) : ''; ?>"
            >

            <select name="payment_status" class="filter-select">
                <option value="all">All Payment Status</option>
                <option value="paid" <?php echo (isset($_GET['payment_status']) && $_GET['payment_status'] === 'paid') ? 'selected' : ''; ?>>Paid</option>
                <option value="partial" <?php echo (isset($_GET['payment_status']) && $_GET['payment_status'] === 'partial') ? 'selected' : ''; ?>>Partial</option>
                <option value="unpaid" <?php echo (isset($_GET['payment_status']) && $_GET['payment_status'] === 'unpaid') ? 'selected' : ''; ?>>Unpaid</option>
                <option value="failed" <?php echo (isset($_GET['payment_status']) && $_GET['payment_status'] === 'failed') ? 'selected' : ''; ?>>Failed</option>
            </select>

            <select name="booking_status" class="filter-select">
                <option value="all">All Booking Status</option>
                <option value="pending" <?php echo (isset($_GET['booking_status']) && $_GET['booking_status'] === 'pending') ? 'selected' : ''; ?>>Pending</option>
                <option value="confirmed" <?php echo (isset($_GET['booking_status']) && $_GET['booking_status'] === 'confirmed') ? 'selected' : ''; ?>>Confirmed</option>
                <option value="cancelled" <?php echo (isset($_GET['booking_status']) && $_GET['booking_status'] === 'cancelled') ? 'selected' : ''; ?>>Cancelled</option>
            </select>

            <button type="submit" class="btn btn-primary">Search</button>

            <?php if (!empty($filters)): ?>
                <a href="<?php echo url('admin/bookings.php'); ?>" class="btn btn-secondary">Clear</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<!-- Bookings Table -->
<div class="content-card">
    <?php if (empty($bookings)): ?>
        <div class="empty-state">
            <div class="empty-state-icon">📋</div>
            <h3 class="empty-state-title">No Bookings Found</h3>
            <p class="empty-state-text">
                <?php if (!empty($filters)): ?>
                    No bookings match your search criteria. Try adjusting your filters.
                <?php else: ?>
                    No bookings have been created yet. Create the first booking to get started!
                <?php endif; ?>
            </p>
            <?php if (empty($filters)): ?>
                <a href="/book/" class="btn btn-primary" target="_blank">
                    Create First Booking
                </a>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Reference</th>
                        <th>Booker</th>
                        <th>Contact</th>
                        <th>Attendees</th>
                        <th>Total</th>
                        <th>Paid</th>
                        <th>Outstanding</th>
                        <th>Payment Status</th>
                        <th>Booking Status</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($bookings as $booking): ?>
                        <tr>
                            <td>
                                <strong><?php echo e($booking['booking_reference']); ?></strong>
                            </td>
                            <td><?php echo e($booking['booker_name']); ?></td>
                            <td>
                                <div><?php echo e($booking['booker_email']); ?></div>
                                <div style="font-size: 12px; color: var(--text-medium);">
                                    <?php echo e($booking['booker_phone']); ?>
                                </div>
                            </td>
                            <td class="text-center"><?php echo $booking['attendee_count']; ?></td>
                            <td><?php echo formatCurrency($booking['total_amount']); ?></td>
                            <td><?php echo formatCurrency($booking['amount_paid']); ?></td>
                            <td>
                                <?php if ($booking['amount_outstanding'] > 0): ?>
                                    <span style="color: var(--danger-color); font-weight: 600;">
                                        <?php echo formatCurrency($booking['amount_outstanding']); ?>
                                    </span>
                                <?php else: ?>
                                    <span style="color: var(--text-medium);">-</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo getPaymentStatusBadge($booking['payment_status']); ?></td>
                            <td><?php echo getBookingStatusBadge($booking['booking_status']); ?></td>
                            <td>
                                <?php echo formatDate($booking['created_at'], 'd/m/Y'); ?>
                                <div style="font-size: 11px; color: var(--text-medium);">
                                    <?php echo formatDate($booking['created_at'], 'H:i'); ?>
                                </div>
                            </td>
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
