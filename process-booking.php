<?php
/**
 * Process Booking Form Submission
 * Validates, creates booking, and redirects appropriately
 */

// Initialize
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/sanitize.php';
require_once __DIR__ . '/classes/Booking.php';
require_once __DIR__ . '/classes/Email.php';
require_once __DIR__ . '/classes/StripeHandler.php';

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/book/');
}

// Start session for error messages
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verify CSRF token
requireCsrfToken();

// Prevent duplicate submission (double-click protection)
$submissionToken = $_POST['submission_token'] ?? '';
if (!empty($submissionToken) && isset($_SESSION['last_submission_token']) && $_SESSION['last_submission_token'] === $submissionToken) {
    // Duplicate submission detected
    $_SESSION['booking_error'] = 'This booking has already been submitted. Please check your booking confirmation.';
    redirect('/book/?error=1');
}

// Store submission token to prevent duplicates
if (!empty($submissionToken)) {
    $_SESSION['last_submission_token'] = $submissionToken;
}

try {
    // ============================================
    // Step 1: Validate and Sanitize Booking Data
    // ============================================

    $bookingData = sanitizeBookingData($_POST);

    if (isset($bookingData['error'])) {
        throw new Exception($bookingData['error']);
    }

    // ============================================
    // Step 2: Validate and Sanitize Attendees
    // ============================================

    if (!isset($_POST['attendees']) || !is_array($_POST['attendees']) || empty($_POST['attendees'])) {
        throw new Exception('At least one attendee is required');
    }

    $attendees = [];
    $totalAmount = 0;

    foreach ($_POST['attendees'] as $index => $attendeePost) {
        $attendeeData = sanitizeAttendeeData($attendeePost);

        if (isset($attendeeData['error'])) {
            throw new Exception("Attendee " . ($index + 1) . ": " . $attendeeData['error']);
        }

        $attendees[] = $attendeeData;
        $totalAmount += $attendeeData['ticket_price'];
    }

    // Validate total amount
    if ($totalAmount <= 0) {
        throw new Exception('Booking total must be greater than zero');
    }

    // ============================================
    // Step 3: Validate Payment Method & Plan
    // ============================================

    // For non-Stripe payments, only allow "full" payment plan
    if ($bookingData['payment_method'] !== 'stripe' && $bookingData['payment_plan'] !== 'full') {
        $bookingData['payment_plan'] = 'full';
    }

    // ============================================
    // Step 4: Create Booking
    // ============================================

    // Create booking (transaction handled inside Booking::create)
    $booking = new Booking();
    $bookingId = $booking->create($bookingData, $attendees);

    // Create payment schedule if installments selected
    if (in_array($bookingData['payment_plan'], ['monthly', 'three_payments'])) {
        $booking->createPaymentSchedule($bookingData['payment_plan']);
    }

    // ============================================
    // Step 5: Handle Payment Method
    // ============================================

    if ($bookingData['payment_method'] === 'stripe') {
        // Stripe payment processing
        try {
            $stripe = new StripeHandler();
            $bookingRef = $booking->getReference();
            $bookerData = $booking->getData();

            // Store booking reference in session
            $_SESSION['booking_reference'] = $bookingRef;
            $_SESSION['pending_booking_id'] = $bookingId;

            // Store form data in case user cancels payment
            $_SESSION['booking_form_data'] = [
                'booker' => $bookingData,
                'attendees' => $attendees,
                'total_amount' => $totalAmount
            ];

            if ($bookingData['payment_plan'] === 'full') {
                // One-time payment - create Payment Intent
                $result = $stripe->createPaymentIntent(
                    $bookerData['total_amount'],
                    $bookingId,
                    $bookerData['booker_email'],
                    "Camp Booking {$bookingRef}"
                );

                // Store client secret and intent ID
                $_SESSION['stripe_client_secret'] = $result['client_secret'];
                $_SESSION['stripe_is_setup_intent'] = false;

                // Update booking with payment intent ID
                $booking->update([
                    'stripe_payment_intent_id' => $result['payment_intent_id']
                ]);

            } else {
                // Installment payments - create Setup Intent to save payment method
                $result = $stripe->createSetupIntent(
                    $bookingId,
                    $bookerData['booker_email'],
                    $bookerData['booker_name']
                );

                // Store client secret and customer ID
                $_SESSION['stripe_client_secret'] = $result['client_secret'];
                $_SESSION['stripe_is_setup_intent'] = true;

                // Update booking with Stripe customer ID and setup intent ID
                $booking->update([
                    'stripe_customer_id' => $result['customer_id'],
                    'stripe_setup_intent_id' => $result['setup_intent_id']
                ]);
            }

            // Redirect to Stripe checkout page
            redirect(url('stripe-checkout.php'));

        } catch (Exception $e) {
            error_log("Stripe Error: " . $e->getMessage());
            throw new Exception("Payment processing failed. Please try again or choose a different payment method.");
        }

    } elseif ($bookingData['payment_method'] === 'bank_transfer') {
        // Bank transfer - booking complete, awaiting payment
        $booking->update(['booking_status' => 'confirmed']);

        // Send confirmation email with bank transfer details
        try {
            $email = new Email();
            $email->sendBookingConfirmation($bookingId);
        } catch (Exception $e) {
            error_log("Email Error: " . $e->getMessage());
            // Don't fail the booking if email fails
        }

        // Redirect to success page
        redirect(url('payment-success.php?booking=' . $booking->getReference()));

    } elseif ($bookingData['payment_method'] === 'cash') {
        // Cash payment - booking complete, awaiting payment
        $booking->update(['booking_status' => 'confirmed']);

        // Send confirmation email
        $emailSent = false;
        try {
            $email = new Email();
            $emailSent = $email->sendBookingConfirmation($bookingId);
        } catch (Exception $e) {
            error_log("Email Error: " . $e->getMessage());
            // Don't fail the booking if email fails
        }

        // Store email failure warning in session
        if (!$emailSent) {
            $_SESSION['email_warning'] = 'Your booking was successful, but we couldn\'t send the confirmation email. Please contact us if you don\'t receive it within 24 hours.';
        }

        // Redirect to success page
        redirect(url('payment-success.php?booking=' . $booking->getReference()));
    }

} catch (Exception $e) {
    // Log error
    error_log("Booking Error: " . $e->getMessage());

    // Store error in session
    $_SESSION['booking_error'] = $e->getMessage();

    // Redirect back to form
    redirect('/book/?error=1');
}
