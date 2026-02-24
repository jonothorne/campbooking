<?php
/**
 * Delete Booking Handler
 * Delete a booking and all associated records
 */

// Initialize
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../classes/Booking.php';

// Require authentication
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
    $bookingData = $booking->getData();
    $bookingRef = $bookingData['booking_reference'];

    // Delete booking (cascades to attendees, payments, etc.)
    $booking->delete();

    $_SESSION['success'] = "Booking {$bookingRef} has been deleted successfully";
    redirect(url('admin/bookings.php'));

} catch (Exception $e) {
    error_log("Delete Booking Error: " . $e->getMessage());
    $_SESSION['error'] = 'Failed to delete booking: ' . $e->getMessage();

    // Try to redirect back to detail page, or bookings list if booking not found
    if ($bookingId && !isset($e->booking_not_found)) {
        redirect(url('admin/booking-detail.php?id=' . $bookingId));
    } else {
        redirect(url('admin/bookings.php'));
    }
}
