<?php
// Disciplinary Action Types Management Page
// Authentication is handled by index.php router
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
    <title>Disciplinary Action Types - <?php echo htmlspecialchars($school_name); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/notifications.css">
    <style>
        :root {
            --primary-color: #FF6B35;
            --secondary-color: #5f6368;
            --bg-color: #f8f9fa;
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
            justify-content: space-between;
            padding: 0 24px;
            z-index: 1000;
        }
        
        .header-left {
            display: flex;
            align-items: center;
            gap: 16px;
        }
        
        .logo {
            display: flex;
            align-items: center;
            font-size: 18px;
            font-weight: bold;
        }
        
        .menu-btn {
            background: none;
            border: none;
            font-size: 20px;
            color: #5f6368;
            cursor: pointer;
            padding: 8px;
            border-radius: 50%;
            transition: background 0.2s;
        }
        
        .menu-btn:hover {
            background: #e8eaed;
        }
        
        .header-right {
            margin-left: auto;
        }
        
        .user-avatar {
            width: 32px;
            height: 32px;
            background: var(--primary-color);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 500;
            font-size: 14px;
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
            scrollbar-width: none; /* Firefox */
            -ms-overflow-style: none; /* IE and Edge */
        }
        
        .sidebar::-webkit-scrollbar {
            display: none; /* Chrome, Safari, Opera */
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
            padding: 10px 24px;
            color: #5f6368;
            text-decoration: none;
            transition: background 0.2s;
            border: none;
            background: none;
            width: 100%;
            text-align: left;
            cursor: pointer;
            font-size: 14px;
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
            min-height: calc(100vh - var(--header-height));
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
            background: white;
            border: 1px solid #e8eaed;
            border-radius: 8px;
            padding: 24px;
            margin-bottom: 24px;
            box-shadow: 0 1px 2px rgba(0,0,0,0.05);
        }
        
        .card-title {
            font-size: 18px;
            font-weight: 500;
            color: #202124;
            margin-bottom: 20px;
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
            border-radius: 6px;
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
        
        .table {
            background: white;
            border-radius: 8px;
            overflow: hidden;
            border: 1px solid #e8eaed;
        }
        
        .table thead th {
            background: #f8f9fa;
            font-weight: 500;
            color: #5f6368;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            padding: 12px 16px;
            border-bottom: 1px solid #e8eaed;
        }
        
        .table tbody td {
            padding: 12px 16px;
            color: #202124;
            font-size: 14px;
            border-bottom: 1px solid #e8eaed;
        }
        
        .table tbody tr:last-child td {
            border-bottom: none;
        }
        
        .badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .badge-minor { background: #e8f0fe; color: #1967d2; }
        .badge-moderate { background: #fef7e0; color: #f9ab00; }
        .badge-severe { background: #fce8e6; color: #c5221f; }
        .badge-critical { background: #c5221f; color: white; }
        
        .btn {
            padding: 8px 16px;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 500;
            border: none;
            cursor: pointer;
            transition: background 0.2s;
        }
        
        .btn-primary {
            background: var(--primary-color);
            color: white;
        }
        
        .btn-primary:hover {
            background: #e55a2b;
        }
        
        .btn-secondary {
            background: #f1f3f4;
            color: #5f6368;
        }
        
        .btn-secondary:hover {
            background: #e8eaed;
        }
        
        .btn-danger {
            background: #c5221f;
            color: white;
        }
        
        .btn-danger:hover {
            background: #b3261e;
        }
        
        .alert {
            padding: 12px 16px;
            border-radius: 6px;
            margin-bottom: 16px;
            font-size: 14px;
        }
        
        .alert-danger {
            background: #fce8e6;
            color: #c5221f;
            border: 1px solid #f5c6cb;
        }
        
        .alert-success {
            background: #e6f4ea;
            color: #137333;
            border: 1px solid #c6f6d5;
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
            }
            
            .form-grid {
                grid-template-columns: 1fr;
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
                <div style="width: 32px; height: 32px; background: #FFD700; border: 3px solid #FF6B35; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-right: 8px;">
                    <span style="font-weight: bold; font-size: 16px;">
                        <span style="color: #FF6B35; font-size: 18px;">K</span><span style="color: #008000; font-size: 16px;">E</span>
                    </span>
                </div>
                <span style="color: #FF6B35; font-weight: bold;">Kenya</span> <span style="color: #008000; font-weight: bold;">EduHub</span>
            </div>
        </div>
        <div class="header-right">
            <div class="user-avatar">
                <?php echo strtoupper(substr($school_name, 0, 1)); ?>
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
            <a class="nav-link" href="students">
                <i class="fas fa-users"></i> Students
            </a>
            <a class="nav-link" href="teachers">
                <i class="fas fa-chalkboard-teacher"></i> Teachers
            </a>
            <a class="nav-link" href="classes">
                <i class="fas fa-school"></i> Classes
            </a>
            <a class="nav-link" href="fees">
                <i class="fas fa-file-invoice-dollar"></i> Fee Management
            </a>
            <a class="nav-link active" href="disciplinary">
                <i class="fas fa-exclamation-triangle"></i> Disciplinary
            </a>
        </div>
        <div class="sidebar-section">
            <div class="sidebar-title">Settings</div>
            <a class="nav-link" href="settings">
                <i class="fas fa-cog"></i> Settings
            </a>
            <a class="nav-link" href="logout">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </aside>
    
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
            <div class="card-title">Add New Action Type</div>
            <form method="POST">
                <div class="form-grid">
                    <div class="form-group">
                        <label>Action Type (code)</label>
                        <input type="text" class="form-control" name="action_type" placeholder="e.g., warning, suspension" required>
                    </div>
                    <div class="form-group">
                        <label>Action Name (display)</label>
                        <input type="text" class="form-control" name="action_name" placeholder="e.g., Warning, Suspension" required>
                    </div>
                    <div class="form-group">
                        <label>Severity</label>
                        <select class="form-control" name="severity">
                            <option value="minor">Minor</option>
                            <option value="moderate" selected>Moderate</option>
                            <option value="severe">Severe</option>
                            <option value="critical">Critical</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <textarea class="form-control" name="description" rows="3"></textarea>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Add Action Type
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="window.location.href='disciplinary'">
                        <i class="fas fa-arrow-left"></i> Back to Disciplinary
                    </button>
                </div>
            </form>
        </div>
        
        <!-- Existing Action Types -->
        <div class="card">
            <div class="card-title">Existing Action Types</div>
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
                                        <a href="?delete=<?php echo $type['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this action type?');">
                                            <i class="fas fa-trash"></i> Delete
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
    
    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const mainContent = document.getElementById('mainContent');
            sidebar.classList.toggle('collapsed');
            sidebar.classList.toggle('show');
            mainContent.classList.toggle('expanded');
        }
        
    </script>
    <script src="../assets/js/notifications.js"></script>
</body>
</html>
