<?php
/**
 * Attendee Class
 * Manages individual attendee records
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/sanitize.php';

class Attendee {
    private $db;
    private $id;
    private $data;

    public function __construct($attendeeId = null) {
        $this->db = Database::getInstance();

        if ($attendeeId) {
            $this->id = $attendeeId;
            $this->load();
        }
    }

    /**
     * Load attendee data from database
     */
    private function load() {
        $this->data = $this->db->fetchOne(
            "SELECT * FROM attendees WHERE id = ?",
            [$this->id]
        );

        if (!$this->data) {
            throw new Exception("Attendee not found");
        }
    }

    /**
     * Get attendee data
     */
    public function getData() {
        return $this->data;
    }

    /**
     * Get attendee ID
     */
    public function getId() {
        return $this->id;
    }

    /**
     * Get attendee's booking
     */
    public function getBooking() {
        require_once __DIR__ . '/Booking.php';
        return new Booking($this->data['booking_id']);
    }

    /**
     * Get day ticket dates (decoded from JSON)
     */
    public function getDayTicketDates() {
        if (empty($this->data['day_ticket_dates'])) {
            return [];
        }

        $dates = json_decode($this->data['day_ticket_dates'], true);
        return is_array($dates) ? $dates : [];
    }

    /**
     * Get formatted ticket description
     */
    public function getTicketDescription() {
        $type = $this->data['ticket_type'];
        $price = (float)$this->data['ticket_price'];

        switch ($type) {
            case 'adult_weekend':
                return 'Adult Weekend Ticket';

            case 'adult_sponsor':
                return 'Adult Sponsor Ticket (Help fund a young person)';

            case 'child_weekend':
                return 'Child Weekend Ticket';

            case 'free_child':
                return 'Free (Ages 0-4)';

            case 'adult_day':
                $dates = $this->getDayTicketDates();
                $numDays = count($dates);
                return "Adult Day Ticket ({$numDays} day" . ($numDays > 1 ? 's' : '') . ")";

            case 'child_day':
                $dates = $this->getDayTicketDates();
                $numDays = count($dates);
                return "Child Day Ticket ({$numDays} day" . ($numDays > 1 ? 's' : '') . ")";

            default:
                return 'Unknown Ticket Type';
        }
    }

    /**
     * Update attendee data
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

        $sql = "UPDATE attendees SET " . implode(', ', $setClauses) . " WHERE id = ?";

        $this->db->execute($sql, $params);
        $this->load(); // Reload data

        return true;
    }

    /**
     * Delete attendee
     * Note: This will affect the booking's total amount
     */
    public function delete() {
        $bookingId = $this->data['booking_id'];

        // Delete attendee
        $this->db->execute("DELETE FROM attendees WHERE id = ?", [$this->id]);

        // Recalculate booking total
        $this->recalculateBookingTotal($bookingId);

        $this->id = null;
        $this->data = null;
    }

    /**
     * Recalculate booking total amount after attendee changes
     *
     * @param int $bookingId
     */
    private function recalculateBookingTotal($bookingId) {
        // Get all remaining attendees for this booking
        $attendees = $this->db->fetchAll(
            "SELECT ticket_price FROM attendees WHERE booking_id = ?",
            [$bookingId]
        );

        $newTotal = 0;
        foreach ($attendees as $attendee) {
            $newTotal += (float)$attendee['ticket_price'];
        }

        // Update booking
        $this->db->execute(
            "UPDATE bookings SET total_amount = ?, amount_outstanding = total_amount - amount_paid WHERE id = ?",
            [$newTotal, $bookingId]
        );

        // Update payment status
        require_once __DIR__ . '/Booking.php';
        $booking = new Booking($bookingId);
        $booking->updatePaymentStatus();
    }

    /**
     * Get all attendees for a booking
     *
     * @param int $bookingId
     * @return array Attendees
     */
    public static function getByBooking($bookingId) {
        $db = Database::getInstance();

        return $db->fetchAll(
            "SELECT * FROM attendees WHERE booking_id = ? ORDER BY id ASC",
            [$bookingId]
        );
    }

    /**
     * Get attendee count by ticket type
     *
     * @return array Statistics
     */
    public static function getStatistics() {
        $db = Database::getInstance();

        return [
            'total_attendees' => $db->fetchOne("SELECT COUNT(*) as count FROM attendees")['count'],
            'adult_weekend' => $db->fetchOne("SELECT COUNT(*) as count FROM attendees WHERE ticket_type = 'adult_weekend'")['count'],
            'adult_sponsor' => $db->fetchOne("SELECT COUNT(*) as count FROM attendees WHERE ticket_type = 'adult_sponsor'")['count'],
            'child_weekend' => $db->fetchOne("SELECT COUNT(*) as count FROM attendees WHERE ticket_type = 'child_weekend'")['count'],
            'free_child' => $db->fetchOne("SELECT COUNT(*) as count FROM attendees WHERE ticket_type = 'free_child'")['count'],
            'adult_day' => $db->fetchOne("SELECT COUNT(*) as count FROM attendees WHERE ticket_type = 'adult_day'")['count'],
            'child_day' => $db->fetchOne("SELECT COUNT(*) as count FROM attendees WHERE ticket_type = 'child_day'")['count'],
        ];
    }

    /**
     * Get attendees by age group
     *
     * @return array Statistics
     */
    public static function getAgeGroupStatistics() {
        $db = Database::getInstance();

        return [
            'ages_0_4' => $db->fetchOne("SELECT COUNT(*) as count FROM attendees WHERE age <= 4")['count'],
            'ages_5_15' => $db->fetchOne("SELECT COUNT(*) as count FROM attendees WHERE age >= 5 AND age <= 15")['count'],
            'ages_16_plus' => $db->fetchOne("SELECT COUNT(*) as count FROM attendees WHERE age >= 16")['count'],
        ];
    }
}
