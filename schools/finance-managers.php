<?php
// Finance Managers Management Page
// Ensure session is started and config is loaded
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Load config if not already loaded
if (!isset($pdo)) {
    require_once __DIR__ . '/../config.php';
}

// Authentication check
if (!isset($_SESSION['school_id']) || !isset($_SESSION['school_token'])) {
    header('Location: index.php?route=login');
    exit;
}

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
    <meta name="theme-color" content="#FF6B35">
    <title>Finance Managers - <?php echo htmlspecialchars($school_name); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/notifications.css">
    <style>
        :root {
            --primary-color: #FF6B35;
            --secondary-color: #5f6368;
            --bg-color: #f8f9fa;
            --card-bg: #f8f9fa;
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
        
        .page-title {
            font-size: 22px;
            font-weight: 400;
            color: #202124;
            margin-bottom: 24px;
            text-align: center;
        }
        
        .page-subtitle {
            font-size: 14px;
            color: #5f6368;
            margin-bottom: 32px;
            text-align: center;
        }

        /* Main Content */
        .main-content {
            padding: 24px;
            margin-top: 20px;
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
            border: 1px solid #e0e0e0;
            border-radius: 25px;
            padding: 24px;
            margin: 0 0 24px 0;
        }

        .card-title {
            font-size: 18px;
            font-weight: 500;
            color: #202124;
            margin-bottom: 16px;
            text-align: center;
        }

        .form-control {
            border: 1px solid #dadce0;
            border-radius: 25px;
            padding: 10px 12px;
            font-size: 14px;
            background: var(--bg-color);
        }

        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 2px rgba(255, 107, 53, 0.2);
            background: var(--bg-color);
        }
        
        .btn {
            padding: 4px 8px;
            border-radius: 25px;
            font-size: 11px;
            font-weight: 500;
            border: none;
            cursor: pointer;
            transition: background 0.2s;
        }
        
        .btn-primary {
            background: #FF6B35;
            color: white;
        }
        
        .btn-primary:hover {
            background: #e55a2b;
        }

        .action-buttons-cell {
            display: flex;
            gap: 4px;
        }

        /* Action Buttons */
        .action-buttons {
            display: flex;
            gap: 12px;
            margin-bottom: 24px;
        }

        .action-buttons .btn {
            flex: 1;
            padding: 12px 24px;
            border-radius: 25px;
        }

        @media (max-width: 768px) {
            .action-buttons {
                flex-direction: column;
            }
        }

        /* Filter Section */
        .filter-section {
            background: var(--bg-color);
            border-radius: 25px;
            padding: 24px;
            margin: 0 0 24px 0;
            border: 1px solid #e0e0e0;
        }

        .filter-section .card-title {
            font-size: 18px;
            font-weight: 500;
            color: #202124;
            margin-bottom: 16px;
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
            border: 2px solid #000;
            margin: 0;
            font-family: 'Times New Roman', Times, serif;
        }

        .table-responsive {
            width: 100%;
            overflow-x: auto;
            margin: 0;
            padding: 0;
        }

        .table thead {
            background: #f0f0f0;
            border-bottom: 2px solid #000;
        }

        .table thead th {
            background: #f0f0f0;
            border: 2px solid #000;
            border-bottom: 2px solid #000;
            padding: 12px;
            font-weight: bold;
            color: #000;
            white-space: nowrap;
        }

        .table td {
            padding: 12px;
            font-size: 13px;
            color: #000;
            border: 2px solid #000;
        }

        .table tbody tr:nth-child(even) {
            background: #fafafa;
        }

        .table tbody tr:hover {
            background: #f8f9fa;
        }

        .btn-action {
            background: transparent;
            color: #FF6B35;
            border: none;
            cursor: pointer;
            margin-right: 4px;
            padding: 0;
        }

        .btn-action:hover {
            color: #e55a2b;
        }

        .action-buttons-cell {
            display: flex;
            gap: 4px;
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
            .main-content {
                padding: 0;
                width: 100%;
            }

            .page-title {
                font-size: 18px;
                padding: 16px;
            }

            .card {
                padding: 16px;
                margin: 0 4px 16px 4px;
            }

            .filter-section {
                padding: 16px;
                margin: 0 4px 16px 4px;
            }

            .action-buttons {
                padding: 16px;
                margin: 0 4px 16px 4px;
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
                margin-bottom: 12px;
            }

            .btn {
                width: 100% !important;
                padding: 10px 20px;
                font-size: 14px;
            }

            .table-responsive {
                overflow-x: auto;
                width: 100%;
                -webkit-overflow-scrolling: touch;
            }

            .table {
                font-size: 11px;
                min-width: 100%;
            }

            .table thead th {
                padding: 8px 4px;
                font-size: 11px;
            }

            .table td {
                padding: 8px 4px;
                font-size: 11px;
            }

            .quick-access-grid {
                display: grid !important;
                grid-template-columns: repeat(3, 1fr) !important;
                gap: 8px !important;
            }

            .quick-access-item {
                padding: 12px !important;
            }
        }

        /* Icon colors */
        .quick-access-item:nth-child(1) .quick-access-icon { background: #e8f0fe; color: #4285F4; }
        .quick-access-item:nth-child(2) .quick-access-icon { background: #fce8e6; color: #EA4335; }
        .quick-access-item:nth-child(3) .quick-access-icon { background: #e6f4ea; color: #00AC47; }
        .quick-access-item:nth-child(4) .quick-access-icon { background: #fef7e0; color: #FFBA00; }
        .quick-access-item:nth-child(5) .quick-access-icon { background: #e8f0fe; color: #0066DA; }
        .quick-access-item:nth-child(6) .quick-access-icon { background: #fce8e6; color: #4285F4; }
        .fa-plus { color: #00AC47; }
        .fa-download { color: #4285F4; }
        .fa-edit { color: #0066DA; }
        .fa-trash { color: #EA4335; }
    </style>
</head>
<body class="dashboard-page">
    <?php require_once 'includes/header.php'; ?>
    </br>
    </br>
    </br>
    <?php $active_page = 'finance-managers'; require_once 'includes/sidebar.php'; ?>
    
    <br>
    
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
                        <input type="text" class="form-control" name="first_name" placeholder="Enter first name" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Last Name</label>
                        <input type="text" class="form-control" name="last_name" placeholder="Enter last name" required>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-control" name="email" placeholder="Enter email address" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Phone</label>
                        <input type="text" class="form-control" name="phone" placeholder="Enter phone number" required>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">ID Number</label>
                        <input type="text" class="form-control" name="id_number" placeholder="Enter ID number" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Password</label>
                        <input type="password" class="form-control" name="password" placeholder="Enter password" required minlength="8">
                        <small class="text-muted">Minimum 8 characters</small>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Address</label>
                    <textarea class="form-control" name="address" rows="3" placeholder="Enter address"></textarea>
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
                                        <div class="action-buttons-cell">
                                            <form method="POST" style="display: inline; margin: 0;">
                                                <input type="hidden" name="finance_manager_id" value="<?php echo $fm['id']; ?>">
                                                <input type="hidden" name="update_status" value="1">
                                                <input type="hidden" name="status" value="<?php echo $fm['status'] === 'active' ? 'inactive' : 'active'; ?>">
                                                <button type="submit" class="btn-action">
                                                    <i class="fas <?php echo $fm['status'] === 'active' ? 'fa-ban' : 'fa-check'; ?>"></i>
                                                </button>
                                            </form>
                                            <form method="POST" style="display: inline; margin: 0;" onsubmit="return confirm('Are you sure you want to delete this finance manager?');">
                                                <input type="hidden" name="finance_manager_id" value="<?php echo $fm['id']; ?>">
                                                <input type="hidden" name="delete_finance_manager" value="1">
                                                <button type="submit" class="btn-action">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
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
    <script src="../assets/js/notifications.js"></script>
</br>
</br>
</br>
</br>
    <?php require_once '../includes/copywrite.php'; ?>
</body>
</html>
