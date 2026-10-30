<?php
// Schools Examination Department Heads Management
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

$error = '';
$success = '';

// Handle form submission (add new examination head)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    
    $errors = [];
    if (empty($name)) $errors[] = 'Name is required';
    if (empty($email)) $errors[] = 'Email is required';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Invalid email format';
    if (empty($phone)) $errors[] = 'Phone is required';
    if (empty($password)) $errors[] = 'Password is required';
    if (strlen($password) < 8) $errors[] = 'Password must be at least 8 characters';
    
    if (empty($errors)) {
        try {
            // Check if email already exists for this school
            $stmt = $pdo->prepare("SELECT id FROM examination_department_heads WHERE school_id = ? AND email = ?");
            $stmt->execute([$school_id, $email]);
            if ($stmt->fetch()) {
                $errors[] = 'Email already exists for this school';
            }
        } catch (Exception $e) {
            error_log("Error checking email: " . $e->getMessage());
        }
    }
    
    if (empty($errors)) {
        try {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            $stmt = $pdo->prepare("
                INSERT INTO examination_department_heads (school_id, name, email, phone, password, status, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, 'active', NOW(), NOW())
            ");
            $stmt->execute([$school_id, $name, $email, $phone, $hashed_password]);
            
            $success = 'Examination department head added successfully!';
        } catch (Exception $e) {
            error_log("Error adding examination department head: " . $e->getMessage());
            $errors[] = 'An error occurred. Please try again.';
        }
    }
    
    $error = implode('<br>', $errors);
}

// Handle delete action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $head_id = intval($_POST['head_id'] ?? 0);
    
    if ($head_id > 0) {
        try {
            $stmt = $pdo->prepare("DELETE FROM examination_department_heads WHERE id = ? AND school_id = ?");
            $stmt->execute([$head_id, $school_id]);
            $success = 'Examination department head deleted successfully!';
        } catch (Exception $e) {
            error_log("Error deleting examination department head: " . $e->getMessage());
            $error = 'An error occurred. Please try again.';
        }
    }
}

// Handle status toggle
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'toggle_status') {
    $head_id = intval($_POST['head_id'] ?? 0);
    $new_status = $_POST['status'] ?? 'active';
    
    if ($head_id > 0 && in_array($new_status, ['active', 'inactive'])) {
        try {
            $stmt = $pdo->prepare("UPDATE examination_department_heads SET status = ?, updated_at = NOW() WHERE id = ? AND school_id = ?");
            $stmt->execute([$new_status, $head_id, $school_id]);
            $success = 'Status updated successfully!';
        } catch (Exception $e) {
            error_log("Error updating status: " . $e->getMessage());
            $error = 'An error occurred. Please try again.';
        }
    }
}

// Get all examination heads for this school
try {
    $stmt = $pdo->prepare("SELECT * FROM examination_department_heads WHERE school_id = ? ORDER BY created_at DESC");
    $stmt->execute([$school_id]);
    $examination_heads = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log("Error fetching examination heads: " . $e->getMessage());
    $examination_heads = [];
}

// Get all active teachers for this school
try {
    $stmt = $pdo->prepare("SELECT id, first_name, last_name, email, phone FROM teachers WHERE school_id = ? AND status = 'active' ORDER BY first_name, last_name");
    $stmt->execute([$school_id]);
    $teachers = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log("Error fetching teachers: " . $e->getMessage());
    $teachers = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#FF6B35">
    <title>Examination Department Heads - <?php echo htmlspecialchars($school_name); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0" />
    <style>
        :root {
            --primary-color: #1a73e8;
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
            background: #f8f9fa;
            font-family: 'Google Sans', 'Roboto', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            font-size: 14px;
            color: #202124;
        }

        .main-content {
            padding: 24px;
            padding-bottom: 80px;
        }

        .card {
            background: var(--bg-color);
            border: 1px solid #e0e0e0;
            border-radius: 25px;
            box-shadow: none;
            margin: 0 0 24px 0;
        }

        .card-header {
            background: var(--bg-color);
            border-bottom: 1px solid #e0e0e0;
            padding: 16px 20px;
            font-weight: 500;
            color: var(--secondary-color);
            border-radius: 25px 25px 0 0;
            text-align: center;
        }

        .card-body {
            padding: 0;
            border-radius: 0 0 25px 25px;
        }

        .card-title {
            font-size: 18px;
            font-weight: 500;
            color: #202124;
            margin-bottom: 16px;
            text-align: center;
        }

        .page-title {
            font-size: 22px;
            font-weight: 400;
            color: #202124;
            margin-bottom: 24px;
            text-align: center;
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

        .btn-sm {
            padding: 4px 8px;
            font-size: 12px;
        }

        .btn-outline-secondary {
            border: 1px solid #dadce0;
            background: white;
            color: #5f6368;
        }

        .btn-outline-secondary:hover {
            background: #f1f3f4;
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

        .table-responsive {
            width: 100%;
            overflow-x: auto;
            margin: 0;
            padding: 0 20px 20px 20px;
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

        /* Badge */
        .badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 500;
        }

        .badge-active {
            background: #e6f4ea;
            color: #137333;
        }

        .badge-inactive {
            background: #fce8e6;
            color: #c5221f;
        }

        .badge-archived {
            background: #fce8e6;
            color: #c5221f;
        }

        /* Alert */
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

            .card-body {
                padding: 0;
            }

            .table-responsive {
                padding: 0 8px 16px 8px;
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
                padding: 2px 4px !important;
                font-size: 9px !important;
            }

            .table td form button:last-child {
                margin-right: 0 !important;
            }

            .btn {
                width: 100% !important;
            }

            .quick-access-grid {
                display: grid !important;
                grid-template-columns: repeat(3, 1fr) !important;
                gap: 8px !important;
            }

            .quick-access-item {
                padding: 12px !important;
            }

            .password-input-group {
                flex-direction: column !important;
            }

            .password-input-group .form-control {
                width: 100% !important;
            }

            .password-input-group .btn {
                width: 100% !important;
            }
        }

        /* Icon colors */
        .quick-access-item:nth-child(1) .quick-access-icon { background: #e8f0fe; color: #4285F4; }
        .quick-access-item:nth-child(2) .quick-access-icon { background: #fce8e6; color: #EA4335; }
        .quick-access-item:nth-child(3) .quick-access-icon { background: #e6f4ea; color: #00AC47; }
        .quick-access-item:nth-child(4) .quick-access-icon { background: #fef7e0; color: #FFBA00; }
        .quick-access-item:nth-child(5) .quick-access-icon { background: #e8f0fe; color: #0066DA; }
        .quick-access-item:nth-child(6) .quick-access-icon { background: #fce8e6; color: #4285F4; }
        .material-symbols-outlined {
            font-size: 24px;
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
        }

        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
    </style>
</head>
<body class="dashboard-page">
    <?php require_once 'includes/header.php'; ?>
    <?php $active_page = 'examination-heads'; require_once 'includes/sidebar.php'; ?>

    <!-- Main Content -->
    <main class="main-content">
        <div class="container-fluid">
            <br><br>
            <h1 class="page-title">Examination Department Heads</h1>
        
        <?php if ($success): ?>
            <div class="alert alert-success">
                <?php echo $success; ?>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-danger">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <!-- Add New Examination Head Form -->
        <div class="card">
            <div class="card-header">
                <span class="material-symbols-outlined" style="vertical-align: middle; margin-right: 8px;">add</span>Add New Examination Department Head
            </div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="action" value="add">
                    <div style="margin-bottom: 16px;">
                        <label class="form-label">Select from Existing Teachers (Optional)</label>
                        <select class="form-control" id="teacherSelect" name="teacher_id">
                            <option value="">-- Select a teacher to auto-fill details --</option>
                            <?php foreach ($teachers as $teacher): ?>
                                <option value="<?php echo $teacher['id']; ?>" 
                                        data-name="<?php echo htmlspecialchars($teacher['first_name'] . ' ' . $teacher['last_name']); ?>"
                                        data-email="<?php echo htmlspecialchars($teacher['email']); ?>"
                                        data-phone="<?php echo htmlspecialchars($teacher['phone']); ?>">
                                    <?php echo htmlspecialchars($teacher['first_name'] . ' ' . $teacher['last_name']); ?> (<?php echo htmlspecialchars($teacher['email']); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 16px;">
                        <div>
                            <label class="form-label">Full Name *</label>
                            <input type="text" class="form-control" id="name" name="name" required>
                        </div>
                        <div>
                            <label class="form-label">Email *</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                        <div>
                            <label class="form-label">Phone *</label>
                            <input type="text" class="form-control" id="phone" name="phone" required>
                        </div>
                        <div>
                            <label class="form-label">Password *</label>
                            <div class="password-input-group" style="display: flex; gap: 8px;">
                                <input type="password" class="form-control" id="password" name="password" required placeholder="Min 8 characters" style="flex: 1;">
                                <button type="button" class="btn btn-secondary" onclick="togglePassword()" title="Show/Hide Password">
                                    <span class="material-symbols-outlined" id="eyeIcon">visibility</span>
                                </button>
                                <button type="button" class="btn btn-secondary" onclick="generatePassword()" title="Generate Random Password">
                                    <span class="material-symbols-outlined">key</span>
                                </button>
                            </div>
                            <div style="margin-top: 8px;">
                                <input type="checkbox" id="useTeacherPassword" onchange="toggleTeacherPassword()">
                                <label for="useTeacherPassword" style="font-size: 13px; color: #5f6368;">Use teacher's existing password</label>
                            </div>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <span class="material-symbols-outlined" style="vertical-align: middle; margin-right: 8px;">add</span>Add Examination Head
                    </button>
                </form>
            </div>
        </div>
        
        <!-- Examination Heads List -->
        <div class="card">
            <div class="card-header">
                <span class="material-symbols-outlined" style="vertical-align: middle; margin-right: 8px;">list_alt</span>Examination Department Heads List
            </div>
            <div class="card-body">
                <?php if (empty($examination_heads)): ?>
                    <p style="color: #5f6368; text-align: center; padding: 40px;">No examination department heads found. Add one above.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Phone</th>
                                    <th>Status</th>
                                    <th>Created</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($examination_heads as $head): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($head['name']); ?></td>
                                        <td><?php echo htmlspecialchars($head['email']); ?></td>
                                        <td><?php echo htmlspecialchars($head['phone']); ?></td>
                                        <td>
                                            <span class="badge badge-<?php echo $head['status'] === 'active' ? 'active' : 'archived'; ?>">
                                                <?php echo ucfirst($head['status']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('M d, Y', strtotime($head['created_at'])); ?></td>
                                        <td>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="action" value="toggle_status">
                                                <input type="hidden" name="head_id" value="<?php echo $head['id']; ?>">
                                                <input type="hidden" name="status" value="<?php echo $head['status'] === 'active' ? 'inactive' : 'active'; ?>">
                                                <button type="submit" class="btn-action">
                                                    <span class="material-symbols-outlined"><?php echo $head['status'] === 'active' ? 'pause' : 'play_arrow'; ?></span>
                                                </button>
                                            </form>
                                            <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this examination head?');">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="head_id" value="<?php echo $head['id']; ?>">
                                                <button type="submit" class="btn-action">
                                                    <span class="material-symbols-outlined">delete</span>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        </div>
    </main>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-fill teacher details when selected
        document.getElementById('teacherSelect').addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            if (selectedOption.value) {
                document.getElementById('name').value = selectedOption.dataset.name || '';
                document.getElementById('email').value = selectedOption.dataset.email || '';
                document.getElementById('phone').value = selectedOption.dataset.phone || '';
                // Don't auto-generate password when teacher is selected - let user choose
                document.getElementById('password').value = '';
                document.getElementById('useTeacherPassword').checked = false;
            } else {
                // Clear fields if no teacher selected
                document.getElementById('name').value = '';
                document.getElementById('email').value = '';
                document.getElementById('phone').value = '';
                document.getElementById('password').value = '';
                document.getElementById('useTeacherPassword').checked = false;
            }
        });

        // Toggle using teacher's phone as password
        function toggleTeacherPassword() {
            const checkbox = document.getElementById('useTeacherPassword');
            const phoneField = document.getElementById('phone');
            const passwordField = document.getElementById('password');

            if (checkbox.checked) {
                // Use phone number as password (last 6 digits)
                if (phoneField.value) {
                    passwordField.value = phoneField.value.slice(-6);
                } else {
                    alert('Please enter a phone number first');
                    checkbox.checked = false;
                }
            } else {
                // Clear password when unchecked
                passwordField.value = '';
            }
        }

        // Generate random password function
        function generatePassword() {
            const length = 12;
            const charset = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*';
            let password = '';
            for (let i = 0; i < length; i++) {
                password += charset.charAt(Math.floor(Math.random() * charset.length));
            }
            document.getElementById('password').value = password;
        }

        // Toggle password visibility
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const eyeIcon = document.getElementById('eyeIcon');
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                eyeIcon.textContent = 'visibility_off';
            } else {
                passwordInput.type = 'password';
                eyeIcon.textContent = 'visibility';
            }
        }
    </script>

    <?php require_once '../includes/copywrite.php'; ?>
</body>
</html>
