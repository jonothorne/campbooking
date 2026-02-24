#!/usr/bin/env php
<?php
/**
 * Check Failed Payments
 * Retries failed payments and notifies admin after max attempts
 *
 * Cron schedule: 0 11 * * * (Daily at 11am)
 */

// Initialize
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../classes/Booking.php';
require_once __DIR__ . '/../classes/StripeHandler.php';
require_once __DIR__ . '/../classes/Email.php';

$logFile = __DIR__ . '/../logs/payments.log';
$timestamp = date('Y-m-d H:i:s');

logMessage("[$timestamp] Starting failed payment check", $logFile);

try {
    $db = Database::getInstance();
    $stripe = new StripeHandler();

    // Find failed payments ready for retry
    $failedPayments = $db->fetchAll(
        "SELECT ps.*, b.booker_name, b.booker_email, b.stripe_customer_id, b.stripe_payment_method_id
        FROM payment_schedules ps
        JOIN bookings b ON ps.booking_id = b.id
        WHERE ps.status = 'failed'
        AND ps.attempt_count < 3
        AND (ps.next_retry_date IS NULL OR ps.next_retry_date <= CURDATE())
        ORDER BY ps.last_attempt_date ASC"
    );

    if (empty($failedPayments)) {
        logMessage("[$timestamp] No failed payments to retry", $logFile);
    } else {
        logMessage("[$timestamp] Found " . count($failedPayments) . " failed payment(s) to retry", $logFile);

        foreach ($failedPayments as $schedule) {
            $bookingId = $schedule['booking_id'];
            $scheduleId = $schedule['id'];
            $installmentNumber = $schedule['installment_number'];
            $amount = $schedule['amount'];
            $attemptCount = $schedule['attempt_count'];

            logMessage("[$timestamp] Retrying booking #{$bookingId}, installment #{$installmentNumber} (attempt " . ($attemptCount + 1) . "/3)", $logFile);

            if (!$schedule['stripe_customer_id'] || !$schedule['stripe_payment_method_id']) {
                logMessage("[$timestamp] ERROR: No payment method for booking #{$bookingId}", $logFile);
                continue;
            }

            try {
                // Retry payment
                $result = $stripe->chargeSavedPaymentMethod(
                    $schedule['stripe_payment_method_id'],
                    $schedule['stripe_customer_id'],
                    $amount,
                    $bookingId,
                    $installmentNumber
                );

                if ($result['status'] === 'succeeded') {
                    // Success!
                    $booking = new Booking($bookingId);

                    $paymentId = $booking->addPayment(
                        $amount,
                        'stripe',
                        [
                            'payment_type' => 'installment',
                            'status' => 'succeeded',
                            'stripe_payment_intent_id' => $result['payment_intent_id'],
                            'stripe_charge_id' => $result['charge_id'],
                            'admin_notes' => "Retry successful - Installment #{$installmentNumber}"
                        ]
                    );

                    $db->execute(
                        "UPDATE payment_schedules
                        SET status = 'paid', paid_date = NOW(), last_attempt_date = NOW()
                        WHERE id = ?",
                        [$scheduleId]
                    );

                    // Send receipt
                    try {
                        $email = new Email();
                        $email->sendPaymentReceipt($paymentId);
                    } catch (Exception $e) {
                        logMessage("[$timestamp] Email error: " . $e->getMessage(), $logFile);
                    }

                    logMessage("[$timestamp] SUCCESS: Retry successful for booking #{$bookingId}", $logFile);

                } else {
                    // Still failed
                    $errorMsg = $result['error'] ?? 'Unknown error';

                    $db->execute(
                        "UPDATE payment_schedules
                        SET attempt_count = attempt_count + 1,
                            last_attempt_date = NOW(),
                            next_retry_date = DATE_ADD(NOW(), INTERVAL 3 DAY)
                        WHERE id = ?",
                        [$scheduleId]
                    );

                    // Check if max attempts reached
                    if (($attemptCount + 1) >= 3) {
                        logMessage("[$timestamp] MAX ATTEMPTS REACHED for booking #{$bookingId}", $logFile);

                        // TODO: Send notification to admin
                        // Could implement admin email notification here
                    }

                    logMessage("[$timestamp] FAILED: Retry failed for booking #{$bookingId} - {$errorMsg}", $logFile);
                }

            } catch (Exception $e) {
                logMessage("[$timestamp] ERROR retrying booking #{$bookingId}: " . $e->getMessage(), $logFile);
            }

            usleep(500000); // 0.5 seconds
        }
    }

    // Find payments that have reached max attempts
    $maxAttemptPayments = $db->fetchAll(
        "SELECT ps.*, b.booker_name, b.booker_email, b.booking_reference
        FROM payment_schedules ps
        JOIN bookings b ON ps.booking_id = b.id
        WHERE ps.status = 'failed'
        AND ps.attempt_count >= 3
        ORDER BY ps.last_attempt_date DESC
        LIMIT 10"
    );

    if (!empty($maxAttemptPayments)) {
        logMessage("[$timestamp] WARNING: " . count($maxAttemptPayments) . " payment(s) failed after max attempts", $logFile);

        foreach ($maxAttemptPayments as $schedule) {
            logMessage("[$timestamp] Max attempts: Booking #{$schedule['booking_id']} ({$schedule['booking_reference']}) - £{$schedule['amount']}", $logFile);
        }
    }

    logMessage("[$timestamp] Failed payment check complete", $logFile);

} catch (Exception $e) {
    logMessage("[$timestamp] CRITICAL ERROR: " . $e->getMessage(), $logFile);
    exit(1);
}

exit(0);
