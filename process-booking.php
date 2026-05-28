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
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/classes/Booking.php';
require_once __DIR__ . '/classes/Email.php';
require_once __DIR__ . '/classes/StripeHandler.php';

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(url(''));
}

// Start session for error messages
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verify CSRF token
requireCsrfToken();

// Verify idempotency token (prevents duplicate submissions)
$idempotencyToken = $_POST['idempotency_token'] ?? '';
if (empty($idempotencyToken)) {
    $_SESSION['booking_error'] = 'Invalid form submission. Please try again.';
    redirect(url('?error=1'));
}

// Check if this token matches the session (prevents replay from old forms)
if (!isset($_SESSION['idempotency_token']) || $_SESSION['idempotency_token'] !== $idempotencyToken) {
    $_SESSION['booking_error'] = 'This form has expired or was already submitted. Please try again.';
    redirect(url('?error=1'));
}

// Immediately clear the session token so it can't be reused
unset($_SESSION['idempotency_token']);

// Check if a booking with this idempotency token already exists in the database
$db = Database::getInstance();
$existingBooking = $db->fetchOne(
    "SELECT id, booking_reference FROM bookings WHERE idempotency_token = ?",
    [$idempotencyToken]
);

if ($existingBooking) {
    // Booking already exists - redirect to success page instead of creating duplicate
    redirect(url('payment-success.php?booking=' . $existingBooking['booking_reference']));
}

try {
    // ============================================
    // Step 1: Validate and Sanitize Booking Data
    // ============================================

    $bookingData = sanitizeBookingData($_POST);

    if (isset($bookingData['error'])) {
        throw new Exception($bookingData['error']);
    }

    // Attach idempotency token to booking data
    $bookingData['idempotency_token'] = $idempotencyToken;

    // ============================================
    // Step 1b: Check for existing booking with same email
    // ============================================

    $existingEmailBooking = $db->fetchOne(
        "SELECT id, booking_reference FROM bookings WHERE booker_email = ? AND booking_status != 'cancelled' AND event_year = ?",
        [$bookingData['booker_email'], EVENT_YEAR]
    );

    if ($existingEmailBooking) {
        $_SESSION['booking_error_email_exists'] = true;
        redirect(url('?error=1'));
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
    // Step 2b: Validate Discount Code (if provided)
    // ============================================

    $discountCode = null;
    $discountAmount = 0;

    if (!empty($bookingData['discount_code'])) {
        $discountCode = $db->fetchOne(
            "SELECT * FROM discount_codes WHERE code = ? AND event_year = ? AND is_active = 1",
            [$bookingData['discount_code'], EVENT_YEAR]
        );

        if (!$discountCode) {
            throw new Exception('Invalid or inactive discount code.');
        }

        if ($discountCode['expires_at'] && strtotime($discountCode['expires_at']) < time()) {
            throw new Exception('This discount code has expired.');
        }

        if ($discountCode['max_uses'] && $discountCode['times_used'] >= $discountCode['max_uses']) {
            throw new Exception('This discount code has reached its maximum uses.');
        }

        // Calculate discount
        switch ($discountCode['discount_type']) {
            case 'full':
                $discountAmount = $totalAmount;
                break;
            case 'percentage':
                $discountAmount = round($totalAmount * $discountCode['discount_value'] / 100, 2);
                break;
            case 'fixed':
                $discountAmount = min($totalAmount, (float)$discountCode['discount_value']);
                break;
        }

        $bookingData['discount_code_id'] = $discountCode['id'];
        $bookingData['discount_amount'] = $discountAmount;
    }

    // ============================================
    // Step 3: Validate Payment Method & Plan
    // ============================================

    // For non-Stripe payments, only allow single payment
    if ($bookingData['payment_method'] !== 'stripe' && $bookingData['payment_plan'] > 1) {
        $bookingData['payment_plan'] = 1;
    }

    // If fully funded, force single payment (no installments needed)
    $isFullyFunded = ($discountAmount >= $totalAmount);
    if ($isFullyFunded) {
        $bookingData['payment_plan'] = 1;
    }

    // ============================================
    // Step 4: Create Booking
    // ============================================

    // Create booking (transaction handled inside Booking::create)
    $booking = new Booking();
    $bookingId = $booking->create($bookingData, $attendees);

    // Increment discount code usage atomically (prevents race condition exceeding max_uses)
    if ($discountCode) {
        $rowsAffected = $db->execute(
            "UPDATE discount_codes SET times_used = times_used + 1 WHERE id = ? AND (max_uses IS NULL OR max_uses = 0 OR times_used < max_uses)",
            [$discountCode['id']]
        );
        if ($rowsAffected === 0) {
            // Code was used up between validation and here - clean up and fail
            $booking->delete();
            throw new Exception('This discount code has reached its maximum uses. Please try again without a discount code.');
        }
    }

    // Create payment schedule if multiple installments selected
    if ($bookingData['payment_plan'] > 1) {
        $booking->createPaymentSchedule($bookingData['payment_plan']);
    }

    // ============================================
    // Step 5: Handle Payment Method
    // ============================================

    // Fully funded bookings - create discount payment and mark as paid
    if ($isFullyFunded) {
        $db->insert(
            "INSERT INTO payments (booking_id, amount, payment_method, payment_type, status, admin_notes, payment_date)
             VALUES (?, ?, ?, 'discount', 'succeeded', ?, NOW())",
            [
                $bookingId,
                $totalAmount,
                $bookingData['payment_method'],
                'Fully funded via discount code: ' . $discountCode['code']
            ]
        );

        $booking->update([
            'booking_status' => 'confirmed',
            'payment_status' => 'paid',
            'amount_paid' => $totalAmount,
            'amount_outstanding' => 0.00,
        ]);

        // Send confirmation email
        try {
            $email = new Email();
            $email->sendBookingConfirmation($bookingId);
        } catch (Exception $e) {
            error_log("Email Error: " . $e->getMessage());
        }

        redirect(url('payment-success.php?booking=' . $booking->getReference()));
    }

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

            if ($bookingData['payment_plan'] == 1) {
                // One-time payment - create Payment Intent (use amount_outstanding which accounts for any discount)
                $result = $stripe->createPaymentIntent(
                    $bookerData['amount_outstanding'],
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
            // Clean up the orphaned booking since payment setup failed
            try {
                $booking->delete();
            } catch (Exception $deleteErr) {
                error_log("Failed to clean up booking after Stripe error: " . $deleteErr->getMessage());
            }
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
            error_log("Booking confirmation email result for booking #{$bookingId}: " . ($emailSent ? 'SUCCESS' : 'FAILED'));
        } catch (Exception $e) {
            error_log("Email Exception for booking #{$bookingId}: " . $e->getMessage());
            // Don't fail the booking if email fails
        }

        // Store email failure warning in session
        if (!$emailSent) {
            error_log("Setting email warning in session for booking #{$bookingId}");
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
    redirect(url('?error=1'));
}
