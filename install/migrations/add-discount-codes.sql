-- Migration: Add discount codes system
-- Run this migration to add discount code support

-- Discount codes table
CREATE TABLE IF NOT EXISTS `discount_codes` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `code` VARCHAR(50) NOT NULL,
  `description` VARCHAR(255) DEFAULT NULL,
  `discount_type` ENUM('percentage', 'fixed', 'full') NOT NULL,
  `discount_value` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT 'Percentage (0-100) or fixed amount. Ignored for full type.',
  `max_uses` INT(11) UNSIGNED DEFAULT NULL COMMENT 'NULL = unlimited',
  `times_used` INT(11) UNSIGNED NOT NULL DEFAULT 0,
  `expires_at` DATETIME DEFAULT NULL COMMENT 'NULL = no expiry',
  `event_year` SMALLINT UNSIGNED NOT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_code_year` (`code`, `event_year`),
  KEY `idx_active` (`is_active`, `event_year`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add discount_code_id to bookings table
ALTER TABLE `bookings`
  ADD COLUMN `discount_code_id` INT(11) UNSIGNED DEFAULT NULL AFTER `amount_outstanding`,
  ADD COLUMN `discount_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER `discount_code_id`,
  ADD KEY `idx_discount_code` (`discount_code_id`),
  ADD CONSTRAINT `fk_bookings_discount_code` FOREIGN KEY (`discount_code_id`) REFERENCES `discount_codes` (`id`) ON DELETE SET NULL;

-- Add 'discount' to payments.payment_type enum
ALTER TABLE `payments`
  MODIFY COLUMN `payment_type` ENUM('full', 'installment', 'manual', 'portal_payment', 'discount') NOT NULL;
