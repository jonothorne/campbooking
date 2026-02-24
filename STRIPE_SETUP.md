# Stripe Integration Setup Guide

This guide will help you set up Stripe payments for the Camp Booking System.

## Phase 3: Stripe Payment Integration - Complete! ✓

The following has been implemented:

### 1. **Payment Types**
- ✅ One-time full payment (Payment Intent)
- ✅ Installment payments with saved card (Setup Intent)
- ✅ Apple Pay / Google Pay support
- ✅ Multiple card types (Visa, Mastercard, Amex, etc.)

### 2. **Backend Integration**
- ✅ `classes/StripeHandler.php` - Complete Stripe API wrapper
- ✅ `api/stripe-webhook.php` - Webhook event handler
- ✅ `process-booking.php` - Updated for Stripe payments
- ✅ Database fields added for Stripe IDs

### 3. **Frontend Integration**
- ✅ `stripe-checkout.php` - Dedicated Stripe payment page
- ✅ `public/assets/js/stripe-handler.js` - Stripe.js integration
- ✅ Payment Element with Apple Pay/Google Pay support

### 4. **Webhook Events Handled**
- ✅ `payment_intent.succeeded` - Record successful payment
- ✅ `payment_intent.payment_failed` - Handle failed payments with retry logic
- ✅ `setup_intent.succeeded` - Save payment method for installments
- ✅ `charge.refunded` - Handle refunds

---

## Getting Started with Stripe

### Step 1: Create a Stripe Account

1. Go to https://stripe.com
2. Click "Start now" or "Sign up"
3. Complete the registration process
4. You'll be in **Test Mode** by default (perfect for development)

### Step 2: Get Your API Keys

1. Log in to your Stripe Dashboard: https://dashboard.stripe.com
2. Make sure you're in **Test Mode** (toggle in top-right corner should say "Test mode")
3. Click **Developers** → **API keys**
4. You'll see two keys:
   - **Publishable key** (starts with `pk_test_`)
   - **Secret key** (starts with `sk_test_`)

### Step 3: Update Your .env File

Open `/campbooking/.env` and update the Stripe keys:

```env
# Stripe (Test mode)
STRIPE_PUBLIC_KEY=pk_test_YOUR_ACTUAL_PUBLISHABLE_KEY_HERE
STRIPE_SECRET_KEY=sk_test_YOUR_ACTUAL_SECRET_KEY_HERE
STRIPE_WEBHOOK_SECRET=whsec_YOUR_WEBHOOK_SECRET_HERE
```

**Important:**
- Replace `pk_test_xxxxx` with your actual publishable key
- Replace `sk_test_xxxxx` with your actual secret key
- Webhook secret will be configured in Step 4

### Step 4: Configure Webhooks (for local testing)

For local development, you need to forward Stripe webhooks to your local server.

#### Option A: Using Stripe CLI (Recommended)

1. **Install Stripe CLI:**
   ```bash
   brew install stripe/stripe-cli/stripe
   ```

2. **Login to Stripe:**
   ```bash
   stripe login
   ```

3. **Forward webhooks to localhost:**
   ```bash
   stripe listen --forward-to localhost:8444/api/stripe-webhook.php
   ```

4. **Copy the webhook secret** that appears (starts with `whsec_`)

5. **Update .env** with the webhook secret:
   ```env
   STRIPE_WEBHOOK_SECRET=whsec_THE_SECRET_FROM_STRIPE_CLI
   ```

#### Option B: Using ngrok (Alternative)

1. **Install ngrok:** https://ngrok.com/download

2. **Expose your local server:**
   ```bash
   ngrok http 8444
   ```

3. **Add webhook in Stripe Dashboard:**
   - Go to **Developers** → **Webhooks**
   - Click "Add endpoint"
   - Enter your ngrok URL: `https://YOUR_NGROK_URL.ngrok.io/api/stripe-webhook.php`
   - Select events: `payment_intent.succeeded`, `payment_intent.payment_failed`, `setup_intent.succeeded`, `charge.refunded`
   - Copy the **Signing secret** and add to .env

### Step 5: Test the Integration

1. **Start your local server** (if not already running):
   ```bash
   cd /Users/jonothorne/webroot/campbooking
   php -S localhost:8444
   ```

2. **In a separate terminal, start Stripe CLI** (if using):
   ```bash
   stripe listen --forward-to localhost:8444/api/stripe-webhook.php
   ```

3. **Open the booking form:**
   ```
   http://localhost:8444
   ```

4. **Create a test booking:**
   - Fill in the form
   - Select **Stripe** as payment method
   - Choose a payment plan (Full, Monthly, or 3 Payments)
   - Submit the form

5. **You'll be redirected to the Stripe payment page**

6. **Use Stripe test card numbers:**

   **Successful payment:**
   ```
   Card number: 4242 4242 4242 4242
   Expiry: Any future date (e.g., 12/34)
   CVC: Any 3 digits (e.g., 123)
   ZIP: Any 5 digits (e.g., 12345)
   ```

   **Payment declined:**
   ```
   Card number: 4000 0000 0000 0002
   ```

   **Requires authentication (3D Secure):**
   ```
   Card number: 4000 0027 6000 3184
   ```

   **More test cards:** https://stripe.com/docs/testing#cards

7. **Check the webhook logs:**
   - Stripe CLI will show webhook events in real-time
   - Check `logs/webhooks.log` for webhook processing

---

## Testing Different Scenarios

### Test 1: One-Time Full Payment
1. Create booking with £100 total
2. Select **Stripe** → **Pay in Full**
3. Use card `4242 4242 4242 4242`
4. ✅ Should redirect to success page
5. ✅ Check admin panel - booking should show as "Paid"
6. ✅ Check email for payment receipt

### Test 2: Monthly Installments
1. Create booking with £300 total
2. Select **Stripe** → **Monthly Installments**
3. Use card `4242 4242 4242 4242`
4. ✅ Should save payment method successfully
5. ✅ Check admin panel - booking should show payment schedule
6. ✅ Card will be charged automatically on due dates

### Test 3: 3 Equal Payments
1. Create booking with £150 total
2. Select **Stripe** → **3 Equal Payments**
3. Use card `4242 4242 4242 4242`
4. ✅ Should save payment method
5. ✅ 3 installments of £50 each created

### Test 4: Failed Payment
1. Create booking
2. Use card `4000 0000 0000 0002` (decline)
3. ✅ Should show error message
4. ✅ Can retry with valid card

### Test 5: Webhook Processing
1. Make a payment
2. Watch Stripe CLI output
3. ✅ Should see `payment_intent.succeeded` event
4. ✅ Check `logs/webhooks.log`
5. ✅ Check database - payment recorded

---

## Going Live (Production)

When ready to accept real payments:

### 1. Complete Stripe Account Verification
- Provide business information
- Add bank account for payouts
- Verify identity

### 2. Switch to Live Mode
1. In Stripe Dashboard, toggle to **Live mode**
2. Get your **Live API keys** (start with `pk_live_` and `sk_live_`)
3. Update production `.env`:
   ```env
   STRIPE_PUBLIC_KEY=pk_live_YOUR_LIVE_KEY
   STRIPE_SECRET_KEY=sk_live_YOUR_LIVE_KEY
   ```

### 3. Configure Production Webhook
1. In Stripe Dashboard (Live mode):
2. Go to **Developers** → **Webhooks**
3. Add endpoint: `https://yourdomain.com/api/stripe-webhook.php`
4. Select events: `payment_intent.succeeded`, `payment_intent.payment_failed`, `setup_intent.succeeded`, `charge.refunded`
5. Copy signing secret to production `.env`

### 4. Test with Small Amount
- Make a real £1 booking to verify everything works
- Refund the test payment in Stripe Dashboard

---

## Monitoring & Logs

### Webhook Logs
Located at: `logs/webhooks.log`

Example entry:
```
[2026-02-24 10:30:15] Webhook received - Signature: whsec_1234567890ab...
[2026-02-24 10:30:15] Event Type: payment_intent.succeeded
[2026-02-24 10:30:15] Payment succeeded - Booking #123, Amount: £85.00
[2026-02-24 10:30:15] Payment processed successfully - Payment ID: 456
```

### Check Stripe Dashboard
- **Payments:** View all transactions
- **Customers:** View saved payment methods
- **Events:** See all webhook events
- **Logs:** View API requests

---

## Common Issues & Solutions

### Issue: "Invalid API Key"
**Solution:** Double-check your `.env` file has the correct keys from Stripe Dashboard

### Issue: Webhooks not working
**Solution:**
- Make sure Stripe CLI is running: `stripe listen --forward-to localhost:8444/api/stripe-webhook.php`
- Check `STRIPE_WEBHOOK_SECRET` in `.env` matches CLI output
- Verify `api/stripe-webhook.php` file exists and is accessible

### Issue: Payment page shows loading forever
**Solution:**
- Check browser console for JavaScript errors
- Verify `STRIPE_PUBLIC_KEY` in `.env` is correct
- Make sure Stripe.js is loading (check network tab)

### Issue: "Setup intent failed"
**Solution:**
- Check `logs/webhooks.log` for error details
- Verify webhook secret is correct
- Check Stripe Dashboard → Events for error messages

---

## Security Best Practices

1. ✅ **Never commit `.env` to git** - Contains secret keys
2. ✅ **Use webhook signature verification** - Already implemented
3. ✅ **Use HTTPS in production** - Required by Stripe
4. ✅ **Never store card numbers** - Handled by Stripe
5. ✅ **Keep Stripe SDK updated** - Run `composer update` regularly

---

## Support & Resources

- **Stripe Documentation:** https://stripe.com/docs
- **Test Cards:** https://stripe.com/docs/testing
- **Webhook Testing:** https://stripe.com/docs/webhooks/test
- **Stripe Dashboard:** https://dashboard.stripe.com
- **Stripe CLI Docs:** https://stripe.com/docs/stripe-cli

---

## Summary of Files Created

```
classes/StripeHandler.php          - Stripe API integration class
public/assets/js/stripe-handler.js - Frontend Stripe.js handler
api/stripe-webhook.php             - Webhook event processor
stripe-checkout.php                - Payment page
process-booking.php                - Updated with Stripe logic
```

## Next Steps

After Stripe is working:
- **Phase 4: Email System** ✅ (Already complete!)
- **Phase 6: Cron Jobs** - Automated installment processing
- **Phase 7: Security Hardening**
- **Phase 8: Testing & Polish**
- **Phase 9: Production Deployment**

---

**🎉 Stripe integration is ready to test!**
