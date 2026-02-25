/**
 * Admin Panel JavaScript
 * Handles admin interface interactions
 */

// Mobile Menu Toggle
document.addEventListener('DOMContentLoaded', function() {
    console.log('Admin.js loaded');

    const menuToggle = document.getElementById('mobile-menu-toggle');
    const sidebar = document.querySelector('.admin-sidebar');
    const overlay = document.getElementById('mobile-overlay');

    console.log('Menu toggle:', menuToggle);
    console.log('Sidebar:', sidebar);
    console.log('Overlay:', overlay);

    if (menuToggle && sidebar && overlay) {
        console.log('All elements found, attaching event listeners');

        // Toggle menu
        menuToggle.addEventListener('click', function(e) {
            console.log('Menu toggle clicked');
            e.preventDefault();
            menuToggle.classList.toggle('active');
            sidebar.classList.toggle('open');
            overlay.classList.toggle('active');
            console.log('Sidebar classes:', sidebar.className);
        });

        // Close menu when clicking overlay
        overlay.addEventListener('click', function() {
            console.log('Overlay clicked');
            menuToggle.classList.remove('active');
            sidebar.classList.remove('open');
            overlay.classList.remove('active');
        });

        // Close menu when clicking a link
        sidebar.querySelectorAll('.sidebar-link').forEach(link => {
            link.addEventListener('click', function() {
                if (window.innerWidth <= 768) {
                    menuToggle.classList.remove('active');
                    sidebar.classList.remove('open');
                    overlay.classList.remove('active');
                }
            });
        });
    } else {
        console.error('Missing elements:', {
            menuToggle: !!menuToggle,
            sidebar: !!sidebar,
            overlay: !!overlay
        });
    }
});

// Confirm before deleting
document.querySelectorAll('.confirm-delete').forEach(btn => {
    btn.addEventListener('click', function(e) {
        if (!confirm('Are you sure you want to delete this booking? This action cannot be undone.')) {
            e.preventDefault();
        }
    });
});

// Confirm before marking as paid
document.querySelectorAll('.confirm-paid').forEach(btn => {
    btn.addEventListener('click', function(e) {
        if (!confirm('Are you sure you want to mark this booking as paid?')) {
            e.preventDefault();
        }
    });
});

// Auto-hide success messages after 5 seconds
setTimeout(() => {
    document.querySelectorAll('.alert-success').forEach(alert => {
        alert.style.transition = 'opacity 0.5s';
        alert.style.opacity = '0';
        setTimeout(() => alert.remove(), 500);
    });
}, 5000);
