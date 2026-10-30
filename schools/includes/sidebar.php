<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0" />
<style>
    /* Sidebar CSS */
    :root {
        --sidebar-width: 256px;
        --header-height: 64px;
        --primary-color: #1a73e8;
    }

    .sidebar {
        position: fixed;
        top: var(--header-height);
        left: 0;
        width: var(--sidebar-width);
        height: calc(100vh - var(--header-height));
        background: #F0F4F9 !important;
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

    /* Main content styling for large screens */
    @media (min-width: 769px) {
        .main-content {
            margin-left: var(--sidebar-width);
            margin-top: var(--header-height);
            padding: 24px;
            transition: margin-left 0.3s ease;
        }

        .main-content.expanded {
            margin-left: 0;
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
        cursor: pointer;
        display: flex;
        justify-content: space-between;
        align-items: center;
        user-select: none;
    }

    .sidebar-title:hover {
        background: #f1f3f4;
    }

    .sidebar-title .chevron {
        transition: transform 0.3s ease;
    }

    .sidebar-title.collapsed .chevron {
        transform: rotate(-90deg);
    }

    .sidebar-links {
        max-height: 1000px;
        overflow: hidden;
        transition: max-height 0.3s ease;
    }

    .sidebar-links.collapsed {
        max-height: 0;
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
        margin-right: 32px;
        font-size: 20px;
        width: 24px;
        text-align: center;
        color: #5f6368;
    }

    .nav-link[href="logout"] .material-symbols-outlined { color: #d93025; }

    /* Mobile responsive */
    @media (max-width: 768px) {
        .sidebar {
            transform: translateX(-256px);
            z-index: 9999;
        }

        .sidebar.show {
            transform: translateX(0);
        }
    }
</style>

<aside class="sidebar" id="sidebar">
    <div class="sidebar-section">
        <div class="sidebar-title">Main</div>
        <a class="nav-link<?php echo isset($active_page) && $active_page === 'dashboard' ? ' active' : ''; ?>" href="dashboard">
            <span class="material-symbols-outlined">dashboard</span> Dashboard
        </a>
        <a class="nav-link<?php echo isset($active_page) && $active_page === 'students' ? ' active' : ''; ?>" href="students">
            <span class="material-symbols-outlined">school</span> Students
        </a>
        <a class="nav-link<?php echo isset($active_page) && $active_page === 'teachers' ? ' active' : ''; ?>" href="teachers">
            <span class="material-symbols-outlined">person</span> Teachers
        </a>
        <a class="nav-link<?php echo isset($active_page) && $active_page === 'classes' ? ' active' : ''; ?>" href="classes">
            <span class="material-symbols-outlined">class</span> Classes
        </a>
        <a class="nav-link<?php echo isset($active_page) && $active_page === 'streams' ? ' active' : ''; ?>" href="streams">
            <span class="material-symbols-outlined">layers</span> Streams
        </a>
        <a class="nav-link<?php echo isset($active_page) && $active_page === 'subjects' ? ' active' : ''; ?>" href="subjects">
            <span class="material-symbols-outlined">menu_book</span> Subjects
        </a>
        <a class="nav-link<?php echo isset($active_page) && $active_page === 'exam-types' ? ' active' : ''; ?>" href="exam-types">
            <span class="material-symbols-outlined">assignment</span> Exam Types
        </a>
        <a class="nav-link<?php echo isset($active_page) && $active_page === 'timetable' ? ' active' : ''; ?>" href="timetable">
            <span class="material-symbols-outlined">schedule</span> Timetable
        </a>
        <a class="nav-link<?php echo isset($active_page) && $active_page === 'grading' ? ' active' : ''; ?>" href="grading">
            <span class="material-symbols-outlined">bar_chart</span> Grading
        </a>
        <a class="nav-link<?php echo isset($active_page) && $active_page === 'performance' ? ' active' : ''; ?>" href="performance">
            <span class="material-symbols-outlined">trending_up</span> Performance
        </a>
        <a class="nav-link<?php echo isset($active_page) && $active_page === 'results' ? ' active' : ''; ?>" href="results">
            <span class="material-symbols-outlined">fact_check</span> Results
        </a>
        <a class="nav-link<?php echo isset($active_page) && $active_page === 'attendance' ? ' active' : ''; ?>" href="attendance">
            <span class="material-symbols-outlined">event_available</span> Attendance
        </a>
        <a class="nav-link<?php echo isset($active_page) && $active_page === 'calendar' ? ' active' : ''; ?>" href="calendar">
            <span class="material-symbols-outlined">calendar_month</span> Calendar
        </a>
        <a class="nav-link<?php echo isset($active_page) && $active_page === 'fees' ? ' active' : ''; ?>" href="fees">
            <span class="material-symbols-outlined">payments</span> Fees
        </a>
        <a class="nav-link<?php echo isset($active_page) && $active_page === 'invoices' ? ' active' : ''; ?>" href="invoices">
            <span class="material-symbols-outlined">receipt_long</span> Invoices
        </a>
        <a class="nav-link<?php echo isset($active_page) && $active_page === 'finance-managers' ? ' active' : ''; ?>" href="finance-managers">
            <span class="material-symbols-outlined">badge</span> Finance Managers
        </a>
        <a class="nav-link<?php echo isset($active_page) && $active_page === 'account' ? ' active' : ''; ?>" href="account">
            <span class="material-symbols-outlined">account_balance_wallet</span> Account Balance
        </a>
        <a class="nav-link<?php echo isset($active_page) && $active_page === 'parents' ? ' active' : ''; ?>" href="parents">
            <span class="material-symbols-outlined">family_restroom</span> Parents
        </a>
        <a class="nav-link<?php echo isset($active_page) && $active_page === 'disciplinary' ? ' active' : ''; ?>" href="disciplinary">
            <span class="material-symbols-outlined">gavel</span> Disciplinary
        </a>
        <a class="nav-link<?php echo isset($active_page) && $active_page === 'disciplinary-action-types' ? ' active' : ''; ?>" href="disciplinary-action-types">
            <span class="material-symbols-outlined">rule</span> Disciplinary Types
        </a>
        <a class="nav-link<?php echo isset($active_page) && $active_page === 'duty-assignments' ? ' active' : ''; ?>" href="duty-assignments">
            <span class="material-symbols-outlined">task_alt</span> Duty Assignments
        </a>
        <a class="nav-link<?php echo isset($active_page) && $active_page === 'librarians' ? ' active' : ''; ?>" href="librarians">
            <span class="material-symbols-outlined">local_library</span> Librarians
        </a>
        <a class="nav-link<?php echo isset($active_page) && $active_page === 'examination-heads' ? ' active' : ''; ?>" href="examination-heads">
            <span class="material-symbols-outlined">supervisor_account</span> Examination Heads
        </a>
    </div>
    <div class="sidebar-section">
        <div class="sidebar-title">Settings</div>
        <a class="nav-link<?php echo isset($active_page) && $active_page === 'profile' ? ' active' : ''; ?>" href="profile">
            <span class="material-symbols-outlined">person</span> Profile
        </a>
        <a class="nav-link<?php echo isset($active_page) && $active_page === 'settings' ? ' active' : ''; ?>" href="settings">
            <span class="material-symbols-outlined">settings</span> Settings
        </a>
        <a class="nav-link<?php echo isset($active_page) && $active_page === 'logout' ? ' active' : ''; ?>" href="logout">
            <span class="material-symbols-outlined">logout</span> Logout
        </a>
</br>
</br>
</br>
</br>
    </div>
</aside>

<script>
    function toggleSidebar() {
        const sidebar = document.getElementById('sidebar');
        const mainContent = document.getElementById('mainContent');

        if (!sidebar) return;

        // Check if we're on mobile
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

    // Toggle sidebar sections
    document.querySelectorAll('.sidebar-title').forEach(title => {
        title.addEventListener('click', function() {
            this.classList.toggle('collapsed');
            const links = this.nextElementSibling;
            if (links && links.classList.contains('sidebar-links')) {
                links.classList.toggle('collapsed');
            }
        });
    });

    // Only enable collapsible sections on large screens
    function handleResize() {
        const sidebarTitles = document.querySelectorAll('.sidebar-title');
        const sidebarLinks = document.querySelectorAll('.sidebar-links');

        if (window.innerWidth <= 768) {
            // On mobile, expand all sections
            sidebarTitles.forEach(title => title.classList.remove('collapsed'));
            sidebarLinks.forEach(links => links.classList.remove('collapsed'));
        }
    }

    window.addEventListener('resize', handleResize);
    handleResize();
</script>
