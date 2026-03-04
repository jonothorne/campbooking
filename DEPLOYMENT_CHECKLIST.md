# Production Deployment Checklist for ECHO2026 Booking System

**Date Created:** 2026-03-04
**Status:** Ready for deployment with checklist verification

## Pre-Deployment Verification (Do This FIRST)

### 1. Critical Path Testing in Development ⚠️

Test these flows BEFORE deploying:

- [ ] **Full Payment Flow**
  - Make test booking with "Pay in Full"
  - Verify payment succeeds
  - Check booking shows as "paid" (not "unpaid")
  - Check outstanding shows £0.00 (not negative)
  - Verify confirmation email received with PDF attachment
  - Check PDF downloads correctly and shows £0.00 outstanding

- [ ] **Installment Payment Flow**
  - Make test booking with monthly installments
  - Verify Setup Intent succeeds
  - Check first payment charges automatically
  - Verify payment schedules created correctly

- [ ] **Customer Portal**
  - Access portal at /portal/
  - Test login
  - Verify countdown timer shows
  - Test "Add Attendee" functionality
  - Test "Edit Attendee" functionality
  - Test "Delete Attendee" functionality (must keep 1)
  - Verify payment schedules recalculate after changes
  - Test "Download PDF" from portal
  - Verify PDF shows correct amounts

- [ ] **Admin Panel**
  - Login to /admin/
  - View dashboard stats
  - View booking list
  - View booking details
  - Mark manual payment (cash/bank transfer)

### 2. Known Issues to Watch ✅

These have been FIXED but verify they work:
- Duplicate payment recording (FIXED - added duplicate checks)
- Negative outstanding amounts on PDFs (FIXED - using max(0, amount))
- ENUM value mismatch 'full_payment' vs 'full' (FIXED - changed to 'full')
- Email class not loaded (FIXED - added require)
- PDF logo not showing (FIXED - using base64 encoding)

---

## SQL Migration Required

Your **production database** needs these tables/columns. Run migrations in order:

### Check if migrations needed:
```bash
# Check if customer portal columns exist
mysql -u [user] -p [database] -e "SHOW COLUMNS FROM bookings LIKE 'password_hash';"
# If empty result, you need to run migrations below
```

### Migration 1: Remember Tokens
**File:** `/install/migrations/add_remember_tokens.sql`
```sql
-- Creates remember_tokens table for "Remember Me" functionality
CREATE TABLE IF NOT EXISTS `remember_tokens` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `booking_id` int unsigned NOT NULL,
  `token` varchar(64) NOT NULL,
  `expires_at` timestamp NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `booking_id` (`booking_id`),
  KEY `token` (`token`),
  KEY `expires_at` (`expires_at`),
  CONSTRAINT `remember_tokens_booking_id_foreign` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Migration 2: Customer Portal & GDPR
**File:** `/install/migrations/add_customer_portal_gdpr.sql`

This adds:
- `password_hash` column to bookings
- `privacy_policy_accepted`, `marketing_consent` columns
- `data_deletion_requested` GDPR fields
- `password_setup_tokens` table
- `gdpr_log` table
- Email index on bookings
- 'password_setup' email type to email_logs enum

**Run this file as-is in production**

---

## Production Environment Configuration

### 1. Update .env File

Create production `.env` with these changes:

```bash
# Application
APP_ENV=production  # ← CHANGE FROM development
APP_URL=https://yourdomain.com  # ← UPDATE

# Database
DB_HOST=localhost  # or production DB host
DB_NAME=campbooking_prod  # ← production database name
DB_USER=prod_user  # ← production user
DB_PASS=STRONG_PASSWORD_HERE  # ← secure password

# Stripe (LIVE mode) ⚠️ CRITICAL
STRIPE_PUBLIC_KEY=pk_live_XXXXX  # ← CHANGE to LIVE keys
STRIPE_SECRET_KEY=sk_live_XXXXX  # ← CHANGE to LIVE keys
STRIPE_WEBHOOK_SECRET=whsec_XXXXX  # ← NEW webhook secret from Stripe dashboard

# Email/SMTP
SMTP_HOST=localhost  # Check GoDaddy settings
SMTP_PORT=25
SMTP_FROM_EMAIL=office@alive.me.uk
SMTP_FROM_NAME=Alive Church Camp
SMTP_AUTH_REQUIRED=false

# Event Details (should be same)
EVENT_NAME=Alive Church Camp 2026
EVENT_START_DATE=2026-05-29
EVENT_END_DATE=2026-05-31
PAYMENT_DEADLINE=2026-05-20

# Pricing (should be same)
ADULT_PRICE=85.00
ADULT_SPONSOR_PRICE=110.00
CHILD_PRICE=55.00
ADULT_DAY_PRICE=25.00
CHILD_DAY_PRICE=15.00

# Bank Transfer Details (should be same)
BANK_NAME=Alive UK
BANK_ACCOUNT=67366334
BANK_SORT_CODE=08-92-99
BANK_REFERENCE_PREFIX=Camp
```

### 2. Stripe Configuration ⚠️ CRITICAL

**In Stripe Dashboard (LIVE mode):**

1. **Get Live API Keys**
   - Go to Developers → API Keys
   - Copy "Publishable key" (pk_live_...)
   - Copy "Secret key" (sk_live_...)
   - Update .env file

2. **Create Webhook Endpoint**
   - Go to Developers → Webhooks
   - Click "Add endpoint"
   - URL: `https://yourdomain.com/api/stripe-webhook.php`
   - Events to send:
     - `payment_intent.succeeded`
     - `payment_intent.payment_failed`
     - `setup_intent.succeeded`
     - `setup_intent.setup_failed`
     - `charge.refunded`
   - Copy webhook signing secret (whsec_...)
   - Update STRIPE_WEBHOOK_SECRET in .env

3. **Test Webhook**
   - In Stripe dashboard, send test webhook
   - Check `/logs/webhooks.log` for successful receipt

### 3. File Permissions

```bash
# Make logs writable
chmod 777 logs/
chmod 666 logs/*.log

# Protect sensitive files
chmod 600 .env
chmod 600 config/*.php

# Make sure vendor is readable
chmod -R 755 vendor/
```

### 4. Apache/Server Configuration

Ensure `.htaccess` is active and working:
- URL rewriting enabled
- Directory listing disabled
- .env file protected from direct access

Test: Visit `https://yourdomain.com/.env` - should get 403 Forbidden

---

## Deployment Steps

### Phase 1: Database Setup
1. [ ] Create production database
2. [ ] Run `/install/schema.sql` if fresh install
3. [ ] Run migration files in order (if needed)
4. [ ] Create admin user:
   ```sql
   INSERT INTO users (username, email, password_hash, role, created_at)
   VALUES ('admin', 'office@alive.me.uk', '$2y$10$[hash]', 'admin', NOW());
   ```
   Generate hash with: `php -r "echo password_hash('YOUR_PASSWORD', PASSWORD_DEFAULT);"`
5. [ ] Verify all tables exist

### Phase 2: File Upload
1. [ ] Upload all files to production server
2. [ ] Verify Composer dependencies (`vendor/` folder)
   - If missing, run `composer install --no-dev` on server
3. [ ] Check file permissions (see section 3 above)
4. [ ] Create production `.env` file
5. [ ] Verify logs directory exists and is writable

### Phase 3: Stripe Configuration
1. [ ] Switch to Stripe LIVE mode in dashboard
2. [ ] Get live API keys
3. [ ] Create webhook endpoint (see section 2 above)
4. [ ] Update .env with live keys
5. [ ] Send test webhook from Stripe

### Phase 4: Email Testing
1. [ ] Send test email to verify SMTP works
2. [ ] Check email appears professional
3. [ ] Verify PDF attachment works
4. [ ] Check spam folder if not received

### Phase 5: Smoke Testing
**DO NOT skip these tests!**

1. [ ] Visit homepage - booking form loads
2. [ ] Make test booking with £1 (pay in full)
   - Use Stripe test card in LIVE mode (if available) OR
   - Use real card for £1, then refund immediately
3. [ ] Verify payment records correctly
4. [ ] Check confirmation email received with PDF
5. [ ] Access customer portal
6. [ ] Check admin panel shows booking
7. [ ] Test manual payment marking
8. [ ] Verify webhook logged correctly

### Phase 6: Monitoring (First 24 Hours)
1. [ ] Check `/logs/webhooks.log` every hour
2. [ ] Monitor `/logs/errors.log` for issues
3. [ ] Verify first real booking processes correctly
4. [ ] Watch for payment failures
5. [ ] Check email delivery

---

## Critical Security Checks

- [ ] APP_ENV set to 'production' (disables dev mode payment sync)
- [ ] Stripe LIVE keys configured correctly
- [ ] .env file NOT accessible via web
- [ ] Database uses strong password
- [ ] Admin panel requires login
- [ ] HTTPS enabled (SSL certificate active)
- [ ] Webhook signature verification enabled
- [ ] Error display disabled in production
- [ ] All file permissions correct

---

## Rollback Plan

If something goes wrong:

1. **Quick Fix:** Switch APP_ENV back to 'development' in .env
2. **Database Rollback:** Restore from backup taken before migration
3. **Code Rollback:** Revert to previous version
4. **Stripe:** Keep webhook active but disable in Stripe dashboard if needed

---

## Support Contacts

- **Stripe Support:** https://support.stripe.com/
- **GoDaddy Support:** For email/hosting issues
- **Developer:** Check logs first (`/logs/` directory)

---

## Post-Deployment Verification

After going live, verify:

- [ ] Real booking goes through successfully
- [ ] Payment recorded correctly
- [ ] Email sent with PDF
- [ ] Webhook fires and logs correctly
- [ ] Customer can access portal
- [ ] Admin can view booking
- [ ] PDF downloads work
- [ ] No errors in logs

---

## New Features Deployed in This Release

### Customer Portal
✅ Hero section with countdown timer to event
✅ Video modal for ECHO promo video
✅ Add additional attendees after booking
✅ Edit existing attendees (name, age, ticket type)
✅ Delete attendees (minimum 1 required)
✅ Payment schedule automatically recalculates
✅ View all booking details (camping, dietary requirements)
✅ Download professional PDF for check-in

### PDF Export System
✅ Professional branded PDF with ECHO logo
✅ Available on success page after booking
✅ Available in customer portal anytime
✅ Automatically attached to confirmation emails
✅ Includes booking reference, attendees, payment summary
✅ Check-in instructions included

### Payment Fixes
✅ Duplicate payment prevention
✅ Negative outstanding amounts fixed
✅ Dev mode sync processing for local testing
✅ Proper payment status updates

### Admin Features
✅ All existing admin features remain unchanged
✅ Payment history visible in booking details

---

## Known Limitations

1. **Webhooks on localhost:** Don't work - dev mode sync processing handles this
2. **Email testing:** Use real email addresses, check spam folder
3. **Stripe test mode:** Different keys than live mode
4. **Payment schedules:** Calculated on booking date, not configurable after creation (can be manually adjusted in admin)

---

## Success Criteria

System is working correctly if:
- ✅ Users can book tickets without errors
- ✅ Payments process and record correctly
- ✅ Emails send with PDF attachments
- ✅ Customers can access portal and manage bookings
- ✅ Admin can view and manage all bookings
- ✅ No duplicate payments recorded
- ✅ Outstanding amounts display correctly
- ✅ Webhooks fire and log successfully

---

**IMPORTANT:** Keep this checklist and mark items as you complete them. Do not skip testing steps!
