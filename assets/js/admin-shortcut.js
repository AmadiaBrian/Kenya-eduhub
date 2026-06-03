/**
 * Admin Shortcut JavaScript
 * Provides keyboard shortcuts and admin functionality
 */

// Admin keyboard shortcuts
document.addEventListener('keydown', function(e) {
    // Ctrl+Alt+A for admin panel
    if (e.ctrlKey && e.altKey && e.key === 'A') {
        e.preventDefault();
        window.location.href = './admin/login.php';
    }
});
