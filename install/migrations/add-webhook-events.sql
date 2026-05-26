-- Create webhook_events table for Stripe webhook replay attack protection
CREATE TABLE `webhook_events` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `stripe_event_id` VARCHAR(100) NOT NULL,
  `event_type` VARCHAR(100) NOT NULL,
  `processed_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_stripe_event` (`stripe_event_id`),
  KEY `idx_event_lookup` (`stripe_event_id`, `processed_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
