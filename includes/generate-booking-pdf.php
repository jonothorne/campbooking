<?php
/**
 * Generate Booking PDF HTML
 * Shared function for creating booking confirmation PDF content
 */

function generateBookingPDF($bookingData, $attendees, $payments) {
    // Load and encode logo as base64
    $logoPath = dirname(__DIR__) . '/public/assets/images/ECHO-logo-dark.png';
    $logoData = '';
    if (file_exists($logoPath)) {
        $logoContent = file_get_contents($logoPath);
        $logoData = 'data:image/png;base64,' . base64_encode($logoContent);
    }

    // Create HTML for PDF
    $html = '
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        @page {
            margin: 20mm;
        }
        body {
            font-family: Arial, sans-serif;
            font-size: 11pt;
            color: #333;
            line-height: 1.4;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 3px solid #eb008b;
        }
        .logo {
            max-width: 300px;
            height: auto;
            margin-bottom: 20px;
        }
        .event-title {
            font-size: 24pt;
            font-weight: bold;
            color: #eb008b;
            margin: 10px 0;
        }
        .event-dates {
            font-size: 14pt;
            color: #666;
            margin: 5px 0;
        }
        .booking-ref {
            background: #eb008b;
            color: white;
            padding: 15px;
            text-align: center;
            font-size: 20pt;
            font-weight: bold;
            margin: 20px 0;
            border-radius: 5px;
            letter-spacing: 2px;
        }
        .section {
            margin: 25px 0;
        }
        .section-title {
            font-size: 14pt;
            font-weight: bold;
            color: #eb008b;
            margin-bottom: 10px;
            padding-bottom: 5px;
            border-bottom: 2px solid #eb008b;
        }
        .info-grid {
            display: table;
            width: 100%;
            margin: 10px 0;
        }
        .info-row {
            display: table-row;
        }
        .info-label {
            display: table-cell;
            font-weight: bold;
            padding: 5px 15px 5px 0;
            width: 35%;
        }
        .info-value {
            display: table-cell;
            padding: 5px 0;
        }
        .attendees-table {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0;
        }
        .attendees-table th {
            background: #eb008b;
            color: white;
            padding: 10px;
            text-align: left;
            font-weight: bold;
        }
        .attendees-table td {
            padding: 8px 10px;
            border-bottom: 1px solid #ddd;
        }
        .attendees-table tr:nth-child(even) {
            background: #f9f9f9;
        }
        .payment-summary {
            background: #f0f9ff;
            padding: 15px;
            border-radius: 5px;
            margin: 15px 0;
        }
        .payment-row {
            display: table;
            width: 100%;
            margin: 5px 0;
        }
        .payment-label {
            display: table-cell;
            font-weight: bold;
            width: 70%;
        }
        .payment-value {
            display: table-cell;
            text-align: right;
            font-weight: bold;
        }
        .total-row {
            font-size: 14pt;
            color: #eb008b;
            margin-top: 10px;
            padding-top: 10px;
            border-top: 2px solid #eb008b;
        }
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 2px solid #ddd;
            text-align: center;
            font-size: 9pt;
            color: #666;
        }
        .check-in-box {
            background: #fff3cd;
            border: 2px solid #ffc107;
            padding: 15px;
            margin: 20px 0;
            border-radius: 5px;
        }
        .check-in-box h3 {
            margin: 0 0 10px 0;
            color: #856404;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">';

    if ($logoData) {
        $html .= '
        <img src="' . $logoData . '" alt="ECHO2026" class="logo">';
    }

    $html .= '
        <div class="event-dates">' . formatDate(EVENT_START_DATE, 'jS F') . ' - ' . formatDate(EVENT_END_DATE, 'jS F Y') . '</div>
        <div style="font-size: 10pt; color: #666; margin-top: 10px;">Sizewell Hall, Sizewell, Leiston, Suffolk, IP16 4TX</div>
    </div>

    <!-- Booking Reference -->
    <div class="booking-ref">
        BOOKING: ' . e($bookingData['booking_reference']) . '
    </div>

    <div class="check-in-box">
        <h3>📋 Check-In Instructions</h3>
        <p style="margin: 5px 0;">Please bring this confirmation when you arrive at ECHO2026. Show your booking reference to our registration team for a smooth check-in.</p>
    </div>

    <!-- Booker Information -->
    <div class="section">
        <div class="section-title">Booking Contact</div>
        <div class="info-grid">
            <div class="info-row">
                <div class="info-label">Name:</div>
                <div class="info-value">' . e($bookingData['booker_name']) . '</div>
            </div>
            <div class="info-row">
                <div class="info-label">Email:</div>
                <div class="info-value">' . e($bookingData['booker_email']) . '</div>
            </div>
            <div class="info-row">
                <div class="info-label">Phone:</div>
                <div class="info-value">' . e($bookingData['booker_phone']) . '</div>
            </div>
            <div class="info-row">
                <div class="info-label">Booking Date:</div>
                <div class="info-value">' . formatDate($bookingData['created_at'], 'd M Y') . '</div>
            </div>
        </div>
    </div>

    <!-- Attendees -->
    <div class="section">
        <div class="section-title">Attendees (' . count($attendees) . ' people)</div>
        <table class="attendees-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Age</th>
                    <th>Ticket Type</th>
                    <th style="text-align: right;">Price</th>
                </tr>
            </thead>
            <tbody>';

    foreach ($attendees as $attendee) {
        $ticketType = ucwords(str_replace('_', ' ', $attendee['ticket_type']));

        // Add day info for day tickets
        if (!empty($attendee['day_ticket_dates'])) {
            $days = json_decode($attendee['day_ticket_dates'], true);
            if ($days) {
                $dayNames = array_map(function($date) {
                    return formatDate($date, 'D jS');
                }, $days);
                $ticketType .= ' (' . implode(', ', $dayNames) . ')';
            }
        }

        $html .= '
                <tr>
                    <td><strong>' . e($attendee['name']) . '</strong></td>
                    <td>' . $attendee['age'] . ' years</td>
                    <td>' . $ticketType . '</td>
                    <td style="text-align: right;">' . formatCurrency($attendee['ticket_price']) . '</td>
                </tr>';
    }

    $html .= '
            </tbody>
        </table>
    </div>

    <!-- Camping Requirements -->
    <div class="section">
        <div class="section-title">Camping Requirements</div>
        <div class="info-grid">
            <div class="info-row">
                <div class="info-label">Number of Tents:</div>
                <div class="info-value">' . ($bookingData['num_tents'] ?? 0) . '</div>
            </div>
            <div class="info-row">
                <div class="info-label">Caravan/Campervan:</div>
                <div class="info-value">' . ($bookingData['has_caravan'] ? 'Yes' : 'No') . '</div>
            </div>
            <div class="info-row">
                <div class="info-label">Need Tent Provided:</div>
                <div class="info-value">' . ($bookingData['needs_tent_provided'] ? 'Yes' : 'No') . '</div>
            </div>';

    if (!empty($bookingData['special_requirements'])) {
        $html .= '
            <div class="info-row">
                <div class="info-label">Special Requirements:</div>
                <div class="info-value">' . nl2br(e($bookingData['special_requirements'])) . '</div>
            </div>';
    }

    $html .= '
        </div>
    </div>

    <!-- Payment Summary -->
    <div class="section">
        <div class="section-title">Payment Summary</div>
        <div class="payment-summary">
            <div class="payment-row">
                <div class="payment-label">Total Booking Amount:</div>
                <div class="payment-value">' . formatCurrency($bookingData['total_amount']) . '</div>
            </div>
            <div class="payment-row">
                <div class="payment-label">Amount Paid:</div>
                <div class="payment-value" style="color: #10b981;">' . formatCurrency($bookingData['amount_paid']) . '</div>
            </div>
            <div class="payment-row">
                <div class="payment-label">Outstanding:</div>
                <div class="payment-value" style="color: ' . ($bookingData['amount_outstanding'] > 0 ? '#ef4444' : '#10b981') . ';">' . formatCurrency(max(0, $bookingData['amount_outstanding'])) . '</div>
            </div>
            <div class="payment-row total-row">
                <div class="payment-label">Payment Status:</div>
                <div class="payment-value">' . ucfirst($bookingData['payment_status']) . '</div>
            </div>
        </div>
    </div>';

    // Add payment history if available
    if (!empty($payments)) {
        $html .= '
    <div class="section">
        <div class="section-title">Payment History</div>
        <table class="attendees-table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Amount</th>
                    <th>Method</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>';

        foreach ($payments as $payment) {
            $html .= '
                <tr>
                    <td>' . formatDate($payment['payment_date'], 'd M Y H:i') . '</td>
                    <td>' . formatCurrency($payment['amount']) . '</td>
                    <td>' . ucwords(str_replace('_', ' ', $payment['payment_method'])) . '</td>
                    <td>' . ucfirst($payment['status']) . '</td>
                </tr>';
        }

        $html .= '
            </tbody>
        </table>
    </div>';
    }

    $html .= '
    <!-- Footer -->
    <div class="footer">
        <p><strong>Questions?</strong> Contact us at ' . e(env('SMTP_FROM_EMAIL')) . '</p>
        <p style="margin-top: 10px;">We look forward to seeing you at ECHO2026!</p>
        <p style="margin-top: 15px; font-size: 8pt;">Generated on ' . date('d M Y H:i') . '</p>
    </div>
</body>
</html>';

    return $html;
}
