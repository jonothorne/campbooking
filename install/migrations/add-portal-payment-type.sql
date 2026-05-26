-- Add 'portal_payment' to payments.payment_type enum
ALTER TABLE `payments`
MODIFY COLUMN `payment_type` ENUM('full', 'installment', 'manual', 'portal_payment') NOT NULL;
