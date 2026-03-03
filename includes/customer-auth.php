<?php
/**
 * Customer Authentication Functions
 * Handles customer portal login, password setup, GDPR requests
 */

/**
 * Generate password setup token for a booking
 *
 * @param int $bookingId
 * @return string Token to send in email
 */
function generatePasswordSetupToken($bookingId) {
    $db = Database::getInstance();

    // Generate secure random token
    $token = bin2hex(random_bytes(32));
    $expiry = time() + (7 * 24 * 60 * 60); // 7 days

    // Store hashed token in database
    $db->insert(
        "INSERT INTO password_setup_tokens (booking_id, token, expires_at) VALUES (?, ?, FROM_UNIXTIME(?))",
        [$bookingId, hash('sha256', $token), $expiry]
    );

    return $token;
}

/**
 * Verify password setup token
 *
 * @param string $token
 * @return array|false Booking data or false if invalid
 */
function verifyPasswordSetupToken($token) {
    $db = Database::getInstance();

    $tokenData = $db->fetchOne(
        "SELECT pst.id as token_id, pst.booking_id, b.booker_email, b.booker_name
        FROM password_setup_tokens pst
        JOIN bookings b ON pst.booking_id = b.id
        WHERE pst.token = ? AND pst.expires_at > NOW() AND pst.used = 0",
        [hash('sha256', $token)]
    );

    return $tokenData ?: false;
}

/**
 * Mark password setup token as used
 *
 * @param int $tokenId
 */
function markTokenAsUsed($tokenId) {
    $db = Database::getInstance();
    $db->execute(
        "UPDATE password_setup_tokens SET used = 1, used_at = NOW() WHERE id = ?",
        [$tokenId]
    );
}

/**
 * Set password for a booking
 *
 * @param int $bookingId
 * @param string $password
 * @return bool
 */
function setBookingPassword($bookingId, $password) {
    $db = Database::getInstance();
    $passwordHash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 10]);

    return $db->execute(
        "UPDATE bookings SET password_hash = ? WHERE id = ?",
        [$passwordHash, $bookingId]
    ) > 0;
}

/**
 * Customer login
 *
 * @param string $email
 * @param string $password
 * @return array ['success' => bool, 'error' => string|null, 'booking_id' => int|null]
 */
function customerLogin($email, $password) {
    $db = Database::getInstance();

    // Fetch booking by email
    $booking = $db->fetchOne(
        "SELECT id, booker_email, booker_name, password_hash, booking_status
        FROM bookings
        WHERE booker_email = ?
        LIMIT 1",
        [$email]
    );

    if (!$booking) {
        return [
            'success' => false,
            'error' => 'No booking found with this email address.'
        ];
    }

    // Check if password is set
    if (empty($booking['password_hash'])) {
        return [
            'success' => false,
            'error' => 'Please use the password setup link sent to your email to create your password first.'
        ];
    }

    // Verify password
    if (password_verify($password, $booking['password_hash'])) {
        // Update last portal login
        $db->execute(
            "UPDATE bookings SET last_portal_login = NOW() WHERE id = ?",
            [$booking['id']]
        );

        // Regenerate session ID for security
        session_regenerate_id(true);

        // Set session variables
        $_SESSION['customer_id'] = $booking['id'];
        $_SESSION['customer_email'] = $booking['booker_email'];
        $_SESSION['customer_name'] = $booking['booker_name'];
        $_SESSION['customer_csrf_token'] = bin2hex(random_bytes(32));
        $_SESSION['customer_last_activity'] = time();

        // Log GDPR access
        logGDPRAction($booking['id'], 'data_access', 'Customer portal login');

        return [
            'success' => true,
            'booking_id' => $booking['id']
        ];
    }

    return [
        'success' => false,
        'error' => 'Invalid password.'
    ];
}

/**
 * Customer logout
 */
function customerLogout() {
    // Unset customer session variables
    unset($_SESSION['customer_id']);
    unset($_SESSION['customer_email']);
    unset($_SESSION['customer_name']);
    unset($_SESSION['customer_csrf_token']);
    unset($_SESSION['customer_last_activity']);
}

/**
 * Check if customer is logged in
 *
 * @return bool
 */
function isCustomerLoggedIn() {
    if (!isset($_SESSION['customer_id']) || !isset($_SESSION['customer_last_activity'])) {
        return false;
    }

    // Check session timeout (2 hours)
    if (time() - $_SESSION['customer_last_activity'] > 7200) {
        customerLogout();
        return false;
    }

    // Update last activity time
    $_SESSION['customer_last_activity'] = time();

    return true;
}

/**
 * Require customer authentication
 */
function requireCustomerAuth() {
    if (!isCustomerLoggedIn()) {
        redirect(url('portal/login.php'));
    }
}

/**
 * Get current customer booking ID
 *
 * @return int|null
 */
function currentCustomerId() {
    return $_SESSION['customer_id'] ?? null;
}

/**
 * Get current customer email
 *
 * @return string|null
 */
function currentCustomerEmail() {
    return $_SESSION['customer_email'] ?? null;
}

/**
 * Generate customer CSRF token
 *
 * @return string
 */
function generateCustomerCsrfToken() {
    if (!isset($_SESSION['customer_csrf_token'])) {
        $_SESSION['customer_csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['customer_csrf_token'];
}

/**
 * Verify customer CSRF token
 *
 * @param string $token
 * @return bool
 */
function verifyCustomerCsrfToken($token) {
    if (!isset($_SESSION['customer_csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['customer_csrf_token'], $token);
}

/**
 * Require valid customer CSRF token
 */
function requireCustomerCsrfToken() {
    $token = $_POST['csrf_token'] ?? $_GET['csrf_token'] ?? '';

    if (!verifyCustomerCsrfToken($token)) {
        http_response_code(403);
        die('Invalid CSRF token. Please refresh the page and try again.');
    }
}

/**
 * Log GDPR action
 *
 * @param int $bookingId
 * @param string $action
 * @param string $details
 */
function logGDPRAction($bookingId, $action, $details = '') {
    $db = Database::getInstance();

    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
    $performedBy = 'customer';

    if (isset($_SESSION['admin_id'])) {
        $performedBy = 'admin:' . ($_SESSION['admin_username'] ?? 'unknown');
    }

    $db->insert(
        "INSERT INTO gdpr_log (booking_id, action, ip_address, user_agent, details, performed_by)
        VALUES (?, ?, ?, ?, ?, ?)",
        [$bookingId, $action, $ipAddress, $userAgent, $details, $performedBy]
    );
}

/**
 * Export customer data (GDPR compliance)
 *
 * @param int $bookingId
 * @return array Complete customer data
 */
function exportCustomerData($bookingId) {
    $db = Database::getInstance();

    // Get booking data
    $booking = $db->fetchOne("SELECT * FROM bookings WHERE id = ?", [$bookingId]);

    // Get attendees
    $attendees = $db->fetchAll("SELECT * FROM attendees WHERE booking_id = ?", [$bookingId]);

    // Get payments
    $payments = $db->fetchAll("SELECT * FROM payments WHERE booking_id = ?", [$bookingId]);

    // Get payment schedule
    $schedule = $db->fetchAll("SELECT * FROM payment_schedules WHERE booking_id = ?", [$bookingId]);

    // Get email logs
    $emails = $db->fetchAll("SELECT * FROM email_logs WHERE booking_id = ?", [$bookingId]);

    // Get GDPR log
    $gdprLog = $db->fetchAll("SELECT * FROM gdpr_log WHERE booking_id = ?", [$bookingId]);

    // Log this export
    logGDPRAction($bookingId, 'data_export', 'Customer data exported');

    return [
        'booking' => $booking,
        'attendees' => $attendees,
        'payments' => $payments,
        'payment_schedule' => $schedule,
        'email_logs' => $emails,
        'gdpr_log' => $gdprLog,
        'export_date' => date('Y-m-d H:i:s'),
        'export_requested_by' => currentCustomerEmail() ?? currentAdminUsername() ?? 'unknown'
    ];
}

/**
 * Request data deletion (GDPR right to be forgotten)
 *
 * @param int $bookingId
 * @param string $reason
 * @return bool
 */
function requestDataDeletion($bookingId, $reason = '') {
    $db = Database::getInstance();

    // Mark booking for deletion
    $db->execute(
        "UPDATE bookings SET data_deletion_requested = 1, data_deletion_requested_at = NOW() WHERE id = ?",
        [$bookingId]
    );

    // Log the request
    logGDPRAction($bookingId, 'data_deletion_request', 'Reason: ' . $reason);

    // TODO: Send notification to admin about deletion request

    return true;
}

/**
 * Process data deletion (admin action)
 *
 * @param int $bookingId
 * @return bool
 */
function processDataDeletion($bookingId) {
    $db = Database::getInstance();

    try {
        $db->beginTransaction();

        // Log the deletion
        logGDPRAction($bookingId, 'data_deletion', 'Data permanently deleted');

        // Delete related records (cascading foreign keys will handle most)
        // But we'll be explicit for clarity
        $db->execute("DELETE FROM attendees WHERE booking_id = ?", [$bookingId]);
        $db->execute("DELETE FROM payments WHERE booking_id = ?", [$bookingId]);
        $db->execute("DELETE FROM payment_schedules WHERE booking_id = ?", [$bookingId]);
        $db->execute("DELETE FROM email_logs WHERE booking_id = ?", [$bookingId]);
        $db->execute("DELETE FROM password_setup_tokens WHERE booking_id = ?", [$bookingId]);
        $db->execute("DELETE FROM remember_tokens WHERE user_id IN (SELECT id FROM users WHERE email = (SELECT booker_email FROM bookings WHERE id = ?))", [$bookingId]);

        // Finally delete the booking
        $db->execute("DELETE FROM bookings WHERE id = ?", [$bookingId]);

        $db->commit();
        return true;
    } catch (Exception $e) {
        $db->rollback();
        error_log("Data deletion error: " . $e->getMessage());
        return false;
    }
}
