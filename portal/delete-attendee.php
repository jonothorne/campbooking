<?php
/**
 * Customer Portal - Delete Attendee
 * Allow customers to remove an attendee from their booking
 */

// Initialize
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/customer-auth.php';
require_once __DIR__ . '/../classes/Booking.php';

// Require authentication
requireCustomerAuth();

$customerId = currentCustomerId();
$db = Database::getInstance();

// Get attendee ID
$attendeeId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Load attendee and verify ownership
try {
    $attendee = $db->fetchOne(
        "SELECT * FROM attendees WHERE id = ? AND booking_id = ?",
        [$attendeeId, $customerId]
    );

    if (!$attendee) {
        $_SESSION['error'] = 'Attendee not found.';
        redirect(url('portal/dashboard.php'));
    }
} catch (Exception $e) {
    $_SESSION['error'] = 'Failed to load attendee.';
    redirect(url('portal/dashboard.php'));
}

// Check that there's more than one attendee (can't delete the last one)
try {
    $attendeeCount = $db->fetchOne(
        "SELECT COUNT(*) as count FROM attendees WHERE booking_id = ?",
        [$customerId]
    )['count'];

    if ($attendeeCount <= 1) {
        $_SESSION['error'] = 'Cannot delete the last attendee. A booking must have at least one person.';
        redirect(url('portal/dashboard.php'));
    }
} catch (Exception $e) {
    $_SESSION['error'] = 'Failed to check attendee count.';
    redirect(url('portal/dashboard.php'));
}

// Delete the attendee
try {
    $attendeeName = $attendee['name'];
    $attendeePrice = $attendee['ticket_price'];

    // Delete attendee
    $db->execute(
        "DELETE FROM attendees WHERE id = ? AND booking_id = ?",
        [$attendeeId, $customerId]
    );

    // Recalculate booking total
    $newTotal = $db->fetchOne(
        "SELECT SUM(ticket_price) as total FROM attendees WHERE booking_id = ?",
        [$customerId]
    )['total'] ?? 0;

    // Update booking total and outstanding amount
    $db->execute(
        "UPDATE bookings SET
            total_amount = ?,
            amount_outstanding = total_amount - amount_paid
        WHERE id = ?",
        [$newTotal, $customerId]
    );

    // Recalculate payment schedule to redistribute outstanding amount
    $booking = new Booking($customerId);
    $booking->recalculatePaymentSchedule();

    // Log GDPR action
    logGDPRAction($customerId, 'privacy_update', "Customer removed attendee: $attendeeName");

    // Success message
    $_SESSION['success'] = "Removed $attendeeName from your booking. Your new total is " . formatCurrency($newTotal) . ".";
    redirect(url('portal/dashboard.php'));

} catch (Exception $e) {
    error_log("Delete attendee error: " . $e->getMessage());
    $_SESSION['error'] = 'Failed to delete attendee. Please try again or contact support.';
    redirect(url('portal/dashboard.php'));
}
