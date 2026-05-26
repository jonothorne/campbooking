# Production Migration Guide

Run these migrations **in order** against the production database.
Production is currently at commit `757e58d` with migrations `add_remember_tokens.sql` and `add_customer_portal_gdpr.sql` already applied.

## Migration Order

Run each file one at a time. If a step fails, fix the issue before continuing.

```bash
# 1. Add transport fields to bookings
mysql -u USER -p DATABASE < install/migrations/add-transport-fields.sql

# 2. Add idempotency token for duplicate booking prevention
mysql -u USER -p DATABASE < install/migrations/fix-booking-robustness.sql

# 3. Add stripe_payment_intent_id to payment_schedules
mysql -u USER -p DATABASE < install/migrations/add-schedule-payment-intent-id.sql

# 4. Add portal_payment to payments enum
mysql -u USER -p DATABASE < install/migrations/add-portal-payment-type.sql

# 5. Create webhook_events table
mysql -u USER -p DATABASE < install/migrations/add-webhook-events.sql

# 6. Add event_year column (tags all existing bookings as 2026)
mysql -u USER -p DATABASE < install/migrations/add-event-year.sql

# 7. Create portal_users table and migrate passwords from bookings
#    IMPORTANT: Must run BEFORE echo2027-reset because it reads password_hash from bookings
mysql -u USER -p DATABASE < install/migrations/add-portal-users.sql

# 8. ECHO2027 reset - truncates all booking data and updates settings
#    WARNING: This DELETES all existing booking/payment/attendee data!
#    Must run LAST as it depends on schema changes from previous migrations
mysql -u USER -p DATABASE < install/migrations/echo2027-reset.sql
```

## Notes

- Steps 1-5 can be run in any order relative to each other
- Step 6 (event_year) must run before step 8 (echo2027-reset)
- Step 7 (portal_users) MUST run before step 8 because it migrates password data that gets truncated
- Step 8 is destructive — it wipes all booking data and reconfigures settings for ECHO2027
- Back up the database before running any migrations
