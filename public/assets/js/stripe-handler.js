/**
 * Stripe Payment Handler
 * Handles Stripe.js integration for payment and setup intents
 */

class StripePaymentHandler {
    constructor(stripePublicKey) {
        this.stripe = Stripe(stripePublicKey);
        this.elements = null;
        this.paymentElement = null;
        this.clientSecret = null;
        this.isSetupIntent = false;
    }

    /**
     * Initialize payment element
     */
    async initializePaymentElement(clientSecret, isSetupIntent = false) {
        this.clientSecret = clientSecret;
        this.isSetupIntent = isSetupIntent;

        // Create elements instance
        this.elements = this.stripe.elements({
            clientSecret: clientSecret,
            appearance: {
                theme: 'stripe',
                variables: {
                    colorPrimary: '#667eea',
                    colorBackground: '#ffffff',
                    colorText: '#333333',
                    colorDanger: '#df1b41',
                    fontFamily: '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif',
                    spacingUnit: '4px',
                    borderRadius: '8px',
                }
            }
        });

        // Create payment element
        this.paymentElement = this.elements.create('payment');
        this.paymentElement.mount('#stripe-payment-element');

        // Handle element ready
        this.paymentElement.on('ready', () => {
            document.getElementById('stripe-loading')?.classList.add('hidden');
        });

        // Handle element change
        this.paymentElement.on('change', (event) => {
            this.handleElementChange(event);
        });
    }

    /**
     * Handle payment element changes
     */
    handleElementChange(event) {
        const errorDiv = document.getElementById('stripe-errors');

        if (event.error) {
            errorDiv.textContent = event.error.message;
            errorDiv.style.display = 'block';
        } else {
            errorDiv.textContent = '';
            errorDiv.style.display = 'none';
        }
    }

    /**
     * Submit payment or setup
     */
    async submit(returnUrl) {
        try {
            // Disable submit button
            const submitBtn = document.getElementById('stripe-submit-btn');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.textContent = 'Processing...';
            }

            let result;

            if (this.isSetupIntent) {
                // Confirm setup intent (for installments)
                result = await this.stripe.confirmSetup({
                    elements: this.elements,
                    confirmParams: {
                        return_url: returnUrl,
                    },
                });
            } else {
                // Confirm payment intent (for one-time payment)
                result = await this.stripe.confirmPayment({
                    elements: this.elements,
                    confirmParams: {
                        return_url: returnUrl,
                    },
                });
            }

            // Handle errors
            if (result.error) {
                this.handleError(result.error);

                // Re-enable submit button
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.textContent = this.isSetupIntent ? 'Confirm Payment Method' : 'Pay Now';
                }
            }

            // If successful, redirect will happen automatically

        } catch (error) {
            console.error('Stripe error:', error);
            this.handleError(error);
        }
    }

    /**
     * Handle Stripe errors
     */
    handleError(error) {
        const errorDiv = document.getElementById('stripe-errors');

        if (errorDiv) {
            errorDiv.textContent = error.message || 'An unexpected error occurred.';
            errorDiv.style.display = 'block';
        }

        // Scroll to error
        errorDiv?.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }

    /**
     * Destroy payment element
     */
    destroy() {
        if (this.paymentElement) {
            this.paymentElement.destroy();
            this.paymentElement = null;
        }

        if (this.elements) {
            this.elements = null;
        }
    }
}

// Export for use in other scripts
window.StripePaymentHandler = StripePaymentHandler;
