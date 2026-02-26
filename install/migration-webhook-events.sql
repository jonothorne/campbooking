-- Migration: Add webhook_events table for replay attack protection
-- Created: 2026-02-26

CREATE TABLE IF NOT EXISTS `webhook_events` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `stripe_event_id` VARCHAR(100) NOT NULL,
  `event_type` VARCHAR(100) NOT NULL,
  `processed_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_event` (`stripe_event_id`),
  KEY `idx_event_type` (`event_type`),
  KEY `idx_processed_at` (`processed_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Index to quickly check for duplicate events
CREATE INDEX idx_stripe_event_lookup ON webhook_events(stripe_event_id, processed_at);
