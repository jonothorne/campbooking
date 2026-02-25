<?php
/**
 * Admin Header Template
 * Included on all admin pages
 */

// Ensure user is authenticated
if (!function_exists('requireAuth')) {
    require_once __DIR__ . '/../../includes/auth.php';
}
requireAuth();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? e($pageTitle) . ' - ' : ''; ?>Admin - <?php echo e(EVENT_NAME); ?></title>
    <link rel="stylesheet" href="/book/public/assets/css/admin.css">
</head>
<body class="admin-body">
    <div class="admin-container">
        <!-- Top Navigation -->
        <nav class="top-nav">
            <div class="top-nav-content">
                <div class="top-nav-left">
                    <img src="/book/public/assets/images/logo.png" alt="Alive Church" class="admin-logo">
                    <div class="top-nav-text">
                        <h1 class="site-title"><?php echo e(EVENT_NAME); ?></h1>
                        <span class="admin-badge">Admin Panel</span>
                    </div>
                </div>
                <div class="top-nav-right">
                    <span class="admin-user">
                        👤 <?php echo e(currentAdminUsername()); ?>
                    </span>
                    <a href="<?php echo url('admin/logout.php'); ?>" class="btn-logout">Logout</a>
                </div>
            </div>
        </nav>

        <div class="admin-layout">
            <!-- Sidebar Navigation -->
            <?php include __DIR__ . '/sidebar.php'; ?>

            <!-- Main Content -->
            <main class="admin-main">
