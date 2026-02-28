/**
 * Booking Form JavaScript
 * Handles dynamic attendees, price calculation, and form interactions
 */

/**
 * Video Modal Handler
 */
class VideoModal {
    constructor() {
        this.modal = document.getElementById('video-modal');
        this.openBtn = document.getElementById('watch-promo-btn');
        this.closeBtn = document.getElementById('close-video-btn');
        this.bookNowBtn = document.getElementById('book-now-btn');
        this.overlay = this.modal.querySelector('.video-modal-overlay');
        this.iframe = document.getElementById('promo-video');

        // Set your YouTube video ID here
        this.videoId = 'dQw4w9WgXcQ'; // Replace with actual video ID

        this.init();
    }

    init() {
        // Open modal
        this.openBtn.addEventListener('click', () => this.open());

        // Close modal
        this.closeBtn.addEventListener('click', () => this.close());
        this.overlay.addEventListener('click', () => this.close());

        // Book now button - close and scroll to form
        this.bookNowBtn.addEventListener('click', () => {
            this.close();
            // Scroll to form after a short delay to allow modal to close
            setTimeout(() => {
                const form = document.getElementById('booking-form');
                if (form) {
                    form.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            }, 300);
        });

        // Close on ESC key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && this.modal.classList.contains('active')) {
                this.close();
            }
        });
    }

    open() {
        // Set YouTube iframe src with autoplay
        this.iframe.src = `https://www.youtube.com/embed/${this.videoId}?autoplay=1&rel=0`;

        // Show modal
        this.modal.classList.add('active');

        // Prevent body scroll
        document.body.style.overflow = 'hidden';
    }

    close() {
        // Stop video by clearing src
        this.iframe.src = '';

        // Hide modal
        this.modal.classList.remove('active');

        // Restore body scroll
        document.body.style.overflow = '';
    }
}

class BookingForm {
    constructor() {
        this.attendeeCount = 1;
        this.prices = {
            adult: 85.00,
            adultSponsor: 110.00,
            child: 55.00,
            adultDay: 25.00,
            childDay: 15.00,
            free: 0.00
        };

        this.init();
    }

    init() {
        // Setup event listeners
        this.setupAddAttendeeButton();
        this.setupPaymentMethodListeners();
        this.setupPaymentPlanListeners();
        this.setupBookerNameListener();
        this.setupFormSubmit();

        // Setup listeners for first attendee
        this.setupAttendeeListeners(0);

        // Initial calculations
        this.updateTotalPrice();
        this.updatePaymentMethodVisibility();
    }

    /**
     * Setup "Add Attendee" button
     */
    setupAddAttendeeButton() {
        const addBtn = document.getElementById('add-attendee-btn');
        addBtn.addEventListener('click', () => this.addAttendee());
    }

    /**
     * Add new attendee to form
     */
    addAttendee() {
        const container = document.getElementById('attendees-container');
        const template = document.getElementById('attendee-template');

        // Clone template
        let html = template.innerHTML;

        // Replace placeholders
        html = html.replace(/{INDEX}/g, this.attendeeCount);
        html = html.replace(/{NUMBER}/g, this.attendeeCount + 1);

        // Create temporary div to hold HTML
        const temp = document.createElement('div');
        temp.innerHTML = html;
        const newAttendee = temp.firstElementChild;

        // Append to container
        container.appendChild(newAttendee);

        // Setup listeners for new attendee
        this.setupAttendeeListeners(this.attendeeCount);

        // Increment counter
        this.attendeeCount++;

        // Update price
        this.updateTotalPrice();
    }

    /**
     * Setup listeners for a specific attendee
     */
    setupAttendeeListeners(index) {
        // Remove button
        const removeBtn = document.querySelector(`[data-attendee-index="${index}"] .btn-remove-attendee`);
        if (removeBtn) {
            removeBtn.addEventListener('click', () => this.removeAttendee(index));
        }

        // Age input - suggest ticket type
        const ageInput = document.getElementById(`attendee_age_${index}`);
        if (ageInput) {
            ageInput.addEventListener('change', () => this.handleAgeChange(index));
        }

        // Ticket type - show/hide day selection
        const ticketTypeSelect = document.getElementById(`attendee_ticket_type_${index}`);
        if (ticketTypeSelect) {
            ticketTypeSelect.addEventListener('change', () => {
                this.handleTicketTypeChange(index);
                this.updateTotalPrice();
            });
        }

        // Day checkboxes - recalculate price
        const dayCheckboxes = document.querySelectorAll(`#day_dates_${index} .day-checkbox`);
        dayCheckboxes.forEach(checkbox => {
            checkbox.addEventListener('change', () => this.updateTotalPrice());
        });
    }

    /**
     * Remove attendee from form
     */
    removeAttendee(index) {
        const attendeeCard = document.querySelector(`[data-attendee-index="${index}"]`);
        if (attendeeCard) {
            attendeeCard.remove();
            this.updateTotalPrice();
        }
    }

    /**
     * Handle age input change - suggest appropriate ticket type
     */
    handleAgeChange(index) {
        const ageInput = document.getElementById(`attendee_age_${index}`);
        const ticketTypeSelect = document.getElementById(`attendee_ticket_type_${index}`);

        if (!ageInput || !ticketTypeSelect) return;

        const age = parseInt(ageInput.value);

        if (isNaN(age)) return;

        // Auto-suggest ticket type based on age
        if (age <= 4) {
            ticketTypeSelect.value = 'free_child';
        } else if (age <= 15) {
            if (!ticketTypeSelect.value || ticketTypeSelect.value === 'free_child') {
                ticketTypeSelect.value = 'child_weekend';
            }
        } else {
            if (!ticketTypeSelect.value || ticketTypeSelect.value === 'free_child' || ticketTypeSelect.value === 'child_weekend') {
                ticketTypeSelect.value = 'adult_weekend';
            }
        }

        // Trigger ticket type change
        this.handleTicketTypeChange(index);
        this.updateTotalPrice();
    }

    /**
     * Handle ticket type change - show/hide day selection
     */
    handleTicketTypeChange(index) {
        const ticketTypeSelect = document.getElementById(`attendee_ticket_type_${index}`);
        const dayDatesDiv = document.getElementById(`day_dates_${index}`);

        if (!ticketTypeSelect || !dayDatesDiv) return;

        const ticketType = ticketTypeSelect.value;

        // Show day selection for day tickets
        if (ticketType === 'adult_day' || ticketType === 'child_day') {
            dayDatesDiv.style.display = 'block';

            // Make day checkboxes required
            const checkboxes = dayDatesDiv.querySelectorAll('.day-checkbox');
            checkboxes.forEach(cb => cb.required = true);
        } else {
            dayDatesDiv.style.display = 'none';

            // Remove required from day checkboxes and uncheck them
            const checkboxes = dayDatesDiv.querySelectorAll('.day-checkbox');
            checkboxes.forEach(cb => {
                cb.required = false;
                cb.checked = false;
            });
        }
    }

    /**
     * Calculate total price from all attendees
     */
    calculateTotal() {
        let total = 0;
        const attendeeCards = document.querySelectorAll('.attendee-card');

        attendeeCards.forEach(card => {
            const index = card.getAttribute('data-attendee-index');
            const ticketTypeSelect = document.getElementById(`attendee_ticket_type_${index}`);

            if (!ticketTypeSelect || !ticketTypeSelect.value) return;

            const ticketType = ticketTypeSelect.value;
            let price = 0;

            switch (ticketType) {
                case 'adult_weekend':
                    price = this.prices.adult;
                    break;
                case 'adult_sponsor':
                    price = this.prices.adultSponsor;
                    break;
                case 'child_weekend':
                    price = this.prices.child;
                    break;
                case 'adult_day':
                    const adultDays = card.querySelectorAll('#day_dates_' + index + ' .day-checkbox:checked').length;
                    price = this.prices.adultDay * adultDays;
                    break;
                case 'child_day':
                    const childDays = card.querySelectorAll('#day_dates_' + index + ' .day-checkbox:checked').length;
                    price = this.prices.childDay * childDays;
                    break;
                case 'free_child':
                    price = 0;
                    break;
            }

            total += price;
        });

        return total;
    }

    /**
     * Update total price display
     */
    updateTotalPrice() {
        const total = this.calculateTotal();
        const totalElement = document.getElementById('total-price');
        const peopleCountElement = document.getElementById('summary-people-count');

        if (totalElement) {
            totalElement.textContent = '£' + total.toFixed(2);
        }

        if (peopleCountElement) {
            const attendeeCount = document.querySelectorAll('.attendee-card').length;
            peopleCountElement.textContent = attendeeCount;
        }

        // Update installment preview if needed
        this.updateInstallmentPreview();
    }

    /**
     * Setup payment method radio listeners
     */
    setupPaymentMethodListeners() {
        const paymentMethodRadios = document.querySelectorAll('input[name="payment_method"]');

        paymentMethodRadios.forEach(radio => {
            radio.addEventListener('change', () => {
                this.updatePaymentMethodVisibility();
            });
        });
    }

    /**
     * Update visibility based on payment method selection
     */
    updatePaymentMethodVisibility() {
        const selectedMethod = document.querySelector('input[name="payment_method"]:checked');
        if (!selectedMethod) return;

        const method = selectedMethod.value;

        const bankTransferDetails = document.getElementById('bank-transfer-details');
        const paymentPlanGroup = document.getElementById('payment-plan-group');
        const stripeNote = document.getElementById('stripe-note');

        // Show/hide bank transfer details
        if (method === 'bank_transfer') {
            bankTransferDetails.style.display = 'block';
        } else {
            bankTransferDetails.style.display = 'none';
        }

        // Show payment plan group
        paymentPlanGroup.style.display = 'block';

        // Get installment radio buttons
        const fullPaymentRadio = document.querySelector('input[name="payment_plan"][value="full"]');
        const monthlyRadio = document.querySelector('input[name="payment_plan"][value="monthly"]');
        const threePaymentRadio = document.querySelector('input[name="payment_plan"][value="three_payments"]');

        // Only allow installment plans with Stripe
        if (method === 'stripe') {
            if (stripeNote) stripeNote.style.display = 'block';

            // Enable installment options for Stripe
            if (monthlyRadio) monthlyRadio.disabled = false;
            if (threePaymentRadio) threePaymentRadio.disabled = false;
        } else {
            if (stripeNote) stripeNote.style.display = 'none';

            // Force "pay in full" for non-Stripe methods
            if (fullPaymentRadio) {
                fullPaymentRadio.checked = true;
            }

            // Disable installment options for non-Stripe
            if (monthlyRadio) monthlyRadio.disabled = true;
            if (threePaymentRadio) threePaymentRadio.disabled = true;
        }

        this.updateInstallmentPreview();
    }

    /**
     * Setup payment plan radio listeners
     */
    setupPaymentPlanListeners() {
        const paymentPlanRadios = document.querySelectorAll('input[name="payment_plan"]');

        paymentPlanRadios.forEach(radio => {
            radio.addEventListener('change', () => {
                this.updateInstallmentPreview();
            });
        });
    }

    /**
     * Update installment preview display
     */
    updateInstallmentPreview() {
        const selectedPlan = document.querySelector('input[name="payment_plan"]:checked');
        const previewDiv = document.getElementById('installment-preview');
        const detailsDiv = document.getElementById('installment-details');

        if (!selectedPlan || !previewDiv || !detailsDiv) return;

        const plan = selectedPlan.value;
        const total = this.calculateTotal();

        if (plan === 'full' || total === 0) {
            previewDiv.style.display = 'none';
            return;
        }

        // Calculate installments
        let installments = [];

        if (plan === 'monthly') {
            // Calculate months until payment deadline (May 20, 2026) - matches server logic
            const now = new Date();
            const paymentDeadline = new Date('2026-05-20');

            // Calculate months properly (not just days/30)
            let months = (paymentDeadline.getFullYear() - now.getFullYear()) * 12;
            months += paymentDeadline.getMonth() - now.getMonth();

            // If there are remaining days, count as additional month
            if (paymentDeadline.getDate() > now.getDate()) {
                months++;
            }

            months = Math.max(1, months); // At least 1 month
            const monthlyAmount = total / months;

            for (let i = 1; i <= months; i++) {
                const amount = (i === months) ? (total - (monthlyAmount * (months - 1))) : monthlyAmount;
                installments.push({
                    number: i,
                    amount: amount.toFixed(2)
                });
            }
        } else if (plan === 'three_payments') {
            const paymentAmount = total / 3;

            for (let i = 1; i <= 3; i++) {
                const amount = (i === 3) ? (total - (paymentAmount * 2)) : paymentAmount;
                installments.push({
                    number: i,
                    amount: amount.toFixed(2)
                });
            }
        }

        // Build HTML
        let html = '';
        installments.forEach(inst => {
            html += `
                <div class="installment-row">
                    <span>Payment ${inst.number}</span>
                    <span>£${inst.amount}</span>
                </div>
            `;
        });

        detailsDiv.innerHTML = html;
        previewDiv.style.display = 'block';
    }

    /**
     * Setup booker name listener to update bank reference
     */
    setupBookerNameListener() {
        const bookerNameInput = document.getElementById('booker_name');
        const bankReferenceSpan = document.getElementById('bank-reference');

        if (!bookerNameInput || !bankReferenceSpan) return;

        bookerNameInput.addEventListener('input', () => {
            const name = bookerNameInput.value.trim();

            if (!name) {
                bankReferenceSpan.textContent = 'Camp[YourSurname]';
                return;
            }

            // Extract surname (last word)
            const nameParts = name.split(' ');
            const surname = nameParts[nameParts.length - 1];

            // Clean surname (alphanumeric only)
            const cleanSurname = surname.replace(/[^A-Za-z0-9]/g, '');

            if (cleanSurname) {
                bankReferenceSpan.textContent = 'Camp' + cleanSurname.charAt(0).toUpperCase() + cleanSurname.slice(1).toLowerCase();
            } else {
                bankReferenceSpan.textContent = 'Camp[YourSurname]';
            }
        });
    }

    /**
     * Setup form submission
     */
    setupFormSubmit() {
        const form = document.getElementById('booking-form');
        const submitBtn = document.getElementById('submit-btn');

        form.addEventListener('submit', (e) => {
            // Generate unique submission token to prevent duplicate submissions
            const submissionToken = Date.now() + '-' + Math.random().toString(36).substr(2, 9);
            const tokenInput = document.createElement('input');
            tokenInput.type = 'hidden';
            tokenInput.name = 'submission_token';
            tokenInput.value = submissionToken;
            form.appendChild(tokenInput);

            // Disable submit button to prevent double-click
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.textContent = 'Processing...';
            }

            // Basic validation
            const total = this.calculateTotal();

            if (total === 0) {
                alert('Please add at least one paid attendee to your booking.');
                e.preventDefault();
                // Re-enable submit button
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.textContent = 'Complete Booking';
                }
                return false;
            }

            // Check day tickets have days selected
            const dayTickets = document.querySelectorAll('.ticket-type-select');
            let dayTicketError = false;

            dayTickets.forEach(select => {
                const value = select.value;
                if (value === 'adult_day' || value === 'child_day') {
                    const index = select.id.split('_').pop();
                    const checkedDays = document.querySelectorAll(`#day_dates_${index} .day-checkbox:checked`).length;

                    if (checkedDays === 0) {
                        dayTicketError = true;
                    }
                }
            });

            if (dayTicketError) {
                alert('Please select at least one day for each day ticket.');
                e.preventDefault();
                return false;
            }

            // Disable submit button to prevent double submission
            submitBtn.disabled = true;
            submitBtn.classList.add('loading');
            submitBtn.textContent = 'Processing...';

            // Re-enable after 3 seconds (in case of error)
            setTimeout(() => {
                submitBtn.disabled = false;
                submitBtn.classList.remove('loading');
                submitBtn.textContent = 'Complete Booking';
            }, 3000);
        });
    }
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    new VideoModal();
    new BookingForm();
});
