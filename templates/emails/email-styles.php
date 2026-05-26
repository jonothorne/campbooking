<?php
/**
 * Shared Email Styles for ECHO2027: Amplified
 * Consistent branding across all email templates
 * Cyan (#00e5ff) and Magenta (#eb008b) dual accent theme
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
        background-color: #f0f0f2;
    }
    .email-container {
        background: white;
        border-radius: 0;
        padding: 0;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        overflow: hidden;
    }

    /* Header with ECHO Amplified branding */
    .email-header {
        background: #121214;
        text-align: center;
        padding: 40px 30px 30px;
        color: white;
        position: relative;
    }
    .email-header::after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        height: 3px;
        background: linear-gradient(90deg, #00e5ff, #eb008b);
    }
    .email-header-logo {
        width: 180px;
        height: auto;
        margin: 0 auto 15px;
        display: block;
    }
    .email-header h1 {
        color: white;
        margin: 0 0 8px 0;
        font-size: 36px;
        font-weight: 900;
        letter-spacing: 6px;
        text-transform: uppercase;
    }
    .email-header-subtitle {
        color: #00e5ff;
        margin: 0;
        font-size: 13px;
        font-weight: 600;
        letter-spacing: 3px;
        text-transform: uppercase;
    }
    .email-header-verse {
        color: rgba(255,255,255,0.5);
        margin: 20px 0 0 0;
        font-size: 13px;
        font-style: italic;
        border-top: 1px solid rgba(255,255,255,0.1);
        padding-top: 15px;
    }

    /* Content area */
    .email-content {
        padding: 35px 30px;
    }

    /* Booking reference badge */
    .booking-ref {
        background: #121214;
        color: white;
        padding: 20px 25px;
        border-radius: 8px;
        text-align: center;
        margin: 25px 0;
        font-size: 20px;
        font-weight: 700;
        letter-spacing: 3px;
        border-left: 4px solid #00e5ff;
        border-right: 4px solid #eb008b;
    }

    /* Sections */
    .section {
        margin: 30px 0;
    }
    .section h2 {
        color: #1f2937;
        font-size: 18px;
        margin-bottom: 18px;
        border-bottom: 2px solid #00e5ff;
        padding-bottom: 10px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 1px;
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
        border-left: 4px solid #00e5ff;
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
        background: #121214;
        border: none;
        padding: 22px;
        border-radius: 8px;
        margin: 25px 0;
        font-size: 24px;
        font-weight: 700;
        text-align: center;
        color: white;
        border-left: 4px solid #00e5ff;
        border-right: 4px solid #eb008b;
    }

    /* Amount displays */
    .amount-paid {
        background: linear-gradient(135deg, #10b98120 0%, #05966920 100%);
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
        background: #121214;
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
        color: #00e5ff;
        text-decoration: none;
    }

    /* Buttons */
    .button {
        display: inline-block;
        background: linear-gradient(135deg, #eb008b 0%, #d40080 100%);
        color: white;
        padding: 14px 32px;
        text-decoration: none;
        border-radius: 4px;
        margin: 20px 0;
        font-weight: 700;
        letter-spacing: 2px;
        text-transform: uppercase;
        font-size: 14px;
    }
</style>
