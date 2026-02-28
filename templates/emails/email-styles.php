<?php
/**
 * Shared Email Styles for ECHO2026
 * Consistent branding across all email templates
 */
?>
<style>
    body {
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
        line-height: 1.6;
        color: #1f2937;
        max-width: 600px;
        margin: 0 auto;
        padding: 20px;
        background-color: #f5f5f5;
    }
    .email-container {
        background: white;
        border-radius: 0;
        padding: 0;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        overflow: hidden;
    }

    /* Header with ECHO branding */
    .email-header {
        background: linear-gradient(135deg, #eb008b 0%, #d40080 100%);
        text-align: center;
        padding: 40px 30px 30px;
        color: white;
        position: relative;
    }
    .email-header-logo {
        width: 180px;
        height: auto;
        margin: 0 auto 15px;
        display: block;
        filter: brightness(0) invert(1);
    }
    .email-header h1 {
        color: white;
        margin: 0 0 8px 0;
        font-size: 36px;
        font-weight: 700;
        letter-spacing: 4px;
    }
    .email-header-subtitle {
        color: rgba(255,255,255,0.95);
        margin: 0;
        font-size: 14px;
        font-weight: 400;
        letter-spacing: 1px;
    }
    .email-header-verse {
        color: rgba(255,255,255,0.9);
        margin: 20px 0 0 0;
        font-size: 13px;
        font-style: italic;
        border-top: 1px solid rgba(255,255,255,0.3);
        padding-top: 15px;
    }

    /* Content area */
    .email-content {
        padding: 35px 30px;
    }

    /* Booking reference badge */
    .booking-ref {
        background: linear-gradient(135deg, #eb008b 0%, #d40080 100%);
        color: white;
        padding: 18px 25px;
        border-radius: 8px;
        text-align: center;
        margin: 25px 0;
        font-size: 18px;
        font-weight: 700;
        letter-spacing: 2px;
    }

    /* Sections */
    .section {
        margin: 30px 0;
    }
    .section h2 {
        color: #eb008b;
        font-size: 20px;
        margin-bottom: 18px;
        border-bottom: 2px solid #eb008b;
        padding-bottom: 10px;
        font-weight: 700;
    }

    /* Detail rows */
    .detail-row {
        display: flex;
        justify-content: space-between;
        padding: 12px 0;
        border-bottom: 1px solid #e5e7eb;
    }
    .detail-label {
        font-weight: 600;
        color: #6b7280;
    }
    .detail-value {
        color: #1f2937;
        font-weight: 500;
    }

    /* Attendee list */
    .attendee-list {
        background: #f9fafb;
        padding: 18px;
        border-radius: 8px;
        margin: 15px 0;
        border-left: 4px solid #eb008b;
    }
    .attendee-item {
        padding: 10px 0;
        border-bottom: 1px solid #e5e7eb;
    }
    .attendee-item:last-child {
        border-bottom: none;
    }

    /* Total amount */
    .total-amount {
        background: linear-gradient(135deg, #eb008b10 0%, #d4008010 100%);
        border: 2px solid #eb008b;
        padding: 20px;
        border-radius: 8px;
        margin: 25px 0;
        font-size: 22px;
        font-weight: 700;
        text-align: center;
        color: #eb008b;
    }

    /* Amount displays */
    .amount-paid {
        background: linear-gradient(135deg, #10b98120 0%, #059669 20 100%);
        border: 3px solid #10b981;
        color: #047857;
        padding: 25px;
        border-radius: 12px;
        text-align: center;
        font-size: 36px;
        font-weight: 700;
        margin: 25px 0;
    }
    .amount-failed {
        background: linear-gradient(135deg, #ef444420 0%, #dc262620 100%);
        border: 3px solid #ef4444;
        color: #dc2626;
        padding: 25px;
        border-radius: 12px;
        text-align: center;
        font-size: 36px;
        font-weight: 700;
        margin: 25px 0;
    }

    /* Info boxes */
    .payment-info {
        background: #fffbf0;
        border-left: 4px solid #f59e0b;
        padding: 18px;
        margin: 20px 0;
        border-radius: 4px;
    }
    .bank-details {
        background: #f0f9ff;
        border: 2px solid #3b82f6;
        padding: 22px;
        border-radius: 8px;
        margin: 20px 0;
    }
    .bank-details .detail-row {
        border-bottom: 1px solid #bfdbfe;
    }

    /* Alert badges */
    .reminder-badge, .alert-badge {
        background: #fef3c7;
        color: #92400e;
        padding: 15px 20px;
        border-radius: 8px;
        text-align: center;
        font-size: 18px;
        font-weight: 700;
        margin: 20px 0;
        border: 2px solid #fbbf24;
    }
    .alert-badge {
        background: #fee2e2;
        color: #991b1b;
        border-color: #ef4444;
    }

    /* Footer */
    .email-footer {
        background: #1a1a1a;
        color: #9ca3af;
        padding: 30px;
        text-align: center;
        font-size: 13px;
        line-height: 1.8;
    }
    .email-footer strong {
        color: #e5e7eb;
    }
    .email-footer a {
        color: #eb008b;
        text-decoration: none;
    }

    /* Buttons */
    .button {
        display: inline-block;
        background: linear-gradient(135deg, #eb008b 0%, #d40080 100%);
        color: white;
        padding: 14px 32px;
        text-decoration: none;
        border-radius: 8px;
        margin: 20px 0;
        font-weight: 700;
        letter-spacing: 1px;
    }
</style>
