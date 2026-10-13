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

    .nav-link i {
        margin-right: 12px;
        font-size: 18px;
        width: 24px;
        text-align: center;
    }

    .nav-link[href="dashboard"] i { color: #4285F4; }
    .nav-link[href="students"] i { color: #EA4335; }
    .nav-link[href="teachers"] i { color: #00AC47; }
    .nav-link[href="classes"] i { color: #FFBA00; }
    .nav-link[href="streams"] i { color: #0066DA; }
    .nav-link[href="subjects"] i { color: #4285F4; }
    .nav-link[href="exam-types"] i { color: #EA4335; }
    .nav-link[href="timetable"] i { color: #00AC47; }
    .nav-link[href="grading"] i { color: #FFBA00; }
    .nav-link[href="performance"] i { color: #0066DA; }
    .nav-link[href="results"] i { color: #4285F4; }
    .nav-link[href="attendance"] i { color: #EA4335; }
    .nav-link[href="calendar"] i { color: #00AC47; }
    .nav-link[href="fees"] i { color: #FFBA00; }
    .nav-link[href="invoices"] i { color: #0066DA; }
    .nav-link[href="finance-managers"] i { color: #4285F4; }
    .nav-link[href="account"] i { color: #EA4335; }
    .nav-link[href="parents"] i { color: #00AC47; }
    .nav-link[href="disciplinary"] i { color: #FFBA00; }
    .nav-link[href="disciplinary-action-types"] i { color: #0066DA; }
    .nav-link[href="duty-assignments"] i { color: #4285F4; }
    .nav-link[href="librarians"] i { color: #EA4335; }
    .nav-link[href="examination-heads"] i { color: #00AC47; }
    .nav-link[href="profile"] i { color: #5f6368; }
    .nav-link[href="settings"] i { color: #5f6368; }
    .nav-link[href="logout"] i { color: #EA4335; }

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
            <i class="fas fa-home"></i> Dashboard
        </a>
        <a class="nav-link<?php echo isset($active_page) && $active_page === 'students' ? ' active' : ''; ?>" href="students">
            <i class="fas fa-user-graduate"></i> Students
        </a>
        <a class="nav-link<?php echo isset($active_page) && $active_page === 'teachers' ? ' active' : ''; ?>" href="teachers">
            <i class="fas fa-chalkboard-teacher"></i> Teachers
        </a>
        <a class="nav-link<?php echo isset($active_page) && $active_page === 'classes' ? ' active' : ''; ?>" href="classes">
            <i class="fas fa-chalkboard"></i> Classes
        </a>
        <a class="nav-link<?php echo isset($active_page) && $active_page === 'streams' ? ' active' : ''; ?>" href="streams">
            <i class="fas fa-layer-group"></i> Streams
        </a>
        <a class="nav-link<?php echo isset($active_page) && $active_page === 'subjects' ? ' active' : ''; ?>" href="subjects">
            <i class="fas fa-book"></i> Subjects
        </a>
        <a class="nav-link<?php echo isset($active_page) && $active_page === 'exam-types' ? ' active' : ''; ?>" href="exam-types">
            <i class="fas fa-clipboard-list"></i> Exam Types
        </a>
        <a class="nav-link<?php echo isset($active_page) && $active_page === 'timetable' ? ' active' : ''; ?>" href="timetable">
            <i class="fas fa-calendar-alt"></i> Timetable
        </a>
        <a class="nav-link<?php echo isset($active_page) && $active_page === 'grading' ? ' active' : ''; ?>" href="grading">
            <i class="fas fa-chart-bar"></i> Grading
        </a>
        <a class="nav-link<?php echo isset($active_page) && $active_page === 'performance' ? ' active' : ''; ?>" href="performance">
            <i class="fas fa-chart-line"></i> Performance
        </a>
        <a class="nav-link<?php echo isset($active_page) && $active_page === 'results' ? ' active' : ''; ?>" href="results">
            <i class="fas fa-clipboard-list"></i> Results
        </a>
        <a class="nav-link<?php echo isset($active_page) && $active_page === 'attendance' ? ' active' : ''; ?>" href="attendance">
            <i class="fas fa-calendar-check"></i> Attendance
        </a>
        <a class="nav-link<?php echo isset($active_page) && $active_page === 'calendar' ? ' active' : ''; ?>" href="calendar">
            <i class="fas fa-calendar"></i> Calendar
        </a>
        <a class="nav-link<?php echo isset($active_page) && $active_page === 'fees' ? ' active' : ''; ?>" href="fees">
            <i class="fas fa-money-bill-wave"></i> Fees
        </a>
        <a class="nav-link<?php echo isset($active_page) && $active_page === 'invoices' ? ' active' : ''; ?>" href="invoices">
            <i class="fas fa-file-invoice-dollar"></i> Invoices
        </a>
        <a class="nav-link<?php echo isset($active_page) && $active_page === 'finance-managers' ? ' active' : ''; ?>" href="finance-managers">
            <i class="fas fa-user-tie"></i> Finance Managers
        </a>
        <a class="nav-link<?php echo isset($active_page) && $active_page === 'account' ? ' active' : ''; ?>" href="account">
            <i class="fas fa-wallet"></i> Account Balance
        </a>
        <a class="nav-link<?php echo isset($active_page) && $active_page === 'parents' ? ' active' : ''; ?>" href="parents">
            <i class="fas fa-users"></i> Parents
        </a>
        <a class="nav-link<?php echo isset($active_page) && $active_page === 'disciplinary' ? ' active' : ''; ?>" href="disciplinary">
            <i class="fas fa-shield-alt"></i> Disciplinary
        </a>
        <a class="nav-link<?php echo isset($active_page) && $active_page === 'disciplinary-action-types' ? ' active' : ''; ?>" href="disciplinary-action-types">
            <i class="fas fa-list-alt"></i> Disciplinary Types
        </a>
        <a class="nav-link<?php echo isset($active_page) && $active_page === 'duty-assignments' ? ' active' : ''; ?>" href="duty-assignments">
            <i class="fas fa-clipboard-list"></i> Duty Assignments
        </a>
        <a class="nav-link<?php echo isset($active_page) && $active_page === 'librarians' ? ' active' : ''; ?>" href="librarians">
            <i class="fas fa-book-reader"></i> Librarians
        </a>
        <a class="nav-link<?php echo isset($active_page) && $active_page === 'examination-heads' ? ' active' : ''; ?>" href="examination-heads">
            <i class="fas fa-user-tie"></i> Examination Heads
        </a>
    </div>
    <div class="sidebar-section">
        <div class="sidebar-title">Settings</div>
        <a class="nav-link<?php echo isset($active_page) && $active_page === 'profile' ? ' active' : ''; ?>" href="profile">
            <i class="fas fa-user"></i> Profile
        </a>
        <a class="nav-link<?php echo isset($active_page) && $active_page === 'settings' ? ' active' : ''; ?>" href="settings">
            <i class="fas fa-cog"></i> Settings
        </a>
        <a class="nav-link<?php echo isset($active_page) && $active_page === 'logout' ? ' active' : ''; ?>" href="logout">
            <i class="fas fa-sign-out-alt"></i> Logout
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
