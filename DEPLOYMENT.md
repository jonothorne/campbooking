# Deployment Guide - Alive Church Camp Booking System

Complete guide for deploying the booking system to production.

**Production URL**: https://camp.alivechur.ch
**Contact Email**: office@alive.me.uk
**Booking Email**: bookings@alivechur.ch

---

## Pre-Deployment Checklist

### 1. Server Requirements
- [ ] PHP 7.4+ (8.0+ recommended)
- [ ] MySQL 5.7+ or MariaDB 10.2+
- [ ] Apache with mod_rewrite OR Nginx
- [ ] SSL certificate (Let's Encrypt or commercial)
- [ ] Cron job support
- [ ] Composer installed
- [ ] PHP extensions: PDO, mysqli, curl, mbstring, openssl

### 2. Third-Party Accounts
- [ ] Stripe account created and verified
- [ ] Live Stripe API keys obtained
- [ ] SMTP email service configured (Gmail, SendGrid, etc.)
- [ ] Domain configured with SSL

### 3. Code Preparation
- [ ] All `.env` values updated for production
- [ ] Stripe switched to live mode
- [ ] Email SMTP configured for production
- [ ] Database credentials for production
- [ ] Error reporting disabled (`APP_ENV=production`)

---

## Step 1: Server Setup

### Option A: Shared Hosting (cPanel)

1. **Upload files via FTP/SFTP**
   - Upload entire `/campbooking` directory
   - Do NOT upload `.git` directory or `.env.example`

2. **Set file permissions**
   ```bash
   chmod 755 /path/to/campbooking
   chmod 644 /path/to/campbooking/.htaccess
   chmod 600 /path/to/campbooking/.env
   chmod 755 /path/to/campbooking/logs
   chmod 644 /path/to/campbooking/logs/*.log
   ```

3. **Point domain to directory**
   - Set document root to `/path/to/campbooking`
   - NOT `/campbooking/public` (public is for assets only)

### Option B: VPS (Ubuntu/Debian)

1. **Install dependencies**
   ```bash
   sudo apt update
   sudo apt install php8.1 php8.1-cli php8.1-mysql php8.1-curl php8.1-mbstring
   sudo apt install mysql-server apache2
   sudo apt install composer
   ```

2. **Clone or upload files**
   ```bash
   cd /var/www
   # Upload via SFTP or git clone
   sudo chown -R www-data:www-data campbooking
   ```

3. **Configure Apache**
   ```apache
   <VirtualHost *:80>
       ServerName booking.alivechurch.com
       DocumentRoot /var/www/campbooking

       <Directory /var/www/campbooking>
           AllowOverride All
           Require all granted
       </Directory>

       ErrorLog ${APACHE_LOG_DIR}/campbooking-error.log
       CustomLog ${APACHE_LOG_DIR}/campbooking-access.log combined
   </VirtualHost>
   ```

4. **Enable SSL with Let's Encrypt**
   ```bash
   sudo apt install certbot python3-certbot-apache
   sudo certbot --apache -d booking.alivechurch.com
   ```

---

## Step 2: Database Setup

1. **Create production database**
   ```sql
   CREATE DATABASE campbooking_prod CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   CREATE USER 'campbooking'@'localhost' IDENTIFIED BY 'STRONG_PASSWORD_HERE';
   GRANT ALL PRIVILEGES ON campbooking_prod.* TO 'campbooking'@'localhost';
   FLUSH PRIVILEGES;
   ```

2. **Import schema**
   ```bash
   mysql -u campbooking -p campbooking_prod < install/schema.sql
   ```

3. **Verify tables created**
   ```bash
   mysql -u campbooking -p campbooking_prod -e "SHOW TABLES;"
   ```

4. **Change admin password immediately**
   ```bash
   php -r "echo password_hash('YOUR_SECURE_PASSWORD', PASSWORD_BCRYPT);"
   # Copy the hash, then:
   mysql -u campbooking -p campbooking_prod
   UPDATE users SET password_hash = 'YOUR_NEW_HASH' WHERE username = 'admin';
   ```

---

## Step 3: Environment Configuration

1. **Create production `.env` file**
   ```bash
   cp .env.example .env
   nano .env
   ```

2. **Update ALL values:**
   ```env
   # Application
   APP_ENV=production
   APP_URL=https://booking.alivechurch.com

   # Database (PRODUCTION CREDENTIALS!)
   DB_HOST=localhost
   DB_NAME=campbooking_prod
   DB_USER=campbooking
   DB_PASS=YOUR_STRONG_PASSWORD

   # Stripe (LIVE KEYS!)
   STRIPE_PUBLIC_KEY=pk_live_YOUR_LIVE_KEY
   STRIPE_SECRET_KEY=sk_live_YOUR_LIVE_KEY
   STRIPE_WEBHOOK_SECRET=whsec_YOUR_LIVE_WEBHOOK_SECRET

   # Email/SMTP (PRODUCTION SMTP!)
   SMTP_HOST=smtp.gmail.com
   SMTP_PORT=587
   SMTP_USER=bookings@alivechurch.com
   SMTP_PASS=YOUR_APP_PASSWORD
   SMTP_FROM_EMAIL=bookings@alivechurch.com
   SMTP_FROM_NAME=Alive Church Camp

   # Event Details
   EVENT_NAME=Alive Church Camp 2026
   EVENT_START_DATE=2026-05-29
   EVENT_END_DATE=2026-05-31

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

3. **Secure .env file**
   ```bash
   chmod 600 .env
   chown www-data:www-data .env
   ```

---

## Step 4: Composer Dependencies

1. **Install production dependencies**
   ```bash
   cd /path/to/campbooking
   composer install --no-dev --optimize-autoloader
   ```

2. **Verify installation**
   ```bash
   ls -la vendor/
   # Should see: stripe/, phpmailer/, autoload.php
   ```

---

## Step 5: Stripe Configuration (Live Mode)

1. **Get live API keys**
   - Log in to Stripe Dashboard: https://dashboard.stripe.com
   - Toggle to **LIVE MODE** (top-right corner)
   - Go to **Developers** → **API keys**
   - Copy **Live Publishable key** (pk_live_...)
   - Copy **Live Secret key** (sk_live_...)
   - Update `.env` file

2. **Configure live webhook**
   - Go to **Developers** → **Webhooks**
   - Click "Add endpoint"
   - URL: `https://booking.alivechurch.com/api/stripe-webhook.php`
   - Select events:
     - `payment_intent.succeeded`
     - `payment_intent.payment_failed`
     - `setup_intent.succeeded`
     - `charge.refunded`
   - Click "Add endpoint"
   - Copy **Signing secret** (whsec_...)
   - Update `STRIPE_WEBHOOK_SECRET` in `.env`

3. **Test webhook**
   ```bash
   # From Stripe Dashboard → Webhooks → Your endpoint → "Send test webhook"
   # Check logs/webhooks.log for confirmation
   ```

---

## Step 6: Cron Jobs Setup

### For cPanel:
1. Go to **Cron Jobs** in cPanel
2. Add three cron jobs:

**Process Payments (9am daily):**
```
0 9 * * * /usr/bin/php /path/to/campbooking/cron/process-payments.php >> /path/to/campbooking/logs/cron.log 2>&1
```

**Send Reminders (10am daily):**
```
0 10 * * * /usr/bin/php /path/to/campbooking/cron/send-reminders.php >> /path/to/campbooking/logs/cron.log 2>&1
```

**Check Failed Payments (11am daily):**
```
0 11 * * * /usr/bin/php /path/to/campbooking/cron/check-failed-payments.php >> /path/to/campbooking/logs/cron.log 2>&1
```

### For VPS:
1. Edit crontab:
   ```bash
   sudo crontab -e -u www-data
   ```

2. Add these lines:
   ```cron
   0 9 * * * /usr/bin/php /var/www/campbooking/cron/process-payments.php
   0 10 * * * /usr/bin/php /var/www/campbooking/cron/send-reminders.php
   0 11 * * * /usr/bin/php /var/www/campbooking/cron/check-failed-payments.php
   ```

3. Verify cron jobs:
   ```bash
   sudo crontab -l -u www-data
   ```

---

## Step 7: Security Hardening

1. **Enable HTTPS redirect**
   - Edit `.htaccess`
   - Uncomment lines 7-11 (HTTPS redirect)

2. **Remove install directory**
   ```bash
   rm -rf install/
   ```

3. **Secure file permissions**
   ```bash
   find . -type f -exec chmod 644 {} \;
   find . -type d -exec chmod 755 {} \;
   chmod 600 .env
   chmod 755 cron/*.php
   chmod 755 logs/
   chmod 644 logs/*.log
   ```

4. **Disable directory listing** (already in .htaccess)

5. **Set up database backups**
   ```bash
   # Add to crontab (daily at 2am):
   0 2 * * * mysqldump -u campbooking -pPASSWORD campbooking_prod | gzip > /backups/campbooking_$(date +\%Y\%m\%d).sql.gz
   ```

6. **Monitor logs regularly**
   ```bash
   tail -f logs/errors.log
   tail -f logs/payments.log
   tail -f logs/webhooks.log
   ```

---

## Step 8: Testing Production

### Test 1: Small Real Payment
1. Create a booking with £1 total
2. Use your real card
3. Complete payment
4. Verify:
   - [ ] Payment appears in Stripe Dashboard
   - [ ] Booking shows as paid in admin panel
   - [ ] Confirmation email received
   - [ ] Webhook logged

### Test 2: Refund Test Payment
1. In Stripe Dashboard → Payments
2. Find the £1 test payment
3. Click "Refund"
4. Verify refund processed

### Test 3: Admin Login
1. Go to `https://booking.alivechurch.com/admin`
2. Log in with new secure password
3. Verify dashboard loads
4. Check bookings list

### Test 4: Bank Transfer Booking
1. Create booking with bank transfer
2. Verify confirmation email has bank details
3. Manually mark as paid in admin
4. Verify receipt email sent

### Test 5: Cron Job Test
1. Manually run cron scripts:
   ```bash
   php cron/process-payments.php
   php cron/send-reminders.php
   php cron/check-failed-payments.php
   ```
2. Check logs for output

---

## Step 9: Go Live Checklist

- [ ] Production database configured
- [ ] All `.env` values set to production
- [ ] Stripe live keys configured
- [ ] Stripe webhook configured and tested
- [ ] SSL certificate active (HTTPS working)
- [ ] Cron jobs configured and tested
- [ ] Email sending tested
- [ ] Test payment completed and refunded
- [ ] Admin password changed from default
- [ ] File permissions secured
- [ ] Install directory removed
- [ ] Database backups configured
- [ ] Error logs being monitored
- [ ] Documentation reviewed with team

---

## Step 10: Monitoring & Maintenance

### Daily
- Check `logs/errors.log` for PHP errors
- Check `logs/webhooks.log` for Stripe events
- Check `logs/payments.log` for cron job results

### Weekly
- Review failed payments in admin panel
- Check Stripe Dashboard for disputes
- Verify email deliverability

### Monthly
- Review security updates: `composer update`
- Check database size: `mysql -e "SELECT table_name, round(((data_length + index_length) / 1024 / 1024), 2) AS 'Size (MB)' FROM information_schema.TABLES WHERE table_schema = 'campbooking_prod';"`
- Verify backups are running

### Before Event
- Export attendee list for camp organizers
- Generate financial report
- Send final payment reminders

---

## Troubleshooting

### Issue: Emails not sending
**Check:**
1. SMTP credentials in `.env`
2. `logs/errors.log` for SMTP errors
3. Test with: `php -r "mail('test@example.com', 'Test', 'Test');"

### Issue: Stripe webhook failing
**Check:**
1. Webhook secret in `.env` matches Stripe Dashboard
2. `logs/webhooks.log` for signature errors
3. Stripe Dashboard → Webhooks → Your endpoint → Recent deliveries

### Issue: Cron jobs not running
**Check:**
1. Crontab is configured: `crontab -l -u www-data`
2. PHP path is correct: `which php`
3. File permissions: `ls -la cron/`
4. Check `logs/cron.log` for output

### Issue: Database connection failed
**Check:**
1. Database credentials in `.env`
2. Database exists: `mysql -e "SHOW DATABASES;"`
3. User has permissions: `mysql -e "SHOW GRANTS FOR 'campbooking'@'localhost';"`

---

## Backup & Recovery

### Manual Backup
```bash
# Database
mysqldump -u campbooking -p campbooking_prod > backup_$(date +%Y%m%d).sql

# Files
tar -czf backup_files_$(date +%Y%m%d).tar.gz /path/to/campbooking
```

### Restore from Backup
```bash
# Database
mysql -u campbooking -p campbooking_prod < backup_20260224.sql

# Files
tar -xzf backup_files_20260224.tar.gz -C /path/to/restore
```

---

## Support Contacts

- **Stripe Support:** https://support.stripe.com
- **PHP Documentation:** https://php.net/docs.php
- **MySQL Documentation:** https://dev.mysql.com/doc/

---

## Post-Event

After the camp event:

1. **Generate final reports**
   - Export all bookings from admin panel
   - Generate financial summary
   - Review payment statistics

2. **Archive data** (if needed)
   - Backup database
   - Export to CSV for records

3. **Thank attendees** (optional)
   - Send thank you emails
   - Share photos/recap

---

**Deployment complete! Your booking system is now live! 🎉**
