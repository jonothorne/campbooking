<?php
/**
 * Mark Booking as Paid Handler
 * Manually mark a booking as fully paid (for cash/bank transfers)
 */

// Initialize
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../classes/Booking.php';
require_once __DIR__ . '/../classes/Email.php';

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

    // Calculate outstanding amount
    $outstanding = $bookingData['amount_outstanding'];

    if ($outstanding <= 0) {
        $_SESSION['error'] = 'Booking is already fully paid';
        redirect(url('admin/booking-detail.php?id=' . $bookingId));
    }

    // Add payment record
    $paymentId = $booking->addPayment(
        $outstanding,
        $bookingData['payment_method'],
        [
            'payment_type' => 'manual',
            'status' => 'succeeded',
            'admin_notes' => 'Manually marked as paid by ' . currentAdminUsername(),
            'processed_by_admin_id' => currentAdminId()
        ]
    );

    // Update booking status to confirmed
    $booking->update(['booking_status' => 'confirmed']);

    // Send payment receipt email
    try {
        $email = new Email();
        $email->sendPaymentReceipt($paymentId);
    } catch (Exception $e) {
        error_log("Email Error: " . $e->getMessage());
        // Don't fail the payment if email fails
    }

    $_SESSION['success'] = 'Booking marked as fully paid successfully!';
    redirect(url('admin/booking-detail.php?id=' . $bookingId));

} catch (Exception $e) {
    error_log("Mark Paid Error: " . $e->getMessage());
    $_SESSION['error'] = 'Failed to mark booking as paid: ' . $e->getMessage();
    redirect(url('admin/booking-detail.php?id=' . $bookingId));
}
