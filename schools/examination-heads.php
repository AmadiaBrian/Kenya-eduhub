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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
            font-size: 18px;
            font-weight: 400;
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
            padding: 12px 24px;
            color: #5f6368;
            text-decoration: none;
            transition: background 0.2s;
            font-size: 14px;
            border-bottom: 1px solid #f1f3f4;
        }
        
        .nav-link:hover {
            background: #f1f3f4;
        }
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
            padding-bottom: 80px;
            transition: margin-left 0.3s ease;
        }
        
        .main-content.expanded {
            margin-left: 0;
        }
        
        .card {
            background: var(--card-bg);
            border: none;
            border-radius: 12px;
            box-shadow: none;
            margin-bottom: 20px;
        }
        
        .card-header {
            background: var(--card-bg);
            border-bottom: 1px solid rgba(0,0,0,0.04);
            padding: 16px 20px;
            font-weight: 500;
            color: var(--secondary-color);
            border-radius: 12px;
        }
        
        .card-body {
            padding: 20px;
            border-radius: 12px;
        }
        
        .card-title {
            font-size: 18px;
            font-weight: 500;
            color: #202124;
            margin-bottom: 16px;
        }
        
        .page-title {
            font-size: 22px;
            font-weight: 400;
            color: #202124;
            margin-bottom: 24px;
        }
        
        /* Form */
        .form-control {
            width: 100%;
            padding: 10px 12px;
            font-size: 14px;
            border: 1px solid #dadce0;
            border-radius: 8px;
            outline: none;
            transition: border-color 0.2s, box-shadow 0.2s;
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
            border-radius: 4px;
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
            .sidebar {
                transform: translateX(-256px);
                z-index: 9999;
            }
            
            .sidebar.show {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0;
                padding: 16px;
                padding-bottom: 80px;
                padding-top: calc(var(--header-height) + 16px);
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
        </div>
    </header>
    
    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar">
        <a class="nav-link" href="dashboard">
            <i class="fas fa-home"></i> Dashboard
        </a>
        <a class="nav-link" href="students">
            <i class="fas fa-user-graduate"></i> Students
        </a>
        <a class="nav-link" href="teachers">
            <i class="fas fa-chalkboard-teacher"></i> Teachers
        </a>
        <a class="nav-link" href="classes">
            <i class="fas fa-chalkboard"></i> Classes
        </a>
        <a class="nav-link" href="streams">
            <i class="fas fa-layer-group"></i> Streams
        </a>
        <a class="nav-link" href="subjects">
            <i class="fas fa-book"></i> Subjects
        </a>
        <a class="nav-link" href="exam-types">
            <i class="fas fa-clipboard-list"></i> Exam Types
        </a>
        <a class="nav-link" href="timetable">
            <i class="fas fa-calendar-alt"></i> Timetable
        </a>
        <a class="nav-link" href="grading">
            <i class="fas fa-chart-bar"></i> Grading
        </a>
        <a class="nav-link" href="performance">
            <i class="fas fa-chart-line"></i> Performance
        </a>
        <a class="nav-link" href="results">
            <i class="fas fa-clipboard-list"></i> Results
        </a>
        <a class="nav-link" href="attendance">
            <i class="fas fa-calendar-check"></i> Attendance
        </a>
        <a class="nav-link" href="calendar">
            <i class="fas fa-calendar-alt"></i> Calendar
        </a>
        <a class="nav-link" href="fees">
            <i class="fas fa-money-bill-wave"></i> Fees
        </a>
        <a class="nav-link" href="invoices">
            <i class="fas fa-file-invoice-dollar"></i> Invoices
        </a>
        <a class="nav-link" href="finance-managers">
            <i class="fas fa-user-tie"></i> Finance Managers
        </a>
        <a class="nav-link" href="account">
            <i class="fas fa-wallet"></i> Account Balance
        </a>
        <a class="nav-link" href="parents">
            <i class="fas fa-user-friends"></i> Parents
        </a>
        <a class="nav-link" href="disciplinary">
            <i class="fas fa-exclamation-triangle"></i> Disciplinary
        </a>
        <a class="nav-link" href="disciplinary-action-types">
            <i class="fas fa-list-alt"></i> Disciplinary Types
        </a>
        <a class="nav-link" href="duty-assignments">
            <i class="fas fa-clipboard-list"></i> Duty Assignments
        </a>
        <a class="nav-link" href="librarians">
            <i class="fas fa-book-reader"></i> Librarians
        </a>
        <a class="nav-link active" href="examination-heads">
            <i class="fas fa-user-tie"></i> Examination Heads
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
        <div class="container-fluid">
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
                <i class="fas fa-plus me-2"></i>Add New Examination Department Head
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
                            <div style="display: flex; gap: 8px;">
                                <input type="password" class="form-control" id="password" name="password" required placeholder="Min 8 characters" style="flex: 1;">
                                <button type="button" class="btn btn-outline-secondary" onclick="generatePassword()" title="Generate Random Password">
                                    <i class="fas fa-key"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i>Add Examination Head
                    </button>
                </form>
            </div>
        </div>
        
        <!-- Examination Heads List -->
        <div class="card">
            <div class="card-header">
                <i class="fas fa-list me-2"></i>Examination Department Heads List
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
                                                <button type="submit" class="btn btn-sm btn-outline-secondary">
                                                    <i class="fas fa-<?php echo $head['status'] === 'active' ? 'pause' : 'play'; ?>"></i>
                                                </button>
                                            </form>
                                            <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this examination head?');">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="head_id" value="<?php echo $head['id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-secondary">
                                                    <i class="fas fa-trash"></i>
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
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const mainContent = document.getElementById('mainContent');
            
            // Check if we're on mobile
            if (window.innerWidth <= 768) {
                // Mobile: toggle the 'show' class
                sidebar.classList.toggle('show');
            } else {
                // Desktop: toggle the 'collapsed' and 'expanded' classes
                sidebar.classList.toggle('collapsed');
                mainContent.classList.toggle('expanded');
            }
        }
        
        // Auto-fill teacher details when selected
        document.getElementById('teacherSelect').addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            if (selectedOption.value) {
                document.getElementById('name').value = selectedOption.dataset.name || '';
                document.getElementById('email').value = selectedOption.dataset.email || '';
                document.getElementById('phone').value = selectedOption.dataset.phone || '';
                // Auto-generate password when teacher is selected
                generatePassword();
            } else {
                // Clear fields if no teacher selected
                document.getElementById('name').value = '';
                document.getElementById('email').value = '';
                document.getElementById('phone').value = '';
                document.getElementById('password').value = '';
            }
        });
        
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
    </script>
    
    <!-- Footer -->
    <footer style="position: fixed; bottom: 0; left: 0; right: 0; background: #f8f9fa; border-top: 1px solid #e8eaed; padding: 20px 0; z-index: 1000;">
        <div style="text-align: center;">
            <p style="margin: 0; color: #5f6368; font-size: 14px;">
                <span style="color: #FF6B35;">&copy; 2026</span>
                <span style="color: #FF6B35;">Kenya</span>
                <span style="color: #008000;">EduHub</span>
                <span style="color: #5f6368;">. All rights reserved.</span>
            </p>
        </div>
    </footer>
</body>
</html>
