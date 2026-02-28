<?php
/**
 * Email Testing Tool
 * Test email sending and view email templates
 *
 * DELETE THIS FILE AFTER TESTING
 */

// Handle AJAX requests FIRST (before any output)
if (isset($_GET['action'])) {
    session_start();

    // Check authentication for AJAX requests
    if (!isset($_SESSION['email_test_auth'])) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'output' => 'Not authenticated']);
        exit;
    }

    header('Content-Type: application/json');

    switch ($_GET['action']) {
        case 'send':
            sendTestEmail($_GET['type'] ?? '', $_GET['email'] ?? '');
            break;
        case 'preview':
            previewEmail($_GET['type'] ?? '');
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
        $_SESSION['email_test_auth'] = true;
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    } else {
        $loginError = 'Incorrect password';
    }
}

// Show login page if not authenticated
if (!isset($_SESSION['email_test_auth'])) {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Email Test - Authentication</title>
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
            <h2>🔒 Email Test Authentication</h2>
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
?>
