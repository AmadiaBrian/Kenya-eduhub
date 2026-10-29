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
        background: #f8f9fa !important;
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

    .nav-link .material-icons {
        margin-right: 32px;
        font-size: 20px;
        width: 24px;
        text-align: center;
        color: #5f6368;
    }

    .nav-link[href="logout"] .material-icons { color: #d93025; }

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
            <span class="material-icons">dashboard</span> Dashboard
        </a>
        <a class="nav-link<?php echo isset($active_page) && $active_page === 'students' ? ' active' : ''; ?>" href="students">
            <span class="material-icons">school</span> Students
        </a>
        <a class="nav-link<?php echo isset($active_page) && $active_page === 'teachers' ? ' active' : ''; ?>" href="teachers">
            <span class="material-icons">person_apron</span> Teachers
        </a>
        <a class="nav-link<?php echo isset($active_page) && $active_page === 'classes' ? ' active' : ''; ?>" href="classes">
            <span class="material-icons">class</span> Classes
        </a>
        <a class="nav-link<?php echo isset($active_page) && $active_page === 'streams' ? ' active' : ''; ?>" href="streams">
            <span class="material-icons">layers</span> Streams
        </a>
        <a class="nav-link<?php echo isset($active_page) && $active_page === 'subjects' ? ' active' : ''; ?>" href="subjects">
            <span class="material-icons">menu_book</span> Subjects
        </a>
        <a class="nav-link<?php echo isset($active_page) && $active_page === 'exam-types' ? ' active' : ''; ?>" href="exam-types">
            <span class="material-icons">assignment</span> Exam Types
        </a>
        <a class="nav-link<?php echo isset($active_page) && $active_page === 'timetable' ? ' active' : ''; ?>" href="timetable">
            <span class="material-icons">schedule</span> Timetable
        </a>
        <a class="nav-link<?php echo isset($active_page) && $active_page === 'grading' ? ' active' : ''; ?>" href="grading">
            <span class="material-icons">bar_chart</span> Grading
        </a>
        <a class="nav-link<?php echo isset($active_page) && $active_page === 'performance' ? ' active' : ''; ?>" href="performance">
            <span class="material-icons">show_chart</span> Performance
        </a>
        <a class="nav-link<?php echo isset($active_page) && $active_page === 'results' ? ' active' : ''; ?>" href="results">
            <span class="material-icons">fact_check</span> Results
        </a>
        <a class="nav-link<?php echo isset($active_page) && $active_page === 'attendance' ? ' active' : ''; ?>" href="attendance">
            <span class="material-icons">event_available</span> Attendance
        </a>
        <a class="nav-link<?php echo isset($active_page) && $active_page === 'calendar' ? ' active' : ''; ?>" href="calendar">
            <span class="material-icons">calendar_month</span> Calendar
        </a>
        <a class="nav-link<?php echo isset($active_page) && $active_page === 'fees' ? ' active' : ''; ?>" href="fees">
            <span class="material-icons">payments</span> Fees
        </a>
        <a class="nav-link<?php echo isset($active_page) && $active_page === 'invoices' ? ' active' : ''; ?>" href="invoices">
            <span class="material-icons">receipt_long</span> Invoices
        </a>
        <a class="nav-link<?php echo isset($active_page) && $active_page === 'finance-managers' ? ' active' : ''; ?>" href="finance-managers">
            <span class="material-icons">badge</span> Finance Managers
        </a>
        <a class="nav-link<?php echo isset($active_page) && $active_page === 'account' ? ' active' : ''; ?>" href="account">
            <span class="material-icons">account_balance_wallet</span> Account Balance
        </a>
        <a class="nav-link<?php echo isset($active_page) && $active_page === 'parents' ? ' active' : ''; ?>" href="parents">
            <span class="material-icons">family_restroom</span> Parents
        </a>
        <a class="nav-link<?php echo isset($active_page) && $active_page === 'disciplinary' ? ' active' : ''; ?>" href="disciplinary">
            <span class="material-icons">gavel</span> Disciplinary
        </a>
        <a class="nav-link<?php echo isset($active_page) && $active_page === 'disciplinary-action-types' ? ' active' : ''; ?>" href="disciplinary-action-types">
            <span class="material-icons">rule</span> Disciplinary Types
        </a>
        <a class="nav-link<?php echo isset($active_page) && $active_page === 'duty-assignments' ? ' active' : ''; ?>" href="duty-assignments">
            <span class="material-icons">task_alt</span> Duty Assignments
        </a>
        <a class="nav-link<?php echo isset($active_page) && $active_page === 'librarians' ? ' active' : ''; ?>" href="librarians">
            <span class="material-icons">local_library</span> Librarians
        </a>
        <a class="nav-link<?php echo isset($active_page) && $active_page === 'examination-heads' ? ' active' : ''; ?>" href="examination-heads">
            <span class="material-icons">supervisor_account</span> Examination Heads
        </a>
    </div>
    <div class="sidebar-section">
        <div class="sidebar-title">Settings</div>
        <a class="nav-link<?php echo isset($active_page) && $active_page === 'profile' ? ' active' : ''; ?>" href="profile">
            <span class="material-icons">person</span> Profile
        </a>
        <a class="nav-link<?php echo isset($active_page) && $active_page === 'settings' ? ' active' : ''; ?>" href="settings">
            <span class="material-icons">settings</span> Settings
        </a>
        <a class="nav-link<?php echo isset($active_page) && $active_page === 'logout' ? ' active' : ''; ?>" href="logout">
            <span class="material-icons">logout</span> Logout
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
