-- Add stripe_payment_intent_id to payment_schedules
-- Tracks which Stripe charge corresponds to each schedule to prevent double charging
ALTER TABLE `payment_schedules`
ADD COLUMN `stripe_payment_intent_id` VARCHAR(255) DEFAULT NULL COMMENT 'Tracks which Stripe charge was made for this schedule'
AFTER `payment_id`;
