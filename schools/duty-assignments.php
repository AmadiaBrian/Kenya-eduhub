<?php
// Teacher Duty Assignments - Weekly Rotation System
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

$school_name = $_SESSION['school_name'] ?? 'School';
$school_id = $_SESSION['school_id'];

// Get school logo
try {
    $stmt = $pdo->prepare("SELECT logo FROM schools WHERE id = ?");
    $stmt->execute([$school_id]);
    $school = $stmt->fetch();
    $school_logo = $school['logo'] ?? null;
} catch (PDOException $e) {
    $school_logo = null;
}

// Handle weekly duty assignment creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign_weekly_duty'])) {
    try {
        $week_start = $_POST['week_start'];
        $week_end = date('Y-m-d', strtotime($week_start . ' +6 days'));
        
        // Insert multiple teachers for the week
        foreach ($_POST['teacher_ids'] as $teacher_id) {
            $stmt = $pdo->prepare("INSERT INTO duty_assignments (school_id, teacher_id, duty_type, week_start, week_end, assigned_by) VALUES (?, ?, 'weekly', ?, ?, ?)");
            $stmt->execute([
                $school_id,
                $teacher_id,
                $week_start,
                $week_end,
                $_SESSION['school_id']
            ]);
        }
        $success = "Weekly duty team assigned successfully!";
    } catch (PDOException $e) {
        $error = "Failed to assign weekly duty: " . $e->getMessage();
    }
}

// Handle duty deletion
if (isset($_GET['delete'])) {
    try {
        $stmt = $pdo->prepare("DELETE FROM duty_assignments WHERE id = ? AND school_id = ?");
        $stmt->execute([$_GET['delete'], $school_id]);
        $success = "Duty assignment deleted successfully!";
    } catch (PDOException $e) {
        $error = "Failed to delete duty assignment.";
    }
}

// Get all teachers for dropdown
try {
    $stmt = $pdo->prepare("SELECT id, first_name, last_name FROM teachers WHERE school_id = ? ORDER BY first_name, last_name");
    $stmt->execute([$school_id]);
    $teachers = $stmt->fetchAll();
} catch (PDOException $e) {
    $teachers = [];
}

// Get all weekly duty assignments
try {
    $stmt = $pdo->prepare("
        SELECT da.*, t.first_name, t.last_name 
        FROM duty_assignments da 
        JOIN teachers t ON da.teacher_id = t.id 
        WHERE da.school_id = ? AND da.duty_type = 'weekly'
        ORDER BY da.week_start DESC
    ");
    $stmt->execute([$school_id]);
    $duty_assignments = $stmt->fetchAll();
} catch (PDOException $e) {
    $duty_assignments = [];
}

// Group assignments by week
$weekly_assignments = [];
foreach ($duty_assignments as $assignment) {
    $week_key = $assignment['week_start'];
    if (!isset($weekly_assignments[$week_key])) {
        $weekly_assignments[$week_key] = [
            'week_start' => $assignment['week_start'],
            'week_end' => $assignment['week_end'],
            'teachers' => []
        ];
    }
    $weekly_assignments[$week_key]['teachers'][] = $assignment;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#FF6B35">
    <title>Duty Assignments - <?php echo htmlspecialchars($school_name); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
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
        }

        .page-title {
            font-size: 22px;
            font-weight: 400;
            color: #202124;
            margin-bottom: 24px;
            text-align: center;
        }

        .card {
            background: var(--bg-color);
            border: 1px solid #e0e0e0;
            border-radius: 25px;
            padding: 24px;
            margin: 0 0 24px 0;
        }

        .card h4 {
            font-size: 18px;
            font-weight: 500;
            color: #202124;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .form-label {
            font-weight: 500;
            color: #202124;
            margin-bottom: 8px;
        }
        
        .form-control, .form-select {
            border: 1px solid #dadce0;
            border-radius: 20px;
            padding: 10px 12px;
            font-size: 14px;
            background: var(--bg-color);
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 2px rgba(26, 115, 232, 0.2);
        }

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
            background: var(--primary-color);
            border: none;
            padding: 10px 24px;
            border-radius: 25px;
            font-size: 14px;
            font-weight: 500;
        }

        .btn-primary:hover {
            background: #e55a2b;
        }

        .main-action-buttons {
            display: flex;
            gap: 12px;
            margin-bottom: 24px;
        }

        .main-action-buttons .btn {
            flex: 1;
            padding: 12px 24px;
            border-radius: 25px;
        }

        @media (max-width: 768px) {
            .main-action-buttons {
                flex-direction: column;
                margin: 0 16px 16px 16px;
            }

            .main-action-buttons .btn {
                width: auto;
                padding: 10px 20px;
            }
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

        .table tbody td {
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
        
        .badge {
            padding: 6px 12px;
            border-radius: 12px;
            font-size: 12px;
        }
        
        .badge-success { background: #d4edda; color: #155724; }
        .badge-warning { background: #fff3cd; color: #856404; }
        .badge-danger { background: #f8d7da; color: #721c24; }
        .badge-info { background: #d1ecf1; color: #0c5460; }

        /* Footer styling */
        .kenya-eduhub-footer-unique-2026 {
            background: var(--bg-color) !important;
        }

        .footer {
            background: transparent;
            color: white;
            padding: 2rem;
            text-align: center;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            margin-top: 40px;
        }
        
        .weekly-schedule {
            border: 2px solid #e8eaed;
            border-radius: 8px;
            background: var(--bg-color);
            margin-bottom: 20px;
        }
        
        .weekly-schedule-header {
            background: #f0f0f0;
            color: #000;
            padding: 16px 24px;
            border-radius: 6px 6px 0 0;
            text-align: center;
            border: 2px solid #000;
            border-bottom: 2px solid #000;
        }

        .weekly-schedule-header h5 {
            margin: 0;
            font-size: 18px;
            font-weight: 500;
        }
        
        .weekly-schedule-body {
            padding: 20px;
        }
        
        .teacher-badge {
            display: inline-block;
            padding: 8px 16px;
            background: #f8f9fa;
            border: 1px solid #e8eaed;
            border-radius: 20px;
            margin: 4px;
            font-size: 14px;
            font-weight: 500;
        }

        .teacher-badge a {
            padding: 4px 8px;
            border-radius: 12px;
            transition: background 0.2s;
        }
        
        .teacher-badge:hover {
            background: #e8f0fe;
            border-color: #d2e3fc;
        }
        
        .schedule-date {
            font-size: 14px;
            color: #5f6368;
            margin-bottom: 12px;
        }
        
        .schedule-date strong {
            color: #202124;
        }
        
        .no-schedule {
            text-align: center;
            padding: 40px;
            color: #5f6368;
        }
        
        .no-schedule i {
            font-size: 48px;
            color: #e8eaed;
            margin-bottom: 16px;
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

            .schedule-grid {
                grid-template-columns: 1fr;
            }

            .btn {
                width: 100% !important;
            }

            .table-responsive {
                overflow-x: auto;
                width: 100%;
            }

            .table {
                font-size: 11px;
                min-width: 100%;
            }

            .table thead th {
                padding: 8px 4px;
                font-size: 11px;
            }

            .table tbody td {
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
<?php $active_page = 'duty-assignments'; require_once 'includes/sidebar.php'; ?>

<br>
<br>
<br>
<br>
<!-- Main Content -->
    <main class="main-content" id="mainContent">
        <h1 class="page-title">Duty Assignments</h1>

        <!-- Action Buttons -->
        <div class="main-action-buttons">
            <button onclick="window.print()" class="btn btn-primary">
                <i class="fas fa-print me-2"></i>Print Schedule
            </button>
        </div>

        <!-- Assign Duty Form -->
        <div class="card">
            <h4 class="card-title">Assign Weekly Duty Team</h4>
            <p class="text-muted" style="text-align: center; margin-bottom: 20px;">Assign multiple teachers as the duty team for a full week. They will manage the school, handle sick students, report incidents, ensure cleanliness, and maintain order.</p>
            <form method="POST">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Week Start Date (Monday)</label>
                        <input type="date" class="form-control" name="week_start" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Select Teachers on Duty (Multiple)</label>
                        <div style="border: 2px solid #e8eaed; border-radius: 25px; background: white; padding: 16px;">
                            <div style="max-height: 200px; overflow-y: auto;">
                                <?php foreach ($teachers as $teacher): ?>
                                    <div class="form-check mb-2" style="padding: 8px; border-bottom: 1px solid #f1f3f4;">
                                        <input class="form-check-input" type="checkbox" name="teacher_ids[]" value="<?php echo $teacher['id']; ?>" id="teacher_<?php echo $teacher['id']; ?>" style="width: 18px; height: 18px;">
                                        <label class="form-check-label" for="teacher_<?php echo $teacher['id']; ?>" style="font-size: 14px; font-weight: 500; color: #202124;">
                                            <?php echo htmlspecialchars($teacher['first_name'] . ' ' . $teacher['last_name']); ?>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <small class="text-muted">Select multiple teachers by checking the boxes</small>
                    </div>
                    <div class="col-12">
                        <button type="submit" name="assign_weekly_duty" class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i>Assign Weekly Duty Team
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Weekly Duty Schedule -->
        <div class="card">
            <h4 class="card-title">Weekly Duty Schedule</h4>
            <?php if (empty($weekly_assignments)): ?>
                <div class="no-schedule">
                    <i class="fas fa-calendar-times"></i>
                    <h5>No Weekly Duty Schedule</h5>
                    <p>Assign teachers to manage the school for weekly rotations.</p>
                </div>
            <?php else: ?>
                <?php foreach ($weekly_assignments as $week): ?>
                    <div class="weekly-schedule">
                        <div class="weekly-schedule-header">
                            <h5>
                                <i class="fas fa-calendar-week me-2"></i>
                                Week of <?php echo date('F j, Y', strtotime($week['week_start'])); ?>
                            </h5>
                        </div>
                        <div class="weekly-schedule-body">
                            <div class="schedule-date" style="text-align: center;">
                                <strong>Period:</strong> <?php echo date('F j, Y', strtotime($week['week_start'])); ?> - <?php echo date('F j, Y', strtotime($week['week_end'])); ?>
                            </div>
                            <div class="mb-3" style="text-align: center;">
                                <strong style="color: #202124;">Teachers on Duty:</strong>
                            </div>
                            <div style="text-align: center;">
                                <?php foreach ($week['teachers'] as $teacher): ?>
                                    <span class="teacher-badge">
                                        <?php echo htmlspecialchars($teacher['first_name'] . ' ' . $teacher['last_name']); ?>
                                        <a href="?delete=<?php echo $teacher['id']; ?>"
                                           class="text-danger ms-2"
                                           style="text-decoration: none; padding: 4px 8px; border-radius: 12px; transition: background 0.2s;"
                                           onclick="return confirm('Remove this teacher from duty?');">
                                            <i class="fas fa-times"></i>
                                        </a>
                                    </span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/notifications.js"></script>

<br>
<br>
<?php require_once '../includes/copywrite.php'; ?>
</body>
</html>
