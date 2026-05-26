-- Add tent details and transport requirement fields to bookings table
ALTER TABLE bookings
    ADD COLUMN `tent_details` TEXT DEFAULT NULL AFTER `needs_tent_provided`,
    ADD COLUMN `needs_transport` TINYINT(1) NOT NULL DEFAULT 0 AFTER `tent_details`,
    ADD COLUMN `transport_details` TEXT DEFAULT NULL AFTER `needs_transport`;
