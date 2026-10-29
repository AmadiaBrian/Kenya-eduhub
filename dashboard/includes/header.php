<?php
// Dashboard Header Include
// Variables needed: $user, $dark_mode_class
?>
    <!-- Header -->
    <header class="header">
        <div class="header-left">
            <button class="menu-btn" onclick="toggleSidebar()">
                <span class="material-symbols-outlined" style="color: #5f6368; font-size: 24px;">menu</span>
            </button>
            <div class="logo">
                <?php require_once '../includes/logo.php'; ?>
            </div>
        </div>
        <div class="header-right">
            <button class="dark-mode-toggle" onclick="toggleDarkMode()" title="Toggle Dark Mode">
                <span class="material-symbols-outlined" style="color: #5f6368; font-size: 20px;">dark_mode</span>
            </button>
            <div class="user-avatar">
                <?php echo strtoupper(substr($user['name'] ?? 'A', 0, 1)); ?>
            </div>
        </div>
    </header>
