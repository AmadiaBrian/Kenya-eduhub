<?php
// Teacher Calendar View - Read-only view of school calendar
// Authentication is handled by index.php router
$teacher_id = $_SESSION['teacher_id'];
$teacher_name = $_SESSION['teacher_name'] ?? 'Teacher';
$school_id = $_SESSION['school_id'];

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
    <title>Calendar - <?php echo htmlspecialchars($teacher_name); ?></title>
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
        }

        .status-holiday {
            background: #fce8e6;
            color: #c5221f;
        }

        .status-break {
            background: #fef7e0;
            color: #f9ab00;
        }

        /* Table */
        .table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border: 1px solid #000;
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
    </style>
</head>
<body>
    <?php require_once 'includes/header.php'; ?>
    <?php $active_page = 'calendar'; require_once 'includes/sidebar.php'; ?>

    <!-- Main Content -->
    <div class="main-content" id="mainContent">
        <h1 class="page-title">School Calendar</h1>
        
        <!-- Current Status Card -->
        <div class="card">
            <h2 class="card-title">Current School Status</h2>
            <div style="display: flex; align-items: center; gap: 16px; margin-bottom: 16px;">
                <span class="status-badge status-<?php echo $current_status['school_status']; ?>">
                    <?php echo ucfirst(str_replace('_', ' ', $current_status['school_status'])); ?>
                </span>
                <span style="color: #5f6368;">
                    <?php echo date('F j, Y'); ?>
                </span>
            </div>
            
            <p style="color: #5f6368; margin-bottom: 8px;">
                <strong>Current Year:</strong> <?php echo $current_status['current_year']; ?>
            </p>
            
            <?php if ($current_status['current_term']): ?>
                <p style="color: #5f6368; margin-bottom: 8px;">
                    <strong>Current Term:</strong> <?php echo htmlspecialchars($current_status['current_term']['term_name']); ?>
                    (<?php echo date('M j, Y', strtotime($current_status['current_term']['start_date'])); ?> - 
                    <?php echo date('M j, Y', strtotime($current_status['current_term']['end_date'])); ?>)
                </p>
            <?php endif; ?>
            
            <?php if ($current_status['current_holiday']): ?>
                <p style="color: #c5221f; margin-bottom: 8px;">
                    <strong>Holiday:</strong> <?php echo htmlspecialchars($current_status['current_holiday']['holiday_name']); ?>
                    (<?php echo date('M j, Y', strtotime($current_status['current_holiday']['start_date'])); ?> - 
                    <?php echo date('M j, Y', strtotime($current_status['current_holiday']['end_date'])); ?>)
                </p>
            <?php endif; ?>
        </div>
        
        <!-- Terms -->
        <div class="card">
            <h2 class="card-title">Academic Terms</h2>
            <?php if (empty($terms)): ?>
                <p style="color: #5f6368;">No terms found.</p>
            <?php else: ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Year</th>
                            <th>Term Name</th>
                            <th>Term Number</th>
                            <th>Start Date</th>
                            <th>End Date</th>
                            <th>Status</th>
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
                                    <?php if ($term['is_active']): ?>
                                        <span class="status-badge status-in-session">Active</span>
                                    <?php else: ?>
                                        <span class="status-badge status-break">Not Active</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        
        <!-- Holidays -->
        <div class="card">
            <h2 class="card-title">School Holidays</h2>
            <?php if (empty($holidays)): ?>
                <p style="color: #5f6368;">No holidays found.</p>
            <?php else: ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Holiday Name</th>
                            <th>Description</th>
                            <th>Start Date</th>
                            <th>End Date</th>
                            <th>Type</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($holidays as $holiday): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($holiday['holiday_name']); ?></td>
                                <td><?php echo htmlspecialchars($holiday['description'] ?? '-'); ?></td>
                                <td><?php echo date('M j, Y', strtotime($holiday['start_date'])); ?></td>
                                <td><?php echo date('M j, Y', strtotime($holiday['end_date'])); ?></td>
                                <td><?php echo ucfirst($holiday['holiday_type']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        
        <!-- School Events -->
        <div class="card">
            <h2 class="card-title">School Events</h2>
            <?php if (empty($school_events)): ?>
                <p style="color: #5f6368;">No events found.</p>
            <?php else: ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Event Name</th>
                            <th>Description</th>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Type</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($school_events as $event): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($event['event_name']); ?></td>
                                <td><?php echo htmlspecialchars($event['description'] ?? '-'); ?></td>
                                <td><?php echo date('M j, Y', strtotime($event['event_date'])); ?></td>
                                <td><?php echo htmlspecialchars($event['event_time'] ?? '-'); ?></td>
                                <td><?php echo ucfirst($event['event_type']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <?php require_once '../includes/copywrite.php'; ?>
</body>
</html>
