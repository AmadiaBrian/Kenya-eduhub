<?php
// Librarians Management Page
// Authentication is handled by index.php router
$school_id = $_SESSION['school_id'];
$school_name = $_SESSION['school_name'] ?? 'School';

// Handle librarian creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_librarian'])) {
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = trim($_POST['password'] ?? '');
    
    $errors = [];
    if (empty($first_name)) $errors[] = 'First name is required';
    if (empty($last_name)) $errors[] = 'Last name is required';
    if (empty($email)) $errors[] = 'Email is required';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Invalid email format';
    if (empty($phone)) $errors[] = 'Phone is required';
    if (empty($password)) $errors[] = 'Password is required';
    if (strlen($password) < 8) $errors[] = 'Password must be at least 8 characters';
    
    if (empty($errors)) {
        try {
            $pdo->beginTransaction();
            
            // Check if email already exists in librarians table
            $stmt = $pdo->prepare("SELECT id FROM librarians WHERE email = ? AND school_id = ?");
            $stmt->execute([$email, $school_id]);
            if ($stmt->fetch()) {
                $errors[] = 'Email already exists for this school';
            } else {
                // Insert librarian
                $stmt = $pdo->prepare("INSERT INTO librarians (school_id, first_name, last_name, email, phone) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$school_id, $first_name, $last_name, $email, $phone]);
                $librarian_id = $pdo->lastInsertId();
                
                // Create login credentials
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO librarian_logins (librarian_id, email, password) VALUES (?, ?, ?)");
                $stmt->execute([$librarian_id, $email, $hashed_password]);
                
                $pdo->commit();
                $success = 'Librarian added successfully!';
            }
        } catch (PDOException $e) {
            $pdo->rollBack();
            error_log("Failed to add librarian: " . $e->getMessage());
            $errors[] = 'Failed to add librarian. Please try again.';
        }
    }
}

// Handle librarian deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_librarian'])) {
    $librarian_id = $_POST['librarian_id'] ?? '';
    
    try {
        $stmt = $pdo->prepare("DELETE FROM librarians WHERE id = ? AND school_id = ?");
        $stmt->execute([$librarian_id, $school_id]);
        $success = 'Librarian deleted successfully!';
    } catch (PDOException $e) {
        error_log("Failed to delete librarian: " . $e->getMessage());
        $errors[] = 'Failed to delete librarian. Please try again.';
    }
}

// Handle librarian status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $librarian_id = $_POST['librarian_id'] ?? '';
    $status = $_POST['status'] ?? '';
    
    try {
        $stmt = $pdo->prepare("UPDATE librarians SET status = ? WHERE id = ? AND school_id = ?");
        $stmt->execute([$status, $librarian_id, $school_id]);
        $success = 'Librarian status updated successfully!';
    } catch (PDOException $e) {
        error_log("Failed to update librarian status: " . $e->getMessage());
        $errors[] = 'Failed to update status. Please try again.';
    }
}

// Get librarians
$librarians = [];
try {
    $stmt = $pdo->prepare("SELECT l.*, ll.is_active FROM librarians l LEFT JOIN librarian_logins ll ON l.id = ll.librarian_id WHERE l.school_id = ? ORDER BY l.created_at DESC");
    $stmt->execute([$school_id]);
    $librarians = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Failed to fetch librarians: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Librarians - <?php echo htmlspecialchars($school_name); ?></title>
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
            padding: 8px;
            border-radius: 50%;
            transition: background 0.2s;
        }
        
        .menu-btn:hover {
            background: rgba(95, 99, 104, 0.1);
        }
        
        .menu-btn i {
            font-size: 20px;
            color: var(--primary-color);
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
            font-size: 14px;
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
            transition: margin-left 0.3s;
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
        .col-md-6 {
            flex: 1;
            padding: 0 12px;
        }
        
        .col-md-3 {
            max-width: 25%;
        }
        
        .col-md-6 {
            max-width: 50%;
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
            margin-bottom: 16px;
            color: #202124;
        }
        
        /* Table */
        .table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        .table th,
        .table td {
            padding: 12px 16px;
            text-align: left;
            border: 1px solid #000;
            border-bottom: 1px solid #000;
            border-right: 1px solid #000;
        }
        
        .table th {
            background: #f0f0f0;
            font-weight: 600;
            color: #000;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-top: 2px solid #000;
        }
        
        .table th:last-child {
            border-right: 2px solid #000;
        }
        
        .table td:last-child {
            border-right: 2px solid #000;
        }
        
        .table tbody tr:last-child td {
            border-bottom: 2px solid #000;
        }
        
        .table tbody tr:hover {
            background: #f9f9f9;
        }
        
        /* Buttons */
        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            transition: background 0.2s;
        }
        
        .btn-primary {
            background: var(--primary-color);
            color: white;
        }
        
        .btn-primary:hover {
            background: #e55a2b;
        }
        
        .btn-secondary {
            background: var(--secondary-color);
            color: white;
        }
        
        .btn-secondary:hover {
            background: #4a4e54;
        }
        
        .btn-danger {
            background: #c5221f;
            color: white;
        }
        
        .btn-danger:hover {
            background: #a91c1a;
        }
        
        .btn-sm {
            padding: 4px 8px;
            font-size: 12px;
        }
        
        /* Form */
        .form-control {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #e8eaed;
            border-radius: 8px;
            font-size: 14px;
        }
        
        .form-control:focus {
            outline: none;
            border-color: var(--primary-color);
        }
        
        .form-label {
            display: block;
            margin-bottom: 4px;
            font-weight: 500;
            color: var(--secondary-color);
        }
        
        .mb-3 {
            margin-bottom: 16px;
        }
        
        /* Alerts */
        .alert {
            padding: 12px 16px;
            border-radius: 4px;
            margin-bottom: 16px;
        }
        
        .alert-success {
            background: #e6f4ea;
            color: #137333;
            border: 1px solid #ceead6;
        }
        
        .alert-danger {
            background: #fce8e6;
            color: #c5221f;
            border: 1px solid #fad2cf;
        }
        
        /* Mobile */
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
            .col-md-6 {
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
            <a href="dashboard" class="nav-link">
                <i class="fas fa-home"></i>
                <span>Dashboard</span>
            </a>
            <a href="students" class="nav-link">
                <i class="fas fa-user-graduate"></i>
                <span>Students</span>
            </a>
            <a href="teachers" class="nav-link">
                <i class="fas fa-chalkboard-teacher"></i>
                <span>Teachers</span>
            </a>
            <a href="classes" class="nav-link">
                <i class="fas fa-chalkboard"></i>
                <span>Classes</span>
            </a>
            <a href="attendance" class="nav-link">
                <i class="fas fa-clipboard-check"></i>
                <span>Attendance</span>
            </a>
            <a href="fees" class="nav-link">
                <i class="fas fa-money-bill-wave"></i>
                <span>Fees</span>
            </a>
        </div>
        <div class="sidebar-section">
            <div class="sidebar-title">Management</div>
            <a href="finance-managers" class="nav-link">
                <i class="fas fa-calculator"></i>
                <span>Finance Managers</span>
            </a>
            <a href="librarians" class="nav-link active">
                <i class="fas fa-book"></i>
                <span>Librarians</span>
            </a>
        </div>
        <div class="sidebar-section">
            <div class="sidebar-title">Account</div>
            <a href="settings" class="nav-link">
                <i class="fas fa-cog"></i>
                <span>Settings</span>
            </a>
            <a href="logout" class="nav-link">
                <i class="fas fa-sign-out-alt"></i>
                <span>Logout</span>
            </a>
        </div>
    </aside>
    
    <!-- Main Content -->
    <main class="main-content" id="mainContent">
        <h1 class="page-title">Librarians</h1>
        <p class="page-subtitle">Manage your school librarians</p>
        
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
        
        <!-- Add Librarian -->
        <div class="card">
            <h2 class="card-title">Add Librarian</h2>
            <form method="POST">
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <label class="form-label">First Name</label>
                        <input type="text" class="form-control" name="first_name" required>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Last Name</label>
                        <input type="text" class="form-control" name="last_name" required>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-control" name="email" required>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Phone</label>
                        <input type="text" class="form-control" name="phone" required>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Password</label>
                        <input type="password" class="form-control" name="password" required minlength="8">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">&nbsp;</label>
                        <button type="submit" name="add_librarian" class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i> Add Librarian
                        </button>
                    </div>
                </div>
            </form>
        </div>
        
        <!-- Librarians List -->
        <div class="card">
            <h2 class="card-title">Librarians</h2>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Status</th>
                            <th>Login Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($librarians)): ?>
                            <tr>
                                <td colspan="6" class="text-center">No librarians found</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($librarians as $librarian): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($librarian['first_name'] . ' ' . $librarian['last_name']); ?></td>
                                    <td><?php echo htmlspecialchars($librarian['email']); ?></td>
                                    <td><?php echo htmlspecialchars($librarian['phone']); ?></td>
                                    <td>
                                        <span style="color: <?php echo $librarian['status'] === 'active' ? '#137333' : '#c5221f'; ?>; font-weight: 500;">
                                            <?php echo ucfirst($librarian['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span style="color: <?php echo $librarian['is_active'] ? '#137333' : '#c5221f'; ?>; font-weight: 500;">
                                            <?php echo $librarian['is_active'] ? 'Active' : 'Inactive'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="librarian_id" value="<?php echo $librarian['id']; ?>">
                                            <input type="hidden" name="status" value="<?php echo $librarian['status'] === 'active' ? 'inactive' : 'active'; ?>">
                                            <button type="submit" name="update_status" class="btn btn-sm btn-secondary">
                                                <?php echo $librarian['status'] === 'active' ? 'Deactivate' : 'Activate'; ?>
                                            </button>
                                        </form>
                                        <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this librarian?');">
                                            <input type="hidden" name="librarian_id" value="<?php echo $librarian['id']; ?>">
                                            <button type="submit" name="delete_librarian" class="btn btn-sm btn-danger">
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
</body>
</html>
