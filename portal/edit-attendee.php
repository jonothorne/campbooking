<?php
/**
 * Customer Portal - Edit Attendee
 * Allow customers to edit an existing attendee in their booking
 */

// Initialize
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/customer-auth.php';
require_once __DIR__ . '/../classes/Booking.php';

// Require authentication
requireCustomerAuth();

$customerId = currentCustomerId();
$error = null;
$db = Database::getInstance();

// Get attendee ID
$attendeeId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Load attendee
try {
    $attendee = $db->fetchOne(
        "SELECT * FROM attendees WHERE id = ? AND booking_id = ?",
        [$attendeeId, $customerId]
    );

    if (!$attendee) {
        $_SESSION['error'] = 'Attendee not found.';
        redirect(url('portal/dashboard.php'));
    }
} catch (Exception $e) {
    $_SESSION['error'] = 'Failed to load attendee.';
    redirect(url('portal/dashboard.php'));
}

// Load booking
try {
    $booking = new Booking($customerId);
    $bookingData = $booking->getData();
} catch (Exception $e) {
    customerLogout();
    redirect(url('portal/login.php'));
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCustomerCsrfToken();

    $attendeeName = trim($_POST['attendee_name'] ?? '');
    $attendeeAge = (int)($_POST['attendee_age'] ?? 0);
    $ticketType = $_POST['ticket_type'] ?? '';
    $dayTicketDates = $_POST['day_ticket_dates'] ?? [];

    if (empty($attendeeName)) {
        $error = 'Please enter the attendee name.';
    } elseif ($attendeeAge < 0 || $attendeeAge > 120) {
        $error = 'Please enter a valid age.';
    } elseif (empty($ticketType)) {
        $error = 'Please select a ticket type.';
    } elseif (($ticketType === 'adult_day' || $ticketType === 'child_day') && empty($dayTicketDates)) {
        $error = 'Please select at least one day for the day ticket.';
    } else {
        // Calculate ticket price
        $ticketPrice = 0;
        $dayTicketDatesJson = null;
        $oldTicketPrice = $attendee['ticket_price'];

        switch ($ticketType) {
            case 'adult_weekend':
                $ticketPrice = ADULT_PRICE;
                break;
            case 'adult_sponsor':
                $ticketPrice = ADULT_SPONSOR_PRICE;
                break;
            case 'child_weekend':
                $ticketPrice = CHILD_PRICE;
                break;
            case 'adult_day':
                $numDays = count($dayTicketDates);
                $ticketPrice = ADULT_DAY_PRICE * $numDays;
                $dayTicketDatesJson = json_encode($dayTicketDates);
                break;
            case 'child_day':
                $numDays = count($dayTicketDates);
                $ticketPrice = CHILD_DAY_PRICE * $numDays;
                $dayTicketDatesJson = json_encode($dayTicketDates);
                break;
            case 'free_child':
                $ticketPrice = 0;
                break;
        }

        // Update attendee
        try {
            $rowsAffected = $db->execute(
                "UPDATE attendees SET name = ?, age = ?, ticket_type = ?, ticket_price = ?, day_ticket_dates = ? WHERE id = ? AND booking_id = ?",
                [$attendeeName, $attendeeAge, $ticketType, $ticketPrice, $dayTicketDatesJson, $attendeeId, $customerId]
            );
            $updated = true; // Query succeeded
        } catch (Exception $e) {
            error_log("Edit attendee error: " . $e->getMessage());
            $error = 'Database error: ' . $e->getMessage();
            $updated = false;
        }

        if ($updated) {
            // Recalculate booking total
            $newTotal = $db->fetchOne(
                "SELECT SUM(ticket_price) as total FROM attendees WHERE booking_id = ?",
                [$customerId]
            )['total'];

            // Calculate the difference
            $priceDifference = $ticketPrice - $oldTicketPrice;

            // Update booking total and outstanding amount
            $db->execute(
                "UPDATE bookings SET
                    total_amount = ?,
                    amount_outstanding = amount_outstanding + ?
                WHERE id = ?",
                [$newTotal, $priceDifference, $customerId]
            );

            // Recalculate payment schedule to redistribute outstanding amount
            $booking->recalculatePaymentSchedule();

            logGDPRAction($customerId, 'privacy_update', "Customer updated attendee: $attendeeName");

            $_SESSION['success'] = "Updated $attendeeName successfully!";
            redirect(url('portal/dashboard.php'));
        } else {
            $error = 'Failed to update attendee. Please try again.';
        }
    }
}

$csrfToken = generateCustomerCsrfToken();

// Parse day ticket dates if it's a day ticket
$selectedDays = [];
if (!empty($attendee['day_ticket_dates'])) {
    $selectedDays = json_decode($attendee['day_ticket_dates'], true) ?: [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Attendee - <?php echo e(EVENT_NAME); ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            background: #f9fafb;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            line-height: 1.6;
            color: #1f2937;
        }
        .portal-header {
            background: linear-gradient(135deg, #1f2937 0%, #d40080 100%);
            color: white;
            padding: 30px 20px;
            margin-bottom: 30px;
            border-color: #eb008b;
            border-top-style: solid;
            border-width: 5px;
        }
        .portal-header-content {
            max-width: 800px;
            margin: 0 auto;
            text-align: center;
        }
        .portal-logo {
            height: 50px;
            margin-bottom: 15px;
        }
        .portal-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 0 20px 40px;
        }
        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .alert-danger {
            background: #fee;
            color: #c33;
            border: 1px solid #fcc;
        }
        .content-card {
            background: white;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #374151;
        }
        .form-control {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            font-size: 16px;
            font-family: inherit;
            transition: border-color 0.3s;
        }
        .form-control:focus {
            outline: none;
            border-color: #eb008b;
        }
        .ticket-option {
            background: white;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            padding: 20px;
            margin: 15px 0;
            cursor: pointer;
            transition: all 0.3s;
            display: block;
        }
        .ticket-option:hover {
            border-color: #eb008b;
            background: #fef2f8;
        }
        .ticket-option input[type="radio"] {
            margin-right: 12px;
        }
        .ticket-price {
            font-weight: 700;
            color: #eb008b;
            font-size: 18px;
        }
        .btn {
            display: inline-block;
            padding: 12px 24px;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            border: none;
            transition: all 0.3s;
        }
        .btn-primary {
            background: linear-gradient(135deg, #eb008b 0%, #d40080 100%);
            color: white;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(235, 0, 139, 0.4);
        }
        .btn-secondary {
            background: #6b7280;
            color: white;
        }
        .btn-secondary:hover {
            background: #4b5563;
        }
    </style>
</head>
<body>
    <div class="portal-header">
        <div class="portal-header-content">
            <img src="<?php echo basePath('public/assets/images/echo-logo.png'); ?>" alt="ECHO2026" class="portal-logo">
            <h1 style="margin: 0; font-size: 28px;">Edit Attendee</h1>
            <p style="margin: 10px 0 0 0; opacity: 0.9;">Update attendee information</p>
        </div>
    </div>

    <div class="portal-container">
        <?php if ($error): ?>
            <div class="alert alert-danger">
                <?php echo e($error); ?>
            </div>
        <?php endif; ?>

        <div class="content-card">
            <form method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?php echo e($csrfToken); ?>">

                <div class="form-group">
                    <label for="attendee_name">Attendee Name *</label>
                    <input type="text" id="attendee_name" name="attendee_name" class="form-control" value="<?php echo e($attendee['name']); ?>" required>
                </div>

                <div class="form-group">
                    <label for="attendee_age">Age *</label>
                    <input type="number" id="attendee_age" name="attendee_age" class="form-control" min="0" max="120" value="<?php echo $attendee['age']; ?>" required>
                </div>

                <div class="form-group">
                    <label>Ticket Type *</label>

                    <label class="ticket-option">
                        <input type="radio" name="ticket_type" value="adult_weekend" class="ticket-type-radio" <?php echo $attendee['ticket_type'] === 'adult_weekend' ? 'checked' : ''; ?> required>
                        <div style="display: flex; justify-content: space-between; align-items: center; width: 100%;">
                            <div>
                                <strong style="font-size: 16px;">Adult Weekend Ticket</strong>
                                <p style="margin: 5px 0 0 0; color: #6b7280; font-size: 14px;">Ages 16+</p>
                            </div>
                            <div class="ticket-price"><?php echo formatCurrency(ADULT_PRICE); ?></div>
                        </div>
                    </label>

                    <label class="ticket-option">
                        <input type="radio" name="ticket_type" value="adult_sponsor" class="ticket-type-radio" <?php echo $attendee['ticket_type'] === 'adult_sponsor' ? 'checked' : ''; ?> required>
                        <div style="display: flex; justify-content: space-between; align-items: center; width: 100%;">
                            <div>
                                <strong style="font-size: 16px;">Adult Sponsor Ticket</strong>
                                <p style="margin: 5px 0 0 0; color: #6b7280; font-size: 14px;">Help fund a young person (Ages 16+)</p>
                            </div>
                            <div class="ticket-price"><?php echo formatCurrency(ADULT_SPONSOR_PRICE); ?></div>
                        </div>
                    </label>

                    <label class="ticket-option">
                        <input type="radio" name="ticket_type" value="child_weekend" class="ticket-type-radio" <?php echo $attendee['ticket_type'] === 'child_weekend' ? 'checked' : ''; ?> required>
                        <div style="display: flex; justify-content: space-between; align-items: center; width: 100%;">
                            <div>
                                <strong style="font-size: 16px;">Child Weekend Ticket</strong>
                                <p style="margin: 5px 0 0 0; color: #6b7280; font-size: 14px;">Ages 5-15</p>
                            </div>
                            <div class="ticket-price"><?php echo formatCurrency(CHILD_PRICE); ?></div>
                        </div>
                    </label>

                    <label class="ticket-option">
                        <input type="radio" name="ticket_type" value="adult_day" class="ticket-type-radio" <?php echo $attendee['ticket_type'] === 'adult_day' ? 'checked' : ''; ?> required>
                        <div style="display: flex; justify-content: space-between; align-items: center; width: 100%;">
                            <div>
                                <strong style="font-size: 16px;">Adult Day Ticket</strong>
                                <p style="margin: 5px 0 0 0; color: #6b7280; font-size: 14px;">Ages 16+ (per day)</p>
                            </div>
                            <div class="ticket-price"><?php echo formatCurrency(ADULT_DAY_PRICE); ?>/day</div>
                        </div>
                    </label>

                    <label class="ticket-option">
                        <input type="radio" name="ticket_type" value="child_day" class="ticket-type-radio" <?php echo $attendee['ticket_type'] === 'child_day' ? 'checked' : ''; ?> required>
                        <div style="display: flex; justify-content: space-between; align-items: center; width: 100%;">
                            <div>
                                <strong style="font-size: 16px;">Child Day Ticket</strong>
                                <p style="margin: 5px 0 0 0; color: #6b7280; font-size: 14px;">Ages 5-15 (per day)</p>
                            </div>
                            <div class="ticket-price"><?php echo formatCurrency(CHILD_DAY_PRICE); ?>/day</div>
                        </div>
                    </label>

                    <label class="ticket-option">
                        <input type="radio" name="ticket_type" value="free_child" class="ticket-type-radio" <?php echo $attendee['ticket_type'] === 'free_child' ? 'checked' : ''; ?> required>
                        <div style="display: flex; justify-content: space-between; align-items: center; width: 100%;">
                            <div>
                                <strong style="font-size: 16px;">Free Child Ticket</strong>
                                <p style="margin: 5px 0 0 0; color: #6b7280; font-size: 14px;">Ages 0-4</p>
                            </div>
                            <div class="ticket-price">FREE</div>
                        </div>
                    </label>
                </div>

                <!-- Day ticket date selection (hidden by default) -->
                <div class="form-group" id="day-ticket-dates" style="display: <?php echo ($attendee['ticket_type'] === 'adult_day' || $attendee['ticket_type'] === 'child_day') ? 'block' : 'none'; ?>;">
                    <label>Select Days *</label>
                    <div style="display: flex; flex-direction: column; gap: 10px; margin-top: 10px;">
                        <?php
                        $eventDates = getEventDatesFormatted();
                        foreach ($eventDates as $date):
                        ?>
                            <label style="display: flex; align-items: center; gap: 10px; cursor: pointer; padding: 12px; background: #f9fafb; border-radius: 6px;">
                                <input type="checkbox" name="day_ticket_dates[]" value="<?php echo e($date['date']); ?>" class="day-checkbox" style="width: auto;" <?php echo in_array($date['date'], $selectedDays) ? 'checked' : ''; ?>>
                                <span><?php echo e($date['display']); ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div style="margin-top: 30px; display: flex; gap: 15px;">
                    <button type="submit" class="btn btn-primary" style="flex: 1;">
                        💾 Save Changes
                    </button>
                    <a href="<?php echo url('portal/dashboard.php'); ?>" class="btn btn-secondary" style="flex: 1; text-align: center;">
                        Cancel
                    </a>
                </div>
            </form>

            <div style="margin-top: 25px; padding: 20px; background: #fff3cd; border-radius: 8px; font-size: 14px;">
                <strong>ℹ️ Note:</strong>
                <p style="margin: 10px 0 0 0; color: #666;">
                    Changing the ticket type may affect your booking total and outstanding payment amount.
                </p>
            </div>
        </div>
    </div>

    <script>
        // Show/hide day selection based on ticket type
        const ticketRadios = document.querySelectorAll('input[name="ticket_type"]');
        const dayTicketDates = document.getElementById('day-ticket-dates');

        ticketRadios.forEach(radio => {
            radio.addEventListener('change', function() {
                const ticketType = this.value;
                if (ticketType === 'adult_day' || ticketType === 'child_day') {
                    dayTicketDates.style.display = 'block';
                    // Make checkboxes required
                    document.querySelectorAll('.day-checkbox').forEach(cb => {
                        cb.required = true;
                    });
                } else {
                    dayTicketDates.style.display = 'none';
                    // Remove required from checkboxes
                    document.querySelectorAll('.day-checkbox').forEach(cb => {
                        cb.required = false;
                        cb.checked = false;
                    });
                }
            });
        });

        // Auto-select ticket type based on age
        const ageInput = document.getElementById('attendee_age');

        ageInput.addEventListener('input', function() {
            const age = parseInt(this.value);

            if (isNaN(age) || age < 0) {
                return; // Don't auto-select for invalid ages
            }

            let selectedTicket = null;

            // Determine appropriate ticket type based on age (default to weekend tickets)
            if (age >= 0 && age <= 4) {
                selectedTicket = 'free_child';
            } else if (age >= 5 && age <= 15) {
                selectedTicket = 'child_weekend';
            } else if (age >= 16) {
                selectedTicket = 'adult_weekend';
            }

            // Auto-select the appropriate ticket
            if (selectedTicket) {
                ticketRadios.forEach(radio => {
                    if (radio.value === selectedTicket) {
                        radio.checked = true;
                        // Trigger change event to update day ticket visibility
                        radio.dispatchEvent(new Event('change'));
                    }
                });
            }
        });
    </script>
</body>
</html>
