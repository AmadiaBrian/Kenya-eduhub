<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0">
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
        background: #F0F4F9 !important;
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

    .nav-link .material-symbols-outlined {
        margin-right: 12px;
        font-size: 20px;
        width: 24px;
        text-align: center;
        color: #5f6368;
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
            <span class="material-symbols-outlined">dashboard</span> Dashboard
        </a>
        <a class="nav-link <?php echo ($active_page ?? '') === 'timetable' ? 'active' : ''; ?>" href="timetable">
            <span class="material-symbols-outlined">schedule</span> Timetable
        </a>
        <a class="nav-link <?php echo ($active_page ?? '') === 'attendance' ? 'active' : ''; ?>" href="attendance">
            <span class="material-symbols-outlined">fact_check</span> Attendance
        </a>
        <a class="nav-link <?php echo ($active_page ?? '') === 'calendar' ? 'active' : ''; ?>" href="calendar">
            <span class="material-symbols-outlined">calendar_today</span> Calendar
        </a>
        <a class="nav-link <?php echo ($active_page ?? '') === 'performance' ? 'active' : ''; ?>" href="performance">
            <span class="material-symbols-outlined">trending_up</span> Performance
        </a>
        <a class="nav-link <?php echo ($active_page ?? '') === 'results' ? 'active' : ''; ?>" href="results">
            <span class="material-symbols-outlined">emoji_events</span> Results
        </a>
        <a class="nav-link <?php echo ($active_page ?? '') === 'students' ? 'active' : ''; ?>" href="students">
            <span class="material-symbols-outlined">school</span> Students
        </a>
        <a class="nav-link <?php echo ($active_page ?? '') === 'student-subjects' ? 'active' : ''; ?>" href="student-subjects">
            <span class="material-symbols-outlined">menu_book</span> Student Subjects
        </a>
        <a class="nav-link <?php echo ($active_page ?? '') === 'assignments' ? 'active' : ''; ?>" href="assignments">
            <span class="material-symbols-outlined">assignment</span> Assignments
        </a>
        <a class="nav-link <?php echo ($active_page ?? '') === 'parents' ? 'active' : ''; ?>" href="parents">
            <span class="material-symbols-outlined">groups</span> Parents
        </a>
        <a class="nav-link <?php echo ($active_page ?? '') === 'duty' ? 'active' : ''; ?>" href="duty">
            <span class="material-symbols-outlined">assignment</span> My Duties
        </a>
        <a class="nav-link <?php echo ($active_page ?? '') === 'fees' ? 'active' : ''; ?>" href="fees">
            <span class="material-symbols-outlined">payments</span> Fee Payments
        </a>
    </div>
    <div class="sidebar-section">
        <div class="sidebar-title">Account</div>
        <a class="nav-link <?php echo ($active_page ?? '') === 'profile' ? 'active' : ''; ?>" href="profile">
            <span class="material-symbols-outlined">person</span> Profile
        </a>
        <a class="nav-link <?php echo ($active_page ?? '') === 'logout' ? 'active' : ''; ?>" href="logout">
            <span class="material-symbols-outlined">logout</span> Logout
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
