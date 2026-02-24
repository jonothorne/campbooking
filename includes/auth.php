<?php
/**
 * Authentication Functions
 * Handles login, logout, CSRF protection, rate limiting
 */

/**
 * Attempt to log in a user
 *
 * @param string $username
 * @param string $password
 * @return array ['success' => bool, 'error' => string|null]
 */
function login($username, $password) {
    $db = Database::getInstance();

    // Check rate limiting
    if (isRateLimited($username)) {
        return [
            'success' => false,
            'error' => 'Too many login attempts. Please try again in 15 minutes.'
        ];
    }

    // Fetch user from database
    $user = $db->fetchOne(
        "SELECT id, username, password_hash, is_active FROM users WHERE username = ?",
        [$username]
    );

    if (!$user || !$user['is_active']) {
        recordFailedAttempt($username);
        return [
            'success' => false,
            'error' => 'Invalid username or password.'
        ];
    }

    // Verify password
    if (password_verify($password, $user['password_hash'])) {
        // Update last login
        $db->execute(
            "UPDATE users SET last_login = NOW() WHERE id = ?",
            [$user['id']]
        );

        // Regenerate session ID for security
        session_regenerate_id(true);

        // Set session variables
        $_SESSION['admin_id'] = $user['id'];
        $_SESSION['admin_username'] = $user['username'];
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        $_SESSION['last_activity'] = time();

        // Clear failed attempts
        clearFailedAttempts($username);

        return ['success' => true];
    }

    // Failed login
    recordFailedAttempt($username);
    return [
        'success' => false,
        'error' => 'Invalid username or password.'
    ];
}

/**
 * Log out current user
 */
function logout() {
    // Unset all session variables
    $_SESSION = [];

    // Destroy the session cookie
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time() - 3600, '/');
    }

    // Destroy the session
    session_destroy();
}

/**
 * Check if user is authenticated
 *
 * @return bool
 */
function isLoggedIn() {
    if (!isset($_SESSION['admin_id']) || !isset($_SESSION['last_activity'])) {
        return false;
    }

    // Check session timeout (2 hours)
    if (time() - $_SESSION['last_activity'] > 7200) {
        logout();
        return false;
    }

    // Update last activity time
    $_SESSION['last_activity'] = time();

    return true;
}

/**
 * Require authentication - redirect to login if not authenticated
 *
 * @param string $redirectTo URL to redirect to after login
 */
function requireAuth($redirectTo = null) {
    if (!isLoggedIn()) {
        $loginUrl = url('admin/login.php');

        if ($redirectTo) {
            $loginUrl .= '?redirect=' . urlencode($redirectTo);
        }

        redirect($loginUrl);
    }
}

/**
 * Get current admin user ID
 *
 * @return int|null
 */
function currentAdminId() {
    return $_SESSION['admin_id'] ?? null;
}

/**
 * Get current admin username
 *
 * @return string|null
 */
function currentAdminUsername() {
    return $_SESSION['admin_username'] ?? null;
}

/**
 * Generate CSRF token
 *
 * @return string
 */
function generateCsrfToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token
 *
 * @param string $token
 * @return bool
 */
function verifyCsrfToken($token) {
    if (!isset($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Require valid CSRF token - die if invalid
 */
function requireCsrfToken() {
    $token = $_POST['csrf_token'] ?? $_GET['csrf_token'] ?? '';

    if (!verifyCsrfToken($token)) {
        http_response_code(403);
        die('Invalid CSRF token. Please refresh the page and try again.');
    }
}

/**
 * Output CSRF token as hidden input field
 */
function csrfField() {
    $token = generateCsrfToken();
    echo '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
}

/**
 * Get CSRF token value
 *
 * @return string
 */
function csrfToken() {
    return generateCsrfToken();
}

/**
 * Check if user is rate limited
 *
 * @param string $identifier Username or IP
 * @return bool
 */
function isRateLimited($identifier) {
    $cacheFile = sys_get_temp_dir() . '/login_attempts_' . md5($identifier);

    if (file_exists($cacheFile)) {
        $data = json_decode(file_get_contents($cacheFile), true);

        if (!$data || !is_array($data)) {
            return false;
        }

        // Filter attempts within last 15 minutes
        $recentAttempts = array_filter($data, function($timestamp) {
            return $timestamp > time() - 900; // 15 minutes
        });

        // Update file with filtered attempts
        file_put_contents($cacheFile, json_encode(array_values($recentAttempts)));

        // Rate limit if 5 or more attempts
        if (count($recentAttempts) >= 5) {
            return true;
        }
    }

    return false;
}

/**
 * Record a failed login attempt
 *
 * @param string $identifier Username or IP
 */
function recordFailedAttempt($identifier) {
    $cacheFile = sys_get_temp_dir() . '/login_attempts_' . md5($identifier);

    $attempts = [];
    if (file_exists($cacheFile)) {
        $data = json_decode(file_get_contents($cacheFile), true);
        if (is_array($data)) {
            $attempts = $data;
        }
    }

    $attempts[] = time();
    file_put_contents($cacheFile, json_encode($attempts));
}

/**
 * Clear failed login attempts
 *
 * @param string $identifier Username or IP
 */
function clearFailedAttempts($identifier) {
    $cacheFile = sys_get_temp_dir() . '/login_attempts_' . md5($identifier);

    if (file_exists($cacheFile)) {
        unlink($cacheFile);
    }
}

/**
 * Hash a password
 *
 * @param string $password
 * @return string
 */
function hashPassword($password) {
    return password_hash($password, PASSWORD_BCRYPT, ['cost' => 10]);
}

/**
 * Verify a password against a hash
 *
 * @param string $password
 * @param string $hash
 * @return bool
 */
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

/**
 * Check if password needs rehashing (security upgrade)
 *
 * @param string $hash
 * @return bool
 */
function needsRehash($hash) {
    return password_needs_rehash($hash, PASSWORD_BCRYPT, ['cost' => 10]);
}
