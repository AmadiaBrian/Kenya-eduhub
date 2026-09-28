<style>
    /* Header CSS */
    :root {
        --header-height: 64px;
    }

    .header {
        position: fixed !important;
        top: 0;
        left: 0;
        right: 0;
        height: var(--header-height);
        background: #f8f9fa !important;
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
        padding: 16px;
        border-radius: 50%;
        color: #5f6368;
        transition: background 0.2s;
        font-size: 20px;
    }

    .menu-btn:hover {
        background: #f1f3f4;
    }

    .header-right {
        margin-left: auto;
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
</style>

<!-- Header -->
<header class="header">
    <div class="header-left">
        <button class="menu-btn" onclick="toggleSidebar()">
            <i class="fas fa-bars"></i>
        </button>
        <?php require_once dirname(__DIR__) . '/../includes/logo.php'; ?>
    </div>
    <div class="header-right">
        <div class="user-avatar">
            <?php echo strtoupper(substr($teacher_name ?? 'T', 0, 1)); ?>
        </div>
    </div>
</header>
