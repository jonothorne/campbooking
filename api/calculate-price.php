<?php
/**
 * Price Calculation API Endpoint
 * Returns total booking price based on attendees
 */

// Initialize
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/sanitize.php';

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonError('Method not allowed', 405);
}

// Get attendees data
$attendees = $_POST['attendees'] ?? [];

if (empty($attendees) || !is_array($attendees)) {
    jsonError('No attendees provided');
}

$totalAmount = 0;
$breakdown = [];
$errors = [];

// Calculate price for each attendee
foreach ($attendees as $index => $attendee) {
    $age = sanitizeInt($attendee['age'] ?? 0);
    $ticketType = $attendee['ticket_type'] ?? '';
    $days = $attendee['days'] ?? [];

    if ($age < 0 || $age > 120) {
        $errors[] = "Invalid age for attendee " . ($index + 1);
        continue;
    }

    if (empty($ticketType)) {
        continue; // Skip attendees without ticket type
    }

    // Calculate price based on ticket type
    $price = 0;
    $description = '';

    switch ($ticketType) {
        case 'adult_weekend':
            $price = ADULT_PRICE;
            $description = 'Adult Weekend';
            break;

        case 'adult_sponsor':
            $price = ADULT_SPONSOR_PRICE;
            $description = 'Adult Sponsor';
            break;

        case 'child_weekend':
            $price = CHILD_PRICE;
            $description = 'Child Weekend';
            break;

        case 'free_child':
            $price = 0;
            $description = 'Free (0-4 years)';
            break;

        case 'adult_day':
            if (!is_array($days) || empty($days)) {
                $errors[] = "No days selected for adult day ticket (attendee " . ($index + 1) . ")";
                continue 2;
            }
            $numDays = count($days);
            $price = ADULT_DAY_PRICE * $numDays;
            $description = "Adult Day Ticket ({$numDays} day" . ($numDays > 1 ? 's' : '') . ")";
            break;

        case 'child_day':
            if (!is_array($days) || empty($days)) {
                $errors[] = "No days selected for child day ticket (attendee " . ($index + 1) . ")";
                continue 2;
            }
            $numDays = count($days);
            $price = CHILD_DAY_PRICE * $numDays;
            $description = "Child Day Ticket ({$numDays} day" . ($numDays > 1 ? 's' : '') . ")";
            break;

        default:
            $errors[] = "Invalid ticket type for attendee " . ($index + 1);
            continue 2;
    }

    $totalAmount += $price;

    $breakdown[] = [
        'index' => $index,
        'name' => $attendee['name'] ?? 'Attendee ' . ($index + 1),
        'age' => $age,
        'ticket_type' => $ticketType,
        'description' => $description,
        'price' => $price,
        'formatted_price' => formatCurrency($price)
    ];
}

// Return errors if any
if (!empty($errors)) {
    jsonError(implode(', ', $errors));
}

// Calculate payment schedule if requested
$paymentSchedule = null;
$paymentPlan = $_POST['payment_plan'] ?? 'full';

if (in_array($paymentPlan, ['monthly', 'three_payments']) && $totalAmount > 0) {
    $bookingDate = date('Y-m-d');
    $schedule = calculatePaymentSchedule($totalAmount, $paymentPlan, $bookingDate);

    $paymentSchedule = array_map(function($item) {
        return [
            'installment_number' => $item['installment_number'],
            'amount' => $item['amount'],
            'formatted_amount' => formatCurrency($item['amount']),
            'due_date' => $item['due_date'],
            'formatted_due_date' => formatDate($item['due_date'])
        ];
    }, $schedule);
}

// Return success response
jsonSuccess([
    'total' => $totalAmount,
    'formatted_total' => formatCurrency($totalAmount),
    'attendee_count' => count($breakdown),
    'breakdown' => $breakdown,
    'payment_schedule' => $paymentSchedule
]);
