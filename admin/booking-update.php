<?php
/**
 * Admin Booking Update Handler
 * Processes booking edit form submission
 */

// Initialize
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/sanitize.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../classes/Booking.php';

// Check authentication
requireAuth();

// Only allow POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(url('admin/bookings.php'));
}

// Verify CSRF token
requireCsrfToken();

// Get booking ID
$bookingId = isset($_POST['booking_id']) ? (int)$_POST['booking_id'] : 0;

if (!$bookingId) {
    $_SESSION['error'] = 'Invalid booking ID';
    redirect(url('admin/bookings.php'));
}

try {
    // Load booking
    $booking = new Booking($bookingId);
    $db = Database::getInstance();

    // Begin transaction
    $db->beginTransaction();

    // Sanitize booker information
    $bookerData = [
        'booker_name' => sanitizeString($_POST['booker_name']),
        'booker_email' => sanitizeEmail($_POST['booker_email']),
        'booker_phone' => sanitizeString($_POST['booker_phone']),
        'num_tents' => isset($_POST['num_tents']) ? (int)$_POST['num_tents'] : 0,
        'has_caravan' => isset($_POST['has_caravan']) ? 1 : 0,
        'needs_tent_provided' => isset($_POST['needs_tent_provided']) ? 1 : 0,
        'special_requirements' => sanitizeString($_POST['special_requirements'] ?? ''),
        'payment_method' => sanitizeString($_POST['payment_method']),
        'payment_plan' => sanitizeString($_POST['payment_plan']),
        'booking_status' => sanitizeString($_POST['booking_status'])
    ];

    // Validate required fields
    if (empty($bookerData['booker_name']) || empty($bookerData['booker_email']) || empty($bookerData['booker_phone'])) {
        throw new Exception('Please fill in all required fields');
    }

    // Update booking
    $booking->update($bookerData);

    // Process attendees
    $attendees = $_POST['attendees'] ?? [];

    if (empty($attendees)) {
        throw new Exception('You must have at least one attendee');
    }

    // Get existing attendee IDs
    $existingAttendees = $booking->getAttendees();
    $existingAttendeeIds = array_column($existingAttendees, 'id');

    // Track which attendees are being kept
    $keptAttendeeIds = [];
    $newTotalAmount = 0;

    // Process each attendee
    foreach ($attendees as $index => $attendeeData) {
        $attendeeId = (int)($attendeeData['id'] ?? 0);
        $name = sanitizeString($attendeeData['name']);
        $age = (int)$attendeeData['age'];
        $ticketType = sanitizeString($attendeeData['ticket_type']);

        // Validate
        if (empty($name) || $age < 0 || empty($ticketType)) {
            throw new Exception('Invalid attendee data');
        }

        // Calculate price
        $price = 0;
        $dayDates = null;

        switch ($ticketType) {
            case 'adult_weekend':
                $price = 85.00;
                break;
            case 'adult_sponsor':
                $price = 110.00;
                break;
            case 'child_weekend':
                $price = 55.00;
                break;
            case 'adult_day':
                $days = isset($attendeeData['day_ticket_dates']) ? count($attendeeData['day_ticket_dates']) : 0;
                $price = 25.00 * $days;
                $dayDates = isset($attendeeData['day_ticket_dates']) ? json_encode($attendeeData['day_ticket_dates']) : null;
                break;
            case 'child_day':
                $days = isset($attendeeData['day_ticket_dates']) ? count($attendeeData['day_ticket_dates']) : 0;
                $price = 15.00 * $days;
                $dayDates = isset($attendeeData['day_ticket_dates']) ? json_encode($attendeeData['day_ticket_dates']) : null;
                break;
            case 'free_child':
                $price = 0.00;
                break;
        }

        $newTotalAmount += $price;

        // Update or insert attendee
        if ($attendeeId > 0 && in_array($attendeeId, $existingAttendeeIds)) {
            // Update existing attendee
            $db->execute(
                "UPDATE attendees
                SET name = ?, age = ?, ticket_type = ?, ticket_price = ?, day_ticket_dates = ?
                WHERE id = ? AND booking_id = ?",
                [$name, $age, $ticketType, $price, $dayDates, $attendeeId, $bookingId]
            );
            $keptAttendeeIds[] = $attendeeId;
        } else {
            // Insert new attendee
            $newAttendeeId = $db->insert(
                "INSERT INTO attendees (booking_id, name, age, ticket_type, ticket_price, day_ticket_dates)
                VALUES (?, ?, ?, ?, ?, ?)",
                [$bookingId, $name, $age, $ticketType, $price, $dayDates]
            );
            $keptAttendeeIds[] = $newAttendeeId;
        }
    }

    // Delete attendees that were removed
    $attendeesToDelete = array_diff($existingAttendeeIds, $keptAttendeeIds);
    if (!empty($attendeesToDelete)) {
        $placeholders = implode(',', array_fill(0, count($attendeesToDelete), '?'));
        $db->execute(
            "DELETE FROM attendees WHERE id IN ($placeholders) AND booking_id = ?",
            array_merge($attendeesToDelete, [$bookingId])
        );
    }

    // Update total amount
    $booking->update(['total_amount' => $newTotalAmount]);

    // Recalculate payment status
    $booking->updatePaymentStatus();

    // Commit transaction
    $db->commit();

    $_SESSION['success'] = 'Booking updated successfully!';
    redirect(url('admin/booking-detail.php?id=' . $bookingId));

} catch (Exception $e) {
    // Rollback on error
    if (isset($db)) {
        $db->rollback();
    }

    error_log("Booking Update Error: " . $e->getMessage());
    $_SESSION['error'] = 'Failed to update booking: ' . $e->getMessage();
    redirect(url('admin/booking-edit.php?id=' . $bookingId));
}
