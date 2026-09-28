<style>
    /* Sidebar CSS */
    :root {
        --sidebar-width: 256px;
        --header-height: 64px;
        --primary-color: #FF6B35;
    }

    .sidebar {
        position: fixed;
        top: var(--header-height);
        left: 0;
        width: var(--sidebar-width);
        height: calc(100vh - var(--header-height));
        background: #f8f9fa !important;
        border-right: 1px solid #e0e0e0;
        overflow-y: auto;
        transition: transform 0.3s ease, margin-left 0.3s ease;
        z-index: 999;
        scrollbar-width: none; /* Firefox */
        -ms-overflow-style: none; /* IE and Edge */
    }

    .sidebar::-webkit-scrollbar {
        display: none; /* Chrome, Safari, Opera */
    }

    .sidebar.collapsed {
        transform: translateX(-256px);
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
        color: var(--primary-color);
    }

    .nav-link i {
        margin-right: 12px;
        font-size: 18px;
        width: 24px;
        text-align: center;
        color: #FF6B35;
    }

    /* Header CSS */
    .header {
        position: fixed !important;
        top: 0;
        left: 0;
        right: 0;
        height: var(--header-height);
        background: #ffffff;
        border-bottom: 1px solid #e0e0e0;
        display: flex;
        align-items: center;
        padding: 0 24px;
        z-index: 1000;
    }

    .header-left {
        display: flex;
        align-items: center;
        gap: 16px;
    }

    .menu-btn {
        background: none;
        border: none;
        cursor: pointer;
        padding: 12px;
        border-radius: 50%;
        color: #5f6368;
        transition: background 0.2s;
    }

    .menu-btn:hover {
        background: #f1f3f4;
    }

    .header-right {
        display: flex;
        align-items: center;
        gap: 16px;
    }

    .user-avatar {
        width: 40px;
        height: 40px;
        background: #FF6B35;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 500;
        color: #ffffff;
    }

    /* Main Content CSS */
    .main-content {
        margin-left: var(--sidebar-width);
        margin-top: var(--header-height);
        padding: 24px;
        padding-bottom: 80px;
        transition: margin-left 0.3s ease;
    }

    .main-content.expanded {
        margin-left: 0;
    }

    /* Mobile Responsive */
    @media (max-width: 768px) {
        .sidebar {
            transform: translateX(-100%);
        }

        .sidebar.show {
            transform: translateX(0);
        }

        .main-content {
            margin-left: 0;
        }

        .main-content.expanded {
            margin-left: 0;
        }
    }
</style>

<!-- Sidebar -->
<aside class="sidebar" id="sidebar">
    <div class="sidebar-section">
        <div class="sidebar-title">Main</div>
        <a class="nav-link <?php echo ($active_page ?? '') === 'dashboard' ? 'active' : ''; ?>" href="dashboard">
            <i class="fas fa-home"></i> Dashboard
        </a>
        <a class="nav-link <?php echo ($active_page ?? '') === 'timetable' ? 'active' : ''; ?>" href="timetable">
            <i class="fas fa-calendar-alt"></i> Timetable
        </a>
        <a class="nav-link <?php echo ($active_page ?? '') === 'attendance' ? 'active' : ''; ?>" href="attendance">
            <i class="fas fa-calendar-check"></i> Attendance
        </a>
        <a class="nav-link <?php echo ($active_page ?? '') === 'calendar' ? 'active' : ''; ?>" href="calendar">
            <i class="fas fa-calendar"></i> Calendar
        </a>
        <a class="nav-link <?php echo ($active_page ?? '') === 'performance' ? 'active' : ''; ?>" href="performance">
            <i class="fas fa-chart-line"></i> Performance
        </a>
        <a class="nav-link <?php echo ($active_page ?? '') === 'results' ? 'active' : ''; ?>" href="results">
            <i class="fas fa-award"></i> Results
        </a>
        <a class="nav-link <?php echo ($active_page ?? '') === 'students' ? 'active' : ''; ?>" href="students">
            <i class="fas fa-user-graduate"></i> Students
        </a>
        <a class="nav-link <?php echo ($active_page ?? '') === 'student-subjects' ? 'active' : ''; ?>" href="student-subjects">
            <i class="fas fa-book"></i> Student Subjects
        </a>
        <a class="nav-link <?php echo ($active_page ?? '') === 'assignments' ? 'active' : ''; ?>" href="assignments">
            <i class="fas fa-tasks"></i> Assignments
        </a>
        <a class="nav-link <?php echo ($active_page ?? '') === 'parents' ? 'active' : ''; ?>" href="parents">
            <i class="fas fa-users"></i> Parents
        </a>
        <a class="nav-link <?php echo ($active_page ?? '') === 'duty' ? 'active' : ''; ?>" href="duty">
            <i class="fas fa-clipboard-list"></i> My Duties
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
        <a class="nav-link <?php echo ($active_page ?? '') === 'logout' ? 'active' : ''; ?>" href="logout">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </div>
</aside>

<script>
    function toggleSidebar() {
        const sidebar = document.getElementById('sidebar');
        const mainContent = document.getElementById('mainContent');
        
        if (window.innerWidth <= 768) {
            // Mobile: toggle the 'show' class
            sidebar.classList.toggle('show');
        } else {
            // Desktop: toggle the 'collapsed' and 'expanded' classes
            sidebar.classList.toggle('collapsed');
            if (mainContent) {
                mainContent.classList.toggle('expanded');
            }
        }
    }
</script>
