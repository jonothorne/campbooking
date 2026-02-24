# Production Deployment Checklist - camp.alivechur.ch

## Pre-Deployment Setup

### 1. Domain & Hosting
- [x] Domain: `camp.alivechur.ch`
- [ ] DNS configured to point to GoDaddy hosting
- [ ] SSL certificate installed (Let's Encrypt via cPanel)
- [ ] Verify HTTPS works: https://camp.alivechur.ch

### 2. Email Configuration
- [ ] Create email account in cPanel: `bookings@alivechur.ch`
- [ ] Verify `office@alive.me.uk` exists for replies
- [ ] Test email sending from server using `test-email.php`

### 3. Database Setup
- [ ] Create MySQL database in cPanel
- [ ] Create database user with ALL privileges
- [ ] Note database name, username, and password
- [ ] Import schema: `mysql -u user -p dbname < install/schema.sql`

### 4. Stripe Configuration
- [ ] Create Stripe account (or login to existing)
- [ ] Get **LIVE** API keys from https://dashboard.stripe.com/apikeys
- [ ] Configure webhook endpoint: `https://camp.alivechur.ch/api/stripe-webhook.php`
- [ ] Note webhook signing secret
- [ ] Test with live mode (small real payment)

## File Upload

### 1. Upload Files to Server
- [ ] Upload all files to: `/public_html/camp.alivechur.ch/` (or subdomain directory)
- [ ] **DO NOT** upload:
  - `.git/` directory
  - `.env` file (create new on server)
  - `node_modules/` if any
  - Local test databases

### 2. Install Composer Dependencies
```bash
cd /path/to/camp.alivechur.ch
composer install --no-dev --optimize-autoloader
```

### 3. Set File Permissions
```bash
# Make logs writable
chmod 755 logs/
chmod 644 logs/*.log

# Make cron scripts executable
chmod +x cron/*.php

# Protect sensitive files
chmod 600 .env

# Ensure web server can read files
chmod 644 *.php
chmod 755 public/
```

## Environment Configuration

### 1. Create Production `.env` File

Create `.env` on the server with:

```bash
# Application
APP_ENV=production
APP_URL=https://camp.alivechur.ch

# Database
DB_HOST=localhost
DB_NAME=your_database_name
DB_USER=your_database_user
DB_PASS=your_database_password

# Stripe (LIVE MODE - DO NOT USE TEST KEYS)
STRIPE_PUBLIC_KEY=pk_live_xxxxx
STRIPE_SECRET_KEY=sk_live_xxxxx
STRIPE_WEBHOOK_SECRET=whsec_xxxxx

# Email/SMTP (GoDaddy)
SMTP_HOST=localhost
SMTP_PORT=25
SMTP_USER=
SMTP_PASS=
SMTP_FROM_EMAIL=bookings@alivechur.ch
SMTP_FROM_NAME=Alive Church Camp
SMTP_AUTH_REQUIRED=false

# Event Details
EVENT_NAME=Alive Church Camp 2026
EVENT_START_DATE=2026-05-29
EVENT_END_DATE=2026-05-31
PAYMENT_DEADLINE=2026-05-20

# Pricing
ADULT_PRICE=85.00
ADULT_SPONSOR_PRICE=110.00
CHILD_PRICE=55.00
ADULT_DAY_PRICE=25.00
CHILD_DAY_PRICE=15.00

# Bank Transfer Details
BANK_NAME=Alive UK
BANK_ACCOUNT=67366334
BANK_SORT_CODE=08-92-99
BANK_REFERENCE_PREFIX=Camp
```

### 2. Verify Configuration
```bash
php -r "require 'config/constants.php'; echo 'Environment: ' . APP_ENV . PHP_EOL; echo 'URL: ' . APP_URL . PHP_EOL;"
```

Should output:
```
Environment: production
URL: https://camp.alivechur.ch
```

## Security Setup

### 1. Verify .htaccess Protection
- [ ] Test that `.env` is not accessible: https://camp.alivechur.ch/.env (should 403)
- [ ] Test that `logs/` is not accessible: https://camp.alivechur.ch/logs/ (should 403)
- [ ] Test that HTTPS redirect works: http://camp.alivechur.ch (should redirect to HTTPS)

### 2. Create Admin User
Run on server:
```bash
php -r "
require 'vendor/autoload.php';
require 'includes/db.php';
\$db = Database::getInstance();
\$hash = password_hash('YourSecurePassword123!', PASSWORD_BCRYPT);
\$db->execute(
    \"INSERT INTO users (username, email, password_hash, is_active) VALUES (?, ?, ?, 1)\",
    ['admin', 'office@alive.me.uk', \$hash]
);
echo 'Admin user created: admin / YourSecurePassword123!' . PHP_EOL;
"
```

**IMPORTANT**: Change the password immediately after first login!

### 3. Test Admin Login
- [ ] Go to https://camp.alivechur.ch/admin
- [ ] Login with admin credentials
- [ ] Change password: Admin → Users → Change Password

## Cron Job Setup

### 1. Configure Cron Jobs in cPanel

**Cron → Add New Cron Job**

**Daily Payment Processing (9:00 AM)**
```
0 9 * * * /usr/bin/php /home/username/public_html/camp.alivechur.ch/cron/process-payments.php
```

**Payment Reminders (10:00 AM)**
```
0 10 * * * /usr/bin/php /home/username/public_html/camp.alivechur.ch/cron/send-reminders.php
```

**Failed Payment Checker (11:00 AM)**
```
0 11 * * * /usr/bin/php /home/username/public_html/camp.alivechur.ch/cron/check-failed-payments.php
```

**Note**: Replace `/home/username/public_html/camp.alivechur.ch/` with your actual server path.

### 2. Test Cron Jobs Manually
```bash
php /path/to/cron/process-payments.php
php /path/to/cron/send-reminders.php
php /path/to/cron/check-failed-payments.php
```

Check `logs/payments.log` for output.

## Testing

### 1. Test Email Sending
```bash
php test-email.php
```
Enter `office@alive.me.uk` when prompted. Check inbox.

### 2. Test Booking Form
- [ ] Go to https://camp.alivechur.ch
- [ ] Fill out booking form
- [ ] Select each payment method (cash, bank transfer, Stripe)
- [ ] Verify booking creates successfully
- [ ] Check admin panel shows booking

### 3. Test Stripe Payment (LIVE MODE - Use Real Card)
⚠️ **WARNING**: This will charge a REAL credit card!

- [ ] Create small test booking (e.g., 1 adult day ticket = £25)
- [ ] Choose Stripe payment method
- [ ] Use REAL card for testing (will be charged)
- [ ] Verify payment processes
- [ ] Check Stripe dashboard for transaction
- [ ] Verify booking shows as paid in admin panel
- [ ] Check `bookings@alivechur.ch` receives confirmation email
- [ ] **IMPORTANT**: Refund test payment in Stripe dashboard

### 4. Test Stripe Webhook
- [ ] Go to https://dashboard.stripe.com/test/webhooks
- [ ] Find your webhook endpoint
- [ ] Click "Send test webhook" → `payment_intent.succeeded`
- [ ] Check `logs/webhooks.log` for webhook receipt

### 5. Test Admin Panel
- [ ] Dashboard shows correct stats
- [ ] Bookings list displays
- [ ] Booking detail view works
- [ ] Mark payment as paid (for bank transfer)
- [ ] Delete test booking

## Post-Deployment

### 1. Monitor Logs (First 24 Hours)
```bash
tail -f logs/errors.log
tail -f logs/payments.log
tail -f logs/webhooks.log
```

### 2. Verify First Real Booking
- [ ] Monitor when first real customer books
- [ ] Verify confirmation email sends
- [ ] Check payment processes correctly
- [ ] Verify data shows correctly in admin panel

### 3. Test Automated Payments
- [ ] Wait for first scheduled payment (or manually trigger cron)
- [ ] Verify payment charges successfully
- [ ] Check receipt email sends
- [ ] Verify payment status updates in admin

### 4. Remove Test Files
```bash
rm test-email.php
rm -rf install/
```

## Important URLs

- **Public Booking Form**: https://camp.alivechur.ch
- **Admin Panel**: https://camp.alivechur.ch/admin
- **Stripe Dashboard**: https://dashboard.stripe.com
- **GoDaddy cPanel**: https://login.godaddy.com

## Support Contacts

- **Primary Email**: office@alive.me.uk
- **Booking Notifications**: bookings@alivechur.ch
- **Stripe Support**: https://support.stripe.com
- **GoDaddy Support**: https://godaddy.com/help

## Backup Strategy

### Database Backups (Automated via cPanel)
- [ ] Enable daily automatic backups in cPanel
- [ ] Verify backups are working: cPanel → Backup → Download Database Backup

### Manual Backup (Before Major Changes)
```bash
# Database
mysqldump -u user -p dbname > backup_YYYY-MM-DD.sql

# Files
tar -czf backup_files_YYYY-MM-DD.tar.gz /path/to/camp.alivechur.ch
```

## Rollback Plan

If something goes wrong:

1. **Restore Database**: Upload backup SQL via phpMyAdmin
2. **Restore Files**: Re-upload previous version
3. **Check Logs**: Review `logs/errors.log` for issues
4. **Disable Site**: Create `maintenance.html` in root

## Maintenance Mode

To enable maintenance mode, create `/maintenance.html`:

```html
<!DOCTYPE html>
<html>
<head>
    <title>Maintenance - Alive Church Camp</title>
</head>
<body>
    <h1>Site Under Maintenance</h1>
    <p>We're making improvements. Please check back soon!</p>
    <p>Contact: office@alive.me.uk</p>
</body>
</html>
```

Add to top of `index.php`:
```php
if (file_exists(__DIR__ . '/maintenance.html') && !isset($_GET['admin'])) {
    include __DIR__ . '/maintenance.html';
    exit;
}
```

Access admin during maintenance: `https://camp.alivechur.ch/admin?admin=1`

## Completion

- [ ] All tests pass
- [ ] Emails sending correctly
- [ ] Payments processing
- [ ] Cron jobs running
- [ ] Backups configured
- [ ] Monitoring in place
- [ ] Test booking refunded
- [ ] Admin password changed
- [ ] Test files removed

**Date Deployed**: _______________
**Deployed By**: _______________
**First Real Booking**: _______________

---

**System Ready for Production** ✅

Contact office@alive.me.uk for support.
