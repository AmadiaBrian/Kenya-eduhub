<?php
// Parent Header Include
// Usage: <?php require_once 'includes/header.php'; ?>
// Requires $parent_name variable to be set with parent name
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
        <div class="user-avatar">
            <?php echo strtoupper(substr($parent_name ?? 'P', 0, 1)); ?>
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