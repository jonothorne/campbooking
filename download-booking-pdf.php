<?php
/**
 * Public Booking PDF Download
 * Allows downloading booking confirmation PDF with booking reference
 */

// Initialize
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/classes/Booking.php';

use Dompdf\Dompdf;
use Dompdf\Options;

// Get booking reference from URL
$bookingReference = $_GET['booking'] ?? '';

if (empty($bookingReference)) {
    die('Booking reference required');
}

// Load booking data
try {
    $db = Database::getInstance();
    $bookingData = $db->fetchOne(
        "SELECT * FROM bookings WHERE booking_reference = ?",
        [$bookingReference]
    );

    if (!$bookingData) {
        die('Booking not found');
    }

    $booking = new Booking($bookingData['id']);
    $attendees = $booking->getAttendees();
    $payments = $booking->getPayments();
} catch (Exception $e) {
    die('Failed to load booking data');
}

// Include the PDF generation code
require_once __DIR__ . '/includes/generate-booking-pdf.php';

$pdfContent = generateBookingPDF($bookingData, $attendees, $payments);

// Configure Dompdf
$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true);
$options->set('defaultFont', 'Arial');

// Create PDF
$dompdf = new Dompdf($options);
$dompdf->loadHtml($pdfContent);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

// Output PDF
$filename = 'ECHO2026-Booking-' . $bookingData['booking_reference'] . '.pdf';
$dompdf->stream($filename, ['Attachment' => true]);
exit;
