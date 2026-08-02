<?php
// Admin Users Management
// Session is started by index.php router
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/security_lite.php';

// Output CSRF token variable for use in HTML
$csrf_token = generateCSRFLite();

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!isset($user['role']) || $user['role'] !== 'admin') {
    header("Location: ../dashboard/index.php");
    exit();
}

// Handle user actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'delete_user') {
        $user_id = $_POST['user_id'] ?? '';
        if ($user_id && $user_id != $_SESSION['user_id']) {
            $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $success = "User deleted successfully";
        }
    } elseif ($action === 'toggle_status') {
        $user_id = $_POST['user_id'] ?? '';
        $current_status = $_POST['current_status'] ?? '';
        $new_status = $current_status === 'active' ? 'inactive' : 'active';
        
        if ($user_id && $user_id != $_SESSION['user_id']) {
            $stmt = $conn->prepare("UPDATE users SET is_verified = ? WHERE id = ?");
            $verified_status = $new_status === 'active' ? 1 : 0;
            $stmt->bind_param("ii", $verified_status, $user_id);
            $stmt->execute();
            $success = "User status updated successfully";
        }
    }
}

// Get all users with pagination
$page = (int)($_GET['page'] ?? 1);
$per_page = 10;
$offset = ($page - 1) * $per_page;

// Ensure pagination variables are properly set
if ($page < 1) $page = 1;
if ($per_page < 1) $per_page = 10;
if ($offset < 0) $offset = 0;

$search = $_GET['search'] ?? '';
$where_clause = '';
$params = [];
$types = '';

if (!empty($search)) {
    $where_clause = "WHERE name LIKE ? OR email LIKE ?";
    $search_param = "%$search%";
    $params = [$search_param, $search_param];
    $types = "ss";
}

// Get total count
$count_sql = "SELECT COUNT(*) as total FROM users $where_clause";
$stmt = $conn->prepare($count_sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$total_users = $stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_users / $per_page);

// Get users
$sql = "SELECT * FROM users $where_clause ORDER BY id DESC LIMIT ? OFFSET ?";
$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $all_params = array_merge($params, [$per_page, $offset]);
    $all_types = $types . "ii";
    $stmt->bind_param($all_types, ...$all_params);
} else {
    $stmt->bind_param("ii", $per_page, $offset);
}
$stmt->execute();
$users = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Recent users (ordered by id since created_at doesn't exist)
$stmt = $conn->prepare("SELECT * FROM users ORDER BY id DESC LIMIT 5");
$stmt->execute();
$recent_users = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get total users count for statistics
$stmt = $conn->prepare("SELECT COUNT(*) as total, SUM(CASE WHEN is_verified = 1 THEN 1 ELSE 0 END) as active FROM users");
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();
$total_users_count = $stats['total'];
$total_active_users = $stats['active'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Users Management - Kenya EduHub</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <script>window.currentCSRFToken = "<?php echo $csrf_token; ?>";</script>
    <style>
        :root {
            --primary-color: #1a73e8;
            --secondary-color: #5f6368;
            --bg-color: #f8f9fa;
            --card-bg: #ffffff;
            --sidebar-width: 256px;
            --header-height: 64px;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            background: var(--bg-color);
            font-family: 'Google Sans', 'Roboto', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            font-size: 14px;
            color: #202124;
        }
        
        .sidebar {
            position: fixed;
            top: var(--header-height);
            left: 0;
            width: var(--sidebar-width);
            height: calc(100vh - var(--header-height));
            background: var(--bg-color);
            overflow-y: auto;
            transition: transform 0.3s ease;
            z-index: 999;
            scrollbar-width: none;
            -ms-overflow-style: none;
        }
        
        .sidebar::-webkit-scrollbar {
            display: none;
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
            color: #FF6B35;
        }
        
        .main-content {
            margin-left: var(--sidebar-width);
            margin-top: var(--header-height);
            padding: 24px;
            transition: margin-left 0.3s ease;
        }
        
        .main-content.expanded {
            margin-left: 0;
        }
        
        .page-title {
            font-size: 22px;
            font-weight: 400;
            color: #202124;
            margin-bottom: 24px;
        }
        
        .header {
            position: fixed !important;
            top: 0;
            left: 0;
            right: 0;
            height: var(--header-height);
            background: var(--card-bg);
            border-bottom: 1px solid #e8eaed;
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
        
        .logo {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 18px;
            font-weight: 400;
            color: #202124;
        }
        
        .logo i {
            color: var(--primary-color);
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
            .sidebar {
                transform: translateX(-256px);
            }
            
            .sidebar.show {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0;
                padding: 16px;
                padding-top: calc(var(--header-height) + 16px);
            }
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: transparent;
            padding: 25px;
            border-radius: 8px;
            border: 1px solid #e8eaed;
        }
        
        .stat-card h3 {
            font-size: 36px;
            font-weight: 400;
            color: var(--primary-color);
            margin-bottom: 5px;
        }
        
        .stat-card p {
            font-size: 14px;
            color: var(--secondary-color);
        }
        
        .card {
            background: transparent;
            border-radius: 8px;
            border: 1px solid #e8eaed;
            overflow: hidden;
        }
        
        .card-header {
            background: transparent;
            padding: 20px 25px;
            border-bottom: 1px solid #e8eaed;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .card-header h2 {
            font-size: 20px;
            font-weight: 500;
            color: #202124;
        }
        
        .btn {
            padding: 10px 24px;
            border: none;
            border-radius: 25px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            transition: background 0.2s;
        }
        
        .btn-primary {
            background: #FF6B35;
            color: white;
        }
        
        .btn-primary:hover {
            background: #e55a2b;
        }
        
        .btn-sm {
            padding: 6px 12px;
            font-size: 12px;
        }
        
        .btn-action {
            background: #f8f9fa;
            color: #000;
            border: 1px solid #000;
            cursor: pointer;
        }
        
        .btn-action:hover {
            background: #e9ecef;
        }

        .table-responsive {
            overflow-x: auto;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border: 1px solid #000;
        }
        
        thead {
            background: #f0f0f0;
            border-bottom: 2px solid #000;
        }
        
        th {
            padding: 12px 15px;
            text-align: left;
            font-weight: 500;
            font-size: 13px;
            color: #000;
            border: 1px solid #000;
            border-bottom: 2px solid #000;
        }
        
        td {
            padding: 12px 15px;
            font-size: 13px;
            border: 1px solid #000;
            color: #000;
        }
        
        tbody tr:hover {
            background: #f8f9fa;
        }
        
        .status-badge {
            padding: 4px 12px;
            border-radius: 16px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .status-active {
            background: #e6f4ea;
            color: #137333;
        }
        
        .status-inactive {
            background: #fce8e6;
            color: #c5221f;
        }
        
        .status-suspended {
            background: #fef7e0;
            color: #f9ab00;
        }
        
        .nav-back {
            margin-bottom: 20px;
        }
        
        .nav-back a {
            color: var(--primary-color);
            text-decoration: none;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="header-left">
            <button class="menu-btn" onclick="toggleSidebar()">
                <i class="fas fa-bars"></i>
            </button>
            <div class="logo">
                <div style="width: 40px; height: 40px; background: #FFD700; border: 3px solid #FF6B35; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-right: 0;">
                    <span style="font-weight: bold; font-size: 20px;">
                        <span style="color: #FF6B35; font-size: 24px;">K</span><span style="color: #008000; font-size: 20px;">E</span>
                    </span>
                </div>
                <span style="color: #FF6B35; font-weight: bold;">Kenya</span> <span style="color: #008000; font-weight: bold;">EduHub</span>
            </div>
        </div>
        <div class="header-right">
            <div class="user-avatar">
                <?php echo strtoupper(substr($user['name'] ?? 'A', 0, 1)); ?>
            </div>
        </div>
    </header>
    
    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar">
        <a class="nav-link" href="dashboard">
            <i class="fas fa-tachometer-alt"></i> Dashboard
        </a>
        <a class="nav-link active" href="users">
            <i class="fas fa-users"></i> Users
        </a>
        <a class="nav-link" href="schools">
            <i class="fas fa-school"></i> Schools
        </a>
        <a class="nav-link" href="resources">
            <i class="fas fa-book"></i> Resources
        </a>
        <a class="nav-link" href="reports">
            <i class="fas fa-chart-bar"></i> Reports
        </a>
        <a class="nav-link" href="logs">
            <i class="fas fa-file-alt"></i> Logs
        </a>
        <a class="nav-link" href="settings">
            <i class="fas fa-cog"></i> Settings
        </a>
        <a class="nav-link" href="logout">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </aside>
    
    <!-- Main Content -->
    <main class="main-content" id="mainContent">
        <h1 class="page-title">Users Management</h1>
        
        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <h3><?php echo $total_users_count; ?></h3>
                <p>Total Users</p>
            </div>
            <div class="stat-card">
                <h3><?php echo $total_active_users; ?></h3>
                <p>Active Users</p>
            </div>
            <div class="stat-card">
                <h3><?php echo $total_users_count - $total_active_users; ?></h3>
                <p>Inactive Users</p>
            </div>
        </div>
        
        <!-- Users Table -->
        <div class="card">
            <div class="card-header">
                <h2>All Users</h2>
                <form method="GET" style="display: flex; gap: 10px;">
                    <input type="text" name="search" placeholder="Search users..." 
                           value="<?php echo htmlspecialchars($search); ?>"
                           style="padding: 8px 12px; border: 1px solid #e8eaed; border-radius: 4px;">
                    <button type="submit" class="btn btn-primary btn-sm">
                        <i class="fas fa-search"></i> Search
                    </button>
                    <?php if (!empty($search)): ?>
                        <a href="users" class="btn btn-sm btn-action">
                            <i class="fas fa-times"></i> Clear
                        </a>
                    <?php endif; ?>
                </form>
            </div>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($users)): ?>
                            <tr>
                                <td colspan="6" style="text-align: center; padding: 40px;">
                                    No users found.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($users as $user_item): ?>
                                <tr data-user-id="<?php echo $user_item['id']; ?>">
                                    <td><?php echo $user_item['id']; ?></td>
                                    <td><?php echo htmlspecialchars($user_item['name']); ?></td>
                                    <td><?php echo htmlspecialchars($user_item['email']); ?></td>
                                    <td><?php echo ucfirst($user_item['role'] ?? 'user'); ?></td>
                                    <td>
                                        <span class="status-badge <?php echo ($user_item['is_verified'] ?? 0) == 1 ? 'status-active' : 'status-inactive'; ?>">
                                            <?php echo ($user_item['is_verified'] ?? 0) == 1 ? 'Verified' : 'Not Verified'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($user_item['id'] != $_SESSION['user_id']): ?>
                                            <button class="btn btn-sm btn-action" onclick="toggleUserStatus(<?php echo $user_item['id']; ?>, <?php echo ($user_item['is_verified'] ?? 0) == 1 ? '0' : '1'; ?>)">
                                                <i class="fas fa-<?php echo ($user_item['is_verified'] ?? 0) == 1 ? 'ban' : 'check'; ?>"></i>
                                            </button>
                                            <button class="btn btn-sm btn-action" onclick="deleteUser(<?php echo $user_item['id']; ?>)">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        <?php else: ?>
                                            <span style="color: #5f6368; font-size: 12px;">Current User</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <div style="display: flex; justify-content: center; gap: 8px; margin-top: 24px; padding: 0 24px 24px;">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>" 
                           style="padding: 8px 12px; border: 1px solid #e8eaed; border-radius: 4px; text-decoration: none; color: #202124;">
                            <i class="fas fa-chevron-left"></i> Previous
                        </a>
                    <?php endif; ?>
                    
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <?php if ($i == $page): ?>
                            <a href="#" style="padding: 8px 12px; background: var(--primary-color); color: white; border-radius: 4px; text-decoration: none;"><?php echo $i; ?></a>
                        <?php else: ?>
                            <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>" 
                               style="padding: 8px 12px; border: 1px solid #e8eaed; border-radius: 4px; text-decoration: none; color: #202124;">
                                <?php echo $i; ?>
                            </a>
                        <?php endif; ?>
                    <?php endfor; ?>
                    
                    <?php if ($page < $total_pages): ?>
                        <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>" 
                           style="padding: 8px 12px; border: 1px solid #e8eaed; border-radius: 4px; text-decoration: none; color: #202124;">
                            Next <i class="fas fa-chevron-right"></i>
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const mainContent = document.getElementById('mainContent');
            sidebar.classList.toggle('collapsed');
            mainContent.classList.toggle('expanded');
        }
        
        function toggleSidebarSection(element) {
            element.classList.toggle('collapsed');
            const links = element.nextElementSibling;
            links.classList.toggle('collapsed');
        }
        
        function toggleUserStatus(id, status) {
            try {
                console.log('toggleUserStatus called with id:', id, 'status:', status);
                
                // Set values
                document.getElementById('confirmUserId').value = id;
                document.getElementById('confirmUserStatus').value = status;
                const statusText = status == 1 ? 'verify' : 'unverify';
                document.getElementById('confirmUserMessage').textContent = 'Are you sure you want to ' + statusText + ' this user?';
                
                console.log('Modal element:', document.getElementById('confirmUserModal'));
                console.log('Bootstrap available:', typeof bootstrap !== 'undefined');
                
                // Try to show modal
                if (typeof bootstrap !== 'undefined') {
                    const modal = new bootstrap.Modal(document.getElementById('confirmUserModal'));
                    modal.show();
                    console.log('Modal shown via Bootstrap');
                } else {
                    console.error('Bootstrap not available');
                    // Fallback to browser confirm with AJAX
                    if (confirm('Are you sure you want to ' + statusText + ' this user?')) {
                        performToggleStatus(id, status);
                    }
                }
            } catch (error) {
                console.error('Error showing modal:', error);
                // Fallback to browser confirm if modal fails
                const statusText = status == 1 ? 'verify' : 'unverify';
                if (confirm('Are you sure you want to ' + statusText + ' this user?')) {
                    performToggleStatus(id, status);
                }
            }
        }

        function performToggleStatus(id, status) {
            fetch('api/users.php?action=toggle_status&id=' + id + '&status=' + status + '&csrf_token=' + window.currentCSRFToken)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Update the status badge in the UI
                        const statusBadge = document.querySelector(`tr[data-user-id="${id}"] .status-badge`);
                        if (statusBadge) {
                            statusBadge.className = 'status-badge ' + (status == 1 ? 'status-active' : 'status-inactive');
                            statusBadge.textContent = status == 1 ? 'Verified' : 'Not Verified';
                        }
                        
                        // Update the button icon
                        const button = document.querySelector(`button[onclick*="toggleUserStatus(${id}"]`);
                        if (button) {
                            const icon = button.querySelector('i');
                            if (icon) {
                                icon.className = 'fas fa-' + (status == 1 ? 'ban' : 'check');
                            }
                        }
                        
                        // Show success message in modal
                        showResultModal(data.message, true);
                    } else {
                        showResultModal(data.message || 'Failed to update user status', false);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showResultModal('An error occurred while updating user status', false);
                });
        }

        function deleteUser(id) {
            // Show custom delete confirmation modal
            const modalElement = document.getElementById('confirmUserModal');
            const modalTitle = modalElement.querySelector('.modal-title');
            const modalMessage = document.getElementById('confirmUserMessage');
            const modalFooter = modalElement.querySelector('.modal-footer');
            
            // Update modal content for delete confirmation
            modalTitle.textContent = 'Confirm Deletion';
            modalMessage.textContent = 'Are you sure you want to delete this user? This action cannot be undone.';
            
            // Update footer buttons
            modalFooter.innerHTML = `
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" style="background: #f1f3f4; color: #202124; border: 1px solid #dadce0; border-radius: 8px; padding: 10px 24px; font-size: 14px; font-weight: 500; transition: all 0.2s ease;">Cancel</button>
                <button type="button" class="btn btn-danger" onclick="confirmDeleteUser(${id})" style="background: #dc3545; color: white; border: none; border-radius: 8px; padding: 10px 24px; font-size: 14px; font-weight: 500; transition: all 0.2s ease;">Delete</button>
            `;
            
            // Show modal
            if (typeof bootstrap !== 'undefined') {
                const modal = new bootstrap.Modal(modalElement);
                modal.show();
            }
        }

        function confirmDeleteUser(id) {
            // Hide modal
            if (typeof bootstrap !== 'undefined') {
                const modal = bootstrap.Modal.getInstance(document.getElementById('confirmUserModal'));
                if (modal) {
                    modal.hide();
                }
            }
            
            // Perform delete via AJAX
            fetch('api/users.php?action=delete&id=' + id + '&csrf_token=' + window.currentCSRFToken)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Remove the row from the table
                        const row = document.querySelector(`tr[data-user-id="${id}"]`);
                        if (row) {
                            row.remove();
                        }
                        showResultModal(data.message, true);
                    } else {
                        showResultModal(data.message || 'Failed to delete user', false);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showResultModal('An error occurred while deleting user', false);
                });
        }

        function showResultModal(message, isSuccess) {
            const modalElement = document.getElementById('confirmUserModal');
            const modalTitle = modalElement.querySelector('.modal-title');
            const modalMessage = document.getElementById('confirmUserMessage');
            const modalFooter = modalElement.querySelector('.modal-footer');
            
            // Update modal content
            modalTitle.textContent = isSuccess ? 'Success' : 'Error';
            modalMessage.textContent = message;
            
            // Update footer buttons
            modalFooter.innerHTML = `
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" onclick="location.reload()" style="background: #f1f3f4; color: #202124; border: 1px solid #dadce0; border-radius: 8px; padding: 10px 24px; font-size: 14px; font-weight: 500; transition: all 0.2s ease;">Close</button>
            `;
            
            // Show modal
            if (typeof bootstrap !== 'undefined') {
                const modal = new bootstrap.Modal(modalElement);
                modal.show();
            }
        }

        function confirmUserStatusChange() {
            const id = document.getElementById('confirmUserId').value;
            const status = document.getElementById('confirmUserStatus').value;
            
            // Perform AJAX request
            performToggleStatus(id, status);
        }
    </script>

    <!-- Confirmation Modal -->
    <div class="modal fade" id="confirmUserModal" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="border: none; border-radius: 24px; box-shadow: 0 24px 38px 3px rgba(0,0,0,0.14), 0 9px 46px 8px rgba(0,0,0,0.12);">
                <div class="modal-header" style="border: none; padding: 24px 32px 0 32px;">
                    <h5 class="modal-title" style="font-size: 22px; font-weight: 400; color: #202124;">Confirm Status Change</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" style="padding: 24px 32px;">
                    <p id="confirmUserMessage" style="font-size: 14px; color: #5f6368;"></p>
                    <input type="hidden" id="confirmUserId">
                    <input type="hidden" id="confirmUserStatus">
                </div>
                <div class="modal-footer" style="border: none; padding: 0 32px 32px 32px;">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" style="background: transparent; color: #5f6368; border: none; border-radius: 25px; padding: 10px 24px; font-size: 14px; font-weight: 500; letter-spacing: 0.25px; text-transform: uppercase;">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="confirmUserStatusChange()" style="background: #FF6B35; color: white; border: none; border-radius: 25px; padding: 10px 24px; font-size: 14px; font-weight: 500; letter-spacing: 0.25px; text-transform: uppercase;">Confirm</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer style="background: transparent; color: #5f6368; padding: 2rem; text-align: center; border-top: 1px solid #e8eaed; margin-top: 40px;">
        <p style="margin: 0;">
            <span style="color: #FF6B35;">&copy; 2026</span>
            <span style="color: #FF6B35;">Kenya</span>
            <span style="color: #008000;">EduHub</span>
            <span style="color: #5f6368;">. All rights reserved.</span>
        </p>
    </footer>
</body>
</html>
