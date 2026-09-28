<?php
// Teacher Timetable View
// Authentication is handled by index.php router
$teacher_id = $_SESSION['teacher_id'];
$teacher_name = $_SESSION['teacher_name'] ?? 'Teacher';
$school_id = $_SESSION['school_id'];

// Load calendar helpers
require_once __DIR__ . '/../includes/calendar_helpers.php';

// Get calendar status
$calendar_status = getSchoolCalendarStatus($pdo, $school_id);

// Get teacher's timetable assignments
$timetable_assignments = [];
try {
    $stmt = $pdo->prepare("
        SELECT ta.*, ts.day_of_week, ts.start_time, ts.end_time, ts.break_type,
               s.subject_name, t.name as timetable_name, t.year, t.term, t.status,
               c.class_name, st.stream_name
        FROM timetable_assignments ta
        JOIN timetable_slots ts ON ta.slot_id = ts.id
        JOIN subjects s ON ta.subject_id = s.id
        JOIN timetables t ON ta.timetable_id = t.id
        JOIN classes c ON ta.class_id = c.id
        JOIN streams st ON ta.stream_id = st.id
        WHERE ta.teacher_id = ? AND ta.school_id = ?
        ORDER BY FIELD(ts.day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'), ts.start_time
    ");
    $stmt->execute([$teacher_id, $school_id]);
    $timetable_assignments = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Failed to fetch teacher timetable: " . $e->getMessage());
}

// Get school-wide breaks
$school_breaks = [];
try {
    $stmt = $pdo->prepare("SELECT * FROM school_breaks WHERE school_id = ? AND is_active = 1 ORDER BY start_time");
    $stmt->execute([$school_id]);
    $school_breaks = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Failed to fetch school breaks: " . $e->getMessage());
}

// Group assignments by day for better display
$assignments_by_day = [];
$days_order = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
foreach ($days_order as $day) {
    $assignments_by_day[$day] = [];
}
foreach ($timetable_assignments as $assignment) {
    $assignments_by_day[$assignment['day_of_week']][] = $assignment;
}

// Apply breaks to weekdays only (Monday-Friday)
$breaks_by_day = [];
$weekdays = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
foreach ($days_order as $day) {
    if (in_array($day, $weekdays)) {
        $breaks_by_day[$day] = $school_breaks; // Breaks apply to weekdays
    } else {
        $breaks_by_day[$day] = []; // No breaks on weekends
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#FF6B35">
    <title>My Timetable - <?php echo htmlspecialchars($teacher_name); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/notifications.css">
    <style>
        :root {
            --primary-color: #FF6B35;
        }

        body {
            background: #f8f9fa;
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

        .card {
            background: #f8f9fa;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 24px;
            margin-bottom: 24px;
        }

        .card-title {
            font-size: 18px;
            font-weight: 500;
            color: #202124;
            margin-bottom: 16px;
            text-align: center;
        }

        .table {
            border-collapse: collapse;
            background: white;
            border: 1px solid #000;
            width: 100%;
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
            border: 1px solid #000;
            border-bottom: 2px solid #000;
            padding: 12px;
            font-weight: 600;
            color: #000;
            font-size: 13px;
            text-transform: uppercase;
        }

        .table td {
            padding: 12px;
            border: 1px solid #000;
            color: #000;
            font-size: 13px;
        }

        .table tbody tr:nth-child(even) {
            background: #f9f9f9;
        }

        .table tbody tr:hover {
            background: #f0f0f0;
        }

        .day-section {
            margin-bottom: 24px;
        }

        .day-title {
            font-size: 16px;
            font-weight: 500;
            color: var(--primary-color);
            margin-bottom: 12px;
            padding-bottom: 8px;
            border-bottom: 2px solid var(--primary-color);
        }

        .alert {
            padding: 12px 16px;
            border-radius: 4px;
            margin-bottom: 16px;
        }

        .alert-info {
            background: #e8f0fe;
            color: #1967d2;
            border: 1px solid #d2e3fc;
        }

        .badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 500;
        }

        .badge-break {
            background: #fce8e6;
            color: #c5221f;
        }
    </style>
</head>
<body>
    <?php require_once 'includes/header.php'; ?>
    <?php $active_page = 'timetable'; require_once 'includes/sidebar.php'; ?>

    <!-- Main Content -->
    <div class="main-content" id="mainContent">
        <h1 class="page-title">My Timetable</h1>
        
        <!-- Calendar Status -->
        <div style="margin-bottom: 24px;">
            <?php if ($calendar_status['is_holiday']): ?>
                <div style="background: #fce8e6; border: 1px solid #c5221f; padding: 16px; border-radius: 8px;">
                    <div style="display: flex; align-items: center; gap: 12px;">
                        <i class="fas fa-exclamation-triangle" style="color: #c5221f; font-size: 20px;"></i>
                        <div>
                            <strong style="color: #c5221f;">School is on Holiday</strong>
                            <p style="margin: 4px 0 0 0; color: #5f6368; font-size: 14px;">
                                <?php echo htmlspecialchars($calendar_status['current_holiday']['holiday_name']); ?> 
                                (<?php echo date('M j, Y', strtotime($calendar_status['current_holiday']['start_date'])); ?> - 
                                <?php echo date('M j, Y', strtotime($calendar_status['current_holiday']['end_date'])); ?>)
                            </p>
                        </div>
                    </div>
                </div>
            <?php elseif ($calendar_status['school_status'] === 'break'): ?>
                <div style="background: #fef7e0; border: 1px solid #f9ab00; padding: 16px; border-radius: 8px;">
                    <div style="display: flex; align-items: center; gap: 12px;">
                        <i class="fas fa-info-circle" style="color: #f9ab00; font-size: 20px;"></i>
                        <div>
                            <strong style="color: #b06000;">School is on Break</strong>
                            <p style="margin: 4px 0 0 0; color: #5f6368; font-size: 14px;">No active term is currently set.</p>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div style="background: #e6f4ea; border: 1px solid #137333; padding: 16px; border-radius: 8px;">
                    <div style="display: flex; align-items: center; gap: 12px;">
                        <i class="fas fa-check-circle" style="color: #137333; font-size: 20px;"></i>
                        <div>
                            <strong style="color: #137333;">School is In Session</strong>
                            <p style="margin: 4px 0 0 0; color: #5f6368; font-size: 14px;">
                                <?php if ($calendar_status['current_term']): ?>
                                    Active Term: <?php echo htmlspecialchars($calendar_status['current_term']['term_name']); ?> 
                                    (<?php echo date('M j, Y', strtotime($calendar_status['current_term']['start_date'])); ?> - 
                                    <?php echo date('M j, Y', strtotime($calendar_status['current_term']['end_date'])); ?>)
                                <?php else: ?>
                                    Year: <?php echo $calendar_status['current_year']; ?>
                                <?php endif; ?>
                            </p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        
        <?php if (empty($timetable_assignments) && empty($school_breaks)): ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i>
                No timetable assignments found. Contact your school administrator to get assigned to a timetable.
            </div>
        <?php endif; ?>
            <?php if (!empty($school_breaks)): ?>
                <div class="card mb-4" style="background: #fff3cd; border: 1px solid #ffc107;">
                    <div class="card-body">
                        <h5 class="card-title" style="color: #856404;">
                            <i class="fas fa-clock me-2"></i>School-Wide Breaks
                        </h5>
                        <p style="color: #856404; margin-bottom: 10px;">These breaks apply to weekdays (Monday-Friday):</p>
                        <?php foreach ($school_breaks as $break): ?>
                            <span class="badge me-2" style="background: #ffc107; color: #000;">
                                <i class="fas fa-coffee me-1"></i>
                                <?php echo htmlspecialchars($break['break_name']); ?>: 
                                <?php echo htmlspecialchars($break['start_time']); ?> - <?php echo htmlspecialchars($break['end_time']); ?>
                            </span>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
            
            <div class="day-section">
                <h3 class="day-title">Weekly Timetable</h3>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Day</th>
                                <th>Time</th>
                                <th>Class</th>
                                <th>Stream</th>
                                <th>Subject</th>
                                <th>Timetable</th>
                                <th>Notes</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($timetable_assignments)): ?>
                                <?php foreach ($timetable_assignments as $assignment): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($assignment['day_of_week']); ?></td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($assignment['start_time']); ?></strong> - 
                                            <?php echo htmlspecialchars($assignment['end_time']); ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($assignment['class_name']); ?></td>
                                        <td><?php echo htmlspecialchars($assignment['stream_name']); ?></td>
                                        <td><?php echo htmlspecialchars($assignment['subject_name']); ?></td>
                                        <td>
                                            <?php echo htmlspecialchars($assignment['timetable_name']); ?><br>
                                            <small style="color: #5f6368;">
                                                <?php echo $assignment['year']; ?> - <?php echo htmlspecialchars($assignment['term']); ?>
                                            </small>
                                        </td>
                                        <td><?php echo htmlspecialchars($assignment['notes'] ?? '-'); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" style="text-align: center; padding: 20px; color: #5f6368;">
                                        <i class="fas fa-calendar-times me-2"></i>
                                        No lessons scheduled for the week.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function logout() {
            if (confirm('Are you sure you want to logout?')) {
                window.location.href = 'index.php?route=logout';
            }
        }
    </script>
    <?php require_once '../includes/copywrite.php'; ?>
</body>
</html>
