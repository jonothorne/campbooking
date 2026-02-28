<?php
/**
 * Cron Job Tester - Web Interface
 * Access via: https://echo.alivechur.ch/test-cron.php
 *
 * DELETE THIS FILE AFTER TESTING
 */

// Security: Basic password protection
session_start();
$password = 'test123'; // Change this to something secure

if (!isset($_SESSION['cron_test_auth'])) {
    if (isset($_POST['password']) && $_POST['password'] === $password) {
        $_SESSION['cron_test_auth'] = true;
    } else {
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
                }
                h2 { color: #eb008b; }
                input {
                    padding: 10px;
                    width: 100%;
                    margin: 10px 0;
                    border: 1px solid #444;
                    background: #1a1a1a;
                    color: #f5f5f5;
                    border-radius: 4px;
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
            </style>
        </head>
        <body>
            <div class="auth-box">
                <h2>🔒 Cron Test Authentication</h2>
                <form method="POST">
                    <input type="password" name="password" placeholder="Enter password" required autofocus>
                    <button type="submit">Login</button>
                </form>
                <p style="font-size: 12px; color: #888; margin-top: 20px;">Password: test123</p>
            </div>
        </body>
        </html>
        <?php
        exit;
    }
}

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
    </style>
</head>
<body>
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
            <li><strong>Testing payment processing:</strong> Make sure you have at least one booking with a payment due today</li>
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
        File: /home/xvn00ltbgeh7/public_html/repositories/campbooking/test-cron.php
    </div>

    <script>
        async function testCron(script) {
            const outputDiv = document.getElementById('output-' + script);
            const button = event.target;

            outputDiv.style.display = 'block';
            outputDiv.innerHTML = '<span class="loading"></span> Running ' + script + '.php...';
            button.disabled = true;

            try {
                const response = await fetch('?action=test&script=' + script);
                const data = await response.json();

                if (data.success) {
                    outputDiv.innerHTML = '<span class="success">✓ SUCCESS</span>\n\n' + data.output;
                } else {
                    outputDiv.innerHTML = '<span class="error">✗ ERROR</span>\n\n' + data.output;
                }
            } catch (error) {
                outputDiv.innerHTML = '<span class="error">✗ FAILED</span>\n\nError: ' + error.message;
            } finally {
                button.disabled = false;
            }
        }

        async function viewLog(logType) {
            const outputDiv = document.getElementById('output-logs');

            outputDiv.style.display = 'block';
            outputDiv.innerHTML = '<span class="loading"></span> Loading ' + logType + ' logs...';

            try {
                const response = await fetch('?action=viewlog&type=' + logType);
                const data = await response.json();

                if (data.success) {
                    outputDiv.innerHTML = '<span class="success">✓ Log File: ' + data.file + '</span>\n\n' + data.content;
                } else {
                    outputDiv.innerHTML = '<span class="error">✗ ERROR</span>\n\n' + data.message;
                }
            } catch (error) {
                outputDiv.innerHTML = '<span class="error">✗ FAILED</span>\n\nError: ' + error.message;
            }
        }

        async function checkDatabase(table) {
            const outputDiv = document.getElementById('output-db-' + table);

            outputDiv.style.display = 'block';
            outputDiv.innerHTML = '<span class="loading"></span> Querying database...';

            try {
                const response = await fetch('?action=checkdb&table=' + table);
                const data = await response.json();

                if (data.success) {
                    outputDiv.innerHTML = '<span class="success">✓ Database Query Results</span>\n\n' + data.output;
                } else {
                    outputDiv.innerHTML = '<span class="error">✗ ERROR</span>\n\n' + data.output;
                }
            } catch (error) {
                outputDiv.innerHTML = '<span class="error">✗ FAILED</span>\n\nError: ' + error.message;
            }
        }
    </script>
</body>
</html>

<?php
// Handle AJAX requests
if (isset($_GET['action'])) {
    header('Content-Type: application/json');

    switch ($_GET['action']) {
        case 'test':
            testCronScript($_GET['script']);
            break;
        case 'viewlog':
            viewLogFile($_GET['type']);
            break;
        case 'checkdb':
            checkDatabaseTable($_GET['table']);
            break;
    }
    exit;
}

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

    // Capture output
    ob_start();

    try {
        // Include and run the script
        include $scriptPath;

        $output = ob_get_clean();

        if (empty($output)) {
            $output = "Script executed successfully (no output).\n\nThis means the script ran without errors, but there may have been no work to do (e.g., no payments due today).";
        }

        echo json_encode(['success' => true, 'output' => $output]);

    } catch (Exception $e) {
        $output = ob_get_clean();
        $output .= "\n\nEXCEPTION: " . $e->getMessage() . "\n" . $e->getTraceAsString();

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
        echo json_encode(['success' => false, 'message' => "Log file doesn't exist yet: $logFile"]);
        return;
    }

    // Get last 100 lines
    $lines = file($logFile);
    $recentLines = array_slice($lines, -100);
    $content = implode('', $recentLines);

    if (empty($content)) {
        $content = "(Log file is empty)";
    }

    echo json_encode(['success' => true, 'file' => $logFile, 'content' => $content]);
}

function checkDatabaseTable($table) {
    require_once __DIR__ . '/config/constants.php';
    require_once __DIR__ . '/includes/db.php';

    try {
        $db = Database::getInstance();

        if ($table === 'schedules') {
            $schedules = $db->fetchAll(
                "SELECT ps.*, b.booking_reference, b.booker_name
                FROM payment_schedules ps
                JOIN bookings b ON ps.booking_id = b.id
                ORDER BY ps.due_date DESC
                LIMIT 20"
            );

            $output = "RECENT PAYMENT SCHEDULES:\n";
            $output .= str_repeat("=", 80) . "\n\n";

            if (empty($schedules)) {
                $output .= "No payment schedules found.\n";
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

            $output = "RECENT PAYMENTS:\n";
            $output .= str_repeat("=", 80) . "\n\n";

            if (empty($payments)) {
                $output .= "No payments found.\n";
            } else {
                foreach ($payments as $p) {
                    $output .= "Booking: {$p['booking_reference']} ({$p['booker_name']})\n";
                    $output .= "  Date: {$p['payment_date']}\n";
                    $output .= "  Amount: £" . number_format($p['amount'], 2) . "\n";
                    $output .= "  Method: {$p['payment_method']}\n";
                    $output .= "  Status: {$p['status']}\n";
                    $output .= "  Type: {$p['payment_type']}\n";
                    if ($p['stripe_payment_intent_id']) {
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
