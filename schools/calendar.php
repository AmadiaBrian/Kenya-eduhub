<?php
// Academic Calendar Management Page
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Load config if not already loaded
if (!isset($pdo)) {
    require_once __DIR__ . '/../config.php';
}

// Authentication check
if (!isset($_SESSION['school_id']) || !isset($_SESSION['school_token'])) {
    header('Location: login');
    exit;
}

$school_id = $_SESSION['school_id'];
$school_name = $_SESSION['school_name'] ?? 'School';

// Handle Term Creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_term'])) {
    $year = (int)($_POST['year'] ?? date('Y'));
    $term_number = (int)($_POST['term_number'] ?? 0);
    $start_date = $_POST['start_date'] ?? '';
    $end_date = $_POST['end_date'] ?? '';
    
    // Auto-generate term name based on term number
    $term_name = 'Term ' . $term_number;
    
    $errors = [];
    if ($term_number === 0) $errors[] = 'Term number is required';
    if (empty($start_date)) $errors[] = 'Start date is required';
    if (empty($end_date)) $errors[] = 'End date is required';
    if (strtotime($end_date) <= strtotime($start_date)) $errors[] = 'End date must be after start date';
    
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO terms (school_id, year, term_name, term_number, start_date, end_date, status) VALUES (?, ?, ?, ?, ?, ?, 'upcoming')");
            $stmt->execute([$school_id, $year, $term_name, $term_number, $start_date, $end_date]);
            $success = 'Term created successfully!';
        } catch (PDOException $e) {
            error_log("Failed to create term: " . $e->getMessage());
            $errors[] = 'Failed to create term: ' . $e->getMessage();
        }
    }
}

// Handle Term Deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_term'])) {
    $term_id = (int)($_POST['term_id'] ?? 0);
    
    try {
        $stmt = $pdo->prepare("DELETE FROM terms WHERE id = ? AND school_id = ?");
        $stmt->execute([$term_id, $school_id]);
        $success = 'Term deleted successfully!';
    } catch (PDOException $e) {
        error_log("Failed to delete term: " . $e->getMessage());
        $errors[] = 'Failed to delete term. Please try again.';
    }
}

// Handle Term Activation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['activate_term'])) {
    $term_id = (int)($_POST['term_id'] ?? 0);
    
    try {
        // Deactivate all terms for this school
        $stmt = $pdo->prepare("UPDATE terms SET is_active = 0 WHERE school_id = ?");
        $stmt->execute([$school_id]);
        
        // Activate the selected term
        $stmt = $pdo->prepare("UPDATE terms SET is_active = 1, status = 'active' WHERE id = ? AND school_id = ?");
        $stmt->execute([$term_id, $school_id]);
        $success = 'Term activated successfully!';
    } catch (PDOException $e) {
        error_log("Failed to activate term: " . $e->getMessage());
        $errors[] = 'Failed to activate term. Please try again.';
    }
}

// Handle Holiday Creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_holiday'])) {
    $holiday_name = trim($_POST['holiday_name'] ?? '');
    $description = $_POST['description'] ?? '';
    $start_date = $_POST['start_date'] ?? '';
    $end_date = $_POST['end_date'] ?? '';
    $holiday_type = $_POST['holiday_type'] ?? 'school';
    
    $errors = [];
    if (empty($holiday_name)) $errors[] = 'Holiday name is required';
    if (empty($start_date)) $errors[] = 'Start date is required';
    if (empty($end_date)) $errors[] = 'End date is required';
    if (strtotime($end_date) < strtotime($start_date)) $errors[] = 'End date cannot be before start date';
    
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO holidays (school_id, holiday_name, description, start_date, end_date, holiday_type) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$school_id, $holiday_name, $description, $start_date, $end_date, $holiday_type]);
            $success = 'Holiday created successfully!';
        } catch (PDOException $e) {
            error_log("Failed to create holiday: " . $e->getMessage());
            $errors[] = 'Failed to create holiday. Please try again.';
        }
    }
}

// Handle Holiday Deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_holiday'])) {
    $holiday_id = (int)($_POST['holiday_id'] ?? 0);
    
    try {
        $stmt = $pdo->prepare("DELETE FROM holidays WHERE id = ? AND school_id = ?");
        $stmt->execute([$holiday_id, $school_id]);
        $success = 'Holiday deleted successfully!';
    } catch (PDOException $e) {
        error_log("Failed to delete holiday: " . $e->getMessage());
        $errors[] = 'Failed to delete holiday. Please try again.';
    }
}

// Handle School Event Creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_event'])) {
    $event_name = trim($_POST['event_name'] ?? '');
    $description = $_POST['description'] ?? '';
    $event_date = $_POST['event_date'] ?? '';
    $event_time = $_POST['event_time'] ?? '';
    $event_type = $_POST['event_type'] ?? 'other';
    
    $errors = [];
    if (empty($event_name)) $errors[] = 'Event name is required';
    if (empty($event_date)) $errors[] = 'Event date is required';
    
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO school_events (school_id, event_name, description, event_date, event_time, event_type) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$school_id, $event_name, $description, $event_date, $event_time, $event_type]);
            $success = 'Event created successfully!';
        } catch (PDOException $e) {
            error_log("Failed to create event: " . $e->getMessage());
            $errors[] = 'Failed to create event. Please try again.';
        }
    }
}

// Handle Event Deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_event'])) {
    $event_id = (int)($_POST['event_id'] ?? 0);
    
    try {
        $stmt = $pdo->prepare("DELETE FROM school_events WHERE id = ? AND school_id = ?");
        $stmt->execute([$event_id, $school_id]);
        $success = 'Event deleted successfully!';
    } catch (PDOException $e) {
        error_log("Failed to delete event: " . $e->getMessage());
        $errors[] = 'Failed to delete event. Please try again.';
    }
}

// Get Terms
$terms = [];
try {
    $stmt = $pdo->prepare("SELECT * FROM terms WHERE school_id = ? ORDER BY year DESC, term_number ASC");
    $stmt->execute([$school_id]);
    $terms = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Failed to fetch terms: " . $e->getMessage());
}

// Get Holidays
$holidays = [];
try {
    $stmt = $pdo->prepare("SELECT * FROM holidays WHERE school_id = ? AND is_active = 1 ORDER BY start_date ASC");
    $stmt->execute([$school_id]);
    $holidays = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Failed to fetch holidays: " . $e->getMessage());
}

// Get School Events
$school_events = [];
try {
    $stmt = $pdo->prepare("SELECT * FROM school_events WHERE school_id = ? AND is_active = 1 ORDER BY event_date ASC");
    $stmt->execute([$school_id]);
    $school_events = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Failed to fetch school events: " . $e->getMessage());
}

// Load calendar helpers
require_once __DIR__ . '/../includes/calendar_helpers.php';

// Update active term based on current date
updateActiveTerm($pdo, $school_id);

// Get Current Academic Status
$current_status = [
    'current_year' => date('Y'),
    'current_term' => null,
    'is_holiday' => false,
    'current_holiday' => null,
    'school_status' => 'unknown'
];

try {
    $today = date('Y-m-d');
    $current_year = date('Y');
    
    // Get current term based on actual date range from database (not is_active flag)
    $stmt = $pdo->prepare("SELECT * FROM terms WHERE school_id = ? AND start_date <= ? AND end_date >= ? ORDER BY year DESC, term_number ASC LIMIT 1");
    $stmt->execute([$school_id, $today, $today]);
    $current_status['current_term'] = $stmt->fetch();
    
    // Check if today is a holiday
    $stmt = $pdo->prepare("SELECT * FROM holidays WHERE school_id = ? AND start_date <= ? AND end_date >= ? AND is_active = 1");
    $stmt->execute([$school_id, $today, $today]);
    $current_status['current_holiday'] = $stmt->fetch();
    $current_status['is_holiday'] = (bool)$current_status['current_holiday'];
    
    // Determine school status - holidays override term status
    if ($current_status['is_holiday']) {
        // School is closed during holidays
        $current_status['school_status'] = 'holiday';
    } elseif ($current_status['current_term']) {
        // School is in session if today falls within a term's date range and no holiday
        $current_status['school_status'] = 'in_session';
    } else {
        // No term active for today's date and no holiday
        $current_status['school_status'] = 'break';
    }
} catch (PDOException $e) {
    error_log("Failed to get current status: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#FF6B35">
    <title>Academic Calendar - <?php echo htmlspecialchars($school_name); ?></title>
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
        
        /* Status Badge */
        .status-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 16px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .status-in-session {
            background: #e6f4ea;
            color: #137333;
            border: 1px solid #e8eaed;
        }
        
        .status-holiday {
            background: #fce8e6;
            color: #c5221f;
            border: 1px solid #e8eaed;
        }
        
        .status-break {
            background: #fef7e0;
            color: #f9ab00;
            border: 1px solid #e8eaed;
        }
        
        .status-closed {
            background: #f1f3f4;
            color: #5f6368;
            border: 1px solid #e8eaed;
        }
        
        /* Form */
        .form-control {
            width: 100%;
            padding: 10px 12px;
            font-size: 14px;
            border: 1px solid #dadce0;
            border-radius: 25px;
            outline: none;
            transition: border-color 0.2s, box-shadow 0.2s;
            background: var(--bg-color);
        }

        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 2px rgba(255, 107, 53, 0.2);
            background: var(--bg-color);
        }
        
        .btn {
            padding: 10px 24px;
            border-radius: 25px;
            font-size: 14px;
            font-weight: 500;
            border: none;
            cursor: pointer;
            transition: background 0.2s;
            width: auto;
        }

        @media (max-width: 768px) {
            .btn {
                width: 100% !important;
            }
            
            .card-header {
                flex-direction: column !important;
                align-items: stretch !important;
            }
            
            .card-header .d-flex {
                flex-direction: column !important;
                width: 100% !important;
            }
        }
        
        .btn-primary {
            background: #FF6B35;
            color: white;
        }
        
        .btn-primary:hover {
            background: #e55a2b;
        }
        
        .btn-danger {
            background: #d32f2f;
            color: white;
        }
        
        .btn-danger:hover {
            background: #b71c1c;
        }
        
        .btn-action {
            background: #f8f9fa;
            color: #202124;
            border: 1px solid #e8eaed;
            cursor: pointer;
            margin-right: 4px;
        }

        .btn-action:hover {
            background: #e9ecef;
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
            border-radius: 8px;
            padding: 24px;
            margin-bottom: 24px;
            border: 1px solid #e8eaed;
        }

        .filter-section .card-title {
            font-size: 18px;
            font-weight: 500;
            color: #202124;
            margin-bottom: 16px;
        }
        
        .btn-sm {
            padding: 6px 12px;
            font-size: 12px;
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
            border: 2px solid #000;
            color: #000;
            font-size: 13px;
        }

        .table tbody tr:nth-child(even) {
            background: #fafafa;
        }

        .table tbody tr:hover {
            background: #f8f9fa;
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
            border: 1px solid #e8eaed;
        }
        
        .alert-danger {
            background: #fce8e6;
            color: #c5221f;
            border: 1px solid #e8eaed;
        }
        
        /* Modal */
        .modal {
            display: none;
            position: fixed;
            z-index: 10000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.32);
            font-family: 'Roboto', 'Segoe UI', Arial, sans-serif;
        }
        
        .modal-content {
            background: #ffffff;
            padding: 20px 24px;
            border-radius: 24px;
            max-width: 400px;
            width: 90%;
            margin: 15% auto;
            text-align: center;
            box-shadow: 0 24px 38px 3px rgba(0,0,0,0.14), 0 9px 46px 8px rgba(0,0,0,0.12);
            animation: modalSlideIn 0.3s ease-out;
            border: 1px solid #e8eaed;
            position: relative;
        }
        
        @keyframes modalSlideIn {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .modal-header h3 {
            margin: 0 0 8px 0;
            font-size: 20px;
            font-weight: 400;
            color: #202124;
            line-height: 24px;
        }
        
        .close {
            position: absolute;
            top: 12px;
            right: 12px;
            background: none;
            border: none;
            font-size: 20px;
            color: #5f6368;
            cursor: pointer;
            width: 28px;
            height: 28px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: background 0.2s;
        }
        
        .close:hover {
            background: #f1f3f4;
        }
        
        .modal-body {
            margin-bottom: 16px;
        }
        
        .modal-body p {
            margin: 0;
            font-size: 14px;
            color: #5f6368;
            line-height: 20px;
        }
        
        .modal-footer {
            display: flex;
            justify-content: flex-end;
            margin-top: 16px;
            padding-top: 16px;
            gap: 8px;
            border-top: 1px solid #e8eaed;
        }
        
        .modal-footer button {
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            letter-spacing: 0.25px;
            text-transform: uppercase;
            transition: background 0.2s;
        }
        
        .modal-footer .btn-cancel {
            background: transparent;
            color: #1a73e8;
        }
        
        .modal-footer .btn-cancel:hover {
            background: #f1f3f4;
        }
        
        .modal-footer .btn-confirm {
            background: #1a73e8;
            color: white;
        }
        
        .modal-footer .btn-confirm:hover {
            background: #1557b0;
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

            .form-control {
                margin-bottom: 12px;
            }

            .table-responsive {
                overflow-x: auto;
                width: 100%;
            }

            .table {
                min-width: 100%;
                width: 100%;
                font-size: 11px;
            }

            .table thead th {
                padding: 8px 4px;
                font-size: 11px;
            }

            .table td {
                padding: 8px 4px;
                font-size: 11px;
            }

            .calendar-grid {
                grid-template-columns: repeat(7, 1fr);
                gap: 4px;
            }

            .calendar-header {
                font-size: 12px;
                padding: 8px 4px;
            }

            .calendar-day {
                font-size: 12px;
                padding: 8px 4px;
                min-height: 60px;
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
    <br>
    <br>
    
    <?php $active_page = 'calendar'; require_once 'includes/sidebar.php'; ?>
    
    <br>
    
    <!-- Main Content -->
    <main class="main-content" id="mainContent">
        <h1 class="page-title">Academic Calendar</h1>
        
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
        
        <!-- School Session Status Card -->
        <div class="card" style="background: rgba(255, 107, 53, 0.85); backdrop-filter: blur(10px); -webkit-backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.3); box-shadow: 0 8px 32px rgba(255, 107, 53, 0.3); padding: 40px; min-height: 150px;">
            <h2 class="card-title" style="color: white;">School Session Status</h2>
            <?php if ($current_status['is_holiday']): ?>
                <div style="display: flex; align-items: center; gap: 16px; margin-bottom: 12px;">
                    <i class="fas fa-umbrella-beach" style="color: white; font-size: 32px;"></i>
                    <div>
                        <p style="color: white; font-weight: 600; margin: 0; font-size: 16px;">
                            School is on Holiday
                        </p>
                        <p style="color: white; margin: 6px 0 0 0; font-size: 14px;">
                            <?php echo htmlspecialchars($current_status['current_holiday']['holiday_name']); ?>
                            (<?php echo date('M j, Y', strtotime($current_status['current_holiday']['start_date'])); ?> -
                            <?php echo date('M j, Y', strtotime($current_status['current_holiday']['end_date'])); ?>)
                        </p>
                    </div>
                </div>
                <div style="margin-top: 16px; padding-top: 16px; border-top: 1px solid rgba(255,255,255,0.3);">
                    <p style="color: white; margin: 0; font-size: 13px;">
                        <i class="fas fa-calendar-alt" style="margin-right: 8px;"></i>
                        Academic Year: <?php echo $current_status['current_year']; ?>
                    </p>
                    <p style="color: white; margin: 8px 0 0 0; font-size: 13px;">
                        <i class="fas fa-clock" style="margin-right: 8px;"></i>
                        Today: <?php echo date('F j, Y'); ?>
                    </p>
                </div>
            <?php elseif ($current_status['current_term']): ?>
                <div style="display: flex; align-items: center; gap: 16px; margin-bottom: 12px;">
                    <i class="fas fa-school" style="color: white; font-size: 32px;"></i>
                    <div>
                        <p style="color: white; font-weight: 600; margin: 0; font-size: 16px;">
                            School is in Session
                        </p>
                        <p style="color: white; margin: 6px 0 0 0; font-size: 14px;">
                            <?php echo htmlspecialchars($current_status['current_term']['term_name']); ?>
                        </p>
                        <p style="color: white; margin: 4px 0 0 0; font-size: 13px; opacity: 0.9;">
                            <?php echo date('M j, Y', strtotime($current_status['current_term']['start_date'])); ?> -
                            <?php echo date('M j, Y', strtotime($current_status['current_term']['end_date'])); ?>
                        </p>
                    </div>
                </div>
                <div style="margin-top: 16px; padding-top: 16px; border-top: 1px solid rgba(255,255,255,0.3);">
                    <p style="color: white; margin: 0; font-size: 13px;">
                        <i class="fas fa-calendar-alt" style="margin-right: 8px;"></i>
                        Academic Year: <?php echo $current_status['current_year']; ?>
                    </p>
                    <p style="color: white; margin: 8px 0 0 0; font-size: 13px;">
                        <i class="fas fa-clock" style="margin-right: 8px;"></i>
                        Today: <?php echo date('F j, Y'); ?>
                    </p>
                    <?php
                    $end_date = new DateTime($current_status['current_term']['end_date']);
                    $today = new DateTime();
                    $interval = $today->diff($end_date);
                    $days_remaining = $interval->days;
                    if ($days_remaining > 0):
                    ?>
                    <p style="color: white; margin: 8px 0 0 0; font-size: 13px;">
                        <i class="fas fa-hourglass-half" style="margin-right: 8px;"></i>
                        <span id="daysRemainingCount">0</span> days remaining in term
                    </p>
                    <script>
                        (function() {
                            const target = <?php echo $days_remaining; ?>;
                            const element = document.getElementById('daysRemainingCount');
                            let current = 0;
                            const increment = Math.ceil(target / 50);
                            const timer = setInterval(() => {
                                current += increment;
                                if (current >= target) {
                                    current = target;
                                    clearInterval(timer);
                                }
                                element.textContent = current;
                            }, 30);
                        })();
                    </script>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div style="display: flex; align-items: center; gap: 16px; margin-bottom: 12px;">
                    <i class="fas fa-calendar-times" style="color: white; font-size: 32px;"></i>
                    <div>
                        <p style="color: white; font-weight: 600; margin: 0; font-size: 16px;">
                            School is on Break
                        </p>
                        <p style="color: white; margin: 6px 0 0 0; font-size: 14px;">
                            No active term for current date
                        </p>
                    </div>
                </div>
                <div style="margin-top: 16px; padding-top: 16px; border-top: 1px solid rgba(255,255,255,0.3);">
                    <p style="color: white; margin: 0; font-size: 13px;">
                        <i class="fas fa-calendar-alt" style="margin-right: 8px;"></i>
                        Academic Year: <?php echo $current_status['current_year']; ?>
                    </p>
                    <p style="color: white; margin: 8px 0 0 0; font-size: 13px;">
                        <i class="fas fa-clock" style="margin-right: 8px;"></i>
                        Today: <?php echo date('F j, Y'); ?>
                    </p>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Terms -->
        <div class="card">
            <h2 class="card-title">Terms</h2>
            <form method="POST" style="margin-bottom: 24px;">
                <input type="hidden" name="create_term" value="1">
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px;">
                    <div>
                        <label style="display: block; font-size: 12px; font-weight: 500; color: #5f6368; margin-bottom: 8px;">Year</label>
                        <input type="number" class="form-control" name="year" value="<?php echo date('Y'); ?>" required>
                    </div>
                    <div>
                        <label style="display: block; font-size: 12px; font-weight: 500; color: #5f6368; margin-bottom: 8px;">Term Number</label>
                        <select class="form-control" name="term_number" required>
                            <option value="">Select</option>
                            <option value="1">Term 1</option>
                            <option value="2">Term 2</option>
                            <option value="3">Term 3</option>
                        </select>
                    </div>
                    <div>
                        <label style="display: block; font-size: 12px; font-weight: 500; color: #5f6368; margin-bottom: 8px;">Start Date</label>
                        <input type="date" class="form-control" name="start_date" required>
                    </div>
                    <div>
                        <label style="display: block; font-size: 12px; font-weight: 500; color: #5f6368; margin-bottom: 8px;">End Date</label>
                        <input type="date" class="form-control" name="end_date" required>
                    </div>
                    <div style="display: flex; align-items: flex-end;">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Add Term
                        </button>
                    </div>
                </div>
            </form>
            
            <?php if (empty($terms)): ?>
                <p style="color: #5f6368;">No terms found.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table">
                    <thead>
                        <tr>
                            <th>Year</th>
                            <th>Term Name</th>
                            <th>Term Number</th>
                            <th>Start Date</th>
                            <th>End Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($terms as $term): ?>
                            <tr>
                                <td><?php echo $term['year']; ?></td>
                                <td><?php echo htmlspecialchars($term['term_name']); ?></td>
                                <td><?php echo $term['term_number']; ?></td>
                                <td><?php echo date('M j, Y', strtotime($term['start_date'])); ?></td>
                                <td><?php echo date('M j, Y', strtotime($term['end_date'])); ?></td>
                                <td>
                                    <?php
                                    $today = date('Y-m-d');
                                    $start_date = $term['start_date'];
                                    $end_date = $term['end_date'];

                                    if ($today < $start_date) {
                                        // Term hasn't started yet
                                        echo '<span class="status-badge status-break">Coming</span>';
                                    } elseif ($today > $end_date) {
                                        // Term has ended
                                        echo '<span class="status-badge" style="background: #e8eaed; color: #5f6368;">Past</span>';
                                    } else {
                                        // Today is within the term's date range
                                        echo '<span class="status-badge status-in-session">Current</span>';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <div class="action-buttons-cell">
                                        <?php if (!$term['is_active']): ?>
                                            <button type="button" class="btn-action" onclick="activateTerm(<?php echo $term['id']; ?>)">
                                                <i class="fas fa-check"></i>
                                            </button>
                                        <?php else: ?>
                                            <span class="status-badge status-in-session">Active</span>
                                        <?php endif; ?>
                                        <button type="button" class="btn-action" onclick="confirmDeleteTerm(<?php echo $term['id']; ?>)">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Holidays -->
        <div class="card">
            <h2 class="card-title">Holidays</h2>
            <form method="POST" style="margin-bottom: 24px;">
                <input type="hidden" name="create_holiday" value="1">
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px;">
                    <div>
                        <label style="display: block; font-size: 12px; font-weight: 500; color: #5f6368; margin-bottom: 8px;">Holiday Name</label>
                        <input type="text" class="form-control" name="holiday_name" placeholder="e.g., Christmas Break" required>
                    </div>
                    <div>
                        <label style="display: block; font-size: 12px; font-weight: 500; color: #5f6368; margin-bottom: 8px;">Start Date</label>
                        <input type="date" class="form-control" name="start_date" required>
                    </div>
                    <div>
                        <label style="display: block; font-size: 12px; font-weight: 500; color: #5f6368; margin-bottom: 8px;">End Date</label>
                        <input type="date" class="form-control" name="end_date" required>
                    </div>
                    <div>
                        <label style="display: block; font-size: 12px; font-weight: 500; color: #5f6368; margin-bottom: 8px;">Type</label>
                        <select class="form-control" name="holiday_type">
                            <option value="school">School Holiday</option>
                            <option value="public">Public Holiday</option>
                            <option value="religious">Religious Holiday</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div style="grid-column: 1 / -1;">
                        <label style="display: block; font-size: 12px; font-weight: 500; color: #5f6368; margin-bottom: 8px;">Description</label>
                        <textarea class="form-control" name="description" rows="2" placeholder="Optional description..."></textarea>
                    </div>
                    <div style="display: flex; align-items: flex-end;">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Add Holiday
                        </button>
                    </div>
                </div>
            </form>
            
            <?php if (empty($holidays)): ?>
                <p style="color: #5f6368;">No holidays found.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table">
                    <thead>
                        <tr>
                            <th>Holiday Name</th>
                            <th>Type</th>
                            <th>Start Date</th>
                            <th>End Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($holidays as $holiday): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($holiday['holiday_name']); ?></td>
                                <td><?php echo ucfirst($holiday['holiday_type']); ?></td>
                                <td><?php echo date('M j, Y', strtotime($holiday['start_date'])); ?></td>
                                <td><?php echo date('M j, Y', strtotime($holiday['end_date'])); ?></td>
                                <td>
                                    <div class="action-buttons-cell">
                                        <button type="button" class="btn-action" onclick="confirmDeleteHoliday(<?php echo $holiday['id']; ?>)">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- School Events -->
        <div class="card">
            <h2 class="card-title">School Events</h2>
            <form method="POST" style="margin-bottom: 24px;">
                <input type="hidden" name="create_event" value="1">
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px;">
                    <div>
                        <label style="display: block; font-size: 12px; font-weight: 500; color: #5f6368; margin-bottom: 8px;">Event Name</label>
                        <input type="text" class="form-control" name="event_name" placeholder="e.g., Parent Meeting" required>
                    </div>
                    <div>
                        <label style="display: block; font-size: 12px; font-weight: 500; color: #5f6368; margin-bottom: 8px;">Event Date</label>
                        <input type="date" class="form-control" name="event_date" required>
                    </div>
                    <div>
                        <label style="display: block; font-size: 12px; font-weight: 500; color: #5f6368; margin-bottom: 8px;">Event Time</label>
                        <input type="time" class="form-control" name="event_time">
                    </div>
                    <div>
                        <label style="display: block; font-size: 12px; font-weight: 500; color: #5f6368; margin-bottom: 8px;">Event Type</label>
                        <select class="form-control" name="event_type">
                            <option value="other">Other</option>
                            <option value="exam">Exam</option>
                            <option value="meeting">Meeting</option>
                            <option value="sports">Sports</option>
                            <option value="cultural">Cultural</option>
                        </select>
                    </div>
                    <div style="grid-column: 1 / -1;">
                        <label style="display: block; font-size: 12px; font-weight: 500; color: #5f6368; margin-bottom: 8px;">Description</label>
                        <textarea class="form-control" name="description" rows="2" placeholder="Optional description..."></textarea>
                    </div>
                    <div style="display: flex; align-items: flex-end;">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Add Event
                        </button>
                    </div>
                </div>
            </form>
            
            <?php if (empty($school_events)): ?>
                <p style="color: #5f6368;">No events found.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table">
                    <thead>
                        <tr>
                            <th>Event Name</th>
                            <th>Type</th>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($school_events as $event): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($event['event_name']); ?></td>
                                <td><?php echo ucfirst($event['event_type']); ?></td>
                                <td><?php echo date('M j, Y', strtotime($event['event_date'])); ?></td>
                                <td><?php echo $event['event_time'] ? date('h:i A', strtotime($event['event_time'])) : '-'; ?></td>
                                <td>
                                    <div class="action-buttons-cell">
                                        <button type="button" class="btn-action" onclick="confirmDeleteEvent(<?php echo $event['id']; ?>)">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <?php require_once '../includes/copywrite.php'; ?>

    <!-- Confirmation Modal -->
    <div id="confirmModal" class="modal">
        <div class="modal-content">
            <button class="close" onclick="closeModal()">&times;</button>
            <div class="modal-header">
                <h3 id="modalTitle">Confirm Action</h3>
            </div>
            <div class="modal-body">
                <p id="modalMessage">Are you sure you want to proceed?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-cancel" onclick="closeModal()">Cancel</button>
                <button type="button" class="btn-confirm" id="confirmBtn">Confirm</button>
            </div>
        </div>
    </div>
    
    <script>
        // Modal Functions
        let confirmCallback = null;
        
        function showModal(title, message, onConfirm) {
            document.getElementById('modalTitle').textContent = title;
            document.getElementById('modalMessage').textContent = message;
            document.getElementById('confirmModal').style.display = 'block';
            confirmCallback = onConfirm;
        }
        
        function closeModal() {
            document.getElementById('confirmModal').style.display = 'none';
            confirmCallback = null;
        }
        
        document.getElementById('confirmBtn').onclick = function() {
            if (confirmCallback) {
                confirmCallback();
            }
            closeModal();
        };
        
        window.onclick = function(event) {
            const modal = document.getElementById('confirmModal');
            if (event.target == modal) {
                closeModal();
            }
        };
        
        function confirmDeleteTerm(id) {
            showModal('Delete Term', 'Are you sure you want to delete this term?', function() {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="term_id" value="${id}">
                    <input type="hidden" name="delete_term" value="1">
                `;
                document.body.appendChild(form);
                form.submit();
            });
        }
        
        function activateTerm(id) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type="hidden" name="term_id" value="${id}">
                <input type="hidden" name="activate_term" value="1">
            `;
            document.body.appendChild(form);
            form.submit();
        }
        
        function confirmDeleteHoliday(id) {
            showModal('Delete Holiday', 'Are you sure you want to delete this holiday?', function() {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="holiday_id" value="${id}">
                    <input type="hidden" name="delete_holiday" value="1">
                `;
                document.body.appendChild(form);
                form.submit();
            });
        }
        
        function confirmDeleteEvent(id) {
            showModal('Delete Event', 'Are you sure you want to delete this event?', function() {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="event_id" value="${id}">
                    <input type="hidden" name="delete_event" value="1">
                `;
                document.body.appendChild(form);
                form.submit();
            });
        }
    </script>
</body>
</html>
