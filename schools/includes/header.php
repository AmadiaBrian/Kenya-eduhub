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

    .logo {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 18px;
        font-weight: 400;
        color: #202124;
    }

    .header-right {
        margin-left: auto;
        display: flex;
        align-items: center;
        gap: 16px;
    }

    .school-avatar {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        background: #FF6B35;
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 500;
        font-size: 14px;
    }
</style>

<header class="header">
    <div class="header-left">
        <button class="menu-btn" onclick="toggleSidebar()">
            <span class="material-icons" style="font-size: 24px;">menu</span>
        </button>
        <?php require_once dirname(__DIR__) . '/../includes/logo.php'; ?>
    </div>
    <div class="header-right">
        <div class="school-avatar">
            <?php echo strtoupper(substr($school_name ?? 'S', 0, 1)); ?>
        </div>
    </div>
</header>
