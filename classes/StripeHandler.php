<?php
/**
 * Stripe Payment Handler
 * Handles all Stripe API interactions for payments and subscriptions
 */

use Stripe\Stripe;
use Stripe\PaymentIntent;
use Stripe\SetupIntent;
use Stripe\Customer;
use Stripe\PaymentMethod;
use Stripe\Exception\ApiErrorException;

class StripeHandler
{
    private $secretKey;
    private $publicKey;

    public function __construct()
    {
        $this->secretKey = env('STRIPE_SECRET_KEY');
        $this->publicKey = env('STRIPE_PUBLIC_KEY');

        // Set Stripe API key
        Stripe::setApiKey($this->secretKey);
    }

    /**
     * Get Stripe public key
     */
    public function getPublicKey()
    {
        return $this->publicKey;
    }

    /**
     * Create a Payment Intent for one-time payment
     *
     * @param float $amount Amount in GBP
     * @param int $bookingId Booking ID
     * @param string $customerEmail Customer email
     * @param string $description Payment description
     * @return array ['client_secret' => string, 'payment_intent_id' => string]
     */
    public function createPaymentIntent($amount, $bookingId, $customerEmail, $description = null)
    {
        try {
            // Convert amount to pence (Stripe uses smallest currency unit)
            $amountInPence = (int)round($amount * 100);

            $paymentIntent = PaymentIntent::create([
                'amount' => $amountInPence,
                'currency' => 'gbp',
                'receipt_email' => $customerEmail,
                'description' => $description ?? "Camp Booking #{$bookingId}",
                'metadata' => [
                    'booking_id' => $bookingId,
                    'payment_type' => 'full_payment'
                ],
                'automatic_payment_methods' => [
                    'enabled' => true,
                ],
            ]);

            return [
                'client_secret' => $paymentIntent->client_secret,
                'payment_intent_id' => $paymentIntent->id
            ];

        } catch (ApiErrorException $e) {
            error_log("Stripe Payment Intent Error: " . $e->getMessage());
            throw new Exception("Failed to create payment: " . $e->getMessage());
        }
    }

    /**
     * Create a Setup Intent for saving payment method (installments)
     *
     * @param int $bookingId Booking ID
     * @param string $customerEmail Customer email
     * @param string $customerName Customer name
     * @return array ['client_secret' => string, 'setup_intent_id' => string, 'customer_id' => string]
     */
    public function createSetupIntent($bookingId, $customerEmail, $customerName)
    {
        try {
            // Create or retrieve Stripe customer
            $customer = $this->createCustomer($customerEmail, $customerName, $bookingId);

            // Create Setup Intent
            $setupIntent = SetupIntent::create([
                'customer' => $customer->id,
                'payment_method_types' => ['card'],
                'metadata' => [
                    'booking_id' => $bookingId,
                    'payment_type' => 'installment_setup'
                ],
            ]);

            return [
                'client_secret' => $setupIntent->client_secret,
                'setup_intent_id' => $setupIntent->id,
                'customer_id' => $customer->id
            ];

        } catch (ApiErrorException $e) {
            error_log("Stripe Setup Intent Error: " . $e->getMessage());
            throw new Exception("Failed to setup payment method: " . $e->getMessage());
        }
    }

    /**
     * Create Stripe customer
     *
     * @param string $email Customer email
     * @param string $name Customer name
     * @param int $bookingId Booking ID
     * @return \Stripe\Customer
     */
    private function createCustomer($email, $name, $bookingId)
    {
        try {
            // Check if customer already exists
            $customers = Customer::all([
                'email' => $email,
                'limit' => 1
            ]);

            if (!empty($customers->data)) {
                return $customers->data[0];
            }

            // Create new customer
            return Customer::create([
                'email' => $email,
                'name' => $name,
                'metadata' => [
                    'booking_id' => $bookingId
                ]
            ]);

        } catch (ApiErrorException $e) {
            error_log("Stripe Customer Error: " . $e->getMessage());
            throw new Exception("Failed to create customer: " . $e->getMessage());
        }
    }

    /**
     * Charge a saved payment method
     *
     * @param string $paymentMethodId Stripe payment method ID
     * @param string $customerId Stripe customer ID
     * @param float $amount Amount in GBP
     * @param int $bookingId Booking ID
     * @param int $installmentNumber Installment number
     * @return array ['payment_intent_id' => string, 'status' => string]
     */
    public function chargeSavedPaymentMethod($paymentMethodId, $customerId, $amount, $bookingId, $installmentNumber = null)
    {
        try {
            // Convert amount to pence
            $amountInPence = (int)round($amount * 100);

            $metadata = [
                'booking_id' => $bookingId,
                'payment_type' => 'installment'
            ];

            if ($installmentNumber !== null) {
                $metadata['installment_number'] = $installmentNumber;
            }

            $paymentIntent = PaymentIntent::create([
                'amount' => $amountInPence,
                'currency' => 'gbp',
                'customer' => $customerId,
                'payment_method' => $paymentMethodId,
                'off_session' => true,
                'confirm' => true,
                'description' => "Camp Booking #{$bookingId} - Installment #{$installmentNumber}",
                'metadata' => $metadata,
            ]);

            return [
                'payment_intent_id' => $paymentIntent->id,
                'status' => $paymentIntent->status,
                'charge_id' => $paymentIntent->charges->data[0]->id ?? null
            ];

        } catch (ApiErrorException $e) {
            error_log("Stripe Charge Error: " . $e->getMessage());

            // Return error info for retry logic
            return [
                'payment_intent_id' => null,
                'status' => 'failed',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Retrieve a Payment Intent
     *
     * @param string $paymentIntentId Payment Intent ID
     * @return \Stripe\PaymentIntent
     */
    public function retrievePaymentIntent($paymentIntentId)
    {
        try {
            return PaymentIntent::retrieve($paymentIntentId);
        } catch (ApiErrorException $e) {
            error_log("Stripe Retrieve Payment Intent Error: " . $e->getMessage());
            throw new Exception("Failed to retrieve payment intent: " . $e->getMessage());
        }
    }

    /**
     * Retrieve a Setup Intent
     *
     * @param string $setupIntentId Setup Intent ID
     * @return \Stripe\SetupIntent
     */
    public function retrieveSetupIntent($setupIntentId)
    {
        try {
            return SetupIntent::retrieve($setupIntentId);
        } catch (ApiErrorException $e) {
            error_log("Stripe Retrieve Setup Intent Error: " . $e->getMessage());
            throw new Exception("Failed to retrieve setup intent: " . $e->getMessage());
        }
    }

    /**
     * Get default payment method for customer
     *
     * @param string $customerId Stripe customer ID
     * @return string|null Payment method ID
     */
    public function getCustomerDefaultPaymentMethod($customerId)
    {
        try {
            $customer = Customer::retrieve($customerId);

            if ($customer->invoice_settings && $customer->invoice_settings->default_payment_method) {
                return $customer->invoice_settings->default_payment_method;
            }

            // Fallback: get first payment method
            $paymentMethods = PaymentMethod::all([
                'customer' => $customerId,
                'type' => 'card',
                'limit' => 1
            ]);

            return $paymentMethods->data[0]->id ?? null;

        } catch (ApiErrorException $e) {
            error_log("Stripe Get Payment Method Error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Construct webhook event from payload
     *
     * @param string $payload Raw POST body
     * @param string $signature Stripe signature header
     * @return \Stripe\Event
     */
    public function constructWebhookEvent($payload, $signature)
    {
        $webhookSecret = env('STRIPE_WEBHOOK_SECRET');

        try {
            return \Stripe\Webhook::constructEvent(
                $payload,
                $signature,
                $webhookSecret
            );
        } catch (\UnexpectedValueException $e) {
            error_log("Invalid webhook payload: " . $e->getMessage());
            throw new Exception("Invalid payload");
        } catch (\Stripe\Exception\SignatureVerificationException $e) {
            error_log("Invalid webhook signature: " . $e->getMessage());
            throw new Exception("Invalid signature");
        }
    }

    /**
     * Format amount from pence to pounds
     *
     * @param int $amountInPence Amount in pence
     * @return float Amount in pounds
     */
    public static function formatAmountFromPence($amountInPence)
    {
        return (float)($amountInPence / 100);
    }

    /**
     * Format amount from pounds to pence
     *
     * @param float $amountInPounds Amount in pounds
     * @return int Amount in pence
     */
    public static function formatAmountToPence($amountInPounds)
    {
        return (int)round($amountInPounds * 100);
    }
}
