<?php
/**
 * Test Script - PHP Path Detection for Cron Jobs
 * Access via: https://echo.alivechur.ch/test-cron-path.php
 *
 * DELETE THIS FILE AFTER GETTING THE INFORMATION
 */

// Prevent unauthorized access in production
$allowedIPs = ['127.0.0.1', '::1']; // Add your IP if needed
// Comment out the IP check if you need to access from anywhere:
// if (!in_array($_SERVER['REMOTE_ADDR'], $allowedIPs)) {
//     die('Access denied');
// }

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cron Path Test</title>
    <style>
        body {
            font-family: 'Courier New', monospace;
            background: #1a1a1a;
            color: #f5f5f5;
            padding: 40px;
            max-width: 900px;
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
        .info-block {
            background: #2a2a2a;
            border-left: 4px solid #eb008b;
            padding: 15px 20px;
            margin: 15px 0;
            border-radius: 4px;
        }
        .command {
            background: #1a1a1a;
            border: 1px solid #444;
            padding: 10px 15px;
            margin: 10px 0;
            border-radius: 4px;
            font-family: 'Courier New', monospace;
            font-size: 14px;
            overflow-x: auto;
        }
        .success {
            color: #4ade80;
        }
        .warning {
            color: #fbbf24;
        }
        .label {
            color: #9ca3af;
            font-weight: bold;
            display: inline-block;
            min-width: 200px;
        }
        .delete-warning {
            background: #7f1d1d;
            border: 2px solid #dc2626;
            padding: 20px;
            margin: 30px 0;
            border-radius: 8px;
            text-align: center;
        }
        .delete-warning strong {
            color: #fca5a5;
            font-size: 18px;
        }
    </style>
</head>
<body>
    <h1>🔧 Cron Job Configuration Helper</h1>

    <div class="delete-warning">
        <strong>⚠️ SECURITY WARNING ⚠️</strong><br>
        Delete this file immediately after getting the information you need!
    </div>

    <h2>1. PHP Binary Paths</h2>

    <div class="info-block">
        <p><span class="label">PHP_BINDIR Constant:</span> <strong class="success"><?php echo PHP_BINDIR; ?>/php</strong></p>
    </div>

    <?php
    // Method 1: whereis php
    $whereis = [];
    exec('whereis php 2>&1', $whereis);
    ?>
    <div class="info-block">
        <p><span class="label">whereis php:</span></p>
        <div class="command"><?php echo htmlspecialchars(implode("\n", $whereis)); ?></div>
    </div>

    <?php
    // Method 2: which php
    $which = [];
    exec('which php 2>&1', $which);
    ?>
    <div class="info-block">
        <p><span class="label">which php:</span></p>
        <div class="command"><?php echo htmlspecialchars(implode("\n", $which)); ?></div>
    </div>

    <?php
    // Method 3: php -v
    $version = [];
    exec('php -v 2>&1', $version);
    ?>
    <div class="info-block">
        <p><span class="label">PHP Version (php -v):</span></p>
        <div class="command"><?php echo htmlspecialchars(implode("\n", $version)); ?></div>
    </div>

    <?php
    // Method 4: Find all PHP binaries
    $findPhp = [];
    exec('find /usr -name php 2>/dev/null | head -20', $findPhp);
    ?>
    <div class="info-block">
        <p><span class="label">All PHP binaries found:</span></p>
        <div class="command"><?php
            echo !empty($findPhp) ? htmlspecialchars(implode("\n", $findPhp)) : 'No results (may require more permissions)';
        ?></div>
    </div>

    <h2>2. Your Server Paths</h2>

    <div class="info-block">
        <p><span class="label">Document Root:</span> <strong><?php echo $_SERVER['DOCUMENT_ROOT'] ?? 'Not set'; ?></strong></p>
        <p><span class="label">Script Filename:</span> <strong><?php echo __FILE__; ?></strong></p>
        <p><span class="label">Campbooking Root:</span> <strong><?php echo dirname(__FILE__); ?></strong></p>
    </div>

    <h2>3. Recommended Cron Job Commands</h2>

    <?php
    $phpPath = !empty($which[0]) ? trim($which[0]) : '/usr/bin/php';
    $rootPath = dirname(__FILE__);
    ?>

    <div class="info-block">
        <p><strong class="success">Use these commands in your cPanel Cron Jobs:</strong></p>

        <p style="margin-top: 20px;"><span class="label">Process Payments (9am):</span></p>
        <div class="command">0 9 * * * <?php echo $phpPath; ?> <?php echo $rootPath; ?>/cron/process-payments.php</div>

        <p style="margin-top: 20px;"><span class="label">Send Reminders (10am):</span></p>
        <div class="command">0 10 * * * <?php echo $phpPath; ?> <?php echo $rootPath; ?>/cron/send-reminders.php</div>

        <p style="margin-top: 20px;"><span class="label">Check Failed Payments (11am):</span></p>
        <div class="command">0 11 * * * <?php echo $phpPath; ?> <?php echo $rootPath; ?>/cron/check-failed-payments.php</div>
    </div>

    <h2>4. Test Your Cron Scripts Manually</h2>

    <div class="info-block">
        <p><span class="label">Via SSH:</span></p>
        <div class="command">cd <?php echo $rootPath; ?>/cron<br><?php echo $phpPath; ?> process-payments.php</div>

        <p style="margin-top: 20px;"><span class="label">Or click to test:</span></p>
        <p>
            <a href="<?php echo dirname($_SERVER['PHP_SELF']); ?>/cron/process-payments.php" target="_blank" style="color: #eb008b;">Test process-payments.php</a><br>
            <a href="<?php echo dirname($_SERVER['PHP_SELF']); ?>/cron/send-reminders.php" target="_blank" style="color: #eb008b;">Test send-reminders.php</a><br>
            <a href="<?php echo dirname($_SERVER['PHP_SELF']); ?>/cron/check-failed-payments.php" target="_blank" style="color: #eb008b;">Test check-failed-payments.php</a>
        </p>
        <p class="warning">⚠️ Note: These should be protected from web access in production. Test via SSH instead.</p>
    </div>

    <h2>5. Server Information</h2>

    <div class="info-block">
        <p><span class="label">PHP Version:</span> <?php echo phpversion(); ?></p>
        <p><span class="label">Server Software:</span> <?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'; ?></p>
        <p><span class="label">Operating System:</span> <?php echo PHP_OS; ?></p>
        <p><span class="label">User:</span> <?php echo get_current_user(); ?></p>
    </div>

    <div class="delete-warning">
        <strong>🗑️ DELETE THIS FILE NOW</strong><br>
        After copying the cron commands above, delete test-cron-path.php for security.
    </div>

</body>
</html>
