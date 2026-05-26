<?php
/**
 * Customer Authentication Functions
 * Handles customer portal login, password setup, GDPR requests
 * Auth is against the portal_users table (independent of bookings)
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
        "SELECT pst.id as token_id, pst.booking_id, b.booker_email, b.booker_name, b.booker_phone
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
 * Create or update a portal user and link to booking.
 * Called when a customer sets up their password via the setup token.
 *
 * @param int $bookingId The booking that triggered the setup
 * @param string $password Plain text password
 * @return int portal_user ID
 */
function createOrUpdatePortalUser($bookingId, $password) {
    $db = Database::getInstance();
    $passwordHash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 10]);

    // Get booking details
    $booking = $db->fetchOne("SELECT booker_email, booker_name, booker_phone FROM bookings WHERE id = ?", [$bookingId]);
    if (!$booking) {
        throw new Exception("Booking not found");
    }

    // Check if portal user already exists for this email
    $existingUser = $db->fetchOne("SELECT id FROM portal_users WHERE email = ?", [$booking['booker_email']]);

    if ($existingUser) {
        // Update existing user's password
        $db->execute(
            "UPDATE portal_users SET password_hash = ?, name = ?, phone = ? WHERE id = ?",
            [$passwordHash, $booking['booker_name'], $booking['booker_phone'], $existingUser['id']]
        );
        $portalUserId = $existingUser['id'];
    } else {
        // Create new portal user
        $portalUserId = $db->insert(
            "INSERT INTO portal_users (email, name, phone, password_hash) VALUES (?, ?, ?, ?)",
            [$booking['booker_email'], $booking['booker_name'], $booking['booker_phone'], $passwordHash]
        );
    }

    // Link this booking to the portal user
    $db->execute(
        "UPDATE bookings SET portal_user_id = ? WHERE id = ?",
        [$portalUserId, $bookingId]
    );

    // Also link any other bookings with the same email that aren't linked yet
    $db->execute(
        "UPDATE bookings SET portal_user_id = ? WHERE booker_email = ? AND portal_user_id IS NULL",
        [$portalUserId, $booking['booker_email']]
    );

    return $portalUserId;
}

/**
 * Customer login - authenticates against portal_users table
 *
 * @param string $email
 * @param string $password
 * @return array ['success' => bool, 'error' => string|null, 'portal_user_id' => int|null]
 */
function customerLogin($email, $password) {
    $db = Database::getInstance();

    // Fetch portal user by email
    $user = $db->fetchOne(
        "SELECT id, email, name, password_hash
        FROM portal_users
        WHERE email = ?",
        [$email]
    );

    if (!$user) {
        // Check if they have a booking but no portal account yet
        $hasBooking = $db->fetchOne(
            "SELECT id FROM bookings WHERE booker_email = ? AND booking_status != 'cancelled' LIMIT 1",
            [$email]
        );

        if ($hasBooking) {
            return [
                'success' => false,
                'error' => 'Please use the password setup link sent to your email to create your password first.'
            ];
        }

        return [
            'success' => false,
            'error' => 'No account found with this email address.'
        ];
    }

    // Verify password
    if (!password_verify($password, $user['password_hash'])) {
        return [
            'success' => false,
            'error' => 'Invalid password.'
        ];
    }

    // Update last login
    $db->execute("UPDATE portal_users SET last_login = NOW() WHERE id = ?", [$user['id']]);

    // Regenerate session ID for security
    session_regenerate_id(true);

    // Set session variables
    $_SESSION['portal_user_id'] = $user['id'];
    $_SESSION['customer_email'] = $user['email'];
    $_SESSION['customer_name'] = $user['name'];
    $_SESSION['customer_csrf_token'] = bin2hex(random_bytes(32));
    $_SESSION['customer_last_activity'] = time();

    // Find the current event year booking for GDPR logging
    $currentBooking = getPortalUserBooking($user['id']);
    if ($currentBooking) {
        logGDPRAction($currentBooking['id'], 'data_access', 'Customer portal login');
    }

    return [
        'success' => true,
        'portal_user_id' => $user['id']
    ];
}

/**
 * Customer logout
 */
function customerLogout() {
    unset($_SESSION['portal_user_id']);
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
    if (!isset($_SESSION['portal_user_id']) || !isset($_SESSION['customer_last_activity'])) {
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
 * Get current portal user ID
 *
 * @return int|null
 */
function currentPortalUserId() {
    return $_SESSION['portal_user_id'] ?? null;
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
 * Get portal user data
 *
 * @param int $portalUserId
 * @return array|null
 */
function getPortalUser($portalUserId) {
    $db = Database::getInstance();
    return $db->fetchOne("SELECT * FROM portal_users WHERE id = ?", [$portalUserId]);
}

/**
 * Get the booking for a portal user for a given event year.
 * Defaults to the current EVENT_YEAR.
 *
 * @param int $portalUserId
 * @param int|null $eventYear
 * @return array|null Booking row or null
 */
function getPortalUserBooking($portalUserId, $eventYear = null) {
    $db = Database::getInstance();
    $year = $eventYear ?? EVENT_YEAR;

    return $db->fetchOne(
        "SELECT * FROM bookings WHERE portal_user_id = ? AND event_year = ? AND booking_status != 'cancelled' ORDER BY id DESC LIMIT 1",
        [$portalUserId, $year]
    );
}

/**
 * Get all bookings for a portal user (across all event years)
 *
 * @param int $portalUserId
 * @return array
 */
function getPortalUserBookings($portalUserId) {
    $db = Database::getInstance();
    return $db->fetchAll(
        "SELECT * FROM bookings WHERE portal_user_id = ? AND booking_status != 'cancelled' ORDER BY event_year DESC, id DESC",
        [$portalUserId]
    );
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

    $booking = $db->fetchOne("SELECT * FROM bookings WHERE id = ?", [$bookingId]);
    $attendees = $db->fetchAll("SELECT * FROM attendees WHERE booking_id = ?", [$bookingId]);
    $payments = $db->fetchAll("SELECT * FROM payments WHERE booking_id = ?", [$bookingId]);
    $schedule = $db->fetchAll("SELECT * FROM payment_schedules WHERE booking_id = ?", [$bookingId]);
    $emails = $db->fetchAll("SELECT * FROM email_logs WHERE booking_id = ?", [$bookingId]);
    $gdprLog = $db->fetchAll("SELECT * FROM gdpr_log WHERE booking_id = ?", [$bookingId]);

    logGDPRAction($bookingId, 'data_export', 'Customer data exported');

    return [
        'booking' => $booking,
        'attendees' => $attendees,
        'payments' => $payments,
        'payment_schedule' => $schedule,
        'email_logs' => $emails,
        'gdpr_log' => $gdprLog,
        'export_date' => date('Y-m-d H:i:s'),
        'export_requested_by' => currentCustomerEmail() ?? 'unknown'
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

    $db->execute(
        "UPDATE bookings SET data_deletion_requested = 1, data_deletion_requested_at = NOW() WHERE id = ?",
        [$bookingId]
    );

    logGDPRAction($bookingId, 'data_deletion_request', 'Reason: ' . $reason);

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

        logGDPRAction($bookingId, 'data_deletion', 'Data permanently deleted');

        $db->execute("DELETE FROM attendees WHERE booking_id = ?", [$bookingId]);
        $db->execute("DELETE FROM payments WHERE booking_id = ?", [$bookingId]);
        $db->execute("DELETE FROM payment_schedules WHERE booking_id = ?", [$bookingId]);
        $db->execute("DELETE FROM email_logs WHERE booking_id = ?", [$bookingId]);
        $db->execute("DELETE FROM password_setup_tokens WHERE booking_id = ?", [$bookingId]);
        $db->execute("DELETE FROM bookings WHERE id = ?", [$bookingId]);

        $db->commit();
        return true;
    } catch (Exception $e) {
        $db->rollback();
        error_log("Data deletion error: " . $e->getMessage());
        return false;
    }
}
