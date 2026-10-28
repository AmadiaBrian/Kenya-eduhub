<?php
// Disciplinary Action Types Management Page
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

// Get action types for this school
try {
    $stmt = $pdo->prepare("SELECT * FROM disciplinary_action_types WHERE school_id = ? ORDER BY action_name");
    $stmt->execute([$school_id]);
    $action_types = $stmt->fetchAll();
} catch (PDOException $e) {
    $action_types = [];
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action_type = $_POST['action_type'] ?? '';
    $action_name = $_POST['action_name'] ?? '';
    $description = $_POST['description'] ?? '';
    $severity = $_POST['severity'] ?? 'moderate';
    
    if ($action_type && $action_name) {
        try {
            $stmt = $pdo->prepare("INSERT INTO disciplinary_action_types (school_id, action_type, action_name, description, severity) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$school_id, $action_type, $action_name, $description, $severity]);
            header('Location: disciplinary-action-types.php');
            exit;
        } catch (PDOException $e) {
            $error = "Failed to add action type: " . $e->getMessage();
        }
    } else {
        $error = "Please fill in all required fields";
    }
}

// Handle deletion
if (isset($_GET['delete'])) {
    $delete_id = (int)$_GET['delete'];
    try {
        $stmt = $pdo->prepare("DELETE FROM disciplinary_action_types WHERE id = ? AND school_id = ?");
        $stmt->execute([$delete_id, $school_id]);
        header('Location: disciplinary-action-types.php');
        exit;
    } catch (PDOException $e) {
        $error = "Failed to delete action type";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#FF6B35">
    <title>Disciplinary Action Types - <?php echo htmlspecialchars($school_name); ?></title>
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
            box-shadow: none;
            margin: 0 0 24px 0;
        }

        .card-header {
            background: var(--bg-color);
            border-bottom: 1px solid rgba(0,0,0,0.04);
            padding: 16px 20px;
            font-weight: 500;
            color: var(--secondary-color);
            border-radius: 12px;
            text-align: center;
        }
        
        .card-body {
            padding: 0;
            border-radius: 12px;
        }
        
        .card-title {
            font-size: 18px;
            font-weight: 500;
            color: #202124;
            margin-bottom: 16px;
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

        .btn-sm {
            padding: 6px 12px;
            font-size: 12px;
            border-radius: 12px;
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
        
        .badge-minor { 
            background: #e3f2fd; 
            color: #1976d2; 
        }
        .badge-moderate { 
            background: #fff8e1; 
            color: #f57c00; 
        }
        .badge-severe { 
            background: #fce8e6; 
            color: #c5221f; 
        }
        .badge-critical { 
            background: #c5221f; 
            color: white; 
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
        
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .form-group label {
            font-size: 12px;
            font-weight: 500;
            color: #5f6368;
            margin-bottom: 6px;
            display: block;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }
        
        .form-group .form-control {
            font-size: 14px;
            padding: 10px 12px;
            border: 1px solid #dadce0;
            border-radius: 20px;
            transition: border-color 0.2s;
        }
        
        .form-group .form-control:focus {
            border-color: var(--primary-color);
            outline: none;
            box-shadow: 0 0 0 2px rgba(255, 107, 53, 0.2);
        }
        
        .form-actions {
            display: flex;
            gap: 12px;
            margin-top: 20px;
        }
        


        /* Footer styling */
        .kenya-eduhub-footer-unique-2026 {
            background: var(--bg-color) !important;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }

            .sidebar.show {
                transform: translateX(0);
            }

            .main-content {
                margin-left: 0;
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

            .form-grid {
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

            .table th {
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
<br>
<br>
<br>

<?php $active_page = 'disciplinary-action-types'; require_once 'includes/sidebar.php'; ?>

<br>
<br>
<!-- Main Content -->
    <main class="main-content" id="mainContent">
        <h1 class="page-title">Disciplinary Action Types</h1>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-danger">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <!-- Add New Action Type -->
        <div class="card">
            <div class="card-header">
                <i class="fas fa-plus me-2"></i>Add New Action Type
            </div>
            <div class="card-body">
                <form method="POST">
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 16px;">
                        <div>
                            <label class="form-label">Action Type (code)</label>
                            <input type="text" class="form-control" name="action_type" placeholder="e.g., warning, suspension" required>
                        </div>
                        <div>
                            <label class="form-label">Action Name (display)</label>
                            <input type="text" class="form-control" name="action_name" placeholder="e.g., Warning, Suspension" required>
                        </div>
                        <div>
                            <label class="form-label">Severity</label>
                            <select class="form-control" name="severity">
                                <option value="minor">Minor</option>
                                <option value="moderate" selected>Moderate</option>
                                <option value="severe">Severe</option>
                                <option value="critical">Critical</option>
                            </select>
                        </div>
                    </div>
                    <div style="margin-bottom: 16px;">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" name="description" rows="3"></textarea>
                    </div>
                    <div style="display: flex; flex-direction: column; gap: 12px;">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i>Add Action Type
                        </button>
                        <button type="button" class="btn btn-secondary" onclick="window.location.href='disciplinary'">
                            <i class="fas fa-arrow-left me-2"></i>Back to Disciplinary
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Existing Action Types -->
        <div class="card">
            <div class="card-header">
                <i class="fas fa-list me-2"></i>Existing Action Types
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Action Type</th>
                                <th>Action Name</th>
                                <th>Description</th>
                                <th>Severity</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($action_types)): ?>
                                <tr>
                                    <td colspan="5" style="text-align: center; padding: 40px; color: #5f6368;">
                                        No action types found. Add your first action type above.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($action_types as $type): ?>
                                    <tr>
                                        <td><code><?php echo htmlspecialchars($type['action_type']); ?></code></td>
                                        <td><?php echo htmlspecialchars($type['action_name']); ?></td>
                                        <td><?php echo htmlspecialchars($type['description'] ?? '-'); ?></td>
                                        <td>
                                            <span class="badge badge-<?php echo $type['severity']; ?>">
                                                <?php echo ucfirst($type['severity']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="?delete=<?php echo $type['id']; ?>" class="btn-action" onclick="return confirm('Are you sure you want to delete this action type?');">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <script src="../assets/js/notifications.js"></script>

<br>
<br>
<?php require_once '../includes/copywrite.php'; ?>
</body>
</html>
