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
$stmt = $conn->prepare("SELECT COUNT(*) as total, SUM(CASE WHEN is_verified = 1 THEN 1 ELSE 0 END) as active, SUM(CASE WHEN role = 'admin' THEN 1 ELSE 0 END) as admins FROM users");
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();
$total_users_count = $stats['total'];
$total_active_users = $stats['active'];
$total_admin_users = $stats['admins'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#FF6B35">
    <title>Users Management - Kenya EduHub</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <script>window.currentCSRFToken = "<?php echo $csrf_token; ?>";</script>
    <style>
        :root {
            --primary-color: #1a73e8;
            --secondary-color: #5f6368;
            --bg-color: #f8f9fa;
            --card-bg: #f8f9fa;
            --sidebar-width: 256px;
            --header-height: 64px;
            --primary-orange: #FF6B35;
            --primary-gold: #ffc107;
            --text-color: #202124;
            --border-color: #e8eaed;
            --form-border-color: #dadce0;
            --card-hover-bg: #f8f9fa;
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
            color: var(--text-color);
        }

        .sidebar {
            position: fixed;
            top: var(--header-height);
            left: 0;
            width: var(--sidebar-width);
            height: calc(100vh - var(--header-height));
            background: var(--bg-color);
            border-right: 1px solid var(--border-color);
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

        .nav-link {
            display: flex;
            align-items: center;
            padding: 10px 24px;
            color: var(--secondary-color);
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

        .nav-link.active i {
            color: var(--primary-color);
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
            color: var(--text-color);
            margin-bottom: 24px;
            text-align: center;
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

        .logo i {
            color: var(--primary-orange);
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
            background: var(--primary-orange);
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
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 24px;
            margin-bottom: 40px;
        }

        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr) !important;
                gap: 16px !important;
            }
        }

        .stat-card {
            background: var(--bg-color, #f8f9fa);
            border: 1px solid #e8eaed;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            box-shadow: none;
            transition: box-shadow 0.3s ease;
        }

        .stat-card:hover {
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .stat-card h3 {
            font-size: 32px;
            font-weight: 700;
            color: #202124;
            margin-bottom: 4px;
            text-align: center;
        }

        .stat-card p {
            font-size: 14px;
            color: #5f6368;
            margin: 0;
            font-weight: 500;
            text-align: center;
        }

        .stat-card i {
            color: var(--primary-orange) !important;
        }

        .card {
            background: var(--card-bg);
            border-radius: 8px;
            border: 1px solid var(--border-color);
            overflow: hidden;
            transition: background 0.3s ease, border-color 0.3s ease;
        }

        .card-header {
            background: transparent;
            padding: 20px 25px;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .card-header h2 {
            font-size: 20px;
            font-weight: 500;
            color: var(--text-color);
            text-align: center;
        }

        .btn {
            padding: 10px 24px;
            border: none;
            border-radius: 25px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }

        .btn-primary {
            background: var(--primary-orange);
            color: white;
        }

        .btn-primary:hover {
            background: #e55a2b;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(255, 107, 53, 0.4);
        }

        .btn-sm {
            padding: 6px 12px;
            font-size: 12px;
        }

        .btn-action {
            background: #f1f3f4;
            color: #202124;
            border: 1px solid #000;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-action:hover {
            background: var(--primary-orange);
            color: white;
            border-color: #000;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(255, 107, 53, 0.4);
        }

        .table-responsive {
            overflow-x: auto;
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

        input[type="text"] {
            padding: 8px 12px;
            border: 1px solid #e8eaed;
            border-radius: 25px;
            font-size: 14px;
            outline: none;
            transition: border-color 0.2s;
            background: var(--bg-color);
            color: var(--text-color);
        }

        input[type="text"]:focus {
            border-color: var(--primary-orange);
            box-shadow: 0 0 0 2px rgba(255, 107, 53, 0.2);
        }

        /* PDF-style table */
        .pdf-table {
            border: 2px solid #000;
            border-collapse: collapse;
            background: #ffffff;
            font-family: 'Times New Roman', Times, serif;
        }

        .pdf-table thead {
            background: #f0f0f0;
            border-bottom: 2px solid #000;
        }

        .pdf-table th {
            border: 1px solid #000;
            padding: 12px 15px;
            text-align: left;
            font-weight: bold;
            font-size: 14px;
            color: #000;
            background: #f0f0f0;
        }

        .pdf-table td {
            border: 1px solid #000;
            padding: 12px 15px;
            font-size: 12px;
            color: #000;
            font-family: 'Times New Roman', Times, serif;
        }

        .pdf-table tbody tr:nth-child(even) {
            background: #fafafa;
        }

        .pdf-table tbody tr:hover {
            background: #e8e8e8;
        }

        /* Dark mode PDF table */
        .dark-mode .pdf-table {
            border: 2px solid var(--primary-gold);
            background: #1a1a1a;
        }

        .dark-mode .pdf-table thead {
            background: #252525;
            border-bottom: 2px solid var(--primary-gold);
        }

        .dark-mode .pdf-table th {
            border: 1px solid #ffc107;
            color: #ffffff;
            background: #252525;
        }

        .dark-mode .pdf-table td {
            border: 1px solid #ffc107;
            color: #ffffff;
        }

        .dark-mode .pdf-table tbody tr:nth-child(even) {
            background: #2a2a2a;
        }

        .dark-mode .pdf-table tbody tr:hover {
            background: #3a3a3a;
        }
    </style>
</head>
<body class="dashboard-page">
    <!-- Header -->
    <?php require_once 'includes/header.php'; ?>
    
    <!-- Sidebar -->
    <?php 
    $active_page = 'users';
    require_once 'includes/sidebar.php'; 
    ?>
    
    <!-- Main Content -->
    <main class="main-content" id="mainContent">
        <h1 class="page-title">Users Management</h1>
        
        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div style="display: flex; flex-direction: column; align-items: center; gap: 8px;">
                    <h3><?php echo $total_users_count; ?></h3>
                    <p>Total Users</p>
                </div>
            </div>
            <div class="stat-card">
                <div style="display: flex; flex-direction: column; align-items: center; gap: 8px;">
                    <h3><?php echo $total_active_users; ?></h3>
                    <p>Active Users</p>
                </div>
            </div>
            <div class="stat-card">
                <div style="display: flex; flex-direction: column; align-items: center; gap: 8px;">
                    <h3><?php echo $total_users_count - $total_active_users; ?></h3>
                    <p>Inactive Users</p>
                </div>
            </div>
            <div class="stat-card">
                <div style="display: flex; flex-direction: column; align-items: center; gap: 8px;">
                    <h3><?php echo $total_admin_users; ?></h3>
                    <p>Admin Users</p>
                </div>
            </div>
        </div>

        <!-- Search and Filter Section -->
        <div class="card" style="margin-bottom: 24px;">
            <div class="card-header">
                <h2>Search & Filter</h2>
            </div>
            <div class="card-body">
                <form method="GET" style="display: flex; gap: 10px; flex-wrap: wrap; align-items: center;">
                    <input type="text" name="search" placeholder="Search users..."
                           value="<?php echo htmlspecialchars($search); ?>"
                           style="padding: 8px 12px; border: 1px solid #e8eaed; border-radius: 25px; flex: 1; min-width: 200px; background: var(--bg-color); color: var(--text-color);">
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
        </div>

        <!-- Users Table -->
        <div class="card">
            <div class="card-header">
                <h2>All Users</h2>
            </div>
            <div class="table-responsive">
                <table class="pdf-table">
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
            const formData = new FormData();
            formData.append('action', 'toggle_verification');
            formData.append('user_id', id);
            formData.append('csrf_token', window.currentCSRFToken);

            fetch('../api/users.php', {
                method: 'POST',
                body: formData
            })
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

            // Perform delete via AJAX using POST
            const formData = new FormData();
            formData.append('action', 'delete_user');
            formData.append('user_id', id);
            formData.append('csrf_token', window.currentCSRFToken);

            fetch('../api/users.php', {
                method: 'POST',
                body: formData
            })
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
    <?php require_once '../includes/copywrite.php'; ?>
</body>
</html>
