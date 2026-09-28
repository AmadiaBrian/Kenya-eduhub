<?php
// Admin Sidebar Include
// Set $active_page variable before including to highlight the active link
// Set $in_subdirectory to true if called from schools subdirectory
?>

<!-- Sidebar -->
<aside class="sidebar" id="sidebar">
    <a class="nav-link <?php echo ($active_page ?? '') === 'dashboard' ? 'active' : ''; ?>" href="<?php echo ($in_subdirectory ?? false) ? '../dashboard' : 'dashboard'; ?>">
        <i class="fas fa-tachometer-alt"></i> Dashboard
    </a>
    <a class="nav-link <?php echo ($active_page ?? '') === 'my-resources' ? 'active' : ''; ?>" href="<?php echo ($in_subdirectory ?? false) ? '../dashboard?section=resources' : 'dashboard?section=resources'; ?>">
        <i class="fas fa-book"></i> My Resources
    </a>
    <a class="nav-link <?php echo ($active_page ?? '') === 'upload' ? 'active' : ''; ?>" href="<?php echo ($in_subdirectory ?? false) ? '../dashboard?section=upload' : 'dashboard?section=upload'; ?>">
        <i class="fas fa-upload"></i> Upload Resource
    </a>
    <a class="nav-link <?php echo ($active_page ?? '') === 'schools' ? 'active' : ''; ?>" href="<?php echo ($in_subdirectory ?? false) ? '../schools' : 'schools'; ?>">
        <i class="fas fa-school"></i> Schools
    </a>
    <a class="nav-link <?php echo ($active_page ?? '') === 'school-accounts' ? 'active' : ''; ?>" href="<?php echo ($in_subdirectory ?? false) ? '../school-accounts' : 'school-accounts'; ?>">
        <i class="fas fa-wallet"></i> School Accounts
    </a>
    <a class="nav-link <?php echo ($active_page ?? '') === 'users' ? 'active' : ''; ?>" href="<?php echo ($in_subdirectory ?? false) ? '../users' : 'users'; ?>">
        <i class="fas fa-users"></i> Users
    </a>
    <a class="nav-link <?php echo ($active_page ?? '') === 'resources' ? 'active' : ''; ?>" href="<?php echo ($in_subdirectory ?? false) ? '../resources' : 'resources'; ?>">
        <i class="fas fa-book"></i> Resources
    </a>
    <a class="nav-link <?php echo ($active_page ?? '') === 'transaction-rates' ? 'active' : ''; ?>" href="<?php echo ($in_subdirectory ?? false) ? '../transaction-rates' : 'transaction-rates'; ?>">
        <i class="fas fa-percentage"></i> Transaction Rates
    </a>
    <a class="nav-link <?php echo ($active_page ?? '') === 'reports' ? 'active' : ''; ?>" href="<?php echo ($in_subdirectory ?? false) ? '../reports' : 'reports'; ?>">
        <i class="fas fa-chart-bar"></i> Reports
    </a>
    <a class="nav-link <?php echo ($active_page ?? '') === 'logs' ? 'active' : ''; ?>" href="<?php echo ($in_subdirectory ?? false) ? '../logs' : 'logs'; ?>">
        <i class="fas fa-file-alt"></i> Logs
    </a>
    <a class="nav-link <?php echo ($active_page ?? '') === 'settings' ? 'active' : ''; ?>" href="<?php echo ($in_subdirectory ?? false) ? '../settings' : 'settings'; ?>">
        <i class="fas fa-cog"></i> Settings
    </a>
    <a class="nav-link" href="<?php echo ($in_subdirectory ?? false) ? '../logout' : 'logout'; ?>">
        <i class="fas fa-sign-out-alt"></i> Logout
    </a>
</aside>

<style>
    :root {
        --sidebar-width: 256px;
        --header-height: 64px;
    }

    .sidebar {
        position: fixed;
        top: var(--header-height);
        left: 0;
        width: var(--sidebar-width);
        height: calc(100vh - var(--header-height));
        background: var(--bg-color, #f8f9fa);
        border-right: 1px solid var(--border-color, #e8eaed);
        overflow-y: auto;
        transition: transform 0.3s ease;
        z-index: 999;
    }

    .sidebar.collapsed {
        transform: translateX(-256px);
    }

    @media (max-width: 768px) {
        .sidebar {
            transform: translateX(-256px);
        }

        .sidebar.show {
            transform: translateX(0);
        }
    }

    .nav-link {
        display: flex;
        align-items: center;
        padding: 12px 24px;
        color: #5f6368;
        text-decoration: none;
        transition: background 0.2s;
        font-size: 14px;
        font-weight: 500;
    }

    .nav-link:hover {
        background: #f1f3f4;
    }

    .nav-link.active {
        background: #e8f0fe;
        color: var(--primary-color, #1a73e8);
        border-left: 3px solid var(--primary-color, #1a73e8);
    }

    .nav-link i {
        margin-right: 12px;
        font-size: 18px;
        width: 24px;
        text-align: center;
        color: #FF6B35;
    }

    .dark-mode .sidebar {
        background: var(--bg-color, #1a1a1a);
        border-right-color: var(--border-color, #2a2a2a);
    }

    .dark-mode .nav-link {
        color: #e8eaed;
    }

    .dark-mode .nav-link:hover {
        background: rgba(255, 255, 255, 0.05);
    }

    .dark-mode .nav-link.active {
        background: rgba(255, 193, 7, 0.1);
        color: #ffc107;
        border-left-color: #ffc107;
    }
</style>

<script>
    function toggleSidebar() {
        const sidebar = document.getElementById('sidebar');
        const mainContent = document.getElementById('mainContent');

        if (window.innerWidth <= 768) {
            sidebar.classList.toggle('show');
        } else {
            sidebar.classList.toggle('collapsed');
            if (mainContent) {
                mainContent.classList.toggle('expanded');
            }
        }
    }

    function toggleDarkMode() {
        document.body.classList.toggle('dark-mode');
        localStorage.setItem('darkMode', document.body.classList.contains('dark-mode'));
    }

    // Load dark mode preference
    if (localStorage.getItem('darkMode') === 'true') {
        document.body.classList.add('dark-mode');
    }
</script>