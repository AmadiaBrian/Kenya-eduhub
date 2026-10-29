<?php
// Librarians Management Page
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
    <meta name="theme-color" content="#FF6B35">
    <title>Librarians - <?php echo htmlspecialchars($school_name); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/notifications.css">
    <style>
        :root {
            --primary-color: #FF6B35;
            --secondary-color: #5f6368;
            --bg-color: #f8f9fa;
            --card-bg: #ffffff;
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

        .main-content {
            padding: 24px;
            padding-bottom: 80px;
            min-height: 100vh;
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
            border: 1px solid #e0e0e0;
            border-radius: 25px;
            box-shadow: none;
            padding: 24px;
            margin: 0 0 24px 0;
        }

        .card-title {
            font-size: 18px;
            font-weight: 500;
            margin-bottom: 16px;
            color: #202124;
            text-align: center;
        }
        
        /* Table */
        .table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border: 2px solid #000;
            margin: 0;
            font-family: 'Times New Roman', Times, serif;
        }

        .table thead {
            background: #f0f0f0;
            border-bottom: 2px solid #000;
        }

        .table th {
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

        .table-responsive {
            width: 100%;
            overflow-x: auto;
            margin: 0;
            padding: 0;
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

        /* Buttons */
        .btn {
            padding: 10px 24px;
            border-radius: 25px;
            font-size: 14px;
            font-weight: 500;
            border: none;
            cursor: pointer;
            transition: background 0.2s;
        }

        .btn {
            margin-right: 8px;
        }

        .btn:last-child {
            margin-right: 0;
        }

        .btn-primary {
            background: #FF6B35;
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
            background: #3c4043;
        }

        .btn-danger {
            background: #c5221f;
            color: white;
        }

        .btn-danger:hover {
            background: #a91c1a;
        }

        .table td form {
            display: inline;
        }

        .table td form button {
            margin-right: 4px;
        }

        .table td form button:last-child {
            margin-right: 0;
        }

        .table td:last-child {
            white-space: nowrap;
        }
        
        /* Form */
        .form-control {
            width: 100%;
            padding: 10px 12px;
            font-size: 14px;
            border: 1px solid #dadce0;
            border-radius: 20px;
            outline: none;
            transition: border-color 0.2s, box-shadow 0.2s;
            background: var(--bg-color);
        }

        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 2px rgba(26, 115, 232, 0.2);
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
            border: 1px solid rgba(19, 115, 51, 0.1);
        }

        .alert-danger {
            background: #fce8e6;
            color: #c5221f;
            border: 1px solid rgba(197, 34, 31, 0.1);
        }

        /* Footer styling */
        .kenya-eduhub-footer-unique-2026 {
            background: var(--bg-color) !important;
        }

        /* Mobile */
        @media (max-width: 768px) {
            .main-content {
                padding: 0;
                padding-bottom: 80px;
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
                border-radius: 25px;
            }

            .btn {
                width: 100% !important;
                border-radius: 25px;
                padding: 12px 16px;
                font-size: 16px;
            }

            .table-responsive {
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
                width: 100%;
            }

            .table {
                font-size: 11px;
                min-width: 100%;
            }

            .table th,
            .table td {
                padding: 8px 4px;
            }

            .table td:last-child {
                white-space: nowrap !important;
            }

            .table td form {
                display: inline !important;
            }

            .table td form button {
                margin-right: 2px !important;
                padding: 2px 5px !important;
                font-size: 8px !important;
                border-radius: 6px !important;
                line-height: 1 !important;
                min-width: auto !important;
                width: auto !important;
            }

            .table td form button i {
                font-size: 10px !important;
            }

            .table td form button:last-child {
                margin-right: 0 !important;
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
<?php $active_page = 'librarians'; require_once 'includes/sidebar.php'; ?>

<br>

<!-- Main Content -->
    <main class="main-content" id="mainContent">
        <h1 class="page-title">Librarians</h1>

        <p class="page-subtitle" style="text-align: center; color: #5f6368; margin-bottom: 32px;">Manage your school librarians</p>
        
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
                        <input type="text" class="form-control" name="first_name" placeholder="Enter first name" required>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Last Name</label>
                        <input type="text" class="form-control" name="last_name" placeholder="Enter last name" required>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-control" name="email" placeholder="Enter email address" required>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Phone</label>
                        <input type="text" class="form-control" name="phone" placeholder="Enter phone number" required>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Password</label>
                        <input type="password" class="form-control" name="password" placeholder="Enter password (min 8 characters)" required minlength="8">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">&nbsp;</label>
                        <button type="submit" name="add_librarian" class="btn btn-primary">
                            <span class="material-icons" style="vertical-align: middle; margin-right: 8px;">add</span> Add Librarian
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
                                            <button type="submit" name="update_status" class="btn-action" title="<?php echo $librarian['status'] === 'active' ? 'Deactivate' : 'Activate'; ?>">
                                                <span class="material-icons"><?php echo $librarian['status'] === 'active' ? 'toggle_on' : 'toggle_off'; ?></span>
                                            </button>
                                        </form>
                                        <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this librarian?');">
                                            <input type="hidden" name="librarian_id" value="<?php echo $librarian['id']; ?>">
                                            <button type="submit" name="delete_librarian" class="btn-action" title="Delete">
                                                <span class="material-icons" style="color: #d93025;">delete</span>
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
    <script src="../assets/js/notifications.js"></script>

<br>
<br>
<?php require_once '../includes/copywrite.php'; ?>
</body>
</html>
