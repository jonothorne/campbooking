<?php
/**
 * Email Handler
 * Sends transactional emails using PHPMailer
 */

require_once __DIR__ . '/../includes/functions.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class Email
{
    private $mailer;
    private $db;

    public function __construct()
    {
        $this->db = db();
        $this->mailer = new PHPMailer(true);
        $this->configure();
    }

    /**
     * Configure PHPMailer with SMTP settings
     */
    private function configure()
    {
        try {
            $smtpHost = env('SMTP_HOST');
            $smtpPort = env('SMTP_PORT', 587);
            $smtpAuthRequired = env('SMTP_AUTH_REQUIRED', 'true') === 'true';

            // Server settings
            $this->mailer->isSMTP();
            $this->mailer->Host = $smtpHost;
            $this->mailer->Port = $smtpPort;

            // Authentication (optional for local/GoDaddy relay)
            if ($smtpAuthRequired) {
                $this->mailer->SMTPAuth = true;
                $this->mailer->Username = env('SMTP_USER');
                $this->mailer->Password = env('SMTP_PASS');

                // Only use encryption if not localhost
                if ($smtpHost !== 'localhost' && $smtpHost !== '127.0.0.1') {
                    $this->mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                }
            } else {
                // No authentication (for GoDaddy localhost relay)
                $this->mailer->SMTPAuth = false;
                $this->mailer->SMTPAutoTLS = false;
            }

            // Sender
            $this->mailer->setFrom(
                env('SMTP_FROM_EMAIL', env('SMTP_USER', 'noreply@example.com')),
                env('SMTP_FROM_NAME', 'Alive Church Camp')
            );

            // Encoding
            $this->mailer->CharSet = 'UTF-8';
            $this->mailer->isHTML(true);
        } catch (Exception $e) {
            error_log("Email Configuration Error: {$e->getMessage()}");
        }
    }

    /**
     * Send booking confirmation email
     */
    public function sendBookingConfirmation($bookingId)
    {
        try {
            $booking = new Booking($bookingId);
            $bookingData = $booking->getData();
            $attendees = $booking->getAttendees();

            $recipient = $bookingData['booker_email'];
            $subject = "Camp Booking Confirmation - {$bookingData['booking_reference']}";

            $data = [
                'booking' => $bookingData,
                'attendees' => $attendees,
                'booking_reference' => $bookingData['booking_reference'],
                'booker_name' => $bookingData['booker_name'],
                'total_amount' => $bookingData['total_amount'],
                'payment_method' => $bookingData['payment_method'],
                'payment_plan' => $bookingData['payment_plan'],
                'bank_reference' => getBankTransferReference($bookingData['booker_name'])
            ];

            $body = $this->loadTemplate('booking-confirmation', $data);

            return $this->send($recipient, $subject, $body, $bookingId, 'booking_confirmation');

        } catch (Exception $e) {
            error_log("Booking Confirmation Email Error: {$e->getMessage()}");
            return false;
        }
    }

    /**
     * Send payment receipt email
     */
    public function sendPaymentReceipt($paymentId)
    {
        try {
            $payment = $this->db->fetchOne("SELECT * FROM payments WHERE id = ?", [$paymentId]);

            if (!$payment) {
                throw new Exception("Payment not found: {$paymentId}");
            }

            $booking = new Booking($payment['booking_id']);
            $bookingData = $booking->getData();

            $recipient = $bookingData['booker_email'];
            $subject = "Payment Receipt - {$bookingData['booking_reference']}";

            $data = [
                'booking' => $bookingData,
                'payment' => $payment,
                'booking_reference' => $bookingData['booking_reference'],
                'booker_name' => $bookingData['booker_name'],
                'payment_amount' => $payment['amount'],
                'payment_date' => $payment['payment_date'],
                'amount_paid' => $bookingData['amount_paid'],
                'amount_outstanding' => $bookingData['amount_outstanding']
            ];

            $body = $this->loadTemplate('payment-receipt', $data);

            return $this->send($recipient, $subject, $body, $payment['booking_id'], 'payment_receipt');

        } catch (Exception $e) {
            error_log("Payment Receipt Email Error: {$e->getMessage()}");
            return false;
        }
    }

    /**
     * Send payment reminder email
     */
    public function sendPaymentReminder($scheduleId)
    {
        try {
            $schedule = $this->db->fetchOne("SELECT * FROM payment_schedules WHERE id = ?", [$scheduleId]);

            if (!$schedule || $schedule['status'] !== 'pending') {
                return false;
            }

            $booking = new Booking($schedule['booking_id']);
            $bookingData = $booking->getData();

            $recipient = $bookingData['booker_email'];
            $subject = "Payment Reminder - {$bookingData['booking_reference']}";

            $data = [
                'booking' => $bookingData,
                'schedule' => $schedule,
                'booking_reference' => $bookingData['booking_reference'],
                'booker_name' => $bookingData['booker_name'],
                'payment_amount' => $schedule['amount'],
                'due_date' => $schedule['due_date'],
                'installment_number' => $schedule['installment_number'],
                'days_until_due' => daysUntil($schedule['due_date'])
            ];

            $body = $this->loadTemplate('payment-reminder', $data);

            return $this->send($recipient, $subject, $body, $schedule['booking_id'], 'payment_reminder');

        } catch (Exception $e) {
            error_log("Payment Reminder Email Error: {$e->getMessage()}");
            return false;
        }
    }

    /**
     * Send failed payment notification
     */
    public function sendPaymentFailed($scheduleId)
    {
        try {
            $schedule = $this->db->fetchOne("SELECT * FROM payment_schedules WHERE id = ?", [$scheduleId]);

            if (!$schedule) {
                return false;
            }

            $booking = new Booking($schedule['booking_id']);
            $bookingData = $booking->getData();

            $recipient = $bookingData['booker_email'];
            $subject = "Payment Failed - {$bookingData['booking_reference']}";

            $data = [
                'booking' => $bookingData,
                'schedule' => $schedule,
                'booking_reference' => $bookingData['booking_reference'],
                'booker_name' => $bookingData['booker_name'],
                'payment_amount' => $schedule['amount'],
                'due_date' => $schedule['due_date'],
                'installment_number' => $schedule['installment_number'],
                'attempt_count' => $schedule['attempt_count'],
                'next_retry_date' => $schedule['next_retry_date']
            ];

            $body = $this->loadTemplate('payment-failed', $data);

            return $this->send($recipient, $subject, $body, $schedule['booking_id'], 'payment_failed');

        } catch (Exception $e) {
            error_log("Payment Failed Email Error: {$e->getMessage()}");
            return false;
        }
    }

    /**
     * Send email
     */
    private function send($recipient, $subject, $body, $bookingId, $type)
    {
        try {
            $this->mailer->clearAddresses();
            $this->mailer->addAddress($recipient);
            $this->mailer->Subject = $subject;
            $this->mailer->Body = $body;

            $sent = $this->mailer->send();

            // Log email
            $this->logEmail($bookingId, $recipient, $type, $subject, $sent ? 'sent' : 'failed', null);

            // Debug logging
            error_log("Email send result for {$type} to {$recipient}: " . ($sent ? 'SUCCESS' : 'FAILED'));

            return $sent;

        } catch (Exception $e) {
            $this->logEmail($bookingId, $recipient, $type, $subject, 'failed', $e->getMessage());
            error_log("Email Send Exception for {$type} to {$recipient}: {$e->getMessage()}");
            return false;
        }
    }

    /**
     * Load email template
     */
    private function loadTemplate($templateName, $data)
    {
        $templatePath = __DIR__ . "/../templates/emails/{$templateName}.php";

        if (!file_exists($templatePath)) {
            throw new Exception("Email template not found: {$templateName}");
        }

        // Extract data to variables
        extract($data);

        // Start output buffering
        ob_start();
        include $templatePath;
        $content = ob_get_clean();

        return $content;
    }

    /**
     * Log email to database
     */
    private function logEmail($bookingId, $recipient, $type, $subject, $status, $errorMessage = null)
    {
        try {
            $sql = "INSERT INTO email_logs
                    (booking_id, recipient_email, email_type, subject, status, error_message, sent_at)
                    VALUES (?, ?, ?, ?, ?, ?, NOW())";

            $this->db->insert($sql, [
                $bookingId,
                $recipient,
                $type,
                $subject,
                $status,
                $errorMessage
            ]);
        } catch (Exception $e) {
            error_log("Email Log Error: {$e->getMessage()}");
        }
    }
}
