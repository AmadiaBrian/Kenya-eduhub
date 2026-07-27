<?php
// Admin View School Details
// Session is started by index.php router
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/security_lite.php';

// Output CSRF token variable for use in HTML
$csrf_token = generateCSRFLite();

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login");
    exit();
}

$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!isset($user['role']) || $user['role'] !== 'admin') {
    header("Location: ../dashboard");
    exit();
}

$school_id = (int)($_GET['id'] ?? 0);
$school = null;

if ($school_id) {
    try {
        $stmt = $conn->prepare("SELECT * FROM schools WHERE id = ?");
        $stmt->bind_param("i", $school_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $school = $result->fetch_assoc();
        
        if (!$school) {
            header("Location: ../schools");
            exit();
        }
    } catch (Exception $e) {
        error_log("Error fetching school: " . $e->getMessage());
        header("Location: ../schools");
        exit();
    }
} else {
    header("Location: ../schools");
    exit();
}

// Get school statistics
$stats = [
    'students' => 0,
    'teachers' => 0,
    'classes' => 0,
    'streams' => 0,
    'subjects' => 0,
    'payments' => 0
];

try {
    $result = $conn->query("SELECT COUNT(*) as count FROM students WHERE school_id = $school_id");
    if ($result) $stats['students'] = $result->fetch_assoc()['count'] ?? 0;
} catch (Exception $e) {
    error_log("Error fetching students: " . $e->getMessage());
}

try {
    $result = $conn->query("SELECT COUNT(*) as count FROM teachers WHERE school_id = $school_id");
    if ($result) $stats['teachers'] = $result->fetch_assoc()['count'] ?? 0;
} catch (Exception $e) {
    error_log("Error fetching teachers: " . $e->getMessage());
}

try {
    $result = $conn->query("SELECT COUNT(*) as count FROM classes WHERE school_id = $school_id");
    if ($result) $stats['classes'] = $result->fetch_assoc()['count'] ?? 0;
} catch (Exception $e) {
    error_log("Error fetching classes: " . $e->getMessage());
}

try {
    // Count unique stream names across all classes for this school
    $result = $conn->query("SELECT COUNT(DISTINCT stream_name) as count FROM streams s JOIN classes c ON s.class_id = c.id WHERE c.school_id = $school_id");
    if ($result) {
        $stats['streams'] = $result->fetch_assoc()['count'] ?? 0;
    }
} catch (Exception $e) {
    error_log("Error fetching streams: " . $e->getMessage());
}

try {
    $result = $conn->query("SELECT COUNT(*) as count FROM subjects WHERE school_id = $school_id");
    if ($result) $stats['subjects'] = $result->fetch_assoc()['count'] ?? 0;
} catch (Exception $e) {
    error_log("Error fetching subjects: " . $e->getMessage());
}

try {
    $result = $conn->query("SELECT COUNT(*) as count FROM fee_payments WHERE school_id = $school_id AND status = 'completed'");
    if ($result) $stats['payments'] = $result->fetch_assoc()['count'] ?? 0;
} catch (Exception $e) {
    error_log("Error fetching payments: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View School - Kenya EduHub</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script>window.currentCSRFToken = "<?php echo $csrf_token; ?>";</script>
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
            -ms-overflow-style: none;
        }
        
        .sidebar::-webkit-scrollbar {
            display: none;
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
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
            user-select: none;
        }
        
        .sidebar-title:hover {
            background: #f1f3f4;
        }
        
        .sidebar-title .chevron {
            transition: transform 0.3s ease;
        }
        
        .sidebar-title.collapsed .chevron {
            transform: rotate(-90deg);
        }
        
        .sidebar-links {
            max-height: 1000px;
            overflow: hidden;
            transition: max-height 0.3s ease;
        }
        
        .sidebar-links.collapsed {
            max-height: 0;
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
            transition: margin-left 0.3s ease;
        }
        
        .main-content.expanded {
            margin-left: 0;
        }
        
        .page-title {
            font-size: 22px;
            font-weight: 400;
            color: #202124;
            margin-bottom: 24px;
        }
        
        .header {
            position: fixed !important;
            top: 0;
            left: 0;
            right: 0;
            height: var(--header-height);
            background: var(--card-bg);
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
            font-size: 14px;
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
                padding: 16px;
                padding-top: calc(var(--header-height) + 16px);
            }
        }
        
        .card {
            background: transparent;
            border-radius: 8px;
            border: 1px solid #e8eaed;
            padding: 30px;
            margin-bottom: 20px;
        }
        
        .card h2 {
            font-size: 20px;
            font-weight: 500;
            color: #202124;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #e8eaed;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }
        
        .info-item {
            padding: 15px;
            background: #f8f9fa;
            border-radius: 4px;
        }
        
        .info-item label {
            display: block;
            font-size: 12px;
            color: var(--secondary-color);
            margin-bottom: 5px;
        }
        
        .info-item .value {
            font-size: 16px;
            font-weight: 500;
            color: #202124;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
        }
        
        .stat-box {
            text-align: center;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 4px;
        }
        
        .stat-box .number {
            font-size: 32px;
            font-weight: 400;
            color: var(--primary-color);
        }
        
        .stat-box .label {
            font-size: 14px;
            color: var(--secondary-color);
        }
        
        .table-responsive {
            overflow-x: auto;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border: 1px solid #000;
        }
        
        thead {
            background: #f0f0f0;
            border-bottom: 2px solid #000;
        }
        
        th {
            padding: 12px 15px;
            text-align: left;
            font-weight: 500;
            font-size: 13px;
            color: #000;
            border: 1px solid #000;
            border-bottom: 2px solid #000;
        }
        
        td {
            padding: 12px 15px;
            font-size: 13px;
            border: 1px solid #000;
            color: #000;
        }
        
        tbody tr:hover {
            background: #f8f9fa;
        }
        
        .status-badge {
            padding: 6px 16px;
            border-radius: 16px;
            font-size: 14px;
            font-weight: 500;
        }
        
        .status-active {
            background: #e6f4ea;
            color: #137333;
        }
        
        .status-inactive {
            background: #fce8e6;
            color: #c5221f;
        }
        
        .status-suspended {
            background: #fef7e0;
            color: #f9ab00;
        }
        
        .btn {
            padding: 10px 24px;
            border: none;
            border-radius: 25px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            transition: background 0.2s;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-primary {
            background: #FF6B35;
            color: white;
        }
        
        .btn-primary:hover {
            background: #e55a2b;
        }
        
        .btn-action {
            background: #f8f9fa;
            color: #000;
            border: 1px solid #000;
        }
        
        .btn-action:hover {
            background: #e9ecef;
        }
        
        .nav-back {
            margin-bottom: 20px;
        }
        
        .nav-back a {
            color: var(--primary-color);
            text-decoration: none;
            font-size: 14px;
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
                <?php echo strtoupper(substr($user['name'] ?? 'A', 0, 1)); ?>
            </div>
        </div>
    </header>
    
    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-section">
            <div class="sidebar-title" onclick="toggleSidebarSection(this)">
                Main <i class="fas fa-chevron-down chevron"></i>
            </div>
            <div class="sidebar-links">
                <a class="nav-link" href="../dashboard">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
                <a class="nav-link" href="../schools">
                    <i class="fas fa-school"></i> Schools
                </a>
            </div>
        </div>
        
        <div class="sidebar-section">
            <div class="sidebar-title" onclick="toggleSidebarSection(this)">
                Management <i class="fas fa-chevron-down chevron"></i>
            </div>
            <div class="sidebar-links">
                <a class="nav-link" href="../users">
                    <i class="fas fa-users"></i> Users
                </a>
                <a class="nav-link" href="../resources">
                    <i class="fas fa-book"></i> Resources
                </a>
            </div>
        </div>
        
        <div class="sidebar-section">
            <div class="sidebar-title" onclick="toggleSidebarSection(this)">
                Reports <i class="fas fa-chevron-down chevron"></i>
            </div>
            <div class="sidebar-links">
                <a class="nav-link" href="../reports">
                    <i class="fas fa-chart-bar"></i> Reports
                </a>
                <a class="nav-link" href="../logs">
                    <i class="fas fa-file-alt"></i> Logs
                </a>
            </div>
        </div>
        
        <div class="sidebar-section">
            <div class="sidebar-title" onclick="toggleSidebarSection(this)">
                Settings <i class="fas fa-chevron-down chevron"></i>
            </div>
            <div class="sidebar-links">
                <a class="nav-link" href="../settings">
                    <i class="fas fa-cog"></i> Settings
                </a>
                <a class="nav-link" href="../logout">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>
    </aside>
    
    <!-- Main Content -->
    <main class="main-content" id="mainContent">
        <h1 class="page-title"><?php echo htmlspecialchars($school['school_name']); ?></h1>
        
        <!-- School Information -->
        <div class="card">
            <h2>School Information</h2>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Field</th>
                            <th>Value</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>School Code</td>
                            <td><?php echo htmlspecialchars($school['school_code']); ?></td>
                        </tr>
                        <tr>
                            <td>School Name</td>
                            <td><?php echo htmlspecialchars($school['school_name']); ?></td>
                        </tr>
                        <tr>
                            <td>Email</td>
                            <td><?php echo htmlspecialchars($school['email']); ?></td>
                        </tr>
                        <tr>
                            <td>Phone</td>
                            <td><?php echo htmlspecialchars($school['phone']); ?></td>
                        </tr>
                        <tr>
                            <td>County</td>
                            <td><?php echo htmlspecialchars($school['county']); ?></td>
                        </tr>
                        <tr>
                            <td>School Type</td>
                            <td><?php echo htmlspecialchars($school['school_type']); ?></td>
                        </tr>
                        <tr>
                            <td>Admission Prefix</td>
                            <td><?php echo htmlspecialchars($school['admission_prefix']); ?></td>
                        </tr>
                        <tr>
                            <td>Status</td>
                            <td>
                                <span class="status-badge status-<?php echo $school['status']; ?>">
                                    <?php echo ucfirst($school['status']); ?>
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <td>Created</td>
                            <td><?php echo date('M j, Y', strtotime($school['created_at'])); ?></td>
                        </tr>
                        <tr>
                            <td>Updated</td>
                            <td><?php echo date('M j, Y', strtotime($school['updated_at'])); ?></td>
                        </tr>
                        <?php if ($school['address']): ?>
                        <tr>
                            <td>Address</td>
                            <td><?php echo nl2br(htmlspecialchars($school['address'])); ?></td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <div style="margin-top: 30px;">
                <a href="../schools-edit?id=<?php echo $school['id']; ?>" class="btn btn-primary">
                    <i class="fas fa-edit"></i> Edit School
                </a>
            </div>
        </div>
        
        <!-- Statistics -->
        <div class="card">
            <h2>School Statistics</h2>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Metric</th>
                            <th>Count</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Students</td>
                            <td><?php echo $stats['students']; ?></td>
                        </tr>
                        <tr>
                            <td>Teachers</td>
                            <td><?php echo $stats['teachers']; ?></td>
                        </tr>
                        <tr>
                            <td>Classes</td>
                            <td><?php echo $stats['classes']; ?></td>
                        </tr>
                        <tr>
                            <td>Streams</td>
                            <td><?php echo $stats['streams']; ?></td>
                        </tr>
                        <tr>
                            <td>Subjects</td>
                            <td><?php echo $stats['subjects']; ?></td>
                        </tr>
                        <tr>
                            <td>Payments</td>
                            <td><?php echo $stats['payments']; ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const mainContent = document.getElementById('mainContent');
            sidebar.classList.toggle('collapsed');
            mainContent.classList.toggle('expanded');
        }
        
        function toggleSidebarSection(element) {
            element.classList.toggle('collapsed');
            const links = element.nextElementSibling;
            links.classList.toggle('collapsed');
        }
    </script>

    <!-- Footer -->
    <footer style="background: transparent; color: #5f6368; padding: 2rem; text-align: center; border-top: 1px solid #e8eaed; margin-top: 40px;">
        <p style="margin: 0;">
            <span style="color: #FF6B35;">&copy; 2026</span>
            <span style="color: #FF6B35;">Kenya</span>
            <span style="color: #008000;">EduHub</span>
            <span style="color: #5f6368;">. All rights reserved.</span>
        </p>
    </footer>
</body>
</html>
