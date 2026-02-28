#!/usr/bin/env php
<?php
/**
 * Process Scheduled Payments
 * Runs daily to charge installment payments that are due
 *
 * Cron schedule: 0 9 * * * (Daily at 9am)
 */

// Initialize
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../classes/Booking.php';
require_once __DIR__ . '/../classes/StripeHandler.php';
require_once __DIR__ . '/../classes/Email.php';

$logFile = 'payments.log';  // Just filename - logMessage() will prepend LOGS_PATH
$timestamp = date('Y-m-d H:i:s');

logMessage("[$timestamp] Starting payment processing", $logFile);

try {
    $db = Database::getInstance();
    $stripe = new StripeHandler();

    // Find all payment schedules due today or overdue
    $duePayments = $db->fetchAll(
        "SELECT ps.*, b.booker_name, b.booker_email, b.stripe_customer_id, b.stripe_payment_method_id
        FROM payment_schedules ps
        JOIN bookings b ON ps.booking_id = b.id
        WHERE ps.status = 'pending'
        AND ps.due_date <= CURDATE()
        AND ps.attempt_count < 3
        ORDER BY ps.due_date ASC"
    );

    if (empty($duePayments)) {
        logMessage("[$timestamp] No payments due today", $logFile);
        exit(0);
    }

    logMessage("[$timestamp] Found " . count($duePayments) . " payment(s) to process", $logFile);

    $successCount = 0;
    $failureCount = 0;

    foreach ($duePayments as $schedule) {
        $bookingId = $schedule['booking_id'];
        $scheduleId = $schedule['id'];
        $installmentNumber = $schedule['installment_number'];
        $amount = $schedule['amount'];

        logMessage("[$timestamp] Processing booking #{$bookingId}, installment #{$installmentNumber}, amount: £{$amount}", $logFile);

        // Check if booking has saved payment method
        if (!$schedule['stripe_customer_id'] || !$schedule['stripe_payment_method_id']) {
            logMessage("[$timestamp] ERROR: No payment method saved for booking #{$bookingId}", $logFile);
            $failureCount++;
            continue;
        }

        try {
            // Charge the saved payment method
            $result = $stripe->chargeSavedPaymentMethod(
                $schedule['stripe_payment_method_id'],
                $schedule['stripe_customer_id'],
                $amount,
                $bookingId,
                $installmentNumber
            );

            if ($result['status'] === 'succeeded' || $result['status'] === 'requires_capture') {
                // Payment charge initiated successfully
                // NOTE: Do NOT record payment here - the webhook will handle it when payment_intent.succeeded fires

                logMessage("[$timestamp] SUCCESS: Payment charged for booking #{$bookingId}, installment #{$installmentNumber}", $logFile);
                logMessage("[$timestamp] Payment Intent: {$result['payment_intent_id']} - Webhook will record payment", $logFile);
                $successCount++;

            } else {
                // Payment failed
                $errorMsg = $result['error'] ?? 'Unknown error';

                // Update schedule
                $db->execute(
                    "UPDATE payment_schedules
                    SET status = 'failed',
                        attempt_count = attempt_count + 1,
                        last_attempt_date = NOW(),
                        next_retry_date = DATE_ADD(NOW(), INTERVAL 2 DAY)
                    WHERE id = ?",
                    [$scheduleId]
                );

                // Record failed payment
                $booking = new Booking($bookingId);
                $booking->addPayment(
                    $amount,
                    'stripe',
                    [
                        'payment_type' => 'installment',
                        'status' => 'failed',
                        'admin_notes' => "Failed automatic payment: {$errorMsg}"
                    ]
                );

                // Get updated schedule
                $updatedSchedule = $db->fetchOne(
                    "SELECT * FROM payment_schedules WHERE id = ?",
                    [$scheduleId]
                );

                // Send failed payment email if less than 3 attempts
                if ($updatedSchedule['attempt_count'] < 3) {
                    try {
                        $email = new Email();
                        $email->sendPaymentFailed($scheduleId);
                    } catch (Exception $e) {
                        logMessage("[$timestamp] Email error: " . $e->getMessage(), $logFile);
                    }
                }

                logMessage("[$timestamp] FAILED: Payment for booking #{$bookingId} - {$errorMsg}", $logFile);
                $failureCount++;
            }

        } catch (Exception $e) {
            logMessage("[$timestamp] ERROR processing booking #{$bookingId}: " . $e->getMessage(), $logFile);
            $failureCount++;
        }

        // Small delay to avoid rate limits
        usleep(500000); // 0.5 seconds
    }

    logMessage("[$timestamp] Payment processing complete. Success: {$successCount}, Failed: {$failureCount}", $logFile);

} catch (Exception $e) {
    logMessage("[$timestamp] CRITICAL ERROR: " . $e->getMessage(), $logFile);
    exit(1);
}

exit(0);
