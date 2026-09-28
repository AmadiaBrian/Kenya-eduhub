<?php
// Parent Sidebar Include
// Set $active_page variable before including to highlight the active link
?>

<!-- Sidebar -->
<aside class="sidebar" id="sidebar">
    <div class="sidebar-section">
        <div class="sidebar-title">Main</div>
        <a class="nav-link <?php echo ($active_page ?? '') === 'dashboard' ? 'active' : ''; ?>" href="dashboard">
            <i class="fas fa-home"></i> Dashboard
        </a>
        <a class="nav-link <?php echo ($active_page ?? '') === 'children' ? 'active' : ''; ?>" href="children">
            <i class="fas fa-child"></i> My Children
        </a>
        <a class="nav-link <?php echo ($active_page ?? '') === 'performance' ? 'active' : ''; ?>" href="performance">
            <i class="fas fa-chart-line"></i> Performance
        </a>
        <a class="nav-link <?php echo ($active_page ?? '') === 'results' ? 'active' : ''; ?>" href="results">
            <i class="fas fa-award"></i> Results
        </a>
        <a class="nav-link <?php echo ($active_page ?? '') === 'attendance' ? 'active' : ''; ?>" href="attendance">
            <i class="fas fa-calendar-check"></i> Attendance
        </a>
        <a class="nav-link <?php echo ($active_page ?? '') === 'calendar' ? 'active' : ''; ?>" href="calendar">
            <i class="fas fa-calendar"></i> Calendar
        </a>
        <a class="nav-link <?php echo ($active_page ?? '') === 'assignments' ? 'active' : ''; ?>" href="assignments">
            <i class="fas fa-tasks"></i> Assignments
        </a>
        <a class="nav-link <?php echo ($active_page ?? '') === 'fines' ? 'active' : ''; ?>" href="fines">
            <i class="fas fa-book"></i> Library Fines
        </a>
        <a class="nav-link <?php echo ($active_page ?? '') === 'fees' ? 'active' : ''; ?>" href="fees">
            <i class="fas fa-money-bill-wave"></i> Fee Payments
        </a>
    </div>
    <div class="sidebar-section">
        <div class="sidebar-title">Account</div>
        <a class="nav-link <?php echo ($active_page ?? '') === 'profile' ? 'active' : ''; ?>" href="profile">
            <i class="fas fa-user"></i> Profile
        </a>
        <a class="nav-link" href="logout">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </div>
</aside>

<style>
    :root {
        --sidebar-width: 256px;
        --header-height: 64px;
        --sidebar-bg: #f8f9fa;
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

    .sidebar-section {
        padding: 12px 0;
    }

    .sidebar-title {
        padding: 8px 24px;
        font-size: 12px;
        font-weight: 500;
        color: #5f6368;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .nav-link {
        display: flex;
        align-items: center;
        padding: 10px 24px;
        color: #5f6368;
        text-decoration: none;
        transition: background 0.2s;
        border: none;
        background: none;
        width: 100%;
        text-align: left;
        cursor: pointer;
        font-size: 14px;
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