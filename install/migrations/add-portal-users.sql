-- Create portal_users table for customer portal authentication
-- Separates user identity from bookings so logins persist across event years

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

-- Add portal_user_id to bookings
ALTER TABLE `bookings`
ADD COLUMN `portal_user_id` INT(11) UNSIGNED DEFAULT NULL AFTER `booking_reference`,
ADD KEY `idx_portal_user` (`portal_user_id`),
ADD CONSTRAINT `fk_bookings_portal_user` FOREIGN KEY (`portal_user_id`) REFERENCES `portal_users` (`id`) ON DELETE SET NULL;

-- Migrate existing booking passwords into portal_users
-- For each booking that has a password_hash, create a portal_user using the most recent booking
INSERT INTO `portal_users` (`email`, `name`, `phone`, `password_hash`, `created_at`)
SELECT b.`booker_email`, b.`booker_name`, b.`booker_phone`, b.`password_hash`, b.`created_at`
FROM `bookings` b
INNER JOIN (
    SELECT `booker_email`, MAX(`id`) as max_id
    FROM `bookings`
    WHERE `password_hash` IS NOT NULL AND `password_hash` != ''
    GROUP BY `booker_email`
) latest ON b.id = latest.max_id;

-- Link bookings to their portal_users
UPDATE `bookings` b
JOIN `portal_users` pu ON pu.email = b.booker_email
SET b.portal_user_id = pu.id
WHERE b.password_hash IS NOT NULL AND b.password_hash != '';

-- Copy last_portal_login to portal_users.last_login
UPDATE `portal_users` pu
JOIN `bookings` b ON b.portal_user_id = pu.id
SET pu.last_login = b.last_portal_login
WHERE b.last_portal_login IS NOT NULL;

-- Drop the now-redundant columns from bookings
ALTER TABLE `bookings`
DROP COLUMN `password_hash`,
DROP COLUMN `last_portal_login`;
