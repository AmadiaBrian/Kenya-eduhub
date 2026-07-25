<?php
// Finance Manager Payment Reminders Page
// Authentication is handled by index.php router
$finance_manager_id = $_SESSION['finance_manager_id'];
$finance_manager_name = $_SESSION['finance_manager_name'] ?? 'Finance Manager';
$school_id = $_SESSION['school_id'];
$school_name = $_SESSION['school_name'] ?? 'School';

// Load calendar helpers
require_once __DIR__ . '/../includes/calendar_helpers.php';

// Get calendar status
$calendar_status = getSchoolCalendarStatus($pdo, $school_id);

// Get active term from calendar status
$active_term = $calendar_status['current_term']['term_name'] ?? null;

// Get terms from database for current year
$terms = [];
try {
    $current_year = date('Y');
    $stmt = $pdo->prepare("SELECT term_name FROM terms WHERE school_id = ? AND year = ? ORDER BY term_number");
    $stmt->execute([$school_id, $current_year]);
    $term_records = $stmt->fetchAll();
    foreach ($term_records as $term) {
        $terms[] = $term['term_name'];
    }
} catch (PDOException $e) {
    error_log("Failed to fetch terms: " . $e->getMessage());
    $terms = ['Term 1', 'Term 2', 'Term 3'];
}

if (empty($terms)) {
    $terms = ['Term 1', 'Term 2', 'Term 3'];
}

// Get available years from database
$available_years = [];
try {
    $stmt = $pdo->prepare("SELECT DISTINCT year FROM fee_structure WHERE school_id = ? ORDER BY year DESC");
    $stmt->execute([$school_id]);
    $year_records = $stmt->fetchAll();
    foreach ($year_records as $record) {
        $available_years[] = $record['year'];
    }
    
    // Also check fee_payments for years that might not have fee structures
    $stmt = $pdo->prepare("SELECT DISTINCT year FROM fee_payments fp JOIN students s ON fp.student_id = s.id WHERE s.school_id = ? ORDER BY year DESC");
    $stmt->execute([$school_id]);
    $payment_years = $stmt->fetchAll();
    foreach ($payment_years as $record) {
        if (!in_array($record['year'], $available_years)) {
            $available_years[] = $record['year'];
        }
    }
    
    // Sort years descending
    rsort($available_years);
    
    // If no years found, default to current year
    if (empty($available_years)) {
        $available_years = [date('Y')];
    }
} catch (PDOException $e) {
    error_log("Failed to fetch years: " . $e->getMessage());
    $available_years = [date('Y')];
}

// Get filter parameters
$filter_year = $_GET['year'] ?? ($available_years[0] ?? date('Y'));
$filter_term = $_GET['term'] ?? '';
$filter_class = $_GET['class'] ?? '';

// Include PHPMailer
require_once __DIR__ . '/../PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/../PHPMailer/src/SMTP.php';
require_once __DIR__ . '/../PHPMailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Function to get SMTP settings for school
function getSMTPSettings($pdo, $school_id) {
    $stmt = $pdo->prepare("SELECT * FROM smtp_settings WHERE school_id = ?");
    $stmt->execute([$school_id]);
    return $stmt->fetch();
}

// Function to send reminder email
function sendReminderEmail($pdo, $school_id, $student, $outstanding, $term, $custom_message = '') {
    $smtp_settings = getSMTPSettings($pdo, $school_id);
    
    if (!$smtp_settings) {
        return ['success' => false, 'error' => 'SMTP settings not configured'];
    }
    
    // Use parent email if available, otherwise use student email
    $recipient_email = !empty($student['parent_email']) ? $student['parent_email'] : $student['email'];
    $recipient_name = !empty($student['parent_email']) ? 
        ($student['parent_first_name'] . ' ' . $student['parent_last_name']) : 
        ($student['first_name'] . ' ' . $student['last_name']);
    
    if (empty($recipient_email)) {
        return ['success' => false, 'error' => 'No email address available'];
    }
    
    try {
        $mail = new PHPMailer(true);
        
        // Server settings
        $mail->isSMTP();
        $mail->Host = $smtp_settings['smtp_host'];
        $mail->SMTPAuth = true;
        $mail->Username = $smtp_settings['email'];
        $mail->Password = $smtp_settings['app_password'];
        $mail->SMTPSecure = $smtp_settings['encryption'];
        $mail->Port = $smtp_settings['smtp_port'];
        
        // Recipients
        $mail->setFrom($smtp_settings['email'], $school_name);
        $mail->addAddress($recipient_email, $recipient_name);
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Payment Reminder - ' . $school_name;
        
        $message = $custom_message ?: "Dear " . $recipient_name . ",<br><br>
        This is a friendly reminder that your child " . $student['first_name'] . " " . $student['last_name'] . " has an outstanding balance of KES " . number_format($outstanding, 2) . " for " . $term . ".<br><br>
        Please make the payment at your earliest convenience to avoid any late fees.<br><br>
        If you have already made this payment, please disregard this notice.<br><br>
        Thank you,<br>" . $school_name;
        
        $mail->Body = $message;
        $mail->AltBody = strip_tags($message);
        
        $mail->send();
        return ['success' => true];
    } catch (Exception $e) {
        return ['success' => false, 'error' => $mail->ErrorInfo];
    }
}

// Get filter parameters
$filter_year = $_GET['year'] ?? date('Y');
$filter_term = $_GET['term'] ?? '';
$filter_class = $_GET['class'] ?? '';
$filter_status = $_GET['status'] ?? '';

// Get classes
$classes = [];
try {
    $stmt = $pdo->prepare("SELECT * FROM classes WHERE school_id = ? ORDER BY class_name");
    $stmt->execute([$school_id]);
    $classes = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Failed to fetch classes: " . $e->getMessage());
}

// Get students with outstanding balances
$outstanding_students = [];
try {
    $query = "SELECT s.id as student_id, s.first_name, s.last_name, s.admission_number, c.class_name,
              p.email as parent_email, p.first_name as parent_first_name, p.last_name as parent_last_name,
              (fs.amount - COALESCE(SUM(fp.amount), 0)) as outstanding_balance,
              fs.term, fs.year
              FROM students s
              JOIN classes c ON s.class_id = c.id
              JOIN fee_structure fs ON fs.class_id = c.id AND fs.school_id = ? AND fs.year = ? AND fs.fee_type = 'Tuition'
              LEFT JOIN fee_payments fp ON fp.student_id = s.id AND fp.year = fs.year AND fp.term = fs.term AND fp.status = 'completed' AND (fp.fee_type = 'Tuition' OR fp.fee_type IS NULL)
              LEFT JOIN student_parents sp ON sp.student_id = s.id AND sp.is_primary = 1
              LEFT JOIN parents p ON sp.parent_id = p.id
              WHERE s.school_id = ? AND s.status = 'active'";
    $params = [$school_id, $filter_year, $school_id];
    
    if ($filter_term) {
        $query .= " AND fs.term = ?";
        $params[] = $filter_term;
    }
    
    if ($filter_class) {
        $query .= " AND c.id = ?";
        $params[] = $filter_class;
    }
    
    $query .= " GROUP BY s.id, s.first_name, s.last_name, s.admission_number, c.class_name, p.email, p.first_name, p.last_name, fs.term, fs.year, fs.amount
              HAVING (fs.amount - COALESCE(SUM(fp.amount), 0)) > 0
              ORDER BY c.class_name, s.last_name, s.first_name";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $outstanding_students = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Failed to fetch outstanding students: " . $e->getMessage());
}

// Get reminder history
$reminder_history = [];
try {
    $query = "SELECT rh.*, s.first_name, s.last_name, s.admission_number, c.class_name
              FROM reminder_history rh
              JOIN students s ON rh.student_id = s.id
              JOIN classes c ON s.class_id = c.id
              WHERE rh.school_id = ?";
    $params = [$school_id];
    
    if ($filter_year) {
        $query .= " AND rh.year = ?";
        $params[] = $filter_year;
    }
    
    if ($filter_status) {
        $query .= " AND rh.status = ?";
        $params[] = $filter_status;
    }
    
    $query .= " ORDER BY rh.created_at DESC LIMIT 50";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $reminder_history = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Failed to fetch reminder history: " . $e->getMessage());
}

// Handle reminder generation - now handled by AJAX API
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Reminders - <?php echo htmlspecialchars($finance_manager_name); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
            font-size: 20px;
            color: #5f6368;
            cursor: pointer;
            padding: 8px;
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
            gap: 12px;
            padding: 10px 24px;
            color: #202124;
            text-decoration: none;
            transition: background 0.2s;
        }
        
        .nav-link:hover {
            background: #e8eaed;
        }
        
        .nav-link.active {
            background: #e8f0fe;
            color: #1967d2;
            font-weight: 500;
        }
        
        .nav-link i {
            width: 20px;
            text-align: center;
        }

        /* Make all icons orange */
        i {
            color: #FF6B35 !important;
        }
        
        .main-content {
            margin-left: var(--sidebar-width);
            padding: 24px;
            padding-top: calc(var(--header-height) + 24px);
            transition: margin-left 0.3s ease;
        }
        
        .main-content.expanded {
            margin-left: 0;
        }
        
        .page-title {
            font-size: 24px;
            font-weight: 400;
            color: #202124;
            margin-bottom: 24px;
        }
        
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
            border-radius: 4px;
            padding: 10px 12px;
            font-size: 14px;
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            outline: none;
        }
        
        .form-label {
            font-size: 13px;
            font-weight: 500;
            color: #5f6368;
            margin-bottom: 4px;
        }
        
        .btn {
            padding: 8px 16px;
            border-radius: 4px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            border: none;
            transition: all 0.2s;
        }
        
        .btn-primary {
            background: var(--primary-color);
            color: white;
        }
        
        .btn-primary:hover {
            background: #e55a2b;
        }
        
        .table {
            width: 100%;
            border-collapse: collapse;
            margin: 0;
        }
        
        .table thead {
            border-bottom: 1px solid #e8eaed;
        }
        
        .table th {
            text-align: left;
            padding: 12px;
            font-size: 13px;
            font-weight: 600;
            color: #202124;
            border-bottom: 1px solid #e8eaed;
        }
        
        .table td {
            padding: 12px;
            font-size: 13px;
            color: #202124;
            border-bottom: 1px solid #e8eaed;
        }
        
        .table tbody tr:hover {
            background: #f8f9fa;
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
        
        .badge-warning {
            background: #fff3e0;
            color: #b06000;
        }
        
        .badge-danger {
            background: #ffebee;
            color: #c5221f;
        }
        
        .alert {
            padding: 12px 16px;
            border-radius: 4px;
            margin-bottom: 16px;
            border: none;
        }
        
        .alert-success {
            background: #e8f5e9;
            color: #137333;
        }
        
        .alert-danger {
            background: #ffebee;
            color: #c5221f;
        }
        
        /* Loading spinner */
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 9999;
        }
        
        .loading-content {
            background: white;
            padding: 30px;
            border-radius: 8px;
            text-align: center;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        .spinner {
            width: 50px;
            height: 50px;
            border: 5px solid #f3f3f3;
            border-top: 5px solid var(--primary-color);
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto 20px;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .progress-bar {
            width: 100%;
            height: 20px;
            background: #f0f0f0;
            border-radius: 10px;
            overflow: hidden;
            margin: 20px 0;
        }
        
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--primary-color), #ff8c00);
            transition: width 0.3s ease;
            border-radius: 10px;
        }
        
        .progress-text {
            font-size: 16px;
            font-weight: 500;
            color: #5f6368;
        }
        
        .current-student {
            font-size: 14px;
            color: #5f6368;
            margin-top: 10px;
        }
        
        @keyframes slideIn {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        
        @keyframes slideOut {
            from {
                transform: translateX(0);
                opacity: 1;
            }
            to {
                transform: translateX(100%);
                opacity: 0;
            }
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
            <div class="user-avatar">
                <?php echo strtoupper(substr($finance_manager_name, 0, 1)); ?>
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
            <a class="nav-link" href="fees">
                <i class="fas fa-file-invoice-dollar"></i> Fee Management
            </a>
            <a class="nav-link" href="invoices">
                <i class="fas fa-file-invoice"></i> Invoices
            </a>
            <a class="nav-link" href="reports">
                <i class="fas fa-chart-bar"></i> Reports
            </a>
            <a class="nav-link active" href="reminders">
                <i class="fas fa-bell"></i> Payment Reminders
            </a>
            <a class="nav-link" href="account">
                <i class="fas fa-wallet"></i> Account Balance
            </a>
        </div>
        <div class="sidebar-section">
            <div class="sidebar-title">Account</div>
            <a class="nav-link" href="profile">
                <i class="fas fa-user"></i> Profile
            </a>
            <a class="nav-link" href="logout">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </aside>
    
    <!-- Main Content -->
    <main class="main-content" id="mainContent">
        <h1 class="page-title">Payment Reminders</h1>
        
        <?php if (isset($success)): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($errors)): ?>
            <?php foreach ($errors as $error): ?>
                <div class="alert alert-danger">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
        
        <!-- Filter Card -->
        <div class="card">
            <h2 class="card-title">Filter Students</h2>
            <form method="GET">
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Year</label>
                        <select class="form-control" name="year">
                            <?php foreach($available_years as $year): ?>
                                <option value="<?php echo $year; ?>" <?php echo $filter_year == $year ? 'selected' : ''; ?>><?php echo $year; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Term</label>
                        <select class="form-control" name="term">
                            <option value="">All Terms</option>
                            <?php foreach($terms as $term): ?>
                                <option value="<?php echo htmlspecialchars($term); ?>" <?php echo $filter_term === $term ? 'selected' : ''; ?>><?php echo htmlspecialchars($term); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Class</label>
                        <select class="form-control" name="class">
                            <option value="">All Classes</option>
                            <?php foreach ($classes as $class): ?>
                                <option value="<?php echo $class['id']; ?>" <?php echo $filter_class == $class['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($class['class_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">&nbsp;</label>
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-filter me-2"></i> Apply Filter
                        </button>
                    </div>
                </div>
            </form>
        </div>
        
        <!-- Outstanding Students Card -->
        <div class="card">
            <h2 class="card-title">Students with Outstanding Balances (<?php echo count($outstanding_students); ?>)</h2>
            <form method="POST">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th><input type="checkbox" id="selectAll" onclick="toggleSelectAll()"></th>
                                <th>Student</th>
                                <th>Admission No</th>
                                <th>Class</th>
                                <th>Term</th>
                                <th>Outstanding Balance</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($outstanding_students)): ?>
                                <tr>
                                    <td colspan="6" class="text-center">No students with outstanding balances found</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($outstanding_students as $student): ?>
                                    <tr>
                                        <td><input type="checkbox" name="student_ids[]" value="<?php echo $student['student_id']; ?>"></td>
                                        <td><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></td>
                                        <td><?php echo htmlspecialchars($student['admission_number']); ?></td>
                                        <td><?php echo htmlspecialchars($student['class_name']); ?></td>
                                        <td><?php echo htmlspecialchars($student['term']); ?></td>
                                        <td><strong>KES <?php echo number_format($student['outstanding_balance'], 2); ?></strong></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <?php if (!empty($outstanding_students)): ?>
                    <div class="row mt-3">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Reminder Type</label>
                            <select class="form-control" name="reminder_type" id="reminder_type">
                                <option value="email">Email</option>
                                <option value="letter">Print Letter</option>
                                <option value="manual">Manual Contact</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Custom Message (Optional)</label>
                            <textarea class="form-control" name="message" id="custom_message" rows="2" placeholder="Enter custom message for reminder..."></textarea>
                        </div>
                        <div class="col-md-2 mb-3">
                            <label class="form-label">&nbsp;</label>
                            <button type="button" onclick="generateReminders()" class="btn btn-primary w-100" style="background-color: #ff6600; color: white; border: none;">
                                <i class="fas fa-bell me-2"></i> Generate Reminders
                            </button>
                        </div>
                    </div>
                <?php endif; ?>
            </form>
        </div>
        
        <!-- Reminder History Card -->
        <div class="card">
            <h2 class="card-title">Reminder History</h2>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Student</th>
                            <th>Class</th>
                            <th>Term</th>
                            <th>Amount</th>
                            <th>Type</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($reminder_history)): ?>
                            <tr>
                                <td colspan="7" class="text-center">No reminder history found</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($reminder_history as $reminder): ?>
                                <tr>
                                    <td><?php echo date('M d, Y H:i', strtotime($reminder['created_at'])); ?></td>
                                    <td><?php echo htmlspecialchars($reminder['first_name'] . ' ' . $reminder['last_name']); ?></td>
                                    <td><?php echo htmlspecialchars($reminder['class_name']); ?></td>
                                    <td><?php echo htmlspecialchars($reminder['term']); ?></td>
                                    <td><strong>KES <?php echo number_format($reminder['outstanding_amount'], 2); ?></strong></td>
                                    <td><?php echo ucfirst(htmlspecialchars($reminder['reminder_type'])); ?></td>
                                    <td>
                                        <span class="badge badge-<?php echo $reminder['status'] === 'sent' ? 'success' : 'warning'; ?>">
                                            <?php echo ucfirst(htmlspecialchars($reminder['status'])); ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
    
    <!-- Loading Overlay -->
    <div class="loading-overlay" id="loadingOverlay">
        <div class="loading-content">
            <div class="spinner"></div>
            <div class="progress-text">
                <span id="progressPercent">0</span>% Complete
            </div>
            <div class="progress-bar">
                <div class="progress-fill" id="progressFill" style="width: 0%"></div>
            </div>
            <div class="current-student" id="currentStudent">Preparing...</div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const mainContent = document.getElementById('mainContent');
            sidebar.classList.toggle('collapsed');
            sidebar.classList.toggle('show');
            mainContent.classList.toggle('expanded');
        }
        
        function toggleSelectAll() {
            const selectAll = document.getElementById('selectAll');
            const checkboxes = document.querySelectorAll('input[name="student_ids[]"]');
            checkboxes.forEach(checkbox => {
                checkbox.checked = selectAll.checked;
            });
        }
        
        async function generateReminders() {
            const checkboxes = document.querySelectorAll('input[name="student_ids[]"]:checked');
            const studentIds = Array.from(checkboxes).map(cb => cb.value);
            
            if (studentIds.length === 0) {
                alert('Please select at least one student');
                return;
            }
            
            const reminderType = document.getElementById('reminder_type').value;
            const customMessage = document.getElementById('custom_message').value;
            const year = '<?php echo $filter_year; ?>';
            
            // Show loading overlay
            const loadingOverlay = document.getElementById('loadingOverlay');
            const progressFill = document.getElementById('progressFill');
            const progressPercent = document.getElementById('progressPercent');
            const currentStudent = document.getElementById('currentStudent');
            
            loadingOverlay.style.display = 'flex';
            
            try {
                const formData = new FormData();
                formData.append('student_ids', JSON.stringify(studentIds));
                formData.append('reminder_type', reminderType);
                formData.append('message', customMessage);
                formData.append('year', year);
                
                const response = await fetch('/Kenyaeduhub/finance-managers/api/generate_reminders.php', {
                    method: 'POST',
                    body: formData
                });
                
                const reader = response.body.getReader();
                const decoder = new TextDecoder();
                let buffer = '';
                
                while (true) {
                    const { done, value } = await reader.read();
                    if (done) break;
                    
                    buffer += decoder.decode(value, { stream: true });
                    const lines = buffer.split('\n');
                    buffer = lines.pop();
                    
                    for (const line of lines) {
                        if (line.trim()) {
                            try {
                                const data = JSON.parse(line);
                                console.log('Received data:', data); // Debug log
                                
                                if (data.complete) {
                                    // Final result
                                    loadingOverlay.style.display = 'none';
                                    
                                    if (data.success) {
                                        let message = data.sent + ' reminders sent successfully';
                                        if (data.failed > 0) {
                                            message += ', ' + data.failed + ' failed';
                                        }
                                        
                                        // Show success message
                                        const successDiv = document.createElement('div');
                                        successDiv.className = 'alert alert-success';
                                        successDiv.style.position = 'fixed';
                                        successDiv.style.top = '20px';
                                        successDiv.style.right = '20px';
                                        successDiv.style.zIndex = '10000';
                                        successDiv.style.padding = '15px 20px';
                                        successDiv.style.borderRadius = '8px';
                                        successDiv.style.boxShadow = '0 4px 6px rgba(0,0,0,0.1)';
                                        successDiv.style.animation = 'slideIn 0.3s ease';
                                        successDiv.innerHTML = '<i class="fas fa-check-circle me-2"></i>' + message;
                                        
                                        document.body.appendChild(successDiv);
                                        
                                        // Remove success message after 3 seconds and reload
                                        setTimeout(() => {
                                            successDiv.style.animation = 'slideOut 0.3s ease';
                                            setTimeout(() => {
                                                successDiv.remove();
                                                location.reload();
                                            }, 300);
                                        }, 3000);
                                    } else {
                                        alert('Error: ' + data.error);
                                    }
                                } else {
                                    // Progress update
                                    progressFill.style.width = data.progress + '%';
                                    progressPercent.textContent = data.progress;
                                    currentStudent.textContent = 'Processing: ' + data.current_student;
                                }
                            } catch (e) {
                                console.error('Error parsing JSON:', e, 'Line:', line);
                            }
                        }
                    }
                }
            } catch (error) {
                loadingOverlay.style.display = 'none';
                alert('Error generating reminders: ' + error.message);
            }
        }
    </script>
</body>
</html>
