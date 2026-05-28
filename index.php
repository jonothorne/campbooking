<?php
/**
 * Public Booking Form
 * ECHO2027: Amplified
 */

// Initialize
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/sanitize.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/classes/Booking.php';

// Start session for error messages
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Get error message if any
$error = null;
$errorEmailExists = false;
if (isset($_SESSION['booking_error_email_exists'])) {
    $errorEmailExists = true;
    unset($_SESSION['booking_error_email_exists']);
} elseif (isset($_SESSION['booking_error'])) {
    $error = $_SESSION['booking_error'];
    unset($_SESSION['booking_error']);
}

// Check if payment was cancelled
$paymentCancelled = isset($_SESSION['payment_cancelled']) && $_SESSION['payment_cancelled'] === true;
$formData = isset($_SESSION['booking_form_data']) ? $_SESSION['booking_form_data'] : null;

// Clear flags and form data after reading (will be output to JavaScript first)
if ($paymentCancelled) {
    unset($_SESSION['payment_cancelled']);
}

// Check if there's a pending booking from a cancelled Stripe payment
$pendingBookingId = $_SESSION['pending_booking_id'] ?? null;
if ($paymentCancelled && $pendingBookingId) {
    // Delete the orphaned pending booking so user can resubmit cleanly
    try {
        $pendingBooking = new Booking($pendingBookingId);
        $pendingData = $pendingBooking->getData();
        // Only delete if still pending and unpaid
        if ($pendingData['booking_status'] === 'pending' && $pendingData['payment_status'] === 'unpaid') {
            $pendingBooking->delete();
        }
    } catch (Exception $e) {
        // Booking may already be gone, that's fine
    }
    unset($_SESSION['pending_booking_id']);
}

// Generate a unique idempotency token for this form load
// This prevents double submission even across page reloads
$idempotencyToken = bin2hex(random_bytes(32));
$_SESSION['idempotency_token'] = $idempotencyToken;

// Get event dates for day tickets
$eventDates = getEventDatesFormatted();

// Early bird status
$earlyBird = isEarlyBird();
$earlyBirdCutoff = formatDate(EARLY_BIRD_CUTOFF, 'jS F Y');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Your Place - <?php echo e(EVENT_NAME); ?></title>
    <link rel="stylesheet" href="<?php echo basePath('public/assets/css/main.css'); ?>">
</head>
<body>
    <!-- Hero Section -->
    <section class="hero">
        <div class="hero-content">
            <div class="hero-logo">
                <img src="<?php echo basePath('public/assets/images/echo-logo.png'); ?>" alt="ECHO2027"/>
            </div>
            <div class="hero-strapline">
                <img src="<?php echo basePath('public/assets/images/strapline.png'); ?>" alt="We won't let the stones cry out - &quot;I tell you,&quot; Jesus said, &quot;if they keep quiet, the stones will cry out.&quot; Luke 19:40">
            </div>
            <button type="button" class="hero-cta" id="watch-promo-btn">WATCH THE PROMO</button>
        </div>
    </section>

    <!-- Video Modal -->
    <div id="video-modal" class="video-modal">
        <div class="video-modal-overlay"></div>
        <div class="video-modal-content">
            <button type="button" class="video-modal-close" id="close-video-btn">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
            </button>
            <div class="video-container">
                <iframe
                    id="promo-video"
                    width="100%"
                    height="100%"
                    src=""
                    frameborder="0"
                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                    allowfullscreen>
                </iframe>
            </div>
            <div class="video-modal-cta">
                <button type="button" class="btn" id="book-now-btn">BOOK IN NOW</button>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="booking-wrapper">
            <!-- Sidebar -->
            <aside class="sidebar">
                <div class="sidebar-logo">
                    <img src="<?php echo basePath('public/assets/images/echo-logo.png'); ?>" alt="ECHO2027"/>
                </div>

                <div class="event-info">
                    <p style="color: rgba(255,255,255,0.5); font-size: 13px; letter-spacing: 2px; margin-bottom: 24px; text-transform: uppercase;">Resounding the name of Jesus like an echo</p>

                    <div class="event-detail">
                        <div class="event-detail-label">When</div>
                        <div class="event-detail-value">
                            <?php echo formatDate(EVENT_START_DATE, 'jS F'); ?> - <?php echo formatDate(EVENT_END_DATE, 'jS F Y'); ?>
                        </div>
                    </div>

                    <div class="event-detail">
                        <div class="event-detail-label">Where</div>
                        <div class="event-detail-value">Sizewell Hall, Sizewell, Leiston, Suffolk, IP16 4TX</div>
                    </div>

                    <div class="event-detail">
                        <div class="event-detail-label">Duration</div>
                        <div class="event-detail-value">Four days of faith, worship, and community</div>
                    </div>

                    <?php if ($earlyBird): ?>
                    <div class="early-bird-badge">
                        Early Bird Pricing
                        <span class="early-bird-until">Book before <?php echo $earlyBirdCutoff; ?> to save!</span>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="call-section">
                    <h3>Pricing</h3>
                    <div class="price-list">
                        <div class="price-item">
                            <span>Adult Ticket</span>
                            <span><?php echo formatCurrency(ACTIVE_ADULT_PRICE); ?></span>
                        </div>
                        <div class="price-item">
                            <span>Child Ticket (4-15)</span>
                            <span><?php echo formatCurrency(ACTIVE_CHILD_PRICE); ?></span>
                        </div>
                        <div class="price-item">
                            <span>Under 4s</span>
                            <span>Free</span>
                        </div>
                        <div class="price-item">
                            <span>Adult Day Ticket</span>
                            <span><?php echo formatCurrency(ACTIVE_ADULT_DAY_PRICE); ?>/day</span>
                        </div>
                        <div class="price-item">
                            <span>Child Day Ticket</span>
                            <span><?php echo formatCurrency(ACTIVE_CHILD_DAY_PRICE); ?>/day</span>
                        </div>
                    </div>
                    <p class="day-ticket-note">Day tickets include all food and activities.</p>
                </div>
            </aside>

            <!-- Form Container -->
            <main class="form-container">
                <!-- Error Messages -->
                <?php if ($errorEmailExists): ?>
                    <div class="alert alert-danger">
                        <strong>Booking Already Exists</strong><br>
                        A booking already exists for this email address. Please
                        <a href="<?php echo basePath('portal/login.php'); ?>" style="color: inherit; font-weight: bold; text-decoration: underline;">log in to your booking portal</a>
                        to view or edit your existing booking, or add more attendees.
                    </div>
                <?php elseif ($error): ?>
                    <div class="alert alert-danger">
                        <strong>Error:</strong> <?php echo e($error); ?>
                    </div>
                <?php endif; ?>

                <!-- Payment Cancelled Message -->
                <?php if ($paymentCancelled): ?>
                    <div class="alert alert-warning" id="payment-cancelled-alert">
                        <strong>Payment Cancelled</strong><br>
                        Your payment was cancelled and no charges were made. Your booking information has been preserved below. You can choose a different payment method and submit again.
                    </div>
                <?php endif; ?>

        <!-- Booking Form -->
        <form id="booking-form" method="POST" action="<?php echo basePath('process-booking.php'); ?>">
            <?php csrfField(); ?>
            <input type="hidden" name="idempotency_token" value="<?php echo e($idempotencyToken); ?>">

            <!-- Step 1: Your Details -->
            <section class="form-section">
                <h2>Your Details</h2>
                <p class="section-description">Please provide your contact information</p>

                <div class="form-row">
                    <div class="form-group">
                        <label for="booker_name">Full Name <span class="required">*</span></label>
                        <input
                            type="text"
                            id="booker_name"
                            name="booker_name"
                            required
                            placeholder="John Smith"
                        >
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="booker_email">Email Address <span class="required">*</span></label>
                        <input
                            type="email"
                            id="booker_email"
                            name="booker_email"
                            required
                            placeholder="john.smith@example.com"
                        >
                    </div>

                    <div class="form-group">
                        <label for="booker_phone">Phone Number <span class="required">*</span></label>
                        <input
                            type="tel"
                            id="booker_phone"
                            name="booker_phone"
                            required
                            placeholder="07123 456789"
                        >
                    </div>
                </div>
            </section>

            <!-- Step 2: Attendees -->
            <section class="form-section">
                <h2>Who's Coming?</h2>
                <p class="section-description">Add everyone who will be attending camp</p>

                <div id="attendees-container">
                    <!-- First attendee (always present) -->
                    <div class="attendee-card" data-attendee-index="0">
                        <div class="attendee-header">
                            <h3>Person 1</h3>
                            <button type="button" class="btn-remove-attendee" style="display:none;">Remove</button>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="attendee_name_0">Full Name <span class="required">*</span></label>
                                <input
                                    type="text"
                                    id="attendee_name_0"
                                    name="attendees[0][name]"
                                    required
                                    placeholder="Full name"
                                >
                            </div>

                            <div class="form-group">
                                <label for="attendee_age_0">Age <span class="required">*</span></label>
                                <input
                                    type="number"
                                    id="attendee_age_0"
                                    name="attendees[0][age]"
                                    required
                                    min="0"
                                    max="120"
                                    placeholder="Age"
                                    class="attendee-age-input"
                                >
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="attendee_ticket_type_0">Ticket Type <span class="required">*</span></label>
                            <select
                                id="attendee_ticket_type_0"
                                name="attendees[0][ticket_type]"
                                required
                                class="ticket-type-select"
                            >
                                <option value="">Select ticket type</option>
                                <option value="adult_weekend" data-price="<?php echo ACTIVE_ADULT_PRICE; ?>">
                                    Adult Ticket (<?php echo formatCurrency(ACTIVE_ADULT_PRICE); ?>)
                                </option>
                                <option value="adult_sponsor" data-price="<?php echo ADULT_SPONSOR_SUGGESTED; ?>" data-min-price="<?php echo ACTIVE_SPONSOR_MIN; ?>">
                                    Adult Sponsor Ticket (suggested <?php echo formatCurrency(ADULT_SPONSOR_SUGGESTED); ?>) - Help fund a young person
                                </option>
                                <option value="child_weekend" data-price="<?php echo ACTIVE_CHILD_PRICE; ?>">
                                    Child Ticket (<?php echo formatCurrency(ACTIVE_CHILD_PRICE); ?>)
                                </option>
                                <option value="adult_day">Adult Day Ticket (<?php echo formatCurrency(ACTIVE_ADULT_DAY_PRICE); ?> per day)</option>
                                <option value="child_day">Child Day Ticket (<?php echo formatCurrency(ACTIVE_CHILD_DAY_PRICE); ?> per day)</option>
                                <option value="free_child" data-price="0">Free (Under 4s)</option>
                            </select>
                        </div>

                        <!-- Sponsor custom amount (hidden by default) -->
                        <div class="form-group sponsor-amount-group" id="sponsor_amount_0" style="display:none;">
                            <label for="attendee_sponsor_amount_0">Sponsor Amount (minimum <?php echo formatCurrency(ACTIVE_SPONSOR_MIN); ?>) <span class="required">*</span></label>
                            <div class="input-with-prefix">
                                <span class="input-prefix">£</span>
                                <input
                                    type="number"
                                    id="attendee_sponsor_amount_0"
                                    name="attendees[0][sponsor_amount]"
                                    min="<?php echo ACTIVE_SPONSOR_MIN; ?>"
                                    step="0.01"
                                    value="<?php echo ADULT_SPONSOR_SUGGESTED; ?>"
                                    class="sponsor-amount-input"
                                >
                            </div>
                            <p class="field-hint">Choose how much you'd like to pay. The extra goes towards funding a young person's place.</p>
                        </div>

                        <!-- Day ticket date selection (hidden by default) -->
                        <div class="form-group day-ticket-dates" id="day_dates_0" style="display:none;">
                            <label>Select Days <span class="required">*</span></label>
                            <div class="checkbox-group">
                                <?php foreach ($eventDates as $date): ?>
                                    <label class="checkbox-label">
                                        <input
                                            type="checkbox"
                                            name="attendees[0][days][]"
                                            value="<?php echo e($date['date']); ?>"
                                            class="day-checkbox"
                                        >
                                        <?php echo e($date['display']); ?>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <button type="button" id="add-attendee-btn" class="btn btn-secondary">
                    + Add Another Person
                </button>
            </section>

            <!-- Step 3: Camping Requirements -->
            <section class="form-section">
                <h2>Camping Requirements</h2>
                <p class="section-description">Let us know about your camping needs</p>

                <div class="form-row">
                    <div class="form-group">
                        <label for="num_tents">Number of Tents Bringing</label>
                        <input
                            type="number"
                            id="num_tents"
                            name="num_tents"
                            min="0"
                            value="0"
                            placeholder="0"
                        >
                    </div>
                </div>

                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="has_caravan" value="1">
                        I'm bringing a caravan or campervan
                    </label>
                </div>

                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="needs_tent_provided" value="1" id="needs_tent_provided">
                        I need a tent provided
                    </label>
                </div>

                <div class="form-group" id="tent-details-group" style="display:none;">
                    <label for="tent_details">Tent Details</label>
                    <textarea
                        id="tent_details"
                        name="tent_details"
                        rows="3"
                        placeholder="How many people need to sleep in the tent? Any other requirements?"
                    ></textarea>
                </div>

                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="needs_transport" value="1" id="needs_transport">
                        I need transport to the event
                    </label>
                </div>

                <div class="form-group" id="transport-details-group" style="display:none;">
                    <label for="transport_details">Transport Details</label>
                    <textarea
                        id="transport_details"
                        name="transport_details"
                        rows="3"
                        placeholder="Where will you be travelling from? How many people need transport?"
                    ></textarea>
                </div>

                <div class="form-group">
                    <label for="special_requirements">Special Requirements or Dietary Needs</label>
                    <textarea
                        id="special_requirements"
                        name="special_requirements"
                        rows="4"
                        placeholder="Any allergies, dietary requirements, accessibility needs, or other information we should know..."
                    ></textarea>
                </div>
            </section>

            <!-- Step 4: Payment -->
            <section class="form-section">
                <h2>Payment</h2>
                <p class="section-description">Choose your payment method and plan</p>

                <!-- Discount Code -->
                <div class="form-group">
                    <label for="discount_code">Discount Code</label>
                    <div style="display: flex; gap: 10px; align-items: flex-start;">
                        <input
                            type="text"
                            id="discount_code"
                            name="discount_code"
                            placeholder="Enter code if you have one"
                            style="text-transform: uppercase; font-family: monospace; letter-spacing: 1px; flex: 1;"
                        >
                        <button type="button" id="apply-discount-btn" class="btn" style="white-space: nowrap;">
                            Apply
                        </button>
                    </div>
                    <div id="discount-feedback" style="margin-top: 8px; display: none;"></div>
                </div>

                <div class="form-group">
                    <label>Payment Method <span class="required">*</span></label>
                    <div class="radio-group">
                        <label class="radio-card">
                            <input type="radio" name="payment_method" value="stripe" required checked>
                            <div class="radio-content">
                                <strong>Card / Apple Pay / Google Pay</strong>
                                <p>Pay securely online with Stripe</p>
                            </div>
                        </label>

                        <label class="radio-card">
                            <input type="radio" name="payment_method" value="bank_transfer" required>
                            <div class="radio-content">
                                <strong>Bank Transfer</strong>
                                <p>Transfer to our bank account</p>
                            </div>
                        </label>

                        <label class="radio-card">
                            <input type="radio" name="payment_method" value="cash" required>
                            <div class="radio-content">
                                <strong>Cash</strong>
                                <p>Pay cash to your group leader</p>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- Bank transfer details (shown when selected) -->
                <div id="bank-transfer-details" class="info-box" style="display:none;">
                    <h4>Bank Transfer Details</h4>
                    <div class="bank-details">
                        <div class="bank-detail-row">
                            <span class="label">Bank Name:</span>
                            <span class="value"><?php echo e(BANK_NAME); ?></span>
                        </div>
                        <div class="bank-detail-row">
                            <span class="label">Account Number:</span>
                            <span class="value"><?php echo e(BANK_ACCOUNT); ?></span>
                        </div>
                        <div class="bank-detail-row">
                            <span class="label">Sort Code:</span>
                            <span class="value"><?php echo e(BANK_SORT_CODE); ?></span>
                        </div>
                        <div class="bank-detail-row">
                            <span class="label">Reference:</span>
                            <span class="value" id="bank-reference"><?php echo e(BANK_REFERENCE_PREFIX); ?>[YourSurname]</span>
                        </div>
                    </div>
                    <p class="bank-note">Please use the reference shown above to help us identify your payment.</p>
                </div>

                <div class="form-group" id="payment-plan-group">
                    <label>Payment Plan <span class="required">*</span></label>
                    <div class="payment-plan-selector">
                        <label class="radio-card">
                            <input type="radio" name="payment_plan" value="1" required checked>
                            <div class="radio-content">
                                <strong>Pay in Full</strong>
                                <p>Single payment now</p>
                            </div>
                        </label>

                        <label class="radio-card">
                            <input type="radio" name="payment_plan" value="split" required>
                            <div class="radio-content">
                                <strong>Split Payments</strong>
                                <p>Spread across up to <?php echo MAX_INSTALLMENTS; ?> installments</p>
                            </div>
                        </label>
                    </div>

                    <!-- Split payment count selector (hidden by default) -->
                    <div id="split-count-group" style="display:none;">
                        <label for="split-count">How many payments?</label>
                        <div class="split-count-selector">
                            <input
                                type="range"
                                id="split-count"
                                min="2"
                                max="<?php echo MAX_INSTALLMENTS; ?>"
                                value="3"
                                class="split-slider"
                            >
                            <span id="split-count-display" class="split-count-display">3 payments</span>
                        </div>
                    </div>
                </div>

                <!-- Installment preview -->
                <div id="installment-preview" class="info-box" style="display:none;">
                    <h4>Payment Schedule</h4>
                    <div id="installment-details"></div>
                </div>

                <!-- Stripe note -->
                <div id="stripe-note" class="info-box">
                    <p><strong>Note:</strong> For split payments, your card details will be securely saved by Stripe for automatic future payments.</p>
                </div>
            </section>

            <!-- Price Summary -->
            <section class="price-summary">
                <h2>Booking Summary</h2>
                <div class="summary-content">
                    <div class="summary-row">
                        <span>Number of people:</span>
                        <span id="summary-people-count">1</span>
                    </div>
                    <?php if ($earlyBird): ?>
                    <div class="summary-row early-bird-row">
                        <span>Early Bird Pricing</span>
                        <span class="early-bird-tag">Active</span>
                    </div>
                    <?php endif; ?>
                    <div class="summary-row discount-row" id="discount-summary-row" style="display: none; color: #28a745;">
                        <span>Discount (<span id="discount-summary-label"></span>):</span>
                        <span id="discount-summary-amount">-£0.00</span>
                    </div>
                    <div class="summary-row total">
                        <span>Total Amount:</span>
                        <span id="total-price">£0.00</span>
                    </div>
                </div>
            </section>

            <!-- Submit Button -->
            <div class="form-actions">
                <button type="submit" class="btn btn-primary btn-large" id="submit-btn">
                    Complete Booking
                </button>
            </div>
        </form>
            </main>
        </div>
    </div>

    <!-- Attendee Template (for cloning) -->
    <template id="attendee-template">
        <div class="attendee-card" data-attendee-index="{INDEX}">
            <div class="attendee-header">
                <h3>Person {NUMBER}</h3>
                <button type="button" class="btn-remove-attendee">Remove</button>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="attendee_name_{INDEX}">Full Name <span class="required">*</span></label>
                    <input
                        type="text"
                        id="attendee_name_{INDEX}"
                        name="attendees[{INDEX}][name]"
                        required
                        placeholder="Full name"
                    >
                </div>

                <div class="form-group">
                    <label for="attendee_age_{INDEX}">Age <span class="required">*</span></label>
                    <input
                        type="number"
                        id="attendee_age_{INDEX}"
                        name="attendees[{INDEX}][age]"
                        required
                        min="0"
                        max="120"
                        placeholder="Age"
                        class="attendee-age-input"
                    >
                </div>
            </div>

            <div class="form-group">
                <label for="attendee_ticket_type_{INDEX}">Ticket Type <span class="required">*</span></label>
                <select
                    id="attendee_ticket_type_{INDEX}"
                    name="attendees[{INDEX}][ticket_type]"
                    required
                    class="ticket-type-select"
                >
                    <option value="">Select ticket type</option>
                    <option value="adult_weekend" data-price="<?php echo ACTIVE_ADULT_PRICE; ?>">
                        Adult Ticket (<?php echo formatCurrency(ACTIVE_ADULT_PRICE); ?>)
                    </option>
                    <option value="adult_sponsor" data-price="<?php echo ADULT_SPONSOR_SUGGESTED; ?>" data-min-price="<?php echo ACTIVE_SPONSOR_MIN; ?>">
                        Adult Sponsor Ticket (suggested <?php echo formatCurrency(ADULT_SPONSOR_SUGGESTED); ?>) - Help fund a young person
                    </option>
                    <option value="child_weekend" data-price="<?php echo ACTIVE_CHILD_PRICE; ?>">
                        Child Ticket (<?php echo formatCurrency(ACTIVE_CHILD_PRICE); ?>)
                    </option>
                    <option value="adult_day">Adult Day Ticket (<?php echo formatCurrency(ACTIVE_ADULT_DAY_PRICE); ?> per day)</option>
                    <option value="child_day">Child Day Ticket (<?php echo formatCurrency(ACTIVE_CHILD_DAY_PRICE); ?> per day)</option>
                    <option value="free_child" data-price="0">Free (Under 4s)</option>
                </select>
            </div>

            <!-- Sponsor custom amount -->
            <div class="form-group sponsor-amount-group" id="sponsor_amount_{INDEX}" style="display:none;">
                <label for="attendee_sponsor_amount_{INDEX}">Sponsor Amount (minimum <?php echo formatCurrency(ACTIVE_SPONSOR_MIN); ?>) <span class="required">*</span></label>
                <div class="input-with-prefix">
                    <span class="input-prefix">£</span>
                    <input
                        type="number"
                        id="attendee_sponsor_amount_{INDEX}"
                        name="attendees[{INDEX}][sponsor_amount]"
                        min="<?php echo ACTIVE_SPONSOR_MIN; ?>"
                        step="0.01"
                        value="<?php echo ADULT_SPONSOR_SUGGESTED; ?>"
                        class="sponsor-amount-input"
                    >
                </div>
                <p class="field-hint">Choose how much you'd like to pay. The extra goes towards funding a young person's place.</p>
            </div>

            <!-- Day ticket date selection -->
            <div class="form-group day-ticket-dates" id="day_dates_{INDEX}" style="display:none;">
                <label>Select Days <span class="required">*</span></label>
                <div class="checkbox-group">
                    <?php foreach ($eventDates as $date): ?>
                        <label class="checkbox-label">
                            <input
                                type="checkbox"
                                name="attendees[{INDEX}][days][]"
                                value="<?php echo e($date['date']); ?>"
                                class="day-checkbox"
                            >
                            <?php echo e($date['display']); ?>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </template>

    <script src="<?php echo basePath('public/assets/js/booking-form.js'); ?>"></script>
    <script>
    document.getElementById('needs_tent_provided').addEventListener('change', function() {
        document.getElementById('tent-details-group').style.display = this.checked ? 'block' : 'none';
    });
    document.getElementById('needs_transport').addEventListener('change', function() {
        document.getElementById('transport-details-group').style.display = this.checked ? 'block' : 'none';
    });
    </script>

    <?php if ($paymentCancelled && $formData): ?>
    <script>
    // Restore form data after payment cancellation
    document.addEventListener('DOMContentLoaded', function() {
        const formData = <?php echo json_encode($formData, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;

        console.log('Restoring form data after payment cancellation:', formData);

        // Restore booker information
        if (formData.booker) {
            const booker = formData.booker;
            if (booker.booker_name) document.querySelector('[name="booker_name"]').value = booker.booker_name;
            if (booker.booker_email) document.querySelector('[name="booker_email"]').value = booker.booker_email;
            if (booker.booker_phone) document.querySelector('[name="booker_phone"]').value = booker.booker_phone;
            if (booker.num_tents) document.querySelector('[name="num_tents"]').value = booker.num_tents;
            if (booker.has_caravan) document.querySelector('[name="has_caravan"]').checked = booker.has_caravan;
            if (booker.needs_tent_provided) document.querySelector('[name="needs_tent_provided"]').checked = booker.needs_tent_provided;
            if (booker.special_requirements) document.querySelector('[name="special_requirements"]').value = booker.special_requirements;
        }

        // Restore attendees
        if (formData.attendees && formData.attendees.length > 0) {
            const attendeesContainer = document.getElementById('attendees-container');
            const firstAttendee = attendeesContainer.querySelector('.attendee-card');

            formData.attendees.forEach((attendee, index) => {
                let attendeeCard;

                if (index === 0) {
                    attendeeCard = firstAttendee;
                } else {
                    document.getElementById('add-attendee-btn').click();
                    setTimeout(() => {
                        const cards = attendeesContainer.querySelectorAll('.attendee-card');
                        attendeeCard = cards[index];
                        populateAttendeeCard(attendeeCard, attendee, index);
                    }, 100 * index);
                    return;
                }

                populateAttendeeCard(attendeeCard, attendee, index);
            });
        }

        function populateAttendeeCard(card, attendee, index) {
            if (!card) return;

            const nameInput = card.querySelector('[name="attendees[' + index + '][name]"]');
            const ageInput = card.querySelector('[name="attendees[' + index + '][age]"]');
            const ticketSelect = card.querySelector('[name="attendees[' + index + '][ticket_type]"]');

            if (nameInput && attendee.name) nameInput.value = attendee.name;
            if (ageInput && attendee.age) ageInput.value = attendee.age;
            if (ticketSelect && attendee.ticket_type) ticketSelect.value = attendee.ticket_type;

            // Trigger change event to update price calculation
            if (ticketSelect) ticketSelect.dispatchEvent(new Event('change', { bubbles: true }));
        }

        // Scroll to payment method section after a short delay
        setTimeout(() => {
            const paymentSection = document.querySelector('[name="payment_method"]');
            if (paymentSection) {
                paymentSection.closest('.form-section').scrollIntoView({
                    behavior: 'smooth',
                    block: 'center'
                });

                const section = paymentSection.closest('.form-section');
                section.style.transition = 'box-shadow 0.3s';
                section.style.boxShadow = '0 0 0 4px rgba(0, 229, 255, 0.3)';
                setTimeout(() => {
                    section.style.boxShadow = '';
                }, 2000);
            }
        }, 1000);
    });
    </script>
    <?php endif; ?>
</body>
</html>
