<?php
/**
 * General Utility Functions
 * Date formatting, currency, payment calculations, etc.
 */

/**
 * Escape HTML for safe output
 *
 * @param string $string
 * @return string
 */
function e($string) {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Format currency amount
 *
 * @param float $amount
 * @param bool $showSymbol
 * @return string
 */
function formatCurrency($amount, $showSymbol = true) {
    $formatted = number_format($amount, 2);
    return $showSymbol ? '£' . $formatted : $formatted;
}

/**
 * Format date for display
 *
 * @param string $date MySQL date or datetime
 * @param string $format
 * @return string
 */
function formatDate($date, $format = 'd/m/Y') {
    if (empty($date) || $date === '0000-00-00' || $date === '0000-00-00 00:00:00') {
        return '';
    }
    $timestamp = strtotime($date);
    return $timestamp ? date($format, $timestamp) : '';
}

/**
 * Format datetime for display
 *
 * @param string $datetime
 * @param string $format
 * @return string
 */
function formatDateTime($datetime, $format = 'd/m/Y H:i') {
    return formatDate($datetime, $format);
}

/**
 * Calculate months between two dates
 *
 * @param string $fromDate Y-m-d format
 * @param string $toDate Y-m-d format
 * @return int
 */
function monthsBetween($fromDate, $toDate) {
    $from = new DateTime($fromDate);
    $to = new DateTime($toDate);
    $interval = $from->diff($to);

    $months = ($interval->y * 12) + $interval->m;

    // If there are days remaining, count as additional month
    if ($interval->d > 0) {
        $months++;
    }

    return max(1, $months); // At least 1 month
}

/**
 * Calculate payment schedule for a booking
 *
 * @param float $totalAmount
 * @param int $numInstallments Number of installments (1-11)
 * @param string $bookingDate Y-m-d format
 * @return array Array of schedule items with installment_number, amount, due_date
 */
function calculatePaymentSchedule($totalAmount, $numInstallments, $bookingDate) {
    $schedule = [];
    $paymentDeadline = PAYMENT_DEADLINE;
    $numInstallments = max(1, min(MAX_INSTALLMENTS, (int)$numInstallments));

    if ($numInstallments === 1) {
        // Single payment immediately
        $schedule[] = [
            'installment_number' => 1,
            'amount' => round($totalAmount, 2),
            'due_date' => $bookingDate
        ];
        return $schedule;
    }

    // Multiple installments: first payment immediate, rest spread monthly until deadline
    $installmentAmount = $totalAmount / $numInstallments;

    // Calculate the interval between payments
    $bookingTime = strtotime($bookingDate);
    $deadlineTime = strtotime($paymentDeadline);
    $daysUntilDeadline = max(1, ($deadlineTime - $bookingTime) / 86400);
    $daysBetweenPayments = $daysUntilDeadline / ($numInstallments - 1);

    for ($i = 0; $i < $numInstallments; $i++) {
        if ($i === 0) {
            $dueDate = $bookingDate;
        } elseif ($i === $numInstallments - 1) {
            $dueDate = $paymentDeadline;
        } else {
            $daysToAdd = (int)round($daysBetweenPayments * $i);
            $dueDate = date('Y-m-d', strtotime($bookingDate . " +{$daysToAdd} days"));

            // Ensure due date doesn't exceed deadline
            if (strtotime($dueDate) > $deadlineTime) {
                $dueDate = $paymentDeadline;
            }
        }

        // Adjust last payment for rounding
        if ($i === $numInstallments - 1) {
            $amount = $totalAmount - (round($installmentAmount, 2) * ($numInstallments - 1));
        } else {
            $amount = round($installmentAmount, 2);
        }

        $schedule[] = [
            'installment_number' => $i + 1,
            'amount' => round($amount, 2),
            'due_date' => $dueDate
        ];
    }

    return $schedule;
}

/**
 * Calculate total booking amount from attendees
 *
 * @param array $attendees Array of attendee data with ticket_price
 * @return float
 */
function calculateTotalAmount($attendees) {
    $total = 0.00;

    foreach ($attendees as $attendee) {
        $total += (float)($attendee['ticket_price'] ?? 0);
    }

    return round($total, 2);
}

/**
 * Get payment status badge HTML
 *
 * @param string $status
 * @return string
 */
function getPaymentStatusBadge($status, $booking = null) {
    // Check if this is a fully funded booking
    if ($booking && !empty($booking['discount_amount']) && $booking['discount_amount'] >= $booking['total_amount']) {
        return '<span class="badge badge-info" style="background: #6f42c1; color: white;">Funded</span>';
    }

    $badges = [
        'unpaid' => '<span class="badge badge-danger">Unpaid</span>',
        'partial' => '<span class="badge badge-warning">Partial</span>',
        'paid' => '<span class="badge badge-success">Paid</span>',
        'failed' => '<span class="badge badge-danger">Failed</span>'
    ];

    return $badges[$status] ?? '<span class="badge badge-secondary">' . e($status) . '</span>';
}

/**
 * Get booking status badge HTML
 *
 * @param string $status
 * @return string
 */
function getBookingStatusBadge($status) {
    $badges = [
        'pending' => '<span class="badge badge-warning">Pending</span>',
        'confirmed' => '<span class="badge badge-success">Confirmed</span>',
        'cancelled' => '<span class="badge badge-secondary">Cancelled</span>'
    ];

    return $badges[$status] ?? '<span class="badge badge-secondary">' . e($status) . '</span>';
}

/**
 * Get bank transfer reference for a booking
 *
 * @param string $bookerName
 * @return string
 */
function getBankTransferReference($bookerName) {
    // Extract surname (last word)
    $nameParts = explode(' ', trim($bookerName));
    $surname = end($nameParts);

    // Remove non-alphanumeric characters
    $surname = preg_replace('/[^A-Za-z0-9]/', '', $surname);

    return BANK_REFERENCE_PREFIX . ucfirst(strtolower($surname));
}

/**
 * Get event dates as array
 *
 * @return array
 */
function getEventDates() {
    $start = new DateTime(EVENT_START_DATE);
    $end = new DateTime(EVENT_END_DATE);
    $dates = [];

    $current = clone $start;
    while ($current <= $end) {
        $dates[] = $current->format('Y-m-d');
        $current->modify('+1 day');
    }

    return $dates;
}

/**
 * Get event dates formatted for display
 *
 * @return array ['date' => 'Y-m-d', 'display' => 'Day, d M Y']
 */
function getEventDatesFormatted() {
    $dates = getEventDates();
    $formatted = [];

    foreach ($dates as $date) {
        $formatted[] = [
            'date' => $date,
            'display' => date('l, jS F Y', strtotime($date))
        ];
    }

    return $formatted;
}

/**
 * Calculate payment completion percentage
 *
 * @param float $totalAmount
 * @param float $amountPaid
 * @return int Percentage (0-100)
 */
function calculatePaymentPercentage($totalAmount, $amountPaid) {
    if ($totalAmount <= 0) {
        return 0;
    }

    $percentage = ($amountPaid / $totalAmount) * 100;
    return (int)round($percentage);
}

/**
 * Check if date is in the past
 *
 * @param string $date Y-m-d format
 * @return bool
 */
function isDatePast($date) {
    return strtotime($date) < strtotime(date('Y-m-d'));
}

/**
 * Check if date is today
 *
 * @param string $date Y-m-d format
 * @return bool
 */
function isToday($date) {
    return $date === date('Y-m-d');
}

/**
 * Check if date is in the future
 *
 * @param string $date Y-m-d format
 * @return bool
 */
function isDateFuture($date) {
    return strtotime($date) > strtotime(date('Y-m-d'));
}

/**
 * Get days until date
 *
 * @param string $date Y-m-d format
 * @return int Negative if past, positive if future
 */
function daysUntil($date) {
    $target = strtotime($date);
    $now = strtotime(date('Y-m-d'));
    $diff = $target - $now;
    return (int)floor($diff / 86400);
}

/**
 * Send JSON response
 *
 * @param mixed $data
 * @param int $statusCode
 */
function jsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data, JSON_HEX_TAG | JSON_HEX_AMP);
    exit;
}

/**
 * Send error JSON response
 *
 * @param string $message
 * @param int $statusCode
 */
function jsonError($message, $statusCode = 400) {
    jsonResponse(['error' => $message], $statusCode);
}

/**
 * Send success JSON response
 *
 * @param mixed $data
 */
function jsonSuccess($data = null) {
    if ($data === null) {
        jsonResponse(['success' => true]);
    } else {
        jsonResponse(['success' => true, 'data' => $data]);
    }
}

/**
 * Log to file
 *
 * @param string $message
 * @param string $logFile Filename in logs directory
 */
function logMessage($message, $logFile = 'app.log') {
    $logPath = LOGS_PATH . '/' . $logFile;

    // Create logs directory if it doesn't exist
    if (!is_dir(LOGS_PATH)) {
        mkdir(LOGS_PATH, 0755, true);
    }

    $timestamp = date('Y-m-d H:i:s');
    $logEntry = "[{$timestamp}] {$message}" . PHP_EOL;

    file_put_contents($logPath, $logEntry, FILE_APPEND);
}

/**
 * Get age-appropriate ticket types for an age
 *
 * @param int $age
 * @return array
 */
function getTicketTypesForAge($age) {
    if ($age <= FREE_CHILD_MAX_AGE) {
        return [
            'free_child' => 'Free (Under 4s)'
        ];
    } elseif ($age <= CHILD_MAX_AGE) {
        return [
            'child_weekend' => 'Child Ticket (' . formatCurrency(ACTIVE_CHILD_PRICE) . ')',
            'child_day' => 'Child Day Ticket (' . formatCurrency(ACTIVE_CHILD_DAY_PRICE) . ' per day)'
        ];
    } else {
        return [
            'adult_weekend' => 'Adult Ticket (' . formatCurrency(ACTIVE_ADULT_PRICE) . ')',
            'adult_sponsor' => 'Adult Sponsor Ticket (suggested ' . formatCurrency(ADULT_SPONSOR_SUGGESTED) . ') - Help fund a young person',
            'adult_day' => 'Adult Day Ticket (' . formatCurrency(ACTIVE_ADULT_DAY_PRICE) . ' per day)'
        ];
    }
}

/**
 * Check if currently in the early bird pricing period
 *
 * @return bool
 */
function isEarlyBird() {
    return IS_EARLY_BIRD;
}

/**
 * Get the event year currently selected in the admin panel.
 * Defaults to EVENT_YEAR (the current event year).
 */
function getAdminEventYear() {
    return (int)($_SESSION['admin_event_year'] ?? EVENT_YEAR);
}

/**
 * Set the event year for the admin panel view.
 */
function setAdminEventYear($year) {
    $_SESSION['admin_event_year'] = (int)$year;
}

/**
 * Get available event years (distinct years that have bookings, plus the current event year).
 */
function getAvailableEventYears() {
    $db = Database::getInstance();
    $rows = $db->fetchAll("SELECT DISTINCT event_year FROM bookings ORDER BY event_year DESC");
    $years = array_column($rows, 'event_year');

    // Always include the current event year
    if (!in_array(EVENT_YEAR, $years)) {
        $years[] = EVENT_YEAR;
    }

    rsort($years);
    return $years;
}
