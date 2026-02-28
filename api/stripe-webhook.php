<?php
/**
 * Stripe Webhook Handler
 * Processes webhook events from Stripe for payment status updates
 */

// Initialize
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../classes/Booking.php';
require_once __DIR__ . '/../classes/StripeHandler.php';
require_once __DIR__ . '/../classes/Email.php';

// Log webhook receipt
$logFile = 'webhooks.log';  // Just filename - logMessage() will prepend LOGS_PATH
$timestamp = date('Y-m-d H:i:s');

// Get raw POST body
$payload = @file_get_contents('php://input');
$sigHeader = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';

// Log incoming webhook
logMessage("[$timestamp] Webhook received - Signature: " . substr($sigHeader, 0, 20) . "...", $logFile);

try {
    // Verify webhook signature
    $stripe = new StripeHandler();
    $event = $stripe->constructWebhookEvent($payload, $sigHeader);

    logMessage("[$timestamp] Event Type: {$event->type}, Event ID: {$event->id}", $logFile);

    // Check for replay attack - verify event hasn't been processed before
    $db = Database::getInstance();
    $existing = $db->fetchOne(
        "SELECT id FROM webhook_events WHERE stripe_event_id = ?",
        [$event->id]
    );

    if ($existing) {
        logMessage("[$timestamp] Duplicate event detected - already processed: {$event->id}", $logFile);
        http_response_code(200); // Return 200 to acknowledge receipt
        echo json_encode(['status' => 'duplicate', 'message' => 'Event already processed']);
        exit;
    }

    // Record this event to prevent replay attacks
    $db->insert(
        "INSERT INTO webhook_events (stripe_event_id, event_type, processed_at) VALUES (?, ?, NOW())",
        [$event->id, $event->type]
    );

    // Handle different event types
    switch ($event->type) {
        case 'payment_intent.succeeded':
            handlePaymentIntentSucceeded($event->data->object);
            break;

        case 'payment_intent.payment_failed':
            handlePaymentIntentFailed($event->data->object);
            break;

        case 'setup_intent.succeeded':
            handleSetupIntentSucceeded($event->data->object);
            break;

        case 'setup_intent.setup_failed':
            handleSetupIntentFailed($event->data->object);
            break;

        case 'charge.refunded':
            handleChargeRefunded($event->data->object);
            break;

        default:
            logMessage("[$timestamp] Unhandled event type: {$event->type}", $logFile);
    }

    // Return 200 OK
    http_response_code(200);
    echo json_encode(['status' => 'success']);

} catch (Exception $e) {
    logMessage("[$timestamp] ERROR: " . $e->getMessage(), $logFile);
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}

/**
 * Handle successful payment intent
 */
function handlePaymentIntentSucceeded($paymentIntent)
{
    global $logFile, $timestamp;

    $bookingId = $paymentIntent->metadata->booking_id ?? null;
    $paymentType = $paymentIntent->metadata->payment_type ?? 'unknown';
    $installmentNumber = $paymentIntent->metadata->installment_number ?? null;

    if (!$bookingId) {
        logMessage("[$timestamp] No booking ID in payment intent metadata", $logFile);
        return;
    }

    try {
        $booking = new Booking((int)$bookingId);
        $bookingData = $booking->getData();
        $amount = StripeHandler::formatAmountFromPence($paymentIntent->amount);

        logMessage("[$timestamp] Payment succeeded - Booking #{$bookingId}, Amount: £{$amount}", $logFile);

        // Validate payment amount matches expected amount
        $expectedAmount = $bookingData['total_amount'];
        $amountDifference = abs($amount - $expectedAmount);

        // Allow small rounding differences (1 penny)
        if ($amountDifference > 0.01 && $paymentType === 'full_payment') {
            logMessage("[$timestamp] WARNING: Amount mismatch! Expected: £{$expectedAmount}, Received: £{$amount}", $logFile);
            // Continue processing but log the discrepancy for admin review
        }

        // Record payment
        $paymentId = $booking->addPayment(
            $amount,
            'stripe',
            [
                'payment_type' => $paymentType,
                'status' => 'succeeded',
                'stripe_payment_intent_id' => $paymentIntent->id,
                'stripe_charge_id' => $paymentIntent->charges->data[0]->id ?? null,
                'admin_notes' => $installmentNumber ? "Installment #{$installmentNumber}" : 'One-time payment'
            ]
        );

        // Reload booking data to get updated amounts
        $booking->getData(); // This calls load() internally
        $updatedBookingData = $booking->getData();
        if ($updatedBookingData['amount_outstanding'] <= 0) {
            $booking->update(['booking_status' => 'confirmed']);
        }

        // If installment, mark schedule as paid and link to payment
        if ($installmentNumber) {
            $db = Database::getInstance();
            $db->execute(
                "UPDATE payment_schedules
                SET status = 'paid', payment_id = ?
                WHERE booking_id = ? AND installment_number = ?",
                [$paymentId, $bookingId, $installmentNumber]
            );
        }

        // Send emails
        try {
            $email = new Email();

            // Send booking confirmation for first payment
            if ($installmentNumber == 1 || $paymentType === 'full_payment') {
                $email->sendBookingConfirmation($bookingId);
                logMessage("[$timestamp] Booking confirmation email sent", $logFile);
            }

            // Send payment receipt
            $email->sendPaymentReceipt($paymentId);
            logMessage("[$timestamp] Payment receipt email sent", $logFile);
        } catch (Exception $e) {
            logMessage("[$timestamp] Email error: " . $e->getMessage(), $logFile);
        }

        logMessage("[$timestamp] Payment processed successfully - Payment ID: {$paymentId}", $logFile);

    } catch (Exception $e) {
        logMessage("[$timestamp] Error processing payment: " . $e->getMessage(), $logFile);
        throw $e;
    }
}

/**
 * Handle failed payment intent
 */
function handlePaymentIntentFailed($paymentIntent)
{
    global $logFile, $timestamp;

    $bookingId = $paymentIntent->metadata->booking_id ?? null;
    $installmentNumber = $paymentIntent->metadata->installment_number ?? null;

    if (!$bookingId) {
        return;
    }

    try {
        $booking = new Booking((int)$bookingId);
        $amount = StripeHandler::formatAmountFromPence($paymentIntent->amount);

        logMessage("[$timestamp] Payment failed - Booking #{$bookingId}, Amount: £{$amount}", $logFile);

        // Record failed payment
        $booking->addPayment(
            $amount,
            'stripe',
            [
                'payment_type' => 'installment',
                'status' => 'failed',
                'stripe_payment_intent_id' => $paymentIntent->id,
                'admin_notes' => "Failed: " . ($paymentIntent->last_payment_error->message ?? 'Unknown error')
            ]
        );

        // If installment, update schedule
        if ($installmentNumber) {
            $db = Database::getInstance();

            // Increment attempt count
            $db->execute(
                "UPDATE payment_schedules
                SET status = 'failed',
                    attempt_count = attempt_count + 1,
                    last_attempt_date = NOW(),
                    next_retry_date = DATE_ADD(NOW(), INTERVAL 2 DAY)
                WHERE booking_id = ? AND installment_number = ?",
                [$bookingId, $installmentNumber]
            );

            // Get updated schedule for email
            $schedule = $db->fetchOne(
                "SELECT * FROM payment_schedules WHERE booking_id = ? AND installment_number = ?",
                [$bookingId, $installmentNumber]
            );

            // Send failed payment email on all failures (up to 3 attempts)
            if ($schedule && $schedule['attempt_count'] <= 3) {
                try {
                    $email = new Email();
                    $email->sendPaymentFailed($schedule['id']);
                    logMessage("[$timestamp] Failed payment email sent (attempt {$schedule['attempt_count']}/3)", $logFile);
                } catch (Exception $e) {
                    logMessage("[$timestamp] Email error: " . $e->getMessage(), $logFile);
                }
            }

            // After 3 failures, notify admin
            if ($schedule && $schedule['attempt_count'] >= 3) {
                logMessage("[$timestamp] ALERT: Payment failed 3 times for booking #{$bookingId}, installment #{$installmentNumber}", $logFile);
                // TODO: Send admin alert email
            }
        }

    } catch (Exception $e) {
        logMessage("[$timestamp] Error processing failed payment: " . $e->getMessage(), $logFile);
    }
}

/**
 * Handle successful setup intent (payment method saved)
 */
function handleSetupIntentSucceeded($setupIntent)
{
    global $logFile, $timestamp;

    $bookingId = $setupIntent->metadata->booking_id ?? null;

    if (!$bookingId) {
        return;
    }

    try {
        $booking = new Booking((int)$bookingId);
        $db = Database::getInstance();

        logMessage("[$timestamp] Setup intent succeeded - Booking #{$bookingId}", $logFile);

        // Update booking with Stripe IDs
        $booking->update([
            'stripe_customer_id' => $setupIntent->customer,
            'stripe_payment_method_id' => $setupIntent->payment_method,
            'booking_status' => 'confirmed'
        ]);

        logMessage("[$timestamp] Payment method saved - Customer: {$setupIntent->customer}", $logFile);

        // Charge the first installment immediately
        $firstInstallment = $db->fetchOne(
            "SELECT * FROM payment_schedules
            WHERE booking_id = ? AND status = 'pending'
            ORDER BY due_date ASC
            LIMIT 1",
            [$bookingId]
        );

        if ($firstInstallment) {
            logMessage("[$timestamp] Charging first installment - Amount: £{$firstInstallment['amount']}", $logFile);

            try {
                $stripe = new StripeHandler();
                $result = $stripe->chargeSavedPaymentMethod(
                    $setupIntent->payment_method,
                    $setupIntent->customer,
                    $firstInstallment['amount'],
                    $bookingId,
                    $firstInstallment['installment_number']
                );

                logMessage("[$timestamp] First installment charged - Payment Intent: {$result['payment_intent_id']}", $logFile);
                logMessage("[$timestamp] Payment will be recorded when payment_intent.succeeded webhook fires", $logFile);

                // NOTE: Do NOT record payment here - the payment_intent.succeeded webhook will handle it
                // This prevents duplicate payment recording

            } catch (Exception $e) {
                logMessage("[$timestamp] Error charging first installment: " . $e->getMessage(), $logFile);
                // Don't throw - payment method is saved, cron will retry
            }
        }

    } catch (Exception $e) {
        logMessage("[$timestamp] Error processing setup intent: " . $e->getMessage(), $logFile);
    }
}

/**
 * Handle failed setup intent
 */
function handleSetupIntentFailed($setupIntent)
{
    global $logFile, $timestamp;

    $bookingId = $setupIntent->metadata->booking_id ?? null;

    if (!$bookingId) {
        return;
    }

    logMessage("[$timestamp] Setup intent failed - Booking #{$bookingId}", $logFile);

    // Could send notification email here
}

/**
 * Handle charge refunded
 */
function handleChargeRefunded($charge)
{
    global $logFile, $timestamp;

    // Get payment intent to find booking
    if (!isset($charge->payment_intent)) {
        return;
    }

    try {
        $db = Database::getInstance();

        // Find payment by payment intent ID
        $payment = $db->fetchOne(
            "SELECT * FROM payments WHERE stripe_payment_intent_id = ?",
            [$charge->payment_intent]
        );

        if (!$payment) {
            return;
        }

        $refundAmount = StripeHandler::formatAmountFromPence($charge->amount_refunded);

        logMessage("[$timestamp] Charge refunded - Payment #{$payment['id']}, Amount: £{$refundAmount}", $logFile);

        // Update payment status
        $db->execute(
            "UPDATE payments SET status = 'refunded' WHERE id = ?",
            [$payment['id']]
        );

        // Update booking amounts
        $booking = new Booking($payment['booking_id']);
        $bookingData = $booking->getData();

        $newAmountPaid = $bookingData['amount_paid'] - $refundAmount;
        $booking->update(['amount_paid' => max(0, $newAmountPaid)]);
        $booking->updatePaymentStatus();

    } catch (Exception $e) {
        logMessage("[$timestamp] Error processing refund: " . $e->getMessage(), $logFile);
    }
}
