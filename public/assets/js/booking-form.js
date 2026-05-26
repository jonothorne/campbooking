/**
 * Booking Form JavaScript
 * ECHO2027: Amplified
 * Handles dynamic attendees, price calculation, sponsor tickets, and split payments
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
        this.videoId = 'VRr_5ZLL2gg'; // TODO: Update for ECHO2027 promo

        this.init();
    }

    init() {
        this.openBtn.addEventListener('click', () => this.open());
        this.closeBtn.addEventListener('click', () => this.close());
        this.overlay.addEventListener('click', () => this.close());

        this.bookNowBtn.addEventListener('click', () => {
            this.close();
            setTimeout(() => {
                const form = document.getElementById('booking-form');
                if (form) {
                    form.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            }, 300);
        });

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && this.modal.classList.contains('active')) {
                this.close();
            }
        });
    }

    open() {
        this.iframe.src = `https://www.youtube.com/embed/${this.videoId}?autoplay=1&rel=0`;
        this.modal.classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    close() {
        this.iframe.src = '';
        this.modal.classList.remove('active');
        document.body.style.overflow = '';
    }
}

class BookingForm {
    constructor() {
        this.attendeeCount = 1;

        // Read prices from the DOM data attributes (set by PHP)
        const firstTicketSelect = document.getElementById('attendee_ticket_type_0');
        const adultOption = firstTicketSelect.querySelector('[value="adult_weekend"]');
        const childOption = firstTicketSelect.querySelector('[value="child_weekend"]');
        const sponsorOption = firstTicketSelect.querySelector('[value="adult_sponsor"]');
        const adultDayOption = firstTicketSelect.querySelector('[value="adult_day"]');
        const childDayOption = firstTicketSelect.querySelector('[value="child_day"]');

        this.prices = {
            adult: parseFloat(adultOption.dataset.price),
            child: parseFloat(childOption.dataset.price),
            sponsorSuggested: parseFloat(sponsorOption.dataset.price),
            sponsorMin: parseFloat(sponsorOption.dataset.minPrice),
            adultDay: parseFloat(adultDayOption.textContent.match(/£([\d.]+)/)[1]),
            childDay: parseFloat(childDayOption.textContent.match(/£([\d.]+)/)[1]),
            free: 0.00
        };

        this.init();
    }

    init() {
        this.setupAddAttendeeButton();
        this.setupPaymentMethodListeners();
        this.setupPaymentPlanListeners();
        this.setupBookerNameListener();
        this.setupFormSubmit();
        this.setupAttendeeListeners(0);
        this.updateTotalPrice();
        this.updatePaymentMethodVisibility();
    }

    setupAddAttendeeButton() {
        const addBtn = document.getElementById('add-attendee-btn');
        addBtn.addEventListener('click', () => this.addAttendee());
    }

    addAttendee() {
        const container = document.getElementById('attendees-container');
        const template = document.getElementById('attendee-template');

        let html = template.innerHTML;
        html = html.replace(/{INDEX}/g, this.attendeeCount);
        html = html.replace(/{NUMBER}/g, this.attendeeCount + 1);

        const temp = document.createElement('div');
        temp.innerHTML = html;
        const newAttendee = temp.firstElementChild;

        container.appendChild(newAttendee);
        this.setupAttendeeListeners(this.attendeeCount);
        this.attendeeCount++;
        this.updateTotalPrice();
    }

    setupAttendeeListeners(index) {
        const removeBtn = document.querySelector(`[data-attendee-index="${index}"] .btn-remove-attendee`);
        if (removeBtn) {
            removeBtn.addEventListener('click', () => this.removeAttendee(index));
        }

        const ageInput = document.getElementById(`attendee_age_${index}`);
        if (ageInput) {
            ageInput.addEventListener('change', () => this.handleAgeChange(index));
        }

        const ticketTypeSelect = document.getElementById(`attendee_ticket_type_${index}`);
        if (ticketTypeSelect) {
            ticketTypeSelect.addEventListener('change', () => {
                this.handleTicketTypeChange(index);
                this.updateTotalPrice();
            });
        }

        const dayCheckboxes = document.querySelectorAll(`#day_dates_${index} .day-checkbox`);
        dayCheckboxes.forEach(checkbox => {
            checkbox.addEventListener('change', () => this.updateTotalPrice());
        });

        const sponsorInput = document.getElementById(`attendee_sponsor_amount_${index}`);
        if (sponsorInput) {
            sponsorInput.addEventListener('input', () => this.updateTotalPrice());
        }
    }

    removeAttendee(index) {
        const attendeeCard = document.querySelector(`[data-attendee-index="${index}"]`);
        if (attendeeCard) {
            attendeeCard.remove();
            this.updateTotalPrice();
        }
    }

    handleAgeChange(index) {
        const ageInput = document.getElementById(`attendee_age_${index}`);
        const ticketTypeSelect = document.getElementById(`attendee_ticket_type_${index}`);

        if (!ageInput || !ticketTypeSelect) return;

        const age = parseInt(ageInput.value);
        if (isNaN(age)) return;

        if (age <= 3) {
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

        this.handleTicketTypeChange(index);
        this.updateTotalPrice();
    }

    handleTicketTypeChange(index) {
        const ticketTypeSelect = document.getElementById(`attendee_ticket_type_${index}`);
        const dayDatesDiv = document.getElementById(`day_dates_${index}`);
        const sponsorDiv = document.getElementById(`sponsor_amount_${index}`);

        if (!ticketTypeSelect) return;

        const ticketType = ticketTypeSelect.value;

        // Show/hide day selection for day tickets
        if (dayDatesDiv) {
            if (ticketType === 'adult_day' || ticketType === 'child_day') {
                dayDatesDiv.style.display = 'block';
                const checkboxes = dayDatesDiv.querySelectorAll('.day-checkbox');
                checkboxes.forEach(cb => cb.required = true);
            } else {
                dayDatesDiv.style.display = 'none';
                const checkboxes = dayDatesDiv.querySelectorAll('.day-checkbox');
                checkboxes.forEach(cb => {
                    cb.required = false;
                    cb.checked = false;
                });
            }
        }

        // Show/hide sponsor amount input
        if (sponsorDiv) {
            if (ticketType === 'adult_sponsor') {
                sponsorDiv.style.display = 'block';
                const input = sponsorDiv.querySelector('.sponsor-amount-input');
                if (input) input.required = true;
            } else {
                sponsorDiv.style.display = 'none';
                const input = sponsorDiv.querySelector('.sponsor-amount-input');
                if (input) input.required = false;
            }
        }
    }

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
                    const sponsorInput = document.getElementById(`attendee_sponsor_amount_${index}`);
                    if (sponsorInput) {
                        const customAmount = parseFloat(sponsorInput.value) || this.prices.sponsorSuggested;
                        price = Math.max(this.prices.sponsorMin, customAmount);
                    } else {
                        price = this.prices.sponsorSuggested;
                    }
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

        this.updateInstallmentPreview();
    }

    setupPaymentMethodListeners() {
        const paymentMethodRadios = document.querySelectorAll('input[name="payment_method"]');
        paymentMethodRadios.forEach(radio => {
            radio.addEventListener('change', () => {
                this.updatePaymentMethodVisibility();
            });
        });
    }

    updatePaymentMethodVisibility() {
        const selectedMethod = document.querySelector('input[name="payment_method"]:checked');
        if (!selectedMethod) return;

        const method = selectedMethod.value;
        const bankTransferDetails = document.getElementById('bank-transfer-details');
        const paymentPlanGroup = document.getElementById('payment-plan-group');
        const stripeNote = document.getElementById('stripe-note');

        if (method === 'bank_transfer') {
            bankTransferDetails.style.display = 'block';
        } else {
            bankTransferDetails.style.display = 'none';
        }

        paymentPlanGroup.style.display = 'block';

        // Only show split payment option for Stripe
        const splitRadio = document.querySelector('input[name="payment_plan"][value="split"]');
        const splitRadioCard = splitRadio ? splitRadio.closest('.radio-card') : null;

        if (method === 'stripe') {
            if (stripeNote) stripeNote.style.display = 'block';
            if (splitRadioCard) splitRadioCard.style.display = '';
            if (splitRadio) splitRadio.disabled = false;
        } else {
            if (stripeNote) stripeNote.style.display = 'none';
            if (splitRadioCard) splitRadioCard.style.display = 'none';
            if (splitRadio) splitRadio.disabled = true;

            // Reset to full payment if split was selected
            const fullRadio = document.querySelector('input[name="payment_plan"][value="1"]');
            if (fullRadio && splitRadio && splitRadio.checked) {
                fullRadio.checked = true;
                document.getElementById('split-count-group').style.display = 'none';
            }
        }

        this.updateInstallmentPreview();
    }

    setupPaymentPlanListeners() {
        const paymentPlanRadios = document.querySelectorAll('input[name="payment_plan"]');
        const splitCountGroup = document.getElementById('split-count-group');
        const splitSlider = document.getElementById('split-count');
        const splitDisplay = document.getElementById('split-count-display');

        paymentPlanRadios.forEach(radio => {
            radio.addEventListener('change', () => {
                if (radio.value === 'split') {
                    splitCountGroup.style.display = 'block';
                } else {
                    splitCountGroup.style.display = 'none';
                }
                this.updateInstallmentPreview();
            });
        });

        if (splitSlider) {
            splitSlider.addEventListener('input', () => {
                const count = splitSlider.value;
                splitDisplay.textContent = count + ' payments';
                this.updateInstallmentPreview();
            });
        }
    }

    getSelectedInstallments() {
        const selectedPlan = document.querySelector('input[name="payment_plan"]:checked');
        if (!selectedPlan) return 1;

        if (selectedPlan.value === '1') return 1;
        if (selectedPlan.value === 'split') {
            const slider = document.getElementById('split-count');
            return slider ? parseInt(slider.value) : 2;
        }
        return 1;
    }

    updateInstallmentPreview() {
        const previewDiv = document.getElementById('installment-preview');
        const detailsDiv = document.getElementById('installment-details');

        if (!previewDiv || !detailsDiv) return;

        const numInstallments = this.getSelectedInstallments();
        const total = this.calculateTotal();

        if (numInstallments <= 1 || total === 0) {
            previewDiv.style.display = 'none';
            return;
        }

        const installmentAmount = total / numInstallments;
        let html = '';

        for (let i = 1; i <= numInstallments; i++) {
            const amount = (i === numInstallments) ? (total - (installmentAmount * (numInstallments - 1))) : installmentAmount;
            const label = i === 1 ? 'Payment 1 (now)' : `Payment ${i}`;
            html += `
                <div class="installment-row">
                    <span>${label}</span>
                    <span>£${amount.toFixed(2)}</span>
                </div>
            `;
        }

        detailsDiv.innerHTML = html;
        previewDiv.style.display = 'block';
    }

    setupBookerNameListener() {
        const bookerNameInput = document.getElementById('booker_name');
        const bankReferenceSpan = document.getElementById('bank-reference');

        if (!bookerNameInput || !bankReferenceSpan) return;

        const prefix = bankReferenceSpan.textContent.split('[')[0] || 'ECHO';

        bookerNameInput.addEventListener('input', () => {
            const name = bookerNameInput.value.trim();

            if (!name) {
                bankReferenceSpan.textContent = prefix + '[YourSurname]';
                return;
            }

            const nameParts = name.split(' ');
            const surname = nameParts[nameParts.length - 1];
            const cleanSurname = surname.replace(/[^A-Za-z0-9]/g, '');

            if (cleanSurname) {
                bankReferenceSpan.textContent = prefix + cleanSurname.charAt(0).toUpperCase() + cleanSurname.slice(1).toLowerCase();
            } else {
                bankReferenceSpan.textContent = prefix + '[YourSurname]';
            }
        });
    }

    setupFormSubmit() {
        const form = document.getElementById('booking-form');
        const submitBtn = document.getElementById('submit-btn');
        this.isSubmitting = false;

        form.addEventListener('submit', (e) => {
            // Prevent double submission
            if (this.isSubmitting) {
                e.preventDefault();
                return false;
            }

            // Validate total amount first
            const total = this.calculateTotal();
            if (total === 0) {
                alert('Please add at least one paid attendee to your booking.');
                e.preventDefault();
                return false;
            }

            // Validate sponsor amounts
            const sponsorInputs = document.querySelectorAll('.sponsor-amount-input');
            let sponsorError = false;
            sponsorInputs.forEach(input => {
                const group = input.closest('.sponsor-amount-group');
                if (group && group.style.display !== 'none') {
                    const val = parseFloat(input.value);
                    if (isNaN(val) || val < this.prices.sponsorMin) {
                        alert(`Sponsor ticket amount must be at least £${this.prices.sponsorMin.toFixed(2)}`);
                        sponsorError = true;
                        input.focus();
                    }
                }
            });

            if (sponsorError) {
                e.preventDefault();
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

            // Resolve the actual payment_plan value before submit
            const numInstallments = this.getSelectedInstallments();
            const selectedPlan = document.querySelector('input[name="payment_plan"]:checked');

            // If "split" is selected, add a hidden input with the actual count
            // (don't mutate the radio value — it breaks retries)
            if (selectedPlan && selectedPlan.value === 'split') {
                const existing = form.querySelector('input[name="payment_plan_count"]');
                if (existing) existing.remove();
                const hidden = document.createElement('input');
                hidden.type = 'hidden';
                hidden.name = 'payment_plan_count';
                hidden.value = numInstallments;
                form.appendChild(hidden);
            }

            // All validation passed - lock the form to prevent double submission
            this.isSubmitting = true;
            submitBtn.disabled = true;
            submitBtn.classList.add('loading');
            submitBtn.textContent = 'Processing...';

            // Do NOT re-enable the button - the form is submitting and will redirect.
            // The server-side idempotency token prevents duplicates even if this somehow fires twice.
        });
    }
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    new VideoModal();
    new BookingForm();
});
