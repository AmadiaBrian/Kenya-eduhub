<?php
// Admin Header Include
// Requires $user variable to be set with user data
?>

<!-- Header -->
<header class="header">
    <div class="header-left">
        <button class="menu-btn" onclick="toggleSidebar()">
            <i class="fas fa-bars"></i>
        </button>
        <div class="logo">
            <?php require_once dirname(__DIR__) . '/../includes/logo.php'; ?>
        </div>
    </div>
    <div class="header-right">
        <button class="dark-mode-toggle" onclick="toggleDarkMode()" title="Toggle Dark Mode">
            <i class="fas fa-moon"></i>
        </button>
        <div class="user-avatar">
            <?php echo strtoupper(substr($user['name'] ?? 'A', 0, 1)); ?>
        </div>
    </div>
</header>

<style>
    :root {
        --sidebar-width: 256px;
        --header-height: 64px;
        --primary-color: #1a73e8;
        --primary-orange: #FF6B35;
        --primary-gold: #ffc107;
        --secondary-color: #5f6368;
        --bg-color: #f8f9fa;
        --card-bg: #f8f9fa;
        --text-color: #202124;
        --border-color: #e8eaed;
    }

    .dark-mode {
        --bg-color: #1a1a1a;
        --card-bg: #1a1a1a;
        --text-color: #e8eaed;
        --border-color: #2a2a2a;
    }

    .header {
        position: fixed !important;
        top: 0;
        left: 0;
        right: 0;
        height: var(--header-height);
        background: var(--card-bg);
        border-bottom: 1px solid var(--border-color);
        display: flex;
        align-items: center;
        padding: 0 24px;
        z-index: 1000;
        transition: background 0.3s ease, border-color 0.3s ease;
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
        font-size: 18px;
    }

    .menu-btn:hover {
        background: #f1f3f4;
    }

    .dark-mode .menu-btn {
        color: #ffc107;
        font-size: 22px;
    }

    .dark-mode .menu-btn:hover {
        background: rgba(255, 193, 7, 0.1);
    }

    .logo {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 18px;
        font-weight: 400;
        color: var(--text-color);
    }

    .header-right {
        margin-left: auto;
        display: flex;
        align-items: center;
        gap: 16px;
    }

    .dark-mode-toggle {
        background: none;
        border: none;
        cursor: pointer;
        padding: 12px;
        border-radius: 50%;
        color: var(--secondary-color);
        transition: background 0.2s;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
    }

    .dark-mode-toggle:hover {
        background: rgba(255, 255, 255, 0.1);
    }

    .dark-mode .dark-mode-toggle {
        color: #ffc107;
    }

    .dark-mode .dark-mode-toggle:hover {
        background: rgba(255, 193, 7, 0.1);
    }

    .user-avatar {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        background: var(--primary-color);
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 500;
        font-size: 14px;
    }

    @media (max-width: 768px) {
        .header {
            padding: 0 16px;
        }
    }
</style>

<script>
    function toggleDarkMode() {
        document.body.classList.toggle('dark-mode');
        localStorage.setItem('darkMode', document.body.classList.contains('dark-mode'));
    }

    // Load dark mode preference
    if (localStorage.getItem('darkMode') === 'true') {
        document.body.classList.add('dark-mode');
    }
</script>