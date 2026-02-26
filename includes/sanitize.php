<?php
/**
 * Input Validation and Sanitization Functions
 * Protects against XSS, SQL injection, and malicious input
 */

/**
 * Sanitize string input (remove HTML tags, trim)
 *
 * @param string $input
 * @return string
 */
function sanitizeString($input) {
    return trim(strip_tags($input));
}

/**
 * Sanitize email address
 *
 * @param string $email
 * @return string|false
 */
function sanitizeEmail($email) {
    $email = trim($email);
    return filter_var($email, FILTER_SANITIZE_EMAIL);
}

/**
 * Validate email address
 *
 * @param string $email
 * @return bool
 */
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Sanitize phone number (allow digits, spaces, hyphens, plus)
 *
 * @param string $phone
 * @return string
 */
function sanitizePhone($phone) {
    return preg_replace('/[^0-9\s\-\+\(\)]/', '', trim($phone));
}

/**
 * Sanitize integer
 *
 * @param mixed $input
 * @return int
 */
function sanitizeInt($input) {
    return (int)filter_var($input, FILTER_SANITIZE_NUMBER_INT);
}

/**
 * Sanitize float/decimal
 *
 * @param mixed $input
 * @return float
 */
function sanitizeFloat($input) {
    return (float)filter_var($input, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
}

/**
 * Sanitize and validate age
 *
 * @param mixed $age
 * @return int|false Returns age if valid (0-120), false otherwise
 */
function sanitizeAge($age) {
    $age = sanitizeInt($age);
    if ($age < 0 || $age > 120) {
        return false;
    }
    return $age;
}

/**
 * Sanitize textarea/multiline input
 *
 * @param string $input
 * @return string
 */
function sanitizeTextarea($input) {
    return trim(htmlspecialchars($input, ENT_QUOTES, 'UTF-8'));
}

/**
 * Sanitize URL
 *
 * @param string $url
 * @return string|false
 */
function sanitizeUrl($url) {
    return filter_var($url, FILTER_SANITIZE_URL);
}

/**
 * Validate URL
 *
 * @param string $url
 * @return bool
 */
function isValidUrl($url) {
    return filter_var($url, FILTER_VALIDATE_URL) !== false;
}

/**
 * Validate date in Y-m-d format
 *
 * @param string $date
 * @return bool
 */
function isValidDate($date) {
    $d = DateTime::createFromFormat('Y-m-d', $date);
    return $d && $d->format('Y-m-d') === $date;
}

/**
 * Sanitize date input
 *
 * @param string $date
 * @return string|false Returns date if valid, false otherwise
 */
function sanitizeDate($date) {
    $date = trim($date);
    if (isValidDate($date)) {
        return $date;
    }
    return false;
}

/**
 * Validate enum value against allowed values
 *
 * @param mixed $value
 * @param array $allowedValues
 * @return bool
 */
function isValidEnum($value, array $allowedValues) {
    return in_array($value, $allowedValues, true);
}

/**
 * Sanitize booking data from form submission
 *
 * @param array $data
 * @return array|false Returns sanitized data or false if validation fails
 */
function sanitizeBookingData($data) {
    $sanitized = [];

    // Booker information
    $sanitized['booker_name'] = sanitizeString($data['booker_name'] ?? '');
    if (empty($sanitized['booker_name'])) {
        return ['error' => 'Booker name is required'];
    }

    $sanitized['booker_email'] = sanitizeEmail($data['booker_email'] ?? '');
    if (!isValidEmail($sanitized['booker_email'])) {
        return ['error' => 'Invalid email address'];
    }

    $sanitized['booker_phone'] = sanitizePhone($data['booker_phone'] ?? '');
    if (empty($sanitized['booker_phone'])) {
        return ['error' => 'Phone number is required'];
    }

    // Camping requirements
    $sanitized['num_tents'] = max(0, sanitizeInt($data['num_tents'] ?? 0));
    $sanitized['has_caravan'] = !empty($data['has_caravan']) ? 1 : 0;
    $sanitized['needs_tent_provided'] = !empty($data['needs_tent_provided']) ? 1 : 0;
    $sanitized['special_requirements'] = sanitizeTextarea($data['special_requirements'] ?? '');

    // Payment method
    $validPaymentMethods = ['cash', 'bank_transfer', 'stripe'];
    $sanitized['payment_method'] = $data['payment_method'] ?? '';
    if (!isValidEnum($sanitized['payment_method'], $validPaymentMethods)) {
        return ['error' => 'Invalid payment method'];
    }

    // Payment plan
    $validPaymentPlans = ['full', 'monthly', 'three_payments'];
    $sanitized['payment_plan'] = $data['payment_plan'] ?? '';
    if (!isValidEnum($sanitized['payment_plan'], $validPaymentPlans)) {
        return ['error' => 'Invalid payment plan'];
    }

    return $sanitized;
}

/**
 * Validate and sanitize attendee data
 *
 * @param array $attendee
 * @return array|false Returns sanitized attendee or error message
 */
function sanitizeAttendeeData($attendee) {
    $sanitized = [];

    // Name
    $sanitized['name'] = sanitizeString($attendee['name'] ?? '');
    if (empty($sanitized['name'])) {
        return ['error' => 'Attendee name is required'];
    }

    // Age
    $sanitized['age'] = sanitizeAge($attendee['age'] ?? '');
    if ($sanitized['age'] === false) {
        return ['error' => 'Invalid age (must be 0-120)'];
    }

    // Ticket type
    $validTicketTypes = ['adult_weekend', 'adult_sponsor', 'child_weekend', 'free_child', 'adult_day', 'child_day'];
    $sanitized['ticket_type'] = $attendee['ticket_type'] ?? '';
    if (!isValidEnum($sanitized['ticket_type'], $validTicketTypes)) {
        return ['error' => 'Invalid ticket type'];
    }

    // Day ticket dates (for day tickets only)
    if (in_array($sanitized['ticket_type'], ['adult_day', 'child_day'])) {
        $dates = $attendee['day_ticket_dates'] ?? $attendee['days'] ?? [];
        if (!is_array($dates) || empty($dates)) {
            return ['error' => 'Day ticket dates must be selected'];
        }

        $sanitized['day_ticket_dates'] = [];
        $validEventDates = getEventDates(); // Get valid event dates (May 29-31, 2026)

        foreach ($dates as $date) {
            $cleanDate = sanitizeDate($date);
            if ($cleanDate) {
                // Validate date is within event date range
                if (!in_array($cleanDate, $validEventDates)) {
                    return ['error' => 'Selected date is not within the event dates'];
                }
                $sanitized['day_ticket_dates'][] = $cleanDate;
            }
        }

        if (empty($sanitized['day_ticket_dates'])) {
            return ['error' => 'Invalid day ticket dates'];
        }
    } else {
        $sanitized['day_ticket_dates'] = null;
    }

    // Calculate ticket price based on age and type
    $sanitized['ticket_price'] = calculateTicketPrice($sanitized['age'], $sanitized['ticket_type'], $sanitized['day_ticket_dates']);

    return $sanitized;
}

/**
 * Calculate ticket price based on age, type, and dates
 *
 * @param int $age
 * @param string $ticketType
 * @param array|null $dayTicketDates
 * @return float
 */
function calculateTicketPrice($age, $ticketType, $dayTicketDates = null) {
    // Free for children 0-4
    if ($age <= FREE_CHILD_MAX_AGE) {
        return 0.00;
    }

    switch ($ticketType) {
        case 'adult_weekend':
            return ADULT_PRICE;

        case 'adult_sponsor':
            return ADULT_SPONSOR_PRICE;

        case 'child_weekend':
            return CHILD_PRICE;

        case 'free_child':
            return 0.00;

        case 'adult_day':
            $numDays = is_array($dayTicketDates) ? count($dayTicketDates) : 1;
            return ADULT_DAY_PRICE * $numDays;

        case 'child_day':
            $numDays = is_array($dayTicketDates) ? count($dayTicketDates) : 1;
            return CHILD_DAY_PRICE * $numDays;

        default:
            return 0.00;
    }
}

/**
 * Escape output for HTML display (prevent XSS)
 *
 * @param string $string
 * @return string
 */
function e($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

/**
 * Validate and sanitize decimal amount
 *
 * @param mixed $amount
 * @return float|false
 */
function sanitizeAmount($amount) {
    $amount = sanitizeFloat($amount);
    if ($amount < 0) {
        return false;
    }
    return round($amount, 2);
}

/**
 * Validate booking reference format
 *
 * @param string $reference
 * @return bool
 */
function isValidBookingReference($reference) {
    // Format: CAMP-YYYYMMDD-XXXX (e.g., CAMP-20260223-A1B2)
    return preg_match('/^CAMP-\d{8}-[A-Z0-9]{4}$/', $reference) === 1;
}

/**
 * Generate booking reference
 *
 * @return string
 */
function generateBookingReference() {
    $date = date('Ymd');
    $random = strtoupper(substr(md5(uniqid(rand(), true)), 0, 4));
    return "CAMP-{$date}-{$random}";
}
