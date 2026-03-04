<?php
/**
 * Customer Booking PDF Export
 * Downloads booking confirmation as PDF for printing/check-in
 */

// Initialize
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/customer-auth.php';
require_once __DIR__ . '/../classes/Booking.php';

use Dompdf\Dompdf;
use Dompdf\Options;

// Require authentication
requireCustomerAuth();

$customerId = currentCustomerId();
$db = Database::getInstance();

// Load booking data
try {
    $booking = new Booking($customerId);
    $bookingData = $booking->getData();
    $attendees = $booking->getAttendees();
    $payments = $booking->getPayments();
} catch (Exception $e) {
    die('Failed to load booking data');
}

// Log GDPR action
logGDPRAction($customerId, 'data_export', 'Customer downloaded booking PDF');

// Use shared PDF generation function
require_once __DIR__ . '/../includes/generate-booking-pdf.php';
$html = generateBookingPDF($bookingData, $attendees, $payments);

// Configure Dompdf
$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true);
$options->set('defaultFont', 'Arial');

// Create PDF
$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

// Output PDF
$filename = 'ECHO2026-Booking-' . $bookingData['booking_reference'] . '.pdf';
$dompdf->stream($filename, ['Attachment' => true]);
exit;
