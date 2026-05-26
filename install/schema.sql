-- ECHO2027: Amplified - Database Schema
-- Updated: 2026-05-26

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

-- --------------------------------------------------------
-- Table structure for table `users`
-- Admin authentication
-- --------------------------------------------------------

CREATE TABLE `users` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `username` VARCHAR(50) NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `email` VARCHAR(100) NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `last_login` TIMESTAMP NULL DEFAULT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  KEY `idx_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `portal_users`
-- Customer portal authentication (independent of bookings)
-- --------------------------------------------------------

CREATE TABLE `portal_users` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `email` VARCHAR(100) NOT NULL,
  `name` VARCHAR(100) NOT NULL,
  `phone` VARCHAR(20) NOT NULL DEFAULT '',
  `password_hash` VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `last_login` TIMESTAMP NULL DEFAULT NULL,

  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `bookings`
-- Main booking records
-- --------------------------------------------------------

CREATE TABLE `bookings` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `booking_reference` VARCHAR(20) NOT NULL,
  `portal_user_id` INT(11) UNSIGNED DEFAULT NULL,
  `idempotency_token` VARCHAR(64) DEFAULT NULL,
  `booker_name` VARCHAR(100) NOT NULL,
  `booker_email` VARCHAR(100) NOT NULL,
  `booker_phone` VARCHAR(20) NOT NULL,

  -- Camping requirements
  `num_tents` INT(11) NOT NULL DEFAULT 0,
  `has_caravan` TINYINT(1) NOT NULL DEFAULT 0,
  `needs_tent_provided` TINYINT(1) NOT NULL DEFAULT 0,
  `tent_details` TEXT DEFAULT NULL,
  `needs_transport` TINYINT(1) NOT NULL DEFAULT 0,
  `transport_details` TEXT DEFAULT NULL,
  `special_requirements` TEXT DEFAULT NULL,

  -- GDPR / Privacy
  `privacy_policy_accepted` TINYINT(1) NOT NULL DEFAULT 0,
  `privacy_policy_accepted_at` TIMESTAMP NULL DEFAULT NULL,
  `marketing_consent` TINYINT(1) NOT NULL DEFAULT 0,
  `data_deletion_requested` TINYINT(1) NOT NULL DEFAULT 0,
  `data_deletion_requested_at` TIMESTAMP NULL DEFAULT NULL,

  -- Payment information
  `payment_method` ENUM('cash', 'bank_transfer', 'stripe') NOT NULL,
  `payment_plan` TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT 'Number of installments (1-11)',
  `total_amount` DECIMAL(10,2) NOT NULL,
  `amount_paid` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `amount_outstanding` DECIMAL(10,2) NOT NULL,

  -- Stripe-specific fields
  `stripe_customer_id` VARCHAR(100) DEFAULT NULL,
  `stripe_payment_method_id` VARCHAR(100) DEFAULT NULL,
  `stripe_payment_intent_id` VARCHAR(255) DEFAULT NULL,
  `stripe_setup_intent_id` VARCHAR(255) DEFAULT NULL,

  -- Status tracking
  `booking_status` ENUM('pending', 'confirmed', 'cancelled') NOT NULL DEFAULT 'pending',
  `payment_status` ENUM('unpaid', 'partial', 'paid', 'failed') NOT NULL DEFAULT 'unpaid',

  `event_year` SMALLINT UNSIGNED NOT NULL COMMENT 'The event year this booking belongs to',

  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  PRIMARY KEY (`id`),
  UNIQUE KEY `booking_reference` (`booking_reference`),
  UNIQUE KEY `idx_idempotency_token` (`idempotency_token`),
  KEY `idx_email` (`booker_email`),
  KEY `idx_payment_status` (`payment_status`),
  KEY `idx_booking_status` (`booking_status`),
  KEY `idx_stripe_customer` (`stripe_customer_id`),
  KEY `idx_event_year` (`event_year`),
  KEY `idx_portal_user` (`portal_user_id`),
  CONSTRAINT `fk_bookings_portal_user` FOREIGN KEY (`portal_user_id`) REFERENCES `portal_users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `attendees`
-- Individual people in bookings
-- --------------------------------------------------------

CREATE TABLE `attendees` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `booking_id` INT(11) UNSIGNED NOT NULL,
  `name` VARCHAR(100) NOT NULL,
  `age` INT(11) NOT NULL,
  `ticket_type` ENUM('adult_weekend', 'adult_sponsor', 'child_weekend', 'free_child', 'adult_day', 'child_day') NOT NULL,
  `ticket_price` DECIMAL(10,2) NOT NULL,

  -- Day ticket specific fields
  `day_ticket_dates` TEXT DEFAULT NULL COMMENT 'JSON array of dates for day tickets',

  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

  PRIMARY KEY (`id`),
  KEY `idx_booking` (`booking_id`),
  KEY `idx_ticket_type` (`ticket_type`),
  CONSTRAINT `fk_attendees_booking` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `payments`
-- Payment transaction records
-- --------------------------------------------------------

CREATE TABLE `payments` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `booking_id` INT(11) UNSIGNED NOT NULL,
  `amount` DECIMAL(10,2) NOT NULL,
  `payment_method` ENUM('cash', 'bank_transfer', 'stripe') NOT NULL,
  `payment_type` ENUM('full', 'installment', 'manual', 'portal_payment') NOT NULL,

  -- Stripe specific
  `stripe_payment_intent_id` VARCHAR(100) DEFAULT NULL,
  `stripe_charge_id` VARCHAR(100) DEFAULT NULL,

  -- Status
  `status` ENUM('pending', 'succeeded', 'failed', 'refunded') NOT NULL DEFAULT 'pending',

  -- Notes
  `admin_notes` TEXT DEFAULT NULL,
  `failure_reason` TEXT DEFAULT NULL,

  `payment_date` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `processed_by_admin_id` INT(11) UNSIGNED DEFAULT NULL,

  PRIMARY KEY (`id`),
  KEY `idx_booking` (`booking_id`),
  KEY `idx_status` (`status`),
  KEY `idx_stripe_intent` (`stripe_payment_intent_id`),
  KEY `idx_payment_date` (`payment_date`),
  CONSTRAINT `fk_payments_booking` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_payments_admin` FOREIGN KEY (`processed_by_admin_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `payment_schedules`
-- Installment payment schedules
-- --------------------------------------------------------

CREATE TABLE `payment_schedules` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `booking_id` INT(11) UNSIGNED NOT NULL,
  `installment_number` INT(11) NOT NULL,
  `amount` DECIMAL(10,2) NOT NULL,
  `due_date` DATE NOT NULL,
  `status` ENUM('pending', 'paid', 'failed', 'cancelled') NOT NULL DEFAULT 'pending',
  `payment_id` INT(11) UNSIGNED DEFAULT NULL COMMENT 'Links to payments table when paid',
  `stripe_payment_intent_id` VARCHAR(255) DEFAULT NULL COMMENT 'Tracks which Stripe charge was made for this schedule',
  `attempt_count` INT(11) NOT NULL DEFAULT 0,
  `last_attempt_date` TIMESTAMP NULL DEFAULT NULL,
  `next_retry_date` DATE DEFAULT NULL,

  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

  PRIMARY KEY (`id`),
  KEY `idx_booking` (`booking_id`),
  KEY `idx_due_date` (`due_date`),
  KEY `idx_status` (`status`),
  KEY `idx_next_retry` (`next_retry_date`),
  CONSTRAINT `fk_schedules_booking` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_schedules_payment` FOREIGN KEY (`payment_id`) REFERENCES `payments` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `email_logs`
-- Track all emails sent
-- --------------------------------------------------------

CREATE TABLE `email_logs` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `booking_id` INT(11) UNSIGNED DEFAULT NULL,
  `recipient_email` VARCHAR(100) NOT NULL,
  `email_type` ENUM('booking_confirmation', 'payment_receipt', 'payment_reminder', 'payment_failed', 'password_setup', 'general') NOT NULL,
  `subject` VARCHAR(255) NOT NULL,
  `status` ENUM('sent', 'failed') NOT NULL,
  `error_message` TEXT DEFAULT NULL,
  `sent_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

  PRIMARY KEY (`id`),
  KEY `idx_booking` (`booking_id`),
  KEY `idx_type` (`email_type`),
  KEY `idx_sent_at` (`sent_at`),
  CONSTRAINT `fk_emails_booking` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `password_setup_tokens`
-- Secure password creation for customer portal
-- --------------------------------------------------------

CREATE TABLE `password_setup_tokens` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `booking_id` INT(11) UNSIGNED NOT NULL,
  `token` VARCHAR(64) NOT NULL,
  `expires_at` TIMESTAMP NOT NULL,
  `used` TINYINT(1) NOT NULL DEFAULT 0,
  `used_at` TIMESTAMP NULL DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

  PRIMARY KEY (`id`),
  KEY `idx_booking` (`booking_id`),
  KEY `idx_token` (`token`),
  KEY `idx_expires` (`expires_at`),
  CONSTRAINT `fk_tokens_booking` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `gdpr_log`
-- GDPR audit trail
-- --------------------------------------------------------

CREATE TABLE `gdpr_log` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `booking_id` INT(11) UNSIGNED NOT NULL,
  `action` ENUM('data_export', 'data_deletion_request', 'data_deletion', 'data_access', 'privacy_update') NOT NULL,
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `user_agent` VARCHAR(255) DEFAULT NULL,
  `details` TEXT DEFAULT NULL,
  `performed_by` VARCHAR(100) DEFAULT NULL COMMENT 'customer or admin username',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

  PRIMARY KEY (`id`),
  KEY `idx_booking` (`booking_id`),
  KEY `idx_action` (`action`),
  KEY `idx_created` (`created_at`),
  CONSTRAINT `fk_gdpr_booking` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `webhook_events`
-- Stripe webhook replay attack protection
-- --------------------------------------------------------

CREATE TABLE `webhook_events` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `stripe_event_id` VARCHAR(100) NOT NULL,
  `event_type` VARCHAR(100) NOT NULL,
  `processed_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_stripe_event` (`stripe_event_id`),
  KEY `idx_event_lookup` (`stripe_event_id`, `processed_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `settings`
-- System configuration
-- --------------------------------------------------------

CREATE TABLE `settings` (
  `setting_key` VARCHAR(50) NOT NULL,
  `setting_value` TEXT NOT NULL,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  PRIMARY KEY (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Insert default settings
-- --------------------------------------------------------

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

-- --------------------------------------------------------
-- Insert default admin user
-- Username: admin
-- Password: ChangeMeNow123! (MUST be changed on first login)
-- --------------------------------------------------------

INSERT INTO `users` (`username`, `password_hash`, `email`, `is_active`) VALUES
('admin', '$2y$10$71o8.h9sSwN40E4JDdkzO.24nSdja.wP.k9.z.VCcTZESjpYNds56', 'admin@alivechurch.com', 1);

-- Note: Default password is 'ChangeMeNow123!' - CHANGE THIS IMMEDIATELY after installation!
