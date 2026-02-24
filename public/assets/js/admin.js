/**
 * Admin Panel JavaScript
 * Handles admin interface interactions
 */

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
