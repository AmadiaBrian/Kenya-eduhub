<?php
// Get current page for active state
$current_page = basename($_SERVER['PHP_SELF']);
?>

<!-- Mobile Toggle Button -->
<button class="btn btn-primary d-md-none position-fixed top-1 start-1 z-index-1020" 
        id="sidebarToggle" 
        style="width: 40px; height: 40px; border-radius: 50%;">
    <i class="fas fa-bars"></i>
</button>

<!-- Sidebar Overlay -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<!-- Sidebar -->
<aside class="col-md-3 col-lg-2 d-md-block bg-white sidebar" id="sidebarMenu">
    <div class="position-sticky pt-3">
        <div class="d-flex justify-content-between align-items-center mb-4 px-3 d-md-none">
            <h5 class="mb-0">Menu</h5>
            <button type="button" class="btn-close" id="closeSidebar"></button>
        </div>
        
        <!-- User Profile -->
        <div class="text-center mb-4">
            <img src="<?php echo htmlspecialchars($_SESSION['user_avatar'] ?? 'assets/images/default-avatar.png'); ?>" 
                 class="rounded-circle mb-2" 
                 alt="Profile" 
                 width="80" 
                 height="80"
                 style="object-fit: cover; border: 3px solid #f8f9fa; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
            <h6 class="mb-1"><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'User'); ?></h6>
            <p class="text-muted small mb-0">
                <?php echo htmlspecialchars($_SESSION['user_role'] ?? 'User'); ?>
            </p>
            <span class="badge bg-primary mt-2">
                <?php echo htmlspecialchars($_SESSION['user_department'] ?? 'Department'); ?>
            </span>
        </div>

        <!-- Navigation -->
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link <?php echo $current_page === 'dashboard.php' ? 'active' : ''; ?>" 
                   href="dashboard.php">
                    <i class="fas fa-tachometer-alt me-2"></i>
                    Dashboard
                </a>
            </li>
            
            <li class="nav-item">
                <a class="nav-link <?php echo $current_page === 'apply.php' ? 'active' : ''; ?>" 
                   href="apply.php">
                    <i class="fas fa-edit me-2"></i>
                    New Application
                </a>
            </li>
            
            <li class="nav-item">
                <a class="nav-link <?php echo $current_page === 'my_applications.php' ? 'active' : ''; ?>" 
                   href="my_applications.php">
                    <i class="fas fa-file-alt me-2"></i>
                    My Applications
                    <span class="badge bg-primary rounded-pill float-end">
                        <?php echo isset($_SESSION['application_count']) ? $_SESSION['application_count'] : '0'; ?>
                    </span>
                </a>
            </li>
            
            <li class="nav-item">
                <a class="nav-link <?php echo $current_page === 'documents.php' ? 'active' : ''; ?>" 
                   href="documents.php">
                    <i class="fas fa-file-pdf me-2"></i>
                    Documents
                </a>
            </li>
            
            <li class="nav-item">
                <a class="nav-link <?php echo $current_page === 'messages.php' ? 'active' : ''; ?>" 
                   href="messages.php">
                    <i class="fas fa-envelope me-2"></i>
                    Messages
                    <?php if (isset($_SESSION['unread_messages']) && $_SESSION['unread_messages'] > 0): ?>
                        <span class="badge bg-danger rounded-pill float-end">
                            <?php echo $_SESSION['unread_messages']; ?>
                        </span>
                    <?php endif; ?>
                </a>
            </li>
            
            <li class="nav-item">
                <a class="nav-link <?php echo $current_page === 'calendar.php' ? 'active' : ''; ?>" 
                   href="calendar.php">
                    <i class="far fa-calendar-alt me-2"></i>
                    Calendar
                </a>
            </li>
            
            <li class="nav-item mt-3">
                <h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-muted">
                    <span>Account</span>
                </h6>
            </li>
            
            <li class="nav-item">
                <a class="nav-link <?php echo $current_page === 'profile.php' ? 'active' : ''; ?>" 
                   href="profile.php">
                    <i class="fas fa-user me-2"></i>
                    Profile
                </a>
            </li>
            
            <li class="nav-item">
                <a class="nav-link <?php echo $current_page === 'settings.php' ? 'active' : ''; ?>" 
                   href="settings.php">
                    <i class="fas fa-cog me-2"></i>
                    Settings
                </a>
            </li>
            
            <li class="nav-item">
                <a class="nav-link text-danger" href="logout.php">
                    <i class="fas fa-sign-out-alt me-2"></i>
                    Logout
                </a>
            </li>
        </ul>
    </div>
</aside>

<style>
/* Sidebar Styles */
.sidebar {
    position: fixed;
    top: 0;
    left: 0;
    bottom: 0;
    z-index: 1000;
    width: 280px;
    padding: 20px 0;
    box-shadow: 2px 0 5px rgba(0,0,0,0.1);
    transition: transform 0.3s ease-in-out;
    overflow-y: auto;
}

/* Mobile styles */
@media (max-width: 767.98px) {
    .sidebar {
        transform: translateX(-100%);
    }
    
    .sidebar.show {
        transform: translateX(0);
    }
    
    .sidebar-overlay {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0,0,0,0.5);
        z-index: 999;
    }
    
    .sidebar-overlay.show {
        display: block;
    }
    
    #sidebarToggle {
        display: block !important;
        position: fixed;
        top: 15px;
        left: 15px;
        z-index: 1001;
    }
}

/* Desktop styles */
@media (min-width: 768px) {
    .sidebar {
        transform: translateX(0) !important;
    }
    
    #sidebarToggle, 
    .sidebar-overlay {
        display: none !important;
    }
}

/* Active state for nav items */
.nav-link {
    display: flex;
    align-items: center;
    padding: 0.75rem 1.5rem;
    margin: 0.25rem 1rem;
    border-radius: 0.375rem;
    transition: all 0.2s ease;
    color: #333;
    text-decoration: none;
}

.nav-link:hover {
    background-color: rgba(0, 87, 164, 0.1);
    color: var(--primary-color);
}

.nav-link.active {
    background-color: var(--primary-color);
    color: white;
}

.nav-link i {
    width: 20px;
    text-align: center;
    margin-right: 10px;
}

/* Badge styles */
.badge {
    font-weight: 500;
    padding: 0.35em 0.65em;
    font-size: 0.75em;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.getElementById('sidebarMenu');
    const sidebarToggle = document.getElementById('sidebarToggle');
    const closeSidebar = document.getElementById('closeSidebar');
    const sidebarOverlay = document.getElementById('sidebarOverlay');
    const navLinks = document.querySelectorAll('.nav-link');

    // Toggle sidebar
    function toggleSidebar() {
        sidebar.classList.toggle('show');
        sidebarOverlay.classList.toggle('show');
        document.body.style.overflow = sidebar.classList.contains('show') ? 'hidden' : '';
    }

    // Toggle on button click
    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', function(e) {
            e.stopPropagation();
            toggleSidebar();
        });
    }

    // Close sidebar when clicking close button
    if (closeSidebar) {
        closeSidebar.addEventListener('click', toggleSidebar);
    }

    // Close sidebar when clicking overlay
    if (sidebarOverlay) {
        sidebarOverlay.addEventListener('click', toggleSidebar);
    }

    // Close sidebar when clicking on a nav link (on mobile)
    navLinks.forEach(link => {
        link.addEventListener('click', function() {
            if (window.innerWidth < 768) {
                toggleSidebar();
            }
        });
    });

    // Close sidebar when pressing Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && sidebar.classList.contains('show')) {
            toggleSidebar();
        }
    });

    // Close sidebar when window is resized to desktop view
    window.addEventListener('resize', function() {
        if (window.innerWidth >= 768) {
            sidebar.classList.remove('show');
            sidebarOverlay.classList.remove('show');
            document.body.style.overflow = '';
        }
    });
});
</script>