# Alive Church Camp 2026 - Booking System

A comprehensive PHP/MySQL booking system for church camp with Stripe payment integration, installment plans, and admin management.

## Features ✅ ALL COMPLETE

### Public Booking Form ✓
- ✅ Collect booker information (name, email, phone)
- ✅ Add unlimited attendees with ages
- ✅ Multiple ticket types:
  - Adult Weekend (£85) or Sponsor (£110)
  - Child Weekend (£55)
  - Ages 0-4 Free
  - Adult Day Tickets (£25/day)
  - Child Day Tickets (£15/day)
- ✅ Camping requirements collection (tents, caravans, special needs)
- ✅ Payment methods: Cash, Bank Transfer, Stripe (Card/Apple Pay/Google Pay)
- ✅ Payment plans: Full payment, Monthly installments, 3 equal payments
- ✅ Live price calculation
- ✅ Professional responsive design

### Admin Panel ✓
- ✅ Secure login with CSRF protection & rate limiting
- ✅ Dashboard with real-time statistics
- ✅ Booking management (view, search, filter, delete)
- ✅ Detailed booking view with payment history
- ✅ Payment tracking with installment schedules
- ✅ Manual payment marking (cash/bank transfers)
- ✅ Failed payment tracking & alerts
- ✅ Beautiful professional UI

### Payment Processing ✓
- ✅ Full Stripe integration (one-time & installments)
- ✅ Automated installment charging via cron
- ✅ Failed payment retry logic (3 attempts)
- ✅ Payment method saving for recurring payments
- ✅ Webhook event processing
- ✅ Refund handling

### Email System ✓
- ✅ Booking confirmation emails (with payment instructions)
- ✅ Payment receipt emails
- ✅ Payment reminder emails (3 days before due)
- ✅ Failed payment notification emails
- ✅ Beautiful HTML email templates
- ✅ Email logging to database

### Automation & Cron Jobs ✓
- ✅ Daily payment processing (9am)
- ✅ Daily reminder sending (10am)
- ✅ Daily failed payment checks (11am)
- ✅ Automatic payment retries
- ✅ Comprehensive logging

### Security ✓
- ✅ CSRF token protection on all forms
- ✅ SQL injection prevention (prepared statements)
- ✅ XSS protection (output escaping)
- ✅ Rate limiting on login (5 attempts/15 minutes)
- ✅ Bcrypt password hashing
- ✅ Stripe webhook signature verification
- ✅ Secure session handling
- ✅ .htaccess security rules
- ✅ HTTPS enforcement ready

## Requirements

- **PHP**: 7.4+ (8.0+ recommended)
- **MySQL**: 5.7+ or MariaDB 10.2+
- **Web Server**: Apache with mod_rewrite or Nginx
- **SSL Certificate**: Required for production (Let's Encrypt)
- **Cron**: Job scheduling support
- **Composer**: PHP dependency manager
- **PHP Extensions**: PDO, mysqli, curl, mbstring, openssl

## Installation

### Step 1: Clone/Download Files

Place all files in your web server directory (e.g., `/var/www/html/campbooking`).

### Step 2: Install Dependencies

```bash
cd /path/to/campbooking
composer install
```

### Step 3: Configure Environment

Copy `.env.example` to `.env` and configure:

```bash
cp .env.example .env
nano .env
```

Update with your settings:

```env
# Database
DB_HOST=localhost
DB_NAME=campbooking
DB_USER=your_db_user
DB_PASS=your_db_password

# Stripe (use test keys for testing)
STRIPE_PUBLIC_KEY=pk_test_xxxxx
STRIPE_SECRET_KEY=sk_test_xxxxx
STRIPE_WEBHOOK_SECRET=whsec_xxxxx

# Email/SMTP
SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_USER=your_email@domain.com
SMTP_PASS=your_app_password

# Set to production when ready
APP_ENV=development
```

### Step 4: Create Database

```bash
mysql -u root -p
```

```sql
CREATE DATABASE campbooking CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'campuser'@'localhost' IDENTIFIED BY 'secure_password';
GRANT ALL PRIVILEGES ON campbooking.* TO 'campuser'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

### Step 5: Import Schema

```bash
mysql -u campuser -p campbooking < install/schema.sql
```

This creates all tables and inserts:
- Default settings
- Admin user (username: `admin`, password: `ChangeMeNow123!`)

**IMPORTANT: Change the default admin password immediately!**

### Step 6: Configure Stripe Webhook

1. Go to [Stripe Dashboard](https://dashboard.stripe.com/webhooks)
2. Add endpoint: `https://yourdomain.com/api/stripe-webhook.php`
3. Select events:
   - `payment_intent.succeeded`
   - `payment_intent.payment_failed`
   - `setup_intent.succeeded`
4. Copy the webhook signing secret to `.env` as `STRIPE_WEBHOOK_SECRET`

### Step 7: Setup Cron Jobs

Add to crontab (`crontab -e`):

```cron
# Process scheduled payments daily at 9am
0 9 * * * /usr/bin/php /path/to/campbooking/cron/process-payments.php >> /path/to/campbooking/logs/cron.log 2>&1

# Send payment reminders daily at 10am
0 10 * * * /usr/bin/php /path/to/campbooking/cron/send-reminders.php >> /path/to/campbooking/logs/cron.log 2>&1

# Check failed payments daily at 11am
0 11 * * * /usr/bin/php /path/to/campbooking/cron/check-failed-payments.php >> /path/to/campbooking/logs/cron.log 2>&1
```

### Step 8: Set Permissions

```bash
# Make logs writable
chmod -R 755 logs/
chown -R www-data:www-data logs/

# Protect sensitive files
chmod 600 .env
```

### Step 9: Enable HTTPS (Production)

1. Obtain SSL certificate (Let's Encrypt recommended)
2. Configure web server to use HTTPS
3. Uncomment HTTPS redirect in `.htaccess`:

```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteCond %{HTTPS} off
    RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
</IfModule>
```

4. Update `APP_ENV=production` in `.env`

### Step 10: Remove Installation Files

After setup is complete:

```bash
rm -rf install/
```

Or block access in `.htaccess` (already configured).

## Usage

### Public Booking

Access the booking form:
```
https://yourdomain.com/public/index.php
```

### Admin Panel

Access the admin panel:
```
https://yourdomain.com/admin/login.php
```

**Default credentials:**
- Username: `admin`
- Password: `ChangeMeNow123!`

**Change this immediately after first login!**

## Event Details

- **Event**: Alive Church Camp 2026
- **Dates**: May 29-31, 2026
- **Location**: [To be configured]

## Pricing

- **Adults (16+)**: £85 standard or £110 sponsor
- **Children (5-15)**: £55
- **Ages 0-4**: Free
- **Adult Day Ticket**: £25/day
- **Child Day Ticket**: £15/day

## Bank Transfer Details

- **Bank**: Alive UK
- **Account**: 67366334
- **Sort Code**: 08-92-99
- **Reference Format**: Camp[Surname]

Example: If booker is "John Smith", reference is "CampSmith"

## Testing

### Stripe Test Mode

Use test cards for testing:

- **Success**: 4242 4242 4242 4242
- **Decline**: 4000 0000 0000 0002
- **3D Secure**: 4000 0027 6000 3184

Test mode is active when using `pk_test_*` and `sk_test_*` keys.

### Testing Webhooks

Use Stripe CLI to test webhooks locally:

```bash
stripe listen --forward-to localhost/campbooking/api/stripe-webhook.php
stripe trigger payment_intent.succeeded
```

## Security

### Best Practices Implemented

- ✅ Password hashing with bcrypt
- ✅ CSRF protection on all forms
- ✅ SQL injection prevention (prepared statements)
- ✅ XSS prevention (output escaping)
- ✅ Rate limiting on login attempts
- ✅ Secure session configuration
- ✅ Stripe webhook signature verification
- ✅ Environment variable protection
- ✅ HTTPS enforcement (production)
- ✅ Input validation and sanitization

### Important Security Notes

1. **Never commit `.env` to git** - Already in .gitignore
2. **Use strong database passwords** - Update from defaults
3. **Change default admin password** - After first login
4. **Enable HTTPS in production** - Required for Stripe
5. **Keep dependencies updated** - Run `composer update` regularly
6. **Monitor error logs** - Check `logs/errors.log` regularly
7. **Backup database regularly** - Critical data protection

## File Structure

```
campbooking/
├── admin/              # Admin panel pages
├── api/                # API endpoints (webhooks, AJAX)
├── classes/            # PHP classes (to be implemented)
├── config/             # Configuration files
├── cron/               # Cron job scripts (to be implemented)
├── includes/           # Helper functions and libraries
├── install/            # Database schema and installer
├── logs/               # Application logs
├── public/             # Public-facing pages and assets
├── templates/          # Email and page templates (to be implemented)
├── vendor/             # Composer dependencies
├── .env                # Environment configuration (not in git)
├── .env.example        # Environment template
├── .gitignore          # Git ignore rules
├── .htaccess           # Apache security configuration
├── composer.json       # PHP dependencies
└── README.md           # This file
```

## Troubleshooting

### Database Connection Error

- Check database credentials in `.env`
- Verify database exists and user has permissions
- Check MySQL is running: `sudo systemctl status mysql`

### Composer Not Found

Install Composer:
```bash
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
```

### Email Not Sending

- Verify SMTP credentials in `.env`
- Check if your email provider requires app-specific passwords
- Test with a simple email script first
- Check `logs/errors.log` for error messages

### Stripe Payments Failing

- Verify Stripe keys are correct (test vs live)
- Check webhook is configured and receiving events
- Test with Stripe test cards first
- Check `logs/webhooks.log` for webhook errors

### Permission Denied Errors

```bash
# Fix file permissions
sudo chown -R www-data:www-data /path/to/campbooking
sudo chmod -R 755 /path/to/campbooking
sudo chmod -R 777 logs/
```

## Support

For issues or questions:

1. Check error logs: `tail -f logs/errors.log`
2. Check webhook logs: `tail -f logs/webhooks.log`
3. Enable development mode in `.env`: `APP_ENV=development`
4. Contact: [Your contact information]

## Development Roadmap

### Phase 1: Foundation ✅
- [x] Project structure
- [x] Database schema
- [x] Configuration system
- [x] Authentication system
- [x] Basic admin pages

### Phase 2: Booking Form (In Progress)
- [ ] Public booking form
- [ ] Dynamic attendee addition
- [ ] Live price calculation
- [ ] Form validation
- [ ] Booking creation

### Phase 3: Payment Integration
- [ ] Stripe integration
- [ ] One-time payments
- [ ] Installment setup
- [ ] Payment scheduling
- [ ] Webhook handling

### Phase 4: Email System
- [ ] PHPMailer configuration
- [ ] Email templates
- [ ] Confirmation emails
- [ ] Payment receipts
- [ ] Reminder system

### Phase 5: Admin Panel
- [ ] Dashboard with stats
- [ ] Booking list
- [ ] Booking detail/edit
- [ ] Payment management
- [ ] Manual payment marking

### Phase 6: Automation
- [ ] Cron jobs
- [ ] Scheduled payments
- [ ] Payment reminders
- [ ] Failed payment retry

### Phase 7: Testing & Polish
- [ ] Security audit
- [ ] User testing
- [ ] Bug fixes
- [ ] UI/UX polish

### Phase 8: Deployment
- [ ] Production setup
- [ ] Live Stripe configuration
- [ ] Final testing
- [ ] Go live!

## License

Copyright © 2026 Alive Church. All rights reserved.

This is proprietary software developed for Alive Church. Unauthorized copying, distribution, or modification is prohibited.

## Credits

Developed for Alive Church Camp 2026

---

**Last Updated**: February 2026

## Quick Start (Local Development)

1. **Start the server:**
   ```bash
   cd /Users/jonothorne/webroot/campbooking
   php -S localhost:8444
   ```

2. **Access the application:**
   - Booking form: http://localhost:8444
   - Admin panel: http://localhost:8444/admin
   - Admin login: `admin` / `ChangeMeNow123!`

3. **Configure Stripe (optional for testing):**
   - See [STRIPE_SETUP.md](STRIPE_SETUP.md) for detailed setup
   - Update `.env` with your test keys
   - Use test card: `4242 4242 4242 4242`

## Documentation

- **[STRIPE_SETUP.md](STRIPE_SETUP.md)** - Complete Stripe integration guide
- **[DEPLOYMENT.md](DEPLOYMENT.md)** - Production deployment guide
- **[crontab.example](crontab.example)** - Cron job configuration

## Project Structure

```
/campbooking/
├── admin/                    # Admin panel pages
├── api/                      # API endpoints (webhooks, AJAX)
├── classes/                  # PHP classes (Booking, Email, Stripe, etc.)
├── config/                   # Configuration files
├── cron/                     # Cron job scripts
├── includes/                 # Shared PHP includes
├── install/                  # Database schema
├── logs/                     # Application logs
├── public/                   # Static assets (CSS, JS, images)
├── templates/                # Email & admin templates
├── vendor/                   # Composer dependencies
├── .env                      # Environment variables (DO NOT COMMIT)
├── .htaccess                 # Apache security config
├── index.php                 # Main booking form
├── stripe-checkout.php       # Stripe payment page
└── README.md                 # This file
```

## Cron Jobs

Add these to your crontab (see [crontab.example](crontab.example)):

```cron
0 9 * * * php /path/to/campbooking/cron/process-payments.php
0 10 * * * php /path/to/campbooking/cron/send-reminders.php
0 11 * * * php /path/to/campbooking/cron/check-failed-payments.php
```

## Testing

### Test Cards (Stripe Test Mode)
- **Success:** 4242 4242 4242 4242
- **Decline:** 4000 0000 0000 0002
- **Requires Auth:** 4000 0027 6000 3184

### Test Scenarios
1. ✅ Full payment with Stripe
2. ✅ Monthly installment setup
3. ✅ 3-payment plan setup
4. ✅ Bank transfer booking
5. ✅ Cash booking
6. ✅ Manual payment marking
7. ✅ Failed payment handling
8. ✅ Email sending
9. ✅ Webhook processing
10. ✅ Cron job execution

## Troubleshooting

### Common Issues

**"Transaction already active" error:**
- Fixed in process-booking.php - no nested transactions

**Admin login not working:**
- Default password: `ChangeMeNow123!`
- Username: `admin`
- Change immediately after deployment

**Emails not sending:**
- Check SMTP credentials in `.env`
- Review logs/errors.log
- Test SMTP connection

**Stripe webhook failing:**
- Verify webhook secret in `.env`
- Check logs/webhooks.log
- Use Stripe CLI for local testing: `stripe listen --forward-to localhost:8444/api/stripe-webhook.php`

## Security Notes

⚠️ **IMPORTANT:**
- Never commit `.env` to git
- Change default admin password immediately
- Use strong database passwords
- Enable HTTPS in production
- Keep dependencies updated: `composer update`
- Review logs regularly

## License

Proprietary - Alive Church

## Support

For issues or questions:
- Check documentation in `/docs`
- Review logs in `/logs`
- Contact system administrator

---

**Built for Alive Church Camp 2026** 🏕️
