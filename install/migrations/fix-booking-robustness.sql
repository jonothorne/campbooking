-- Migration: Improve booking robustness
-- Adds idempotency token for duplicate prevention
-- Adds submission tracking

-- Add idempotency token to bookings table
ALTER TABLE bookings
    ADD COLUMN `idempotency_token` VARCHAR(64) DEFAULT NULL AFTER `booking_reference`,
    ADD UNIQUE KEY `idx_idempotency_token` (`idempotency_token`);
