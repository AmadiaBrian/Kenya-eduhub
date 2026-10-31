<?php
// Admin Sidebar Include
// Set $active_page variable before including to highlight the active link
// Set $in_subdirectory to true if called from schools subdirectory
?>

<!-- Google Material Symbols -->
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0" />

<!-- Sidebar -->
<aside class="sidebar" id="sidebar">
    <a class="nav-link <?php echo ($active_page ?? '') === 'dashboard' ? 'active' : ''; ?>" href="<?php echo ($in_subdirectory ?? false) ? '../dashboard' : 'dashboard'; ?>">
        <span class="material-symbols-outlined">dashboard</span> Dashboard
    </a>
    <a class="nav-link <?php echo ($active_page ?? '') === 'my-resources' ? 'active' : ''; ?>" href="<?php echo ($in_subdirectory ?? false) ? '../dashboard?section=resources' : 'dashboard?section=resources'; ?>">
        <span class="material-symbols-outlined">menu_book</span> My Resources
    </a>
    <a class="nav-link <?php echo ($active_page ?? '') === 'upload' ? 'active' : ''; ?>" href="<?php echo ($in_subdirectory ?? false) ? '../dashboard?section=upload' : 'dashboard?section=upload'; ?>">
        <span class="material-symbols-outlined">upload</span> Upload Resource
    </a>
    <a class="nav-link <?php echo ($active_page ?? '') === 'schools' ? 'active' : ''; ?>" href="<?php echo ($in_subdirectory ?? false) ? '../schools' : 'schools'; ?>">
        <span class="material-symbols-outlined">school</span> Schools
    </a>
    <a class="nav-link <?php echo ($active_page ?? '') === 'school-accounts' ? 'active' : ''; ?>" href="<?php echo ($in_subdirectory ?? false) ? '../school-accounts' : 'school-accounts'; ?>">
        <span class="material-symbols-outlined">account_balance_wallet</span> School Accounts
    </a>
    <a class="nav-link <?php echo ($active_page ?? '') === 'users' ? 'active' : ''; ?>" href="<?php echo ($in_subdirectory ?? false) ? '../users' : 'users'; ?>">
        <span class="material-symbols-outlined">people</span> Users
    </a>
    <a class="nav-link <?php echo ($active_page ?? '') === 'resources' ? 'active' : ''; ?>" href="<?php echo ($in_subdirectory ?? false) ? '../resources' : 'resources'; ?>">
        <span class="material-symbols-outlined">library_books</span> Resources
    </a>
    <a class="nav-link <?php echo ($active_page ?? '') === 'transaction-rates' ? 'active' : ''; ?>" href="<?php echo ($in_subdirectory ?? false) ? '../transaction-rates' : 'transaction-rates'; ?>">
        <span class="material-symbols-outlined">percent</span> Transaction Rates
    </a>
    <a class="nav-link <?php echo ($active_page ?? '') === 'reports' ? 'active' : ''; ?>" href="<?php echo ($in_subdirectory ?? false) ? '../reports' : 'reports'; ?>">
        <span class="material-symbols-outlined">bar_chart</span> Reports
    </a>
    <a class="nav-link <?php echo ($active_page ?? '') === 'logs' ? 'active' : ''; ?>" href="<?php echo ($in_subdirectory ?? false) ? '../logs' : 'logs'; ?>">
        <span class="material-symbols-outlined">description</span> Logs
    </a>
    <a class="nav-link <?php echo ($active_page ?? '') === 'settings' ? 'active' : ''; ?>" href="<?php echo ($in_subdirectory ?? false) ? '../settings' : 'settings'; ?>">
        <span class="material-symbols-outlined">settings</span> Settings
    </a>
    <a class="nav-link" href="<?php echo ($in_subdirectory ?? false) ? '../logout' : 'logout'; ?>">
        <span class="material-symbols-outlined">logout</span> Logout
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
        background: var(--bg-color, #F0F4F9);
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

    .nav-link .material-symbols-outlined {
        margin-right: 12px;
        font-size: 20px;
        width: 24px;
        text-align: center;
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