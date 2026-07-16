<?php
// Finance Managers Management Page
// Authentication is handled by index.php router
$school_id = $_SESSION['school_id'];
$school_name = $_SESSION['school_name'] ?? 'School';

// Handle finance manager creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_finance_manager'])) {
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $id_number = trim($_POST['id_number'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $password = trim($_POST['password'] ?? '');
    
    $errors = [];
    if (empty($first_name)) $errors[] = 'First name is required';
    if (empty($last_name)) $errors[] = 'Last name is required';
    if (empty($email)) $errors[] = 'Email is required';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Invalid email format';
    if (empty($phone)) $errors[] = 'Phone is required';
    if (empty($id_number)) $errors[] = 'ID number is required';
    if (empty($password)) $errors[] = 'Password is required';
    if (strlen($password) < 8) $errors[] = 'Password must be at least 8 characters';
    
    if (empty($errors)) {
        try {
            $pdo->beginTransaction();
            
            // Check if email already exists in finance_managers table
            $stmt = $pdo->prepare("SELECT id FROM finance_managers WHERE email = ? AND school_id = ?");
            $stmt->execute([$email, $school_id]);
            if ($stmt->fetch()) {
                $errors[] = 'Email already exists for this school';
            } else {
                // Insert finance manager
                $stmt = $pdo->prepare("INSERT INTO finance_managers (school_id, first_name, last_name, email, phone, id_number, address) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$school_id, $first_name, $last_name, $email, $phone, $id_number, $address]);
                $finance_manager_id = $pdo->lastInsertId();
                
                // Create login credentials
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO finance_manager_logins (finance_manager_id, email, password) VALUES (?, ?, ?)");
                $stmt->execute([$finance_manager_id, $email, $hashed_password]);
                
                $pdo->commit();
                $success = 'Finance manager added successfully!';
            }
        } catch (PDOException $e) {
            $pdo->rollBack();
            error_log("Failed to add finance manager: " . $e->getMessage());
            $errors[] = 'Failed to add finance manager. Please try again.';
        }
    }
}

// Handle finance manager deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_finance_manager'])) {
    $finance_manager_id = $_POST['finance_manager_id'] ?? '';
    
    try {
        $stmt = $pdo->prepare("DELETE FROM finance_managers WHERE id = ? AND school_id = ?");
        $stmt->execute([$finance_manager_id, $school_id]);
        $success = 'Finance manager deleted successfully!';
    } catch (PDOException $e) {
        error_log("Failed to delete finance manager: " . $e->getMessage());
        $errors[] = 'Failed to delete finance manager. Please try again.';
    }
}

// Handle finance manager status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $finance_manager_id = $_POST['finance_manager_id'] ?? '';
    $status = $_POST['status'] ?? '';
    
    try {
        $stmt = $pdo->prepare("UPDATE finance_managers SET status = ? WHERE id = ? AND school_id = ?");
        $stmt->execute([$status, $finance_manager_id, $school_id]);
        $success = 'Finance manager status updated successfully!';
    } catch (PDOException $e) {
        error_log("Failed to update finance manager status: " . $e->getMessage());
        $errors[] = 'Failed to update status. Please try again.';
    }
}

// Get finance managers
$finance_managers = [];
try {
    $stmt = $pdo->prepare("SELECT fm.*, fl.is_active FROM finance_managers fm LEFT JOIN finance_manager_logins fl ON fm.id = fl.finance_manager_id WHERE fm.school_id = ? ORDER BY fm.created_at DESC");
    $stmt->execute([$school_id]);
    $finance_managers = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Failed to fetch finance managers: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Finance Managers - <?php echo htmlspecialchars($school_name); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/notifications.css">
    <style>
        :root {
            --primary-color: #FF6B35;
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
        
        /* Header */
        .header {
            position: fixed !important;
            top: 0;
            left: 0;
            right: 0;
            height: var(--header-height);
            background: var(--bg-color);
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
            font-size: 20px;
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
        
        .school-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: var(--primary-color);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 500;
        }
        
        /* Sidebar */
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
            scrollbar-width: none; /* Firefox */
            -ms-overflow-style: none; /* IE and Edge */
        }
        
        .sidebar::-webkit-scrollbar {
            display: none; /* Chrome, Safari, Opera */
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
        
        /* Main Content */
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
        
        .page-subtitle {
            font-size: 14px;
            color: #5f6368;
            margin-bottom: 32px;
        }
        
        .row {
            display: flex;
            flex-wrap: wrap;
            margin: 0 -12px;
        }
        
        .col-md-3,
        .col-md-4,
        .col-md-6 {
            flex: 1;
            padding: 0 12px;
        }
        
        .col-md-3 {
            max-width: 25%;
        }
        
        .col-md-4 {
            max-width: 33.333%;
        }
        
        .col-md-6 {
            max-width: 50%;
        }
        
        .mb-3 {
            margin-bottom: 16px;
        }
        
        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #5f6368;
        }
        
        /* Cards */
        .card {
            background: var(--bg-color);
            border: 1px solid #e8eaed;
            border-radius: 8px;
            padding: 24px;
            margin-bottom: 24px;
        }
        
        .card-title {
            font-size: 18px;
            font-weight: 500;
            color: #202124;
            margin-bottom: 16px;
        }
        
        .form-control {
            border: 1px solid #dadce0;
            border-radius: 8px;
            padding: 10px 12px;
            font-size: 14px;
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 2px rgba(255, 107, 53, 0.2);
        }
        
        .btn {
            padding: 10px 24px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            border: none;
            cursor: pointer;
            transition: background 0.2s;
        }
        
        .btn-primary {
            background: var(--primary-color);
            color: white;
        }
        
        .btn-primary:hover {
            background: #e55a2b;
        }
        
        .btn-danger {
            background: #c5221f;
            color: white;
        }
        
        .btn-danger:hover {
            background: #b3261e;
        }
        
        .table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border: 1px solid #000;
            margin: 0;
        }
        
        .table-responsive {
            width: 100%;
            overflow-x: auto;
        }
        
        .table thead {
            background: #f0f0f0;
            border-bottom: 2px solid #000;
        }
        
        .table th {
            text-align: left;
            padding: 12px;
            font-size: 13px;
            font-weight: 600;
            color: #000;
            border: 1px solid #000;
            border-bottom: 2px solid #000;
        }
        
        .table td {
            padding: 12px;
            font-size: 13px;
            color: #000;
            border: 1px solid #000;
        }
        
        .table tbody tr:nth-child(even) {
            background: #f9f9f9;
        }
        
        .table tbody tr:hover {
            background: #f0f0f0;
        }
        
        .badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .badge-success {
            background: #e8f5e9;
            color: #137333;
        }
        
        .badge-danger {
            background: #ffebee;
            color: #c5221f;
        }
        
        .alert {
            padding: 12px 16px;
            border-radius: 4px;
            margin-bottom: 16px;
        }
        
        .alert-success {
            background: #e8f5e9;
            color: #1e8e3e;
            border: 1px solid #c8e6c9;
        }
        
        .alert-danger {
            background: #ffebee;
            color: #c62828;
            border: 1px solid #ffcdd2;
        }
        
        /* Responsive */
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
            }
            
            .card {
                text-align: left;
            }
            
            .card form {
                text-align: left;
            }
            
            .row {
                flex-direction: column;
            }
            
            .col-md-3,
            .col-md-6,
            .col-md-4 {
                width: 100%;
                max-width: 100%;
                margin-bottom: 12px;
            }
            
            .form-control {
                border-radius: 8px;
            }
            
            .btn {
                width: 100%;
                border-radius: 8px;
                padding: 12px 16px;
                font-size: 16px;
            }
            
            .table-responsive {
                overflow-x: auto;
                width: 100%;
                -webkit-overflow-scrolling: touch;
            }
            
            .table {
                font-size: 12px;
                min-width: 600px;
            }
            
            .table th,
            .table td {
                padding: 8px 10px;
            }
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
            <div class="school-avatar">
                <?php echo strtoupper(substr($school_name, 0, 1)); ?>
            </div>
        </div>
    </header>
    
    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-section">
            <div class="sidebar-title">Main</div>
            <a class="nav-link" href="dashboard">
                <i class="fas fa-home"></i> Dashboard
            </a>
            <a class="nav-link" href="students">
                <i class="fas fa-user-graduate"></i> Students
            </a>
            <a class="nav-link" href="teachers">
                <i class="fas fa-chalkboard-teacher"></i> Teachers
            </a>
            <a class="nav-link" href="parents">
                <i class="fas fa-users"></i> Parents
            </a>
            <a class="nav-link" href="classes">
                <i class="fas fa-school"></i> Classes
            </a>
            <a class="nav-link" href="streams">
                <i class="fas fa-stream"></i> Streams
            </a>
            <a class="nav-link" href="subjects">
                <i class="fas fa-book"></i> Subjects
            </a>
            <a class="nav-link active" href="finance-managers">
                <i class="fas fa-money-bill-wave"></i> Finance Managers
            </a>
        </div>
        <div class="sidebar-section">
            <div class="sidebar-title">Academics</div>
            <a class="nav-link" href="attendance">
                <i class="fas fa-calendar-check"></i> Attendance
            </a>
            <a class="nav-link" href="performance">
                <i class="fas fa-chart-line"></i> Performance
            </a>
            <a class="nav-link" href="grading">
                <i class="fas fa-graduation-cap"></i> Grading
            </a>
        </div>
        <div class="sidebar-section">
            <div class="sidebar-title">Finance</div>
            <a class="nav-link" href="fees">
                <i class="fas fa-file-invoice-dollar"></i> Fees
            </a>
        </div>
        <div class="sidebar-section">
            <div class="sidebar-title">Settings</div>
            <a class="nav-link" href="settings">
                <i class="fas fa-cog"></i> Settings
            </a>
            <a class="nav-link" href="logout">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </aside>
    
    <!-- Main Content -->
    <main class="main-content" id="mainContent">
        <h1 class="page-title">Finance Managers</h1>
        <p class="page-subtitle">Manage your school finance managers</p>
        
        <?php if (isset($success)): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <?php foreach ($errors as $error): ?>
                    <?php echo htmlspecialchars($error); ?><br>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <!-- Add Finance Manager Card -->
        <div class="card">
            <h2 class="card-title">Add New Finance Manager</h2>
            <form method="POST">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">First Name</label>
                        <input type="text" class="form-control" name="first_name" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Last Name</label>
                        <input type="text" class="form-control" name="last_name" required>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-control" name="email" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Phone</label>
                        <input type="text" class="form-control" name="phone" required>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">ID Number</label>
                        <input type="text" class="form-control" name="id_number" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Password</label>
                        <input type="password" class="form-control" name="password" required minlength="8">
                        <small class="text-muted">Minimum 8 characters</small>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Address</label>
                    <textarea class="form-control" name="address" rows="3"></textarea>
                </div>
                <button type="submit" name="add_finance_manager" class="btn btn-primary">
                    <i class="fas fa-plus me-2"></i> Add Finance Manager
                </button>
            </form>
        </div>
        
        <!-- Finance Managers List -->
        <div class="card">
            <h2 class="card-title">All Finance Managers</h2>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>ID Number</th>
                            <th>Status</th>
                            <th>Login Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($finance_managers)): ?>
                            <tr>
                                <td colspan="7" class="text-center">No finance managers found</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($finance_managers as $fm): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($fm['first_name'] . ' ' . $fm['last_name']); ?></td>
                                    <td><?php echo htmlspecialchars($fm['email']); ?></td>
                                    <td><?php echo htmlspecialchars($fm['phone']); ?></td>
                                    <td><?php echo htmlspecialchars($fm['id_number']); ?></td>
                                    <td>
                                        <span class="badge <?php echo $fm['status'] === 'active' ? 'badge-success' : 'badge-danger'; ?>">
                                            <?php echo ucfirst($fm['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge <?php echo ($fm['is_active'] ?? 0) ? 'badge-success' : 'badge-danger'; ?>">
                                            <?php echo ($fm['is_active'] ?? 0) ? 'Active' : 'Inactive'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="finance_manager_id" value="<?php echo $fm['id']; ?>">
                                            <input type="hidden" name="update_status" value="1">
                                            <input type="hidden" name="status" value="<?php echo $fm['status'] === 'active' ? 'inactive' : 'active'; ?>">
                                            <button type="submit" class="btn btn-sm <?php echo $fm['status'] === 'active' ? 'btn-danger' : 'btn-primary'; ?>">
                                                <i class="fas <?php echo $fm['status'] === 'active' ? 'fa-ban' : 'fa-check'; ?>"></i>
                                                <?php echo $fm['status'] === 'active' ? 'Deactivate' : 'Activate'; ?>
                                            </button>
                                        </form>
                                        <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this finance manager?');">
                                            <input type="hidden" name="finance_manager_id" value="<?php echo $fm['id']; ?>">
                                            <input type="hidden" name="delete_finance_manager" value="1">
                                            <button type="submit" class="btn btn-sm btn-danger">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const mainContent = document.getElementById('mainContent');
            sidebar.classList.toggle('collapsed');
            sidebar.classList.toggle('show');
            mainContent.classList.toggle('expanded');
        }
    </script>
    <script src="../assets/js/notifications.js"></script>
    
    <!-- Footer -->
    <footer style="background: transparent; color: white; padding: 2rem; text-align: center; border-top: 1px solid rgba(255, 255, 255, 0.1);">
        <p style="margin: 0;">
            <span style="color: #FF6B35;">&copy; 2026</span> 
            <span style="color: #FF6B35;">Kenya</span> 
            <span style="color: #008000;">EduHub</span>
            <span style="color: #008000;">. All rights reserved.</span>
        </p>
    </footer>
</body>
</html>
