<?php
/**
 * Booking Class
 * Manages booking records and operations
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/sanitize.php';

class Booking {
    private $db;
    private $id;
    private $data;

    public function __construct($bookingId = null) {
        $this->db = Database::getInstance();

        if ($bookingId) {
            $this->id = $bookingId;
            $this->load();
        }
    }

    /**
     * Load booking data from database
     */
    private function load() {
        $this->data = $this->db->fetchOne(
            "SELECT * FROM bookings WHERE id = ?",
            [$this->id]
        );

        if (!$this->data) {
            throw new Exception("Booking not found");
        }
    }

    /**
     * Create new booking
     *
     * @param array $bookingData Sanitized booking data
     * @param array $attendees Array of attendee data
     * @return int Booking ID
     */
    public function create($bookingData, $attendees) {
        // Validate data
        if (empty($bookingData) || empty($attendees)) {
            throw new Exception("Booking data and attendees are required");
        }

        // Calculate total amount from attendees
        $totalAmount = 0;
        foreach ($attendees as $attendee) {
            $totalAmount += (float)($attendee['ticket_price'] ?? 0);
        }

        if ($totalAmount <= 0) {
            throw new Exception("Total amount must be greater than zero");
        }

        // Generate unique booking reference
        $bookingReference = $this->generateUniqueReference();

        // Start transaction
        $this->db->beginTransaction();

        try {
            // Insert booking
            $sql = "INSERT INTO bookings (
                booking_reference,
                booker_name,
                booker_email,
                booker_phone,
                num_tents,
                has_caravan,
                needs_tent_provided,
                special_requirements,
                payment_method,
                payment_plan,
                total_amount,
                amount_paid,
                amount_outstanding,
                booking_status,
                payment_status
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

            $bookingId = $this->db->insert($sql, [
                $bookingReference,
                $bookingData['booker_name'],
                $bookingData['booker_email'],
                $bookingData['booker_phone'],
                $bookingData['num_tents'] ?? 0,
                $bookingData['has_caravan'] ?? 0,
                $bookingData['needs_tent_provided'] ?? 0,
                $bookingData['special_requirements'] ?? '',
                $bookingData['payment_method'],
                $bookingData['payment_plan'],
                $totalAmount,
                0.00, // amount_paid
                $totalAmount, // amount_outstanding
                'pending',
                'unpaid'
            ]);

            // Insert attendees
            foreach ($attendees as $attendee) {
                $this->addAttendee($bookingId, $attendee);
            }

            // Commit transaction
            $this->db->commit();

            // Set instance ID
            $this->id = $bookingId;
            $this->load();

            return $bookingId;

        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    /**
     * Add attendee to booking
     *
     * @param int $bookingId
     * @param array $attendeeData
     * @return int Attendee ID
     */
    private function addAttendee($bookingId, $attendeeData) {
        // Prepare day ticket dates (JSON if present)
        $dayTicketDates = null;
        if (isset($attendeeData['day_ticket_dates']) && is_array($attendeeData['day_ticket_dates'])) {
            $dayTicketDates = json_encode($attendeeData['day_ticket_dates']);
        }

        $sql = "INSERT INTO attendees (
            booking_id,
            name,
            age,
            ticket_type,
            ticket_price,
            day_ticket_dates
        ) VALUES (?, ?, ?, ?, ?, ?)";

        return $this->db->insert($sql, [
            $bookingId,
            $attendeeData['name'],
            $attendeeData['age'],
            $attendeeData['ticket_type'],
            $attendeeData['ticket_price'],
            $dayTicketDates
        ]);
    }

    /**
     * Get booking data
     */
    public function getData() {
        return $this->data;
    }

    /**
     * Get booking ID
     */
    public function getId() {
        return $this->id;
    }

    /**
     * Get booking reference
     */
    public function getReference() {
        return $this->data['booking_reference'] ?? null;
    }

    /**
     * Get attendees for this booking
     */
    public function getAttendees() {
        return $this->db->fetchAll(
            "SELECT * FROM attendees WHERE booking_id = ? ORDER BY id ASC",
            [$this->id]
        );
    }

    /**
     * Get payments for this booking
     */
    public function getPayments() {
        return $this->db->fetchAll(
            "SELECT * FROM payments WHERE booking_id = ? ORDER BY payment_date DESC",
            [$this->id]
        );
    }

    /**
     * Get payment schedule for this booking
     */
    public function getPaymentSchedule() {
        return $this->db->fetchAll(
            "SELECT * FROM payment_schedules WHERE booking_id = ? ORDER BY due_date ASC",
            [$this->id]
        );
    }

    /**
     * Update booking data
     *
     * @param array $updates Associative array of fields to update
     * @return bool Success
     */
    public function update($updates) {
        if (empty($updates)) {
            return false;
        }

        // Build SET clause
        $setClauses = [];
        $params = [];

        foreach ($updates as $field => $value) {
            $setClauses[] = "{$field} = ?";
            $params[] = $value;
        }

        $params[] = $this->id;

        $sql = "UPDATE bookings SET " . implode(', ', $setClauses) . " WHERE id = ?";

        $this->db->execute($sql, $params);
        $this->load(); // Reload data

        return true;
    }

    /**
     * Update payment status based on amounts
     */
    public function updatePaymentStatus() {
        $totalAmount = (float)$this->data['total_amount'];
        $amountPaid = (float)$this->data['amount_paid'];

        if ($amountPaid <= 0) {
            $status = 'unpaid';
        } elseif ($amountPaid >= $totalAmount) {
            $status = 'paid';
        } else {
            $status = 'partial';
        }

        $this->update([
            'payment_status' => $status,
            'amount_outstanding' => $totalAmount - $amountPaid
        ]);
    }

    /**
     * Add payment to booking
     *
     * @param float $amount
     * @param string $method cash, bank_transfer, or stripe
     * @param array $metadata Additional payment data
     * @return int Payment ID
     */
    public function addPayment($amount, $method, $metadata = []) {
        $sql = "INSERT INTO payments (
            booking_id,
            amount,
            payment_method,
            payment_type,
            stripe_payment_intent_id,
            stripe_charge_id,
            status,
            admin_notes,
            processed_by_admin_id
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $paymentId = $this->db->insert($sql, [
            $this->id,
            $amount,
            $method,
            $metadata['payment_type'] ?? 'manual',
            $metadata['stripe_payment_intent_id'] ?? null,
            $metadata['stripe_charge_id'] ?? null,
            $metadata['status'] ?? 'succeeded',
            $metadata['admin_notes'] ?? null,
            $metadata['processed_by_admin_id'] ?? null
        ]);

        // Update booking amounts
        $newAmountPaid = (float)$this->data['amount_paid'] + $amount;
        $this->update(['amount_paid' => $newAmountPaid]);
        $this->updatePaymentStatus();

        return $paymentId;
    }

    /**
     * Create payment schedule for installments
     *
     * @param string $paymentPlan monthly or three_payments
     */
    public function createPaymentSchedule($paymentPlan) {
        $totalAmount = (float)$this->data['total_amount'];
        $bookingDate = $this->data['created_at'];

        // Calculate schedule
        $schedule = calculatePaymentSchedule($totalAmount, $paymentPlan, $bookingDate);

        // Insert schedule records
        foreach ($schedule as $item) {
            $sql = "INSERT INTO payment_schedules (
                booking_id,
                installment_number,
                amount,
                due_date,
                status
            ) VALUES (?, ?, ?, ?, ?)";

            $this->db->insert($sql, [
                $this->id,
                $item['installment_number'],
                $item['amount'],
                $item['due_date'],
                'pending'
            ]);
        }
    }

    /**
     * Delete booking (and cascade attendees, payments, etc.)
     */
    public function delete() {
        // Cascading deletes are handled by foreign key constraints
        $this->db->execute("DELETE FROM bookings WHERE id = ?", [$this->id]);
        $this->id = null;
        $this->data = null;
    }

    /**
     * Get all bookings with filters
     *
     * @param array $filters Optional filters
     * @return array Bookings
     */
    public static function getAll($filters = []) {
        $db = Database::getInstance();

        $sql = "SELECT b.*,
                COUNT(DISTINCT a.id) as attendee_count
                FROM bookings b
                LEFT JOIN attendees a ON b.id = a.booking_id
                WHERE 1=1";

        $params = [];

        // Apply filters
        if (!empty($filters['payment_status'])) {
            $sql .= " AND b.payment_status = ?";
            $params[] = $filters['payment_status'];
        }

        if (!empty($filters['booking_status'])) {
            $sql .= " AND b.booking_status = ?";
            $params[] = $filters['booking_status'];
        }

        if (!empty($filters['search'])) {
            $sql .= " AND (b.booker_name LIKE ? OR b.booker_email LIKE ? OR b.booking_reference LIKE ?)";
            $search = '%' . $filters['search'] . '%';
            $params[] = $search;
            $params[] = $search;
            $params[] = $search;
        }

        $sql .= " GROUP BY b.id ORDER BY b.created_at DESC";

        // Apply limit if set
        if (!empty($filters['limit'])) {
            $sql .= " LIMIT " . (int)$filters['limit'];
        }

        return $db->fetchAll($sql, $params);
    }

    /**
     * Get booking statistics
     */
    public static function getStatistics() {
        $db = Database::getInstance();

        return [
            'total_bookings' => $db->fetchOne("SELECT COUNT(*) as count FROM bookings")['count'],
            'total_attendees' => $db->fetchOne("SELECT COUNT(*) as count FROM attendees")['count'],
            'total_revenue' => $db->fetchOne("SELECT SUM(amount_paid) as total FROM bookings")['total'] ?? 0,
            'outstanding_amount' => $db->fetchOne("SELECT SUM(amount_outstanding) as total FROM bookings WHERE payment_status != 'paid'")['total'] ?? 0,
            'paid_bookings' => $db->fetchOne("SELECT COUNT(*) as count FROM bookings WHERE payment_status = 'paid'")['count'],
            'unpaid_bookings' => $db->fetchOne("SELECT COUNT(*) as count FROM bookings WHERE payment_status = 'unpaid'")['count'],
            'partial_bookings' => $db->fetchOne("SELECT COUNT(*) as count FROM bookings WHERE payment_status = 'partial'")['count']
        ];
    }

    /**
     * Generate unique booking reference
     */
    private function generateUniqueReference() {
        $attempts = 0;
        $maxAttempts = 10;

        do {
            $reference = generateBookingReference();
            $exists = $this->db->fetchOne(
                "SELECT id FROM bookings WHERE booking_reference = ?",
                [$reference]
            );

            if (!$exists) {
                return $reference;
            }

            $attempts++;
        } while ($attempts < $maxAttempts);

        throw new Exception("Failed to generate unique booking reference");
    }
}
