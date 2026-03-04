<?php
/**
 * Admin Booking Edit
 * Edit all booking details
 */

// Initialize
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/sanitize.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../classes/Booking.php';

// Check authentication
requireAuth();

// Get booking ID
$bookingId = isset($_GET['id']) ? sanitizeInt($_GET['id']) : 0;

if (!$bookingId) {
    redirect(url('admin/bookings.php'));
}

// Load booking
try {
    $booking = new Booking($bookingId);
    $bookingData = $booking->getData();
    $attendees = $booking->getAttendees();
} catch (Exception $e) {
    $_SESSION['error'] = 'Booking not found';
    redirect(url('admin/bookings.php'));
}

// Set page title
$pageTitle = 'Edit Booking #' . $bookingData['booking_reference'];

// Get success/error messages
$success = $_SESSION['success'] ?? null;
$error = $_SESSION['error'] ?? null;
unset($_SESSION['success'], $_SESSION['error']);

// Include header
include __DIR__ . '/../templates/admin/header.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Edit Booking</h1>
        <p class="page-subtitle">Reference: <?php echo e($bookingData['booking_reference']); ?></p>
    </div>
    <a href="<?php echo url('admin/booking-detail.php?id=' . $bookingId); ?>" class="btn btn-secondary">
        ← Cancel
    </a>
</div>

<!-- Success/Error Messages -->
<?php if ($success): ?>
    <div class="alert alert-success">
        <?php echo e($success); ?>
    </div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-danger">
        <?php echo e($error); ?>
    </div>
<?php endif; ?>

<form method="POST" action="<?php echo url('admin/booking-update.php'); ?>" id="edit-booking-form">
    <input type="hidden" name="csrf_token" value="<?php echo e(generateCsrfToken()); ?>">
    <input type="hidden" name="booking_id" value="<?php echo $bookingId; ?>">

    <!-- Booker Information -->
    <div class="content-card">
        <div class="card-header">
            <h2 class="card-title">Booker Information</h2>
        </div>

        <div class="form-group">
            <label class="form-label" for="booker_name">Full Name <span style="color: var(--danger-color);">*</span></label>
            <input
                type="text"
                id="booker_name"
                name="booker_name"
                class="form-control"
                value="<?php echo e($bookingData['booker_name']); ?>"
                required
            >
        </div>

        <div class="form-group">
            <label class="form-label" for="booker_email">Email <span style="color: var(--danger-color);">*</span></label>
            <input
                type="email"
                id="booker_email"
                name="booker_email"
                class="form-control"
                value="<?php echo e($bookingData['booker_email']); ?>"
                required
            >
        </div>

        <div class="form-group">
            <label class="form-label" for="booker_phone">Phone <span style="color: var(--danger-color);">*</span></label>
            <input
                type="tel"
                id="booker_phone"
                name="booker_phone"
                class="form-control"
                value="<?php echo e($bookingData['booker_phone']); ?>"
                required
            >
        </div>
    </div>

    <!-- Attendees -->
    <div class="content-card">
        <div class="card-header">
            <h2 class="card-title">Attendees</h2>
            <button type="button" class="btn btn-primary btn-sm" id="add-attendee-btn">+ Add Attendee</button>
        </div>

        <div id="attendees-container">
            <?php foreach ($attendees as $index => $attendee): ?>
                <div class="attendee-edit-card" data-attendee-index="<?php echo $index; ?>">
                    <div class="card-header" style="border-bottom: 1px solid var(--border-color); margin-bottom: 15px;">
                        <h3 style="font-size: 16px; font-weight: 600;">Attendee <?php echo $index + 1; ?></h3>
                        <?php if ($index > 0): ?>
                            <button type="button" class="btn btn-danger btn-sm remove-attendee-btn" data-index="<?php echo $index; ?>">Remove</button>
                        <?php endif; ?>
                    </div>

                    <input type="hidden" name="attendees[<?php echo $index; ?>][id]" value="<?php echo $attendee['id']; ?>">

                    <div class="form-group">
                        <label class="form-label">Name <span style="color: var(--danger-color);">*</span></label>
                        <input
                            type="text"
                            name="attendees[<?php echo $index; ?>][name]"
                            class="form-control"
                            value="<?php echo e($attendee['name']); ?>"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label class="form-label">Age <span style="color: var(--danger-color);">*</span></label>
                        <input
                            type="number"
                            name="attendees[<?php echo $index; ?>][age]"
                            class="form-control"
                            value="<?php echo $attendee['age']; ?>"
                            min="0"
                            max="120"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label class="form-label">Ticket Type <span style="color: var(--danger-color);">*</span></label>
                        <select name="attendees[<?php echo $index; ?>][ticket_type]" class="form-control" required>
                            <option value="adult_weekend" <?php echo $attendee['ticket_type'] === 'adult_weekend' ? 'selected' : ''; ?>>Adult Weekend (£85.00)</option>
                            <option value="adult_sponsor" <?php echo $attendee['ticket_type'] === 'adult_sponsor' ? 'selected' : ''; ?>>Adult Sponsor (£110.00)</option>
                            <option value="child_weekend" <?php echo $attendee['ticket_type'] === 'child_weekend' ? 'selected' : ''; ?>>Child Weekend (£55.00)</option>
                            <option value="adult_day" <?php echo $attendee['ticket_type'] === 'adult_day' ? 'selected' : ''; ?>>Adult Day Ticket (£25.00/day)</option>
                            <option value="child_day" <?php echo $attendee['ticket_type'] === 'child_day' ? 'selected' : ''; ?>>Child Day Ticket (£15.00/day)</option>
                            <option value="free_child" <?php echo $attendee['ticket_type'] === 'free_child' ? 'selected' : ''; ?>>Free Child (0-4 years)</option>
                        </select>
                    </div>

                    <?php if ($attendee['ticket_type'] === 'adult_day' || $attendee['ticket_type'] === 'child_day'): ?>
                        <div class="form-group day-tickets-group">
                            <label class="form-label">Days Attending <span style="color: var(--danger-color);">*</span></label>
                            <?php
                            $selectedDays = !empty($attendee['day_ticket_dates']) ? json_decode($attendee['day_ticket_dates'], true) : [];
                            ?>
                            <div>
                                <label style="display: block; margin-bottom: 8px;">
                                    <input type="checkbox" name="attendees[<?php echo $index; ?>][day_ticket_dates][]" value="2026-05-29" <?php echo in_array('2026-05-29', $selectedDays) ? 'checked' : ''; ?>>
                                    Friday, May 29
                                </label>
                                <label style="display: block; margin-bottom: 8px;">
                                    <input type="checkbox" name="attendees[<?php echo $index; ?>][day_ticket_dates][]" value="2026-05-30" <?php echo in_array('2026-05-30', $selectedDays) ? 'checked' : ''; ?>>
                                    Saturday, May 30
                                </label>
                                <label style="display: block; margin-bottom: 8px;">
                                    <input type="checkbox" name="attendees[<?php echo $index; ?>][day_ticket_dates][]" value="2026-05-31" <?php echo in_array('2026-05-31', $selectedDays) ? 'checked' : ''; ?>>
                                    Sunday, May 31
                                </label>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Camping Requirements -->
    <div class="content-card">
        <div class="card-header">
            <h2 class="card-title">Camping Requirements</h2>
        </div>

        <div class="form-group">
            <label class="form-label" for="num_tents">Number of Tents</label>
            <input
                type="number"
                id="num_tents"
                name="num_tents"
                class="form-control"
                value="<?php echo $bookingData['num_tents']; ?>"
                min="0"
            >
        </div>

        <div class="form-group">
            <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                <input
                    type="checkbox"
                    name="has_caravan"
                    value="1"
                    <?php echo $bookingData['has_caravan'] ? 'checked' : ''; ?>
                >
                <span>Bringing Caravan/Campervan</span>
            </label>
        </div>

        <div class="form-group">
            <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                <input
                    type="checkbox"
                    name="needs_tent_provided"
                    value="1"
                    <?php echo $bookingData['needs_tent_provided'] ? 'checked' : ''; ?>
                >
                <span>Needs Tent Provided</span>
            </label>
        </div>

        <div class="form-group">
            <label class="form-label" for="special_requirements">Special Requirements</label>
            <textarea
                id="special_requirements"
                name="special_requirements"
                class="form-control"
                rows="4"
            ><?php echo e($bookingData['special_requirements']); ?></textarea>
        </div>
    </div>

    <!-- Payment Details -->
    <div class="content-card">
        <div class="card-header">
            <h2 class="card-title">Payment Details</h2>
        </div>

        <div class="form-group">
            <label class="form-label">Payment Method</label>
            <select name="payment_method" class="form-control" required>
                <option value="stripe" <?php echo $bookingData['payment_method'] === 'stripe' ? 'selected' : ''; ?>>Card (Stripe)</option>
                <option value="bank_transfer" <?php echo $bookingData['payment_method'] === 'bank_transfer' ? 'selected' : ''; ?>>Bank Transfer</option>
                <option value="cash" <?php echo $bookingData['payment_method'] === 'cash' ? 'selected' : ''; ?>>Cash</option>
            </select>
        </div>

        <div class="form-group">
            <label class="form-label">Payment Plan</label>
            <select name="payment_plan" class="form-control" required>
                <option value="full" <?php echo $bookingData['payment_plan'] === 'full' ? 'selected' : ''; ?>>Pay in Full</option>
                <option value="monthly" <?php echo $bookingData['payment_plan'] === 'monthly' ? 'selected' : ''; ?>>Monthly Installments</option>
                <option value="three_payments" <?php echo $bookingData['payment_plan'] === 'three_payments' ? 'selected' : ''; ?>>3 Equal Payments</option>
            </select>
        </div>

        <div class="form-group">
            <label class="form-label">Booking Status</label>
            <select name="booking_status" class="form-control" required>
                <option value="pending" <?php echo $bookingData['booking_status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                <option value="confirmed" <?php echo $bookingData['booking_status'] === 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                <option value="cancelled" <?php echo $bookingData['booking_status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
            </select>
        </div>

        <div class="alert alert-info">
            <strong>Note:</strong> Changing payment details will not automatically recalculate payment schedules or process refunds. Any payment adjustments must be handled manually.
        </div>
    </div>

    <!-- Submit Buttons -->
    <div class="content-card">
        <div style="display: flex; gap: 10px; justify-content: flex-end;">
            <a href="<?php echo url('admin/booking-detail.php?id=' . $bookingId); ?>" class="btn btn-secondary">Cancel</a>
            <button type="submit" class="btn btn-primary">Save Changes</button>
        </div>
    </div>
</form>

<!-- Attendee Template (hidden) -->
<template id="attendee-template">
    <div class="attendee-edit-card" data-attendee-index="{INDEX}">
        <div class="card-header" style="border-bottom: 1px solid var(--border-color); margin-bottom: 15px;">
            <h3 style="font-size: 16px; font-weight: 600;">Attendee {NUMBER}</h3>
            <button type="button" class="btn btn-danger btn-sm remove-attendee-btn" data-index="{INDEX}">Remove</button>
        </div>

        <input type="hidden" name="attendees[{INDEX}][id]" value="0">

        <div class="form-group">
            <label class="form-label">Name <span style="color: var(--danger-color);">*</span></label>
            <input type="text" name="attendees[{INDEX}][name]" class="form-control" required>
        </div>

        <div class="form-group">
            <label class="form-label">Age <span style="color: var(--danger-color);">*</span></label>
            <input type="number" name="attendees[{INDEX}][age]" class="form-control" min="0" max="120" required>
        </div>

        <div class="form-group">
            <label class="form-label">Ticket Type <span style="color: var(--danger-color);">*</span></label>
            <select name="attendees[{INDEX}][ticket_type]" class="form-control" required>
                <option value="adult_weekend">Adult Weekend (£85.00)</option>
                <option value="adult_sponsor">Adult Sponsor (£110.00)</option>
                <option value="child_weekend">Child Weekend (£55.00)</option>
                <option value="adult_day">Adult Day Ticket (£25.00/day)</option>
                <option value="child_day">Child Day Ticket (£15.00/day)</option>
                <option value="free_child">Free Child (0-4 years)</option>
            </select>
        </div>
    </div>
</template>

<style>
.attendee-edit-card {
    background: var(--bg-light);
    padding: 20px;
    border-radius: 8px;
    margin-bottom: 20px;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    let attendeeCount = <?php echo count($attendees); ?>;

    // Add attendee
    document.getElementById('add-attendee-btn').addEventListener('click', function() {
        const container = document.getElementById('attendees-container');
        const template = document.getElementById('attendee-template');

        let html = template.innerHTML;
        html = html.replace(/{INDEX}/g, attendeeCount);
        html = html.replace(/{NUMBER}/g, attendeeCount + 1);

        const temp = document.createElement('div');
        temp.innerHTML = html;
        const newAttendee = temp.firstElementChild;

        container.appendChild(newAttendee);

        // Add remove listener
        newAttendee.querySelector('.remove-attendee-btn').addEventListener('click', function() {
            newAttendee.remove();
        });

        attendeeCount++;
    });

    // Remove attendee
    document.querySelectorAll('.remove-attendee-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            if (confirm('Are you sure you want to remove this attendee?')) {
                this.closest('.attendee-edit-card').remove();
            }
        });
    });

    // Form validation
    document.getElementById('edit-booking-form').addEventListener('submit', function(e) {
        const attendees = document.querySelectorAll('.attendee-edit-card');
        if (attendees.length === 0) {
            e.preventDefault();
            alert('You must have at least one attendee.');
            return false;
        }
    });
});
</script>

<?php include __DIR__ . '/../templates/admin/footer.php'; ?>
