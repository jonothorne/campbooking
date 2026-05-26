-- Add event_year column to bookings
-- Allows archiving bookings by event year while keeping all data
ALTER TABLE `bookings`
ADD COLUMN `event_year` SMALLINT UNSIGNED NOT NULL DEFAULT 2026 COMMENT 'The event year this booking belongs to'
AFTER `payment_status`;

-- Add index for filtering by event year
ALTER TABLE `bookings`
ADD KEY `idx_event_year` (`event_year`);

-- Tag all existing bookings as 2026
UPDATE `bookings` SET `event_year` = 2026;

-- Remove the default so new bookings must explicitly set the year
ALTER TABLE `bookings`
ALTER COLUMN `event_year` DROP DEFAULT;
