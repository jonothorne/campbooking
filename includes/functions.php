<?php
/**
 * General Utility Functions
 * Date formatting, currency, payment calculations, etc.
 */

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
 * @param string $paymentPlan 'full', 'monthly', or 'three_payments'
 * @param string $bookingDate Y-m-d format
 * @return array Array of schedule items with installment_number, amount, due_date
 */
function calculatePaymentSchedule($totalAmount, $paymentPlan, $bookingDate) {
    $schedule = [];
    $paymentDeadline = PAYMENT_DEADLINE; // Payment deadline (configured in .env)

    switch ($paymentPlan) {
        case 'full':
            // Single payment immediately
            $schedule[] = [
                'installment_number' => 1,
                'amount' => round($totalAmount, 2),
                'due_date' => $bookingDate
            ];
            break;

        case 'monthly':
            // First payment immediately, then monthly until deadline
            $monthsUntilDeadline = monthsBetween($bookingDate, $paymentDeadline);

            // Ensure at least 1 month
            if ($monthsUntilDeadline < 1) {
                $monthsUntilDeadline = 1;
            }

            $monthlyAmount = $totalAmount / $monthsUntilDeadline;

            for ($i = 0; $i < $monthsUntilDeadline; $i++) {
                if ($i == 0) {
                    // First payment is immediate (on booking date)
                    $dueDate = $bookingDate;
                } else {
                    // Subsequent payments are monthly
                    $dueDate = date('Y-m-d', strtotime($bookingDate . " +{$i} month"));
                }

                // Ensure due date doesn't exceed deadline
                if (strtotime($dueDate) > strtotime($paymentDeadline)) {
                    $dueDate = $paymentDeadline;
                }

                // Adjust last payment for rounding
                if ($i == $monthsUntilDeadline - 1) {
                    $amount = $totalAmount - (round($monthlyAmount, 2) * ($monthsUntilDeadline - 1));
                } else {
                    $amount = round($monthlyAmount, 2);
                }

                $schedule[] = [
                    'installment_number' => $i + 1,
                    'amount' => round($amount, 2),
                    'due_date' => $dueDate
                ];
            }
            break;

        case 'three_payments':
            // First payment immediately, then 2 more evenly spaced until deadline
            $paymentAmount = $totalAmount / 3;

            // Calculate days between booking and deadline
            $bookingTime = strtotime($bookingDate);
            $deadlineTime = strtotime($paymentDeadline);
            $daysUntilDeadline = ($deadlineTime - $bookingTime) / (60 * 60 * 24);

            // Space payments evenly
            $daysBetweenPayments = $daysUntilDeadline / 2; // 2 gaps (for 3 payments)

            for ($i = 0; $i < 3; $i++) {
                if ($i == 0) {
                    // First payment is immediate
                    $dueDate = $bookingDate;
                } else if ($i == 2) {
                    // Last payment is on deadline
                    $dueDate = $paymentDeadline;
                } else {
                    // Second payment is halfway between booking and deadline
                    $daysToAdd = (int)round($daysBetweenPayments);
                    $dueDate = date('Y-m-d', strtotime($bookingDate . " +{$daysToAdd} days"));
                }

                // Adjust last payment for rounding
                if ($i == 2) {
                    $amount = $totalAmount - (round($paymentAmount, 2) * 2);
                } else {
                    $amount = round($paymentAmount, 2);
                }

                $schedule[] = [
                    'installment_number' => $i + 1,
                    'amount' => round($amount, 2),
                    'due_date' => $dueDate
                ];
            }
            break;
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
function getPaymentStatusBadge($status) {
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
            'free_child' => 'Free (Age 0-4)'
        ];
    } elseif ($age <= CHILD_MAX_AGE) {
        return [
            'child_weekend' => 'Child Weekend Ticket (' . formatCurrency(CHILD_PRICE) . ')',
            'child_day' => 'Child Day Ticket (' . formatCurrency(CHILD_DAY_PRICE) . ' per day)'
        ];
    } else {
        return [
            'adult_weekend' => 'Adult Weekend Ticket (' . formatCurrency(ADULT_PRICE) . ')',
            'adult_sponsor' => 'Adult Sponsor Ticket (' . formatCurrency(ADULT_SPONSOR_PRICE) . ') - Help fund a young person',
            'adult_day' => 'Adult Day Ticket (' . formatCurrency(ADULT_DAY_PRICE) . ' per day)'
        ];
    }
}
