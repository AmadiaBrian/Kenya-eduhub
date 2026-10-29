<?php
// Dashboard Sidebar Include
// Variables needed: $current_page (optional, for highlighting active link)
?>
    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar">
        <a class="nav-link <?php echo ($current_page ?? '') === 'dashboard' ? 'active' : ''; ?>" href="dashboard">
            <span class="material-symbols-outlined" style="color: #5f6368; margin-right: 32px;">dashboard</span> Dashboard
        </a>
        <a class="nav-link <?php echo ($current_page ?? '') === 'resources' ? 'active' : ''; ?>" href="?section=resources">
            <span class="material-symbols-outlined" style="color: #5f6368; margin-right: 32px;">menu_book</span> My Resources
        </a>
        <a class="nav-link <?php echo ($current_page ?? '') === 'upload' ? 'active' : ''; ?>" href="?section=upload">
            <span class="material-symbols-outlined" style="color: #5f6368; margin-right: 32px;">upload</span> Upload Resource
        </a>
        <a class="nav-link" href="https://sites.google.com/view/noteselectricalengineering/home" target="_blank">
            <span class="material-symbols-outlined" style="color: #5f6368; margin-right: 32px;">open_in_new</span> More Resources
        </a>
        <a class="nav-link <?php echo ($current_page ?? '') === 'profile' ? 'active' : ''; ?>" href="profile">
            <span class="material-symbols-outlined" style="color: #5f6368; margin-right: 32px;">person</span> Profile
        </a>
        <a class="nav-link <?php echo ($current_page ?? '') === 'settings' ? 'active' : ''; ?>" href="settings">
            <span class="material-symbols-outlined" style="color: #5f6368; margin-right: 32px;">settings</span> Settings
        </a>
        <a class="nav-link" href="../auth/logout" style="margin-top: auto; border-top: 1px solid var(--border-color);">
            <span class="material-symbols-outlined" style="color: #d93025; margin-right: 32px;">logout</span> Logout
        </a>
    </aside>
