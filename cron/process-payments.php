#!/usr/bin/env php
<?php
/**
 * Process Scheduled Payments
 * Runs daily to charge installment payments that are due
 *
 * Cron schedule: 0 9 * * * (Daily at 9am)
 */

 file_put_contents('/home/xvn00ltbgeh7/cron-debug.txt', "Process Payments started at " . date('Y-m-d H:i:s') . "\n", FILE_APPEND);


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

    // Find all payment schedules due today or overdue (pending or failed retries)
    $duePayments = $db->fetchAll(
        "SELECT ps.*, b.booker_name, b.booker_email, b.stripe_customer_id, b.stripe_payment_method_id
        FROM payment_schedules ps
        JOIN bookings b ON ps.booking_id = b.id
        WHERE ps.attempt_count < 3
        AND (
            (ps.status = 'pending' AND ps.due_date <= CURDATE())
            OR (ps.status = 'failed' AND ps.next_retry_date IS NOT NULL AND ps.next_retry_date <= CURDATE())
        )
        AND (b.discount_amount < b.total_amount OR b.discount_amount IS NULL OR b.discount_amount = 0)
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

        // Re-check schedule status just before charging (race condition guard)
        $freshSchedule = $db->fetchOne(
            "SELECT status, stripe_payment_intent_id FROM payment_schedules WHERE id = ?",
            [$scheduleId]
        );
        if ($freshSchedule && ($freshSchedule['status'] === 'paid' || !empty($freshSchedule['stripe_payment_intent_id']))) {
            logMessage("[$timestamp] SKIPPED: Schedule #{$scheduleId} already paid/charged, avoiding double charge", $logFile);
            continue;
        }

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
                // Update schedule status to 'paid' and store payment_intent_id to prevent double charging
                $db->execute(
                    "UPDATE payment_schedules
                    SET status = 'paid',
                        stripe_payment_intent_id = ?,
                        last_attempt_date = NOW()
                    WHERE id = ?",
                    [$result['payment_intent_id'], $scheduleId]
                );

                // NOTE: Webhook will record the payment in the payments table
                logMessage("[$timestamp] SUCCESS: Payment charged for booking #{$bookingId}, installment #{$installmentNumber}", $logFile);
                logMessage("[$timestamp] Payment Intent: {$result['payment_intent_id']} - Schedule marked as paid", $logFile);
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
