# Email Configuration Guide

## GoDaddy Shared Hosting Setup (Recommended)

GoDaddy shared hosting blocks external SMTP ports (25, 465, 587) for security. The solution is to use GoDaddy's local mail relay.

### Configuration

Update your `.env` file with:

```bash
SMTP_HOST=localhost
SMTP_PORT=25
SMTP_USER=
SMTP_PASS=
SMTP_FROM_EMAIL=bookings@alivechurch.com
SMTP_FROM_NAME=Alive Church Camp
SMTP_AUTH_REQUIRED=false
```

### Important Notes

1. **From Email Must Be Valid**: The `SMTP_FROM_EMAIL` must be a valid email address on your domain (e.g., `bookings@alivechurch.com` or `noreply@alivechurch.com`)

2. **Create the Email Account in cPanel**:
   - Log into your GoDaddy cPanel
   - Go to "Email Accounts"
   - Create `bookings@alivechurch.com` (or your chosen email)
   - You don't need to use this email account, just create it

3. **No Authentication Required**: GoDaddy's local relay doesn't require username/password

4. **Testing**: Run the test script to verify:
   ```bash
   php test-email.php
   ```

### Advantages
- ✅ No SMTP credentials needed
- ✅ No external port blocking issues
- ✅ Fast delivery
- ✅ Works on all GoDaddy shared hosting plans
- ✅ Free (included with hosting)

### Disadvantages
- ❌ Only works on GoDaddy servers (not localhost)
- ❌ From email must be on your domain
- ❌ Limited to GoDaddy's sending limits (usually 500-1000/day)

---

## Alternative: Gmail SMTP (For Development/Testing)

If you're testing locally or need a different solution:

### Configuration

Update your `.env` file with:

```bash
SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_USER=your_email@gmail.com
SMTP_PASS=your_app_password_here
SMTP_FROM_EMAIL=your_email@gmail.com
SMTP_FROM_NAME=Alive Church Camp
SMTP_AUTH_REQUIRED=true
```

### Setup Steps

1. **Enable 2-Step Verification**:
   - Go to https://myaccount.google.com/security
   - Enable "2-Step Verification"

2. **Generate App Password**:
   - Go to https://myaccount.google.com/apppasswords
   - Select "Mail" and "Other (Custom name)"
   - Name it "Camp Booking System"
   - Copy the 16-character password (remove spaces)
   - Paste into `SMTP_PASS` in `.env`

3. **Test**:
   ```bash
   php test-email.php
   ```

### Advantages
- ✅ Works anywhere (localhost, any server)
- ✅ Reliable delivery
- ✅ Good for development/testing

### Disadvantages
- ❌ Requires Google account setup
- ❌ Limited to 500 emails/day
- ❌ From address must be Gmail (or authenticated domain)

---

## Alternative: Mailgun (For Production)

Professional transactional email service with high deliverability.

### Configuration

Update your `.env` file with:

```bash
SMTP_HOST=smtp.mailgun.org
SMTP_PORT=587
SMTP_USER=postmaster@mg.yourdomain.com
SMTP_PASS=your_mailgun_password
SMTP_FROM_EMAIL=bookings@alivechurch.com
SMTP_FROM_NAME=Alive Church Camp
SMTP_AUTH_REQUIRED=true
```

### Setup Steps

1. Sign up at https://www.mailgun.com
2. Verify your domain
3. Get SMTP credentials from Dashboard → Sending → Domain Settings
4. Update `.env` with credentials

### Advantages
- ✅ 5,000 free emails/month
- ✅ Excellent deliverability
- ✅ Email tracking and analytics
- ✅ Works on any server

### Disadvantages
- ❌ Requires account setup and domain verification
- ❌ Paid after free tier

---

## Alternative: SendGrid (For Production)

Another popular transactional email service.

### Configuration

Update your `.env` file with:

```bash
SMTP_HOST=smtp.sendgrid.net
SMTP_PORT=587
SMTP_USER=apikey
SMTP_PASS=your_sendgrid_api_key
SMTP_FROM_EMAIL=bookings@alivechurch.com
SMTP_FROM_NAME=Alive Church Camp
SMTP_AUTH_REQUIRED=true
```

### Setup Steps

1. Sign up at https://sendgrid.com
2. Create an API Key (Settings → API Keys)
3. Username is literally "apikey"
4. Password is your API key
5. Update `.env`

### Advantages
- ✅ 100 free emails/day (forever)
- ✅ Excellent deliverability
- ✅ Email analytics
- ✅ Works on any server

### Disadvantages
- ❌ Requires account setup
- ❌ Lower free tier than Mailgun

---

## Testing Email Configuration

Run the test script from the command line:

```bash
php test-email.php
```

Enter your email address when prompted. If configured correctly, you'll receive a test email within seconds.

### Troubleshooting

**"Connection refused" or "Connection timed out"**
- Check firewall settings
- Verify SMTP port is not blocked
- For GoDaddy: ensure you're on GoDaddy's server (not localhost)

**"Authentication failed"**
- Verify username/password are correct
- For Gmail: ensure you're using App Password, not regular password
- Check SMTP_AUTH_REQUIRED is set to "true"

**"From address rejected"**
- For GoDaddy: ensure from email exists in cPanel → Email Accounts
- For Gmail: from address must match authenticated account

**Emails go to spam**
- Ensure SPF/DKIM records are configured
- Use a from address on your domain
- Avoid spam trigger words in subject/body

---

## Recommended Setup by Environment

**Local Development (Mac/Windows)**:
- Use Gmail SMTP

**GoDaddy Shared Hosting (Production)**:
- Use GoDaddy localhost relay (port 25)

**VPS/Cloud Server (Production)**:
- Use Mailgun or SendGrid

**High Volume (>5000 emails/month)**:
- Use Mailgun (paid) or SendGrid (paid)

---

## Email Types Sent by System

1. **Booking Confirmation** - Sent immediately after booking
2. **Payment Receipt** - Sent after each successful payment
3. **Payment Reminder** - Sent 3 days before payment due date
4. **Failed Payment** - Sent when payment fails (up to 3 attempts)

All emails are logged in the `email_logs` table for tracking.
