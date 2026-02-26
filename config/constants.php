<?php
/**
 * Application Constants and Configuration
 * Loads environment variables, configures sessions, error handling
 */

// Load environment variables from .env file
function loadEnv($path) {
    if (!file_exists($path)) {
        die('.env file not found. Please copy .env.example to .env and configure it.');
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        // Skip comments
        if (strpos(trim($line), '#') === 0) {
            continue;
        }

        // Parse KEY=VALUE
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);

            // Remove quotes if present
            $value = trim($value, '"\'');

            // Set as environment variable
            putenv("{$key}={$value}");
            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
        }
    }
}

// Load .env file
$envPath = dirname(__DIR__) . '/.env';
loadEnv($envPath);

// ============================================
// Error Reporting Configuration
// ============================================

$appEnv = getenv('APP_ENV') ?: 'development';

if ($appEnv === 'production') {
    // Production: Log errors, don't display
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
    ini_set('error_log', dirname(__DIR__) . '/logs/errors.log');
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
} else {
    // Development: Display all errors
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
}

// ============================================
// Session Configuration (Secure)
// ============================================

// Secure session settings
ini_set('session.cookie_httponly', '1');
ini_set('session.use_only_cookies', '1');
ini_set('session.cookie_samesite', 'Lax'); // Changed from Strict to Lax for better compatibility

// Use HTTPS cookies in production
if ($appEnv === 'production' || (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on')) {
    ini_set('session.cookie_secure', '1');
}

// Set session cookie path
ini_set('session.cookie_path', '/');

// Session timeout (2 hours)
ini_set('session.gc_maxlifetime', '7200');

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ============================================
// Application Constants
// ============================================

// Paths
define('ROOT_PATH', dirname(__DIR__));
define('PUBLIC_PATH', ROOT_PATH . '/public');
define('ADMIN_PATH', ROOT_PATH . '/admin');
define('CLASSES_PATH', ROOT_PATH . '/classes');
define('CONFIG_PATH', ROOT_PATH . '/config');
define('INCLUDES_PATH', ROOT_PATH . '/includes');
define('TEMPLATES_PATH', ROOT_PATH . '/templates');
define('LOGS_PATH', ROOT_PATH . '/logs');

// URLs
define('APP_URL', getenv('APP_URL') ?: 'http://localhost');
define('APP_ENV', $appEnv);

// Event Details
define('EVENT_NAME', getenv('EVENT_NAME') ?: 'Alive Church Camp 2026');
define('EVENT_START_DATE', getenv('EVENT_START_DATE') ?: '2026-05-29');
define('EVENT_END_DATE', getenv('EVENT_END_DATE') ?: '2026-05-31');
define('PAYMENT_DEADLINE', getenv('PAYMENT_DEADLINE') ?: '2026-05-20');

// Pricing
define('ADULT_PRICE', (float)(getenv('ADULT_PRICE') ?: 85.00));
define('ADULT_SPONSOR_PRICE', (float)(getenv('ADULT_SPONSOR_PRICE') ?: 110.00));
define('CHILD_PRICE', (float)(getenv('CHILD_PRICE') ?: 55.00));
define('ADULT_DAY_PRICE', (float)(getenv('ADULT_DAY_PRICE') ?: 25.00));
define('CHILD_DAY_PRICE', (float)(getenv('CHILD_DAY_PRICE') ?: 15.00));

// Age thresholds
define('FREE_CHILD_MAX_AGE', 4);  // 0-4 years old
define('CHILD_MIN_AGE', 5);       // 5-15 years old
define('CHILD_MAX_AGE', 15);
define('ADULT_MIN_AGE', 16);      // 16+ years old

// Bank Transfer Details
define('BANK_NAME', getenv('BANK_NAME') ?: 'Alive UK');
define('BANK_ACCOUNT', getenv('BANK_ACCOUNT') ?: '67366334');
define('BANK_SORT_CODE', getenv('BANK_SORT_CODE') ?: '08-92-99');
define('BANK_REFERENCE_PREFIX', getenv('BANK_REFERENCE_PREFIX') ?: 'Camp');

// Stripe
define('STRIPE_PUBLIC_KEY', getenv('STRIPE_PUBLIC_KEY') ?: '');
define('STRIPE_SECRET_KEY', getenv('STRIPE_SECRET_KEY') ?: '');
define('STRIPE_WEBHOOK_SECRET', getenv('STRIPE_WEBHOOK_SECRET') ?: '');

// Email/SMTP
define('SMTP_HOST', getenv('SMTP_HOST') ?: 'smtp.gmail.com');
define('SMTP_PORT', getenv('SMTP_PORT') ?: 587);
define('SMTP_USER', getenv('SMTP_USER') ?: '');
define('SMTP_PASS', getenv('SMTP_PASS') ?: '');
define('SMTP_FROM_EMAIL', getenv('SMTP_FROM_EMAIL') ?: 'bookings@alivechurch.com');
define('SMTP_FROM_NAME', getenv('SMTP_FROM_NAME') ?: 'Alive Church Camp');

// Payment Retry Settings
define('MAX_PAYMENT_RETRIES', 3);
define('PAYMENT_RETRY_DAYS', [0, 2, 5]); // Retry on due date, +2 days, +5 days
define('PAYMENT_REMINDER_DAYS_BEFORE', 3); // Send reminder 3 days before due date

// ============================================
// Timezone
// ============================================

date_default_timezone_set('Europe/London');

// ============================================
// Helper Functions
// ============================================

/**
 * Get environment variable with fallback
 */
function env($key, $default = null) {
    $value = getenv($key);
    return $value !== false ? $value : $default;
}

/**
 * Check if app is in production
 */
function isProduction() {
    return APP_ENV === 'production';
}

/**
 * Check if app is in development
 */
function isDevelopment() {
    return APP_ENV === 'development';
}

/**
 * Generate absolute URL
 */
function url($path = '') {
    return rtrim(APP_URL, '/') . '/' . ltrim($path, '/');
}

/**
 * Redirect to URL
 */
function redirect($url, $statusCode = 302) {
    header('Location: ' . $url, true, $statusCode);
    exit;
}

/**
 * Get asset URL
 */
function asset($path) {
    return url('public/assets/' . ltrim($path, '/'));
}
