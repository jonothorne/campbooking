<?php
/**
 * Admin Sidebar Template
 * Navigation menu for admin panel
 */

// Get current page for active state
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<aside class="admin-sidebar">
    <div class="sidebar-header">
        <img src="<?php echo url('public/assets/images/logo.png'); ?>" alt="Alive Church" class="sidebar-logo">
    </div>
    <nav class="sidebar-nav">
        <a href="<?php echo url('admin/dashboard.php'); ?>"
           class="sidebar-link <?php echo $currentPage === 'dashboard.php' ? 'active' : ''; ?>">
            <span class="sidebar-icon">📊</span>
            <span class="sidebar-text">Dashboard</span>
        </a>

        <a href="<?php echo url('admin/bookings.php'); ?>"
           class="sidebar-link <?php echo $currentPage === 'bookings.php' || $currentPage === 'booking-detail.php' ? 'active' : ''; ?>">
            <span class="sidebar-icon">📋</span>
            <span class="sidebar-text">Bookings</span>
        </a>

        <a href="<?php echo url('admin/users.php'); ?>"
           class="sidebar-link <?php echo in_array($currentPage, ['users.php', 'user-add.php', 'user-edit.php', 'change-password.php']) ? 'active' : ''; ?>">
            <span class="sidebar-icon">👥</span>
            <span class="sidebar-text">Users</span>
        </a>

        <a href="<?php echo url('index.php'); ?>"
           class="sidebar-link"
           target="_blank">
            <span class="sidebar-icon">🎫</span>
            <span class="sidebar-text">View Booking Form</span>
        </a>

        <div class="sidebar-divider"></div>

        <a href="<?php echo url('admin/logout.php'); ?>" class="sidebar-link sidebar-link-danger">
            <span class="sidebar-icon">🚪</span>
            <span class="sidebar-text">Logout</span>
        </a>
    </nav>
</aside>
