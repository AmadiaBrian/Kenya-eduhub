<?php
// Parent Sidebar Include
// Set $active_page variable before including to highlight the active link
?>

<!-- Material Symbols Icons -->
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0" />

<!-- Sidebar -->
<aside class="sidebar" id="sidebar">
    <a class="nav-link <?php echo ($active_page ?? '') === 'dashboard' ? 'active' : ''; ?>" href="dashboard">
        <span class="material-symbols-outlined" style="color: #5f6368; margin-right: 32px;">dashboard</span> Dashboard
    </a>
    <a class="nav-link <?php echo ($active_page ?? '') === 'children' ? 'active' : ''; ?>" href="children">
        <span class="material-symbols-outlined" style="color: #5f6368; margin-right: 32px;">family_restroom</span> My Children
    </a>
    <a class="nav-link <?php echo ($active_page ?? '') === 'performance' ? 'active' : ''; ?>" href="performance">
        <span class="material-symbols-outlined" style="color: #5f6368; margin-right: 32px;">trending_up</span> Performance
    </a>
    <a class="nav-link <?php echo ($active_page ?? '') === 'results' ? 'active' : ''; ?>" href="results">
        <span class="material-symbols-outlined" style="color: #5f6368; margin-right: 32px;">school</span> Results
    </a>
    <a class="nav-link <?php echo ($active_page ?? '') === 'attendance' ? 'active' : ''; ?>" href="attendance">
        <span class="material-symbols-outlined" style="color: #5f6368; margin-right: 32px;">fact_check</span> Attendance
    </a>
    <a class="nav-link <?php echo ($active_page ?? '') === 'calendar' ? 'active' : ''; ?>" href="calendar">
        <span class="material-symbols-outlined" style="color: #5f6368; margin-right: 32px;">calendar_month</span> Calendar
    </a>
    <a class="nav-link <?php echo ($active_page ?? '') === 'assignments' ? 'active' : ''; ?>" href="assignments">
        <span class="material-symbols-outlined" style="color: #5f6368; margin-right: 32px;">assignment</span> Assignments
    </a>
    <a class="nav-link <?php echo ($active_page ?? '') === 'fines' ? 'active' : ''; ?>" href="fines">
        <span class="material-symbols-outlined" style="color: #5f6368; margin-right: 32px;">menu_book</span> Library Fines
    </a>
    <a class="nav-link <?php echo ($active_page ?? '') === 'fees' ? 'active' : ''; ?>" href="fees">
        <span class="material-symbols-outlined" style="color: #5f6368; margin-right: 32px;">payments</span> Fee Payments
    </a>
    <a class="nav-link <?php echo ($active_page ?? '') === 'profile' ? 'active' : ''; ?>" href="profile">
        <span class="material-symbols-outlined" style="color: #5f6368; margin-right: 32px;">person</span> Profile
    </a>
    <a class="nav-link" href="logout" style="margin-top: auto; border-top: 1px solid var(--border-color);">
        <span class="material-symbols-outlined" style="color: #d93025; margin-right: 32px;">logout</span> Logout
    </a>
</aside>

<style>
    :root {
        --sidebar-width: 256px;
        --header-height: 64px;
        --sidebar-bg: #F0F4F9;
        --border-color: #e8eaed;
    }

    .sidebar {
        position: fixed;
        top: var(--header-height);
        left: 0;
        width: var(--sidebar-width);
        height: calc(100vh - var(--header-height));
        background: var(--sidebar-bg);
        overflow-y: auto;
        transition: transform 0.3s ease;
        z-index: 999;
        border-right: 1px solid var(--border-color);
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
        padding: 16px 24px;
        color: #5f6368;
        text-decoration: none;
        transition: background 0.2s;
        border: none;
        background: none;
        width: 100%;
        text-align: left;
        cursor: pointer;
        font-size: 14px;
        font-weight: 500;
    }

    .nav-link:hover {
        background: #f1f3f4;
    }

    .nav-link.active {
        background: #e8f0fe;
        color: #1a73e8;
        border-left: 3px solid #1a73e8;
    }

    .material-symbols-outlined {
        font-size: 24px;
        font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
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
</script>
