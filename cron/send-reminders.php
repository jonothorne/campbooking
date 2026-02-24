#!/usr/bin/env php
<?php
/**
 * Send Payment Reminders
 * Sends email reminders for payments due in 3 days
 *
 * Cron schedule: 0 10 * * * (Daily at 10am)
 */

// Initialize
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../classes/Email.php';

$logFile = __DIR__ . '/../logs/payments.log';
$timestamp = date('Y-m-d H:i:s');

logMessage("[$timestamp] Starting payment reminder process", $logFile);

try {
    $db = Database::getInstance();

    // Find payment schedules due in 3 days
    $upcomingPayments = $db->fetchAll(
        "SELECT ps.*, b.booker_name, b.booker_email
        FROM payment_schedules ps
        JOIN bookings b ON ps.booking_id = b.id
        WHERE ps.status = 'pending'
        AND ps.due_date = DATE_ADD(CURDATE(), INTERVAL 3 DAY)
        ORDER BY b.id ASC"
    );

    if (empty($upcomingPayments)) {
        logMessage("[$timestamp] No payment reminders to send", $logFile);
        exit(0);
    }

    logMessage("[$timestamp] Found " . count($upcomingPayments) . " reminder(s) to send", $logFile);

    $successCount = 0;
    $failureCount = 0;

    foreach ($upcomingPayments as $schedule) {
        $bookingId = $schedule['booking_id'];
        $scheduleId = $schedule['id'];
        $installmentNumber = $schedule['installment_number'];
        $amount = $schedule['amount'];
        $dueDate = $schedule['due_date'];

        logMessage("[$timestamp] Sending reminder for booking #{$bookingId}, installment #{$installmentNumber}", $logFile);

        try {
            $email = new Email();
            $sent = $email->sendPaymentReminder($scheduleId);

            if ($sent) {
                logMessage("[$timestamp] SUCCESS: Reminder sent for booking #{$bookingId}", $logFile);
                $successCount++;
            } else {
                logMessage("[$timestamp] FAILED: Could not send reminder for booking #{$bookingId}", $logFile);
                $failureCount++;
            }

        } catch (Exception $e) {
            logMessage("[$timestamp] ERROR sending reminder for booking #{$bookingId}: " . $e->getMessage(), $logFile);
            $failureCount++;
        }

        // Small delay
        usleep(200000); // 0.2 seconds
    }

    logMessage("[$timestamp] Reminder process complete. Success: {$successCount}, Failed: {$failureCount}", $logFile);

} catch (Exception $e) {
    logMessage("[$timestamp] CRITICAL ERROR: " . $e->getMessage(), $logFile);
    exit(1);
}

exit(0);
