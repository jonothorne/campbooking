<?php
/**
 * Add User Page
 * Create new admin user
 */

// Initialize
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/sanitize.php';

// Check authentication
requireAuth();

// Set page title
$pageTitle = 'Add User';

$error = null;
$formData = [];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    requireCsrfToken();

    $username = sanitizeString($_POST['username'] ?? '');
    $email = sanitizeEmail($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $isActive = isset($_POST['is_active']) ? 1 : 0;

    // Store form data for repopulation
    $formData = [
        'username' => $username,
        'email' => $email,
        'is_active' => $isActive
    ];

    // Validation
    if (empty($username) || empty($email) || empty($password)) {
        $error = 'Username, email, and password are required';
    } elseif (!isValidEmail($email)) {
        $error = 'Invalid email address';
    } elseif (strlen($username) < 3) {
        $error = 'Username must be at least 3 characters long';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters long';
    } elseif ($password !== $confirmPassword) {
        $error = 'Passwords do not match';
    } else {
        // Check if username already exists
        $db = Database::getInstance();
        $existing = $db->fetchOne(
            "SELECT id FROM users WHERE username = ?",
            [$username]
        );

        if ($existing) {
            $error = 'Username already exists';
        } else {
            // Check if email already exists
            $existingEmail = $db->fetchOne(
                "SELECT id FROM users WHERE email = ?",
                [$email]
            );

            if ($existingEmail) {
                $error = 'Email already exists';
            } else {
                // Create user
                $passwordHash = password_hash($password, PASSWORD_BCRYPT);

                $db->insert(
                    "INSERT INTO users (username, email, password_hash, is_active, created_at)
                    VALUES (?, ?, ?, ?, NOW())",
                    [$username, $email, $passwordHash, $isActive]
                );

                $_SESSION['success'] = "User '{$username}' created successfully!";
                redirect(url('admin/users.php'));
            }
        }
    }
}

// Include header
include __DIR__ . '/../templates/admin/header.php';
?>

<div class="page-header">
    <div>
        <h1 class="page-title">Add User</h1>
        <p class="page-subtitle">Create new admin user</p>
    </div>
    <a href="<?php echo url('admin/users.php'); ?>" class="btn btn-secondary">
        ← Back to Users
    </a>
</div>

<!-- Error Messages -->
<?php if ($error): ?>
    <div class="alert alert-danger">
        <?php echo e($error); ?>
    </div>
<?php endif; ?>

<!-- Add User Form -->
<div class="content-card">
    <form method="POST" action="">
        <input type="hidden" name="csrf_token" value="<?php echo csrfToken(); ?>">

        <div class="form-row">
            <div class="form-group">
                <label for="username">Username *</label>
                <input
                    type="text"
                    id="username"
                    name="username"
                    class="form-control"
                    value="<?php echo e($formData['username'] ?? ''); ?>"
                    required
                    minlength="3"
                    autocomplete="username"
                >
                <small style="color: var(--text-medium); display: block; margin-top: 5px;">
                    Minimum 3 characters, letters and numbers only
                </small>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="email">Email *</label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    class="form-control"
                    value="<?php echo e($formData['email'] ?? ''); ?>"
                    required
                    autocomplete="email"
                >
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="password">Password *</label>
                <input
                    type="password"
                    id="password"
                    name="password"
                    class="form-control"
                    required
                    minlength="8"
                    autocomplete="new-password"
                >
                <small style="color: var(--text-medium); display: block; margin-top: 5px;">
                    Minimum 8 characters
                </small>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="confirm_password">Confirm Password *</label>
                <input
                    type="password"
                    id="confirm_password"
                    name="confirm_password"
                    class="form-control"
                    required
                    minlength="8"
                    autocomplete="new-password"
                >
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label class="checkbox-label">
                    <input
                        type="checkbox"
                        name="is_active"
                        value="1"
                        <?php echo ($formData['is_active'] ?? 1) ? 'checked' : ''; ?>
                    >
                    <span>Active (user can log in)</span>
                </label>
            </div>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">
                Create User
            </button>
            <a href="<?php echo url('admin/users.php'); ?>" class="btn btn-secondary">
                Cancel
            </a>
        </div>
    </form>
</div>

<?php include __DIR__ . '/../templates/admin/footer.php'; ?>
