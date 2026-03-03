-- Add customer portal and GDPR compliance features
-- Run this migration to enable customer self-service and GDPR tools

-- Add password and GDPR fields to bookings table
ALTER TABLE `bookings`
ADD COLUMN `password_hash` VARCHAR(255) NULL AFTER `booking_reference`,
ADD COLUMN `privacy_policy_accepted` TINYINT(1) NOT NULL DEFAULT 0 AFTER `special_requirements`,
ADD COLUMN `privacy_policy_accepted_at` TIMESTAMP NULL AFTER `privacy_policy_accepted`,
ADD COLUMN `marketing_consent` TINYINT(1) NOT NULL DEFAULT 0 AFTER `privacy_policy_accepted_at`,
ADD COLUMN `data_deletion_requested` TINYINT(1) NOT NULL DEFAULT 0 AFTER `marketing_consent`,
ADD COLUMN `data_deletion_requested_at` TIMESTAMP NULL AFTER `data_deletion_requested`,
ADD COLUMN `last_portal_login` TIMESTAMP NULL AFTER `data_deletion_requested_at`;

-- Create password_setup_tokens table for secure password creation
CREATE TABLE IF NOT EXISTS `password_setup_tokens` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `booking_id` int unsigned NOT NULL,
  `token` varchar(64) NOT NULL,
  `expires_at` timestamp NOT NULL,
  `used` tinyint(1) NOT NULL DEFAULT 0,
  `used_at` timestamp NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `booking_id` (`booking_id`),
  KEY `token` (`token`),
  KEY `expires_at` (`expires_at`),
  CONSTRAINT `password_setup_tokens_booking_id_foreign` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create GDPR audit log table
CREATE TABLE IF NOT EXISTS `gdpr_log` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `booking_id` int unsigned NOT NULL,
  `action` enum('data_export','data_deletion_request','data_deletion','data_access','privacy_update') NOT NULL,
  `ip_address` varchar(45) NULL,
  `user_agent` varchar(255) NULL,
  `details` text NULL,
  `performed_by` varchar(100) NULL COMMENT 'customer or admin username',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `booking_id` (`booking_id`),
  KEY `action` (`action`),
  KEY `created_at` (`created_at`),
  CONSTRAINT `gdpr_log_booking_id_foreign` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create index for faster booking lookup by email
ALTER TABLE `bookings` ADD INDEX `booker_email` (`booker_email`);

-- Add password_setup to email_type enum
ALTER TABLE `email_logs`
MODIFY COLUMN `email_type` ENUM('booking_confirmation','payment_receipt','payment_reminder','payment_failed','password_setup','general') NOT NULL;
