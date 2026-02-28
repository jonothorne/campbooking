<?php
/**
 * Cron Job Tester - Web Interface
 * Access via: https://echo.alivechur.ch/test-cron.php
 *
 * DELETE THIS FILE AFTER TESTING
 */

// Handle AJAX requests FIRST (before any output)
if (isset($_GET['action'])) {
    session_start();

    // Check authentication for AJAX requests
    if (!isset($_SESSION['cron_test_auth'])) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'output' => 'Not authenticated']);
        exit;
    }

    header('Content-Type: application/json');

    switch ($_GET['action']) {
        case 'test':
            testCronScript($_GET['script'] ?? '');
            break;
        case 'viewlog':
            viewLogFile($_GET['type'] ?? '');
            break;
        case 'checkdb':
            checkDatabaseTable($_GET['table'] ?? '');
            break;
        default:
            echo json_encode(['success' => false, 'output' => 'Invalid action']);
    }
    exit;
}

// Now start session for regular page load
session_start();
$password = 'test123'; // Change this to something secure

// Handle login
if (isset($_POST['password'])) {
    if ($_POST['password'] === $password) {
        $_SESSION['cron_test_auth'] = true;
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    } else {
        $loginError = 'Incorrect password';
    }
}

// Show login page if not authenticated
if (!isset($_SESSION['cron_test_auth'])) {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Cron Test - Authentication</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                background: #1a1a1a;
                color: #f5f5f5;
                display: flex;
                justify-content: center;
                align-items: center;
                height: 100vh;
                margin: 0;
            }
            .auth-box {
                background: #2a2a2a;
                padding: 40px;
                border-radius: 8px;
                box-shadow: 0 4px 20px rgba(0,0,0,0.5);
                max-width: 400px;
                width: 100%;
            }
            h2 { color: #eb008b; margin-top: 0; }
            input {
                padding: 10px;
                width: 100%;
                margin: 10px 0;
                border: 1px solid #444;
                background: #1a1a1a;
                color: #f5f5f5;
                border-radius: 4px;
                box-sizing: border-box;
            }
            button {
                padding: 12px 24px;
                background: #eb008b;
                color: white;
                border: none;
                border-radius: 4px;
                cursor: pointer;
                width: 100%;
                font-size: 16px;
            }
            button:hover { background: #d40080; }
            .error {
                background: #7f1d1d;
                color: #fca5a5;
                padding: 10px;
                border-radius: 4px;
                margin-bottom: 15px;
            }
        </style>
    </head>
    <body>
        <div class="auth-box">
            <h2>🔒 Cron Test Authentication</h2>
            <?php if (isset($loginError)): ?>
                <div class="error"><?php echo htmlspecialchars($loginError); ?></div>
            <?php endif; ?>
            <form method="POST">
                <input type="password" name="password" placeholder="Enter password" required autofocus>
                <button type="submit">Login</button>
            </form>
            <p style="font-size: 12px; color: #888; margin-top: 20px;">Default password: test123</p>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// Main page HTML
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cron Job Tester</title>
    <style>
        body {
            font-family: 'Courier New', monospace;
            background: #1a1a1a;
            color: #f5f5f5;
            padding: 40px;
            max-width: 1200px;
            margin: 0 auto;
        }
        h1 {
            color: #eb008b;
            border-bottom: 2px solid #eb008b;
            padding-bottom: 10px;
        }
        h2 {
            color: #ff6b9d;
            margin-top: 30px;
        }
        .warning-box {
            background: #7f1d1d;
            border: 2px solid #dc2626;
            padding: 20px;
            margin: 30px 0;
            border-radius: 8px;
            text-align: center;
        }
        .warning-box strong {
            color: #fca5a5;
            font-size: 18px;
        }
        .test-section {
            background: #2a2a2a;
            border-left: 4px solid #eb008b;
            padding: 20px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .test-button {
            padding: 12px 24px;
            background: linear-gradient(135deg, #eb008b 0%, #d40080 100%);
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            margin: 10px 10px 10px 0;
            transition: all 0.3s ease;
        }
        .test-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(235, 0, 139, 0.4);
        }
        .test-button:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        .output-box {
            background: #1a1a1a;
            border: 1px solid #444;
            padding: 15px;
            margin: 15px 0;
            border-radius: 4px;
            font-family: 'Courier New', monospace;
            font-size: 13px;
            max-height: 400px;
            overflow-y: auto;
            white-space: pre-wrap;
            word-wrap: break-word;
        }
        .success { color: #4ade80; }
        .error { color: #f87171; }
        .info { color: #60a5fa; }
        .loading {
            display: inline-block;
            width: 16px;
            height: 16px;
            border: 3px solid rgba(235, 0, 139, 0.3);
            border-radius: 50%;
            border-top-color: #eb008b;
            animation: spin 1s ease-in-out infinite;
        }
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        .log-viewer {
            background: #1a1a1a;
            border: 1px solid #444;
            padding: 15px;
            margin: 15px 0;
            border-radius: 4px;
            font-family: 'Courier New', monospace;
            font-size: 12px;
            max-height: 300px;
            overflow-y: auto;
        }
        .instructions {
            background: #1e3a5f;
            border-left: 4px solid #3b82f6;
            padding: 15px 20px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .instructions h3 {
            color: #60a5fa;
            margin-top: 0;
        }
        .instructions ul {
            margin: 10px 0;
            padding-left: 20px;
        }
        .instructions li {
            margin: 5px 0;
        }
        .logout-btn {
            position: absolute;
            top: 20px;
            right: 20px;
            padding: 8px 16px;
            background: #444;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
        }
        .logout-btn:hover {
            background: #555;
        }
    </style>
</head>
<body>
    <a href="?logout=1" class="logout-btn">Logout</a>

    <h1>🧪 Cron Job Tester</h1>

    <div class="warning-box">
        <strong>⚠️ SECURITY WARNING ⚠️</strong><br>
        This is a testing tool. DELETE THIS FILE after testing your cron jobs!
    </div>

    <div class="instructions">
        <h3>📋 How to Use This Tester:</h3>
        <ul>
            <li><strong>Before adding cron jobs:</strong> Click each "Test" button below to verify the scripts work correctly</li>
            <li><strong>After adding cron jobs:</strong> Check the log viewer sections to see if they ran</li>
            <li><strong>Testing payment processing:</strong> The script will only process payments that are due today</li>
            <li><strong>After testing:</strong> DELETE this file for security!</li>
        </ul>
    </div>

    <h2>1. Test Cron Scripts Manually</h2>

    <div class="test-section">
        <h3>Process Payments Script</h3>
        <p>This script processes payment schedules that are due today.</p>
        <button class="test-button" onclick="testCron('process-payments')">
            Test process-payments.php
        </button>
        <div id="output-process-payments" class="output-box" style="display: none;"></div>
    </div>

    <div class="test-section">
        <h3>Send Reminders Script</h3>
        <p>This script sends payment reminders for payments due in 3 days.</p>
        <button class="test-button" onclick="testCron('send-reminders')">
            Test send-reminders.php
        </button>
        <div id="output-send-reminders" class="output-box" style="display: none;"></div>
    </div>

    <div class="test-section">
        <h3>Check Failed Payments Script</h3>
        <p>This script retries failed payments according to the retry schedule.</p>
        <button class="test-button" onclick="testCron('check-failed-payments')">
            Test check-failed-payments.php
        </button>
        <div id="output-check-failed-payments" class="output-box" style="display: none;"></div>
    </div>

    <h2>2. View Log Files</h2>

    <div class="test-section">
        <h3>Payment Processing Logs</h3>
        <button class="test-button" onclick="viewLog('payments')">
            View Payment Logs
        </button>
        <button class="test-button" onclick="viewLog('webhooks')">
            View Webhook Logs
        </button>
        <button class="test-button" onclick="viewLog('errors')">
            View Error Logs
        </button>
        <div id="output-logs" class="log-viewer" style="display: none;"></div>
    </div>

    <h2>3. Check Recent Database Activity</h2>

    <div class="test-section">
        <h3>Recent Payment Schedules</h3>
        <button class="test-button" onclick="checkDatabase('schedules')">
            View Payment Schedules
        </button>
        <div id="output-db-schedules" class="output-box" style="display: none;"></div>
    </div>

    <div class="test-section">
        <h3>Recent Payments</h3>
        <button class="test-button" onclick="checkDatabase('payments')">
            View Recent Payments
        </button>
        <div id="output-db-payments" class="output-box" style="display: none;"></div>
    </div>

    <div class="warning-box" style="margin-top: 40px;">
        <strong>🗑️ DELETE THIS FILE AFTER TESTING</strong><br>
        File: test-cron.php
    </div>

    <script>
        async function testCron(script) {
            const outputDiv = document.getElementById('output-' + script);
            const button = event.target;

            outputDiv.style.display = 'block';
            outputDiv.innerHTML = '<span class="loading"></span> Running ' + script + '.php...';
            button.disabled = true;

            try {
                const response = await fetch('?action=test&script=' + encodeURIComponent(script));
                const data = await response.json();

                if (data.success) {
                    outputDiv.innerHTML = '<span class="success">✓ SUCCESS</span>\n\n' + escapeHtml(data.output);
                } else {
                    outputDiv.innerHTML = '<span class="error">✗ ERROR</span>\n\n' + escapeHtml(data.output);
                }
            } catch (error) {
                outputDiv.innerHTML = '<span class="error">✗ FAILED</span>\n\nError: ' + escapeHtml(error.message);
            } finally {
                button.disabled = false;
            }
        }

        async function viewLog(logType) {
            const outputDiv = document.getElementById('output-logs');

            outputDiv.style.display = 'block';
            outputDiv.innerHTML = '<span class="loading"></span> Loading ' + logType + ' logs...';

            try {
                const response = await fetch('?action=viewlog&type=' + encodeURIComponent(logType));
                const data = await response.json();

                if (data.success) {
                    outputDiv.innerHTML = '<span class="success">✓ Log File: ' + escapeHtml(data.file) + '</span>\n\n' + escapeHtml(data.content);
                } else {
                    outputDiv.innerHTML = '<span class="error">✗ ERROR</span>\n\n' + escapeHtml(data.message);
                }
            } catch (error) {
                outputDiv.innerHTML = '<span class="error">✗ FAILED</span>\n\nError: ' + escapeHtml(error.message);
            }
        }

        async function checkDatabase(table) {
            const outputDiv = document.getElementById('output-db-' + table);

            outputDiv.style.display = 'block';
            outputDiv.innerHTML = '<span class="loading"></span> Querying database...';

            try {
                const response = await fetch('?action=checkdb&table=' + encodeURIComponent(table));
                const data = await response.json();

                if (data.success) {
                    outputDiv.innerHTML = '<span class="success">✓ Database Query Results</span>\n\n' + escapeHtml(data.output);
                } else {
                    outputDiv.innerHTML = '<span class="error">✗ ERROR</span>\n\n' + escapeHtml(data.output);
                }
            } catch (error) {
                outputDiv.innerHTML = '<span class="error">✗ FAILED</span>\n\nError: ' + escapeHtml(error.message);
            }
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    </script>
</body>
</html>

<?php
// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?'));
    exit;
}

// ============================================
// AJAX Handler Functions
// ============================================

function testCronScript($script) {
    $validScripts = ['process-payments', 'send-reminders', 'check-failed-payments'];

    if (!in_array($script, $validScripts)) {
        echo json_encode(['success' => false, 'output' => 'Invalid script name']);
        return;
    }

    $scriptPath = __DIR__ . '/cron/' . $script . '.php';

    if (!file_exists($scriptPath)) {
        echo json_encode(['success' => false, 'output' => "Script not found: $scriptPath"]);
        return;
    }

    // Save current working directory
    $originalDir = getcwd();

    // Change to cron directory so relative paths work
    chdir(__DIR__ . '/cron');

    // Capture output
    ob_start();

    try {
        // Read the script content
        $scriptContent = file_get_contents($scriptPath);

        // Remove shebang line if present
        $scriptContent = preg_replace('/^#!.*\n/', '', $scriptContent);

        // Evaluate the script content (without shebang)
        eval('?>' . $scriptContent);

        $output = ob_get_clean();

        if (empty($output)) {
            $output = "✓ Script executed successfully (no output).\n\n";
            $output .= "This means the script ran without errors, but there may have been:\n";
            $output .= "- No payments due today (for process-payments.php)\n";
            $output .= "- No reminders to send (for send-reminders.php)\n";
            $output .= "- No failed payments to retry (for check-failed-payments.php)\n\n";
            $output .= "Check the log files and database sections below for more details.";
        }

        // Restore working directory
        chdir($originalDir);

        echo json_encode(['success' => true, 'output' => $output]);

    } catch (Exception $e) {
        $output = ob_get_clean();
        $output .= "\n\nEXCEPTION: " . $e->getMessage() . "\n";
        $output .= "Stack trace:\n" . $e->getTraceAsString();

        // Restore working directory
        chdir($originalDir);

        echo json_encode(['success' => false, 'output' => $output]);
    } catch (Error $e) {
        $output = ob_get_clean();
        $output .= "\n\nFATAL ERROR: " . $e->getMessage() . "\n";
        $output .= "File: " . $e->getFile() . " Line: " . $e->getLine() . "\n";
        $output .= "Stack trace:\n" . $e->getTraceAsString();

        // Restore working directory
        chdir($originalDir);

        echo json_encode(['success' => false, 'output' => $output]);
    }
}

function viewLogFile($type) {
    $logFiles = [
        'payments' => __DIR__ . '/logs/payments.log',
        'webhooks' => __DIR__ . '/logs/webhooks.log',
        'errors' => __DIR__ . '/logs/errors.log'
    ];

    if (!isset($logFiles[$type])) {
        echo json_encode(['success' => false, 'message' => 'Invalid log type']);
        return;
    }

    $logFile = $logFiles[$type];

    if (!file_exists($logFile)) {
        echo json_encode([
            'success' => false,
            'message' => "Log file doesn't exist yet: $logFile\n\nThis is normal if the system hasn't logged anything yet."
        ]);
        return;
    }

    // Get last 100 lines
    $lines = file($logFile);
    $recentLines = array_slice($lines, -100);
    $content = implode('', $recentLines);

    if (empty($content)) {
        $content = "(Log file is empty - no activity logged yet)";
    }

    echo json_encode(['success' => true, 'file' => $logFile, 'content' => $content]);
}

function checkDatabaseTable($table) {
    try {
        require_once __DIR__ . '/config/constants.php';
        require_once __DIR__ . '/includes/db.php';

        $db = Database::getInstance();

        if ($table === 'schedules') {
            $schedules = $db->fetchAll(
                "SELECT ps.*, b.booking_reference, b.booker_name
                FROM payment_schedules ps
                JOIN bookings b ON ps.booking_id = b.id
                ORDER BY ps.due_date DESC
                LIMIT 20"
            );

            $output = "RECENT PAYMENT SCHEDULES (Last 20):\n";
            $output .= str_repeat("=", 80) . "\n\n";

            if (empty($schedules)) {
                $output .= "No payment schedules found.\n";
                $output .= "This means no bookings have been made with installment payment plans yet.";
            } else {
                foreach ($schedules as $s) {
                    $output .= "Booking: {$s['booking_reference']} ({$s['booker_name']})\n";
                    $output .= "  Due Date: {$s['due_date']}\n";
                    $output .= "  Amount: £" . number_format($s['amount'], 2) . "\n";
                    $output .= "  Status: {$s['status']}\n";
                    $output .= "  Payment Number: {$s['payment_number']}\n";
                    $output .= "  Retry Count: {$s['retry_count']}\n";
                    $output .= "\n";
                }
            }

            echo json_encode(['success' => true, 'output' => $output]);

        } elseif ($table === 'payments') {
            $payments = $db->fetchAll(
                "SELECT p.*, b.booking_reference, b.booker_name
                FROM payments p
                JOIN bookings b ON p.booking_id = b.id
                ORDER BY p.payment_date DESC
                LIMIT 20"
            );

            $output = "RECENT PAYMENTS (Last 20):\n";
            $output .= str_repeat("=", 80) . "\n\n";

            if (empty($payments)) {
                $output .= "No payments found.\n";
                $output .= "This means no payments have been recorded yet.";
            } else {
                foreach ($payments as $p) {
                    $output .= "Booking: {$p['booking_reference']} ({$p['booker_name']})\n";
                    $output .= "  Date: {$p['payment_date']}\n";
                    $output .= "  Amount: £" . number_format($p['amount'], 2) . "\n";
                    $output .= "  Method: {$p['payment_method']}\n";
                    $output .= "  Status: {$p['status']}\n";
                    $output .= "  Type: {$p['payment_type']}\n";
                    if (!empty($p['stripe_payment_intent_id'])) {
                        $output .= "  Stripe Intent: {$p['stripe_payment_intent_id']}\n";
                    }
                    $output .= "\n";
                }
            }

            echo json_encode(['success' => true, 'output' => $output]);

        } else {
            echo json_encode(['success' => false, 'output' => 'Invalid table']);
        }

    } catch (Exception $e) {
        echo json_encode(['success' => false, 'output' => 'Database error: ' . $e->getMessage()]);
    }
}
?>
