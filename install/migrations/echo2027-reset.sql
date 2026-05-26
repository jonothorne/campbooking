-- ECHO2027: Amplified - Database Reset & Migration
-- Run this to clear all 2026 data and prepare for 2027 bookings
-- WARNING: This will DELETE all existing booking data!

-- ============================================
-- Step 1: Clear all booking data
-- ============================================

-- Disable foreign key checks for clean deletion
SET FOREIGN_KEY_CHECKS = 0;

TRUNCATE TABLE email_logs;
TRUNCATE TABLE payment_schedules;
TRUNCATE TABLE payments;
TRUNCATE TABLE attendees;
TRUNCATE TABLE bookings;

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================
-- Step 2: Alter payment_plan column
-- ============================================

ALTER TABLE bookings
    MODIFY COLUMN `payment_plan` TINYINT UNSIGNED NOT NULL DEFAULT 1
    COMMENT 'Number of installments (1-11)';

-- ============================================
-- Step 3: Update settings for ECHO2027
-- ============================================

-- Clear existing settings
DELETE FROM settings;

-- Insert new settings
INSERT INTO `settings` (`setting_key`, `setting_value`) VALUES
('event_name', 'ECHO2027: Amplified'),
('event_start_date', '2027-06-03'),
('event_end_date', '2027-06-06'),
('payment_deadline', '2027-05-20'),
('early_bird_cutoff', '2026-07-31'),
('adult_price', '120.00'),
('child_price', '70.00'),
('adult_day_price', '50.00'),
('child_day_price', '20.00'),
('adult_sponsor_suggested', '150.00'),
('standard_adult_price', '135.00'),
('standard_child_price', '85.00'),
('standard_adult_day_price', '60.00'),
('standard_child_day_price', '25.00'),
('max_installments', '11'),
('bank_name', 'Alive UK'),
('bank_account', '67366334'),
('bank_sort_code', '08-92-99'),
('bank_reference_prefix', 'ECHO'),
('stripe_public_key', ''),
('stripe_secret_key', ''),
('stripe_webhook_secret', '');

-- ============================================
-- Done! Database is ready for ECHO2027 bookings.
-- ============================================
