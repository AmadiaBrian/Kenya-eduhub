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
    <meta name="theme-color" content="#FF6B35">
    <title>View School - Kenya EduHub</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <script>window.currentCSRFToken = "<?php echo $csrf_token; ?>";</script>
    <style>
        :root {
            --primary-color: #1a73e8;
            --primary-orange: #FF6B35;
            --primary-gold: #FFD700;
            --secondary-color: #5f6368;
            --bg-color: #F0F4F9;
            --card-bg: #F0F4F9;
            --sidebar-width: 256px;
            --header-height: 64px;
            --text-color: #202124;
            --border-color: #e8eaed;
            --form-border-color: #dadce0;
            --card-hover-bg: #f8f9fa;
        }

        .dark-mode {
            --bg-color: #1a1a1a;
            --card-bg: #1a1a1a;
            --text-color: #e8eaed;
            --secondary-color: #ffffff;
            --border-color: #2a2a2a;
            --form-border-color: #2a2a2a;
            --card-hover-bg: #252525;
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
            color: var(--text-color);
            transition: background 0.3s ease, color 0.3s ease;
        }

        .sidebar {
            position: fixed;
            top: var(--header-height);
            left: 0;
            width: var(--sidebar-width);
            height: calc(100vh - var(--header-height));
            background: var(--bg-color);
            border-right: 1px solid var(--border-color);
            overflow-y: auto;
            transition: transform 0.3s ease, background 0.3s ease, border-color 0.3s ease;
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

        .dark-mode .nav-link:hover {
            background: rgba(255, 255, 255, 0.05);
        }

        .dark-mode .nav-link.active {
            background: rgba(26, 115, 232, 0.2);
            color: #8ab4f8;
        }

        .dark-mode .nav-link {
            color: #ffffff;
        }

        .nav-link i {
            margin-right: 12px;
            font-size: 18px;
            width: 24px;
            text-align: center;
            color: var(--primary-orange);
        }

        .nav-link.active i {
            color: var(--primary-color);
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
            color: var(--text-color);
            margin-bottom: 24px;
            text-align: center;
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
            font-size: 18px;
        }

        .menu-btn:hover {
            background: #f1f3f4;
        }

        .dark-mode .menu-btn {
            color: var(--primary-gold);
            font-size: 22px;
        }

        .dark-mode .menu-btn:hover {
            background: rgba(255, 215, 0, 0.1);
        }

        .dark-mode-toggle {
            background: none;
            border: none;
            cursor: pointer;
            padding: 12px;
            border-radius: 50%;
            color: var(--secondary-color);
            transition: background 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
        }

        .dark-mode-toggle:hover {
            background: rgba(255, 255, 255, 0.1);
        }

        .dark-mode .dark-mode-toggle {
            color: var(--primary-gold);
        }

        .dark-mode .dark-mode-toggle:hover {
            background: rgba(255, 215, 0, 0.1);
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
            background: var(--card-bg);
            border-radius: 8px;
            border: 1px solid var(--border-color);
            overflow: hidden;
            transition: background 0.3s ease, border-color 0.3s ease;
            padding: 30px;
            margin-bottom: 20px;
        }

        .card-header {
            background: transparent;
            padding: 20px 25px;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .card-header h2 {
            font-size: 20px;
            font-weight: 500;
            color: var(--text-color);
            text-align: center;
        }

        .dark-mode .card-header h2 {
            color: var(--text-color);
        }

        .card h2 {
            font-size: 20px;
            font-weight: 500;
            color: var(--text-color);
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid var(--border-color);
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

        .dark-mode .info-item {
            background: #252525;
        }

        .info-item label {
            display: block;
            font-size: 12px;
            color: #5f6368;
            margin-bottom: 5px;
        }

        .dark-mode .info-item label {
            color: rgba(255, 255, 255, 0.7);
        }

        .info-item .value {
            font-size: 16px;
            font-weight: 500;
            color: #202124;
        }

        .dark-mode .info-item .value {
            color: #ffffff;
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

        .dark-mode .stat-box {
            background: #252525;
        }

        .stat-box .number {
            font-size: 32px;
            font-weight: 400;
            color: var(--primary-color);
        }

        .dark-mode .stat-box .number {
            color: #ffffff;
        }

        .stat-box .label {
            font-size: 14px;
            color: var(--secondary-color);
        }

        .dark-mode .stat-box .label {
            color: rgba(255, 255, 255, 0.8);
        }
        
        .table-responsive {
            overflow-x: auto;
        }

        /* PDF-style table */
        .pdf-table {
            width: 100%;
            border-collapse: collapse;
            font-family: 'Times New Roman', Times, serif;
            border: 2px solid #000;
        }

        .pdf-table thead {
            background: #f0f0f0;
            border-bottom: 2px solid #000;
        }

        .pdf-table th {
            padding: 12px 15px;
            text-align: left;
            font-weight: bold;
            font-size: 14px;
            color: #000;
            border: 2px solid #000;
            border-bottom: 2px solid #000;
        }

        .pdf-table th:last-child {
            width: 200px;
            min-width: 200px;
        }

        .pdf-table td {
            padding: 12px 15px;
            font-size: 14px;
            border: 2px solid #000;
            color: #000;
        }

        .pdf-table td:last-child {
            width: 200px;
            min-width: 200px;
        }

        .pdf-table tbody tr:nth-child(even) {
            background: #fafafa;
        }

        .pdf-table tbody tr:hover {
            background: #f0f0f0;
        }

        /* Dark mode PDF table */
        .dark-mode .pdf-table {
            border: 2px solid var(--primary-gold);
            background: #1a1a1a;
        }

        .dark-mode .pdf-table thead {
            background: #252525;
            border-bottom: 2px solid var(--primary-gold);
        }

        .dark-mode .pdf-table th {
            border: 2px solid var(--primary-gold);
            color: #ffffff;
            background: #252525;
        }

        .dark-mode .pdf-table td {
            border: 2px solid var(--primary-gold);
            color: #ffffff;
        }

        .dark-mode .pdf-table tbody tr:nth-child(even) {
            background: #2a2a2a;
        }

        .dark-mode .pdf-table tbody tr:hover {
            background: #3a3a3a;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border: 2px solid #000;
        }

        thead {
            background: #f0f0f0;
            border-bottom: 2px solid #000;
        }

        th {
            padding: 12px 15px;
            text-align: left;
            font-weight: bold;
            font-size: 14px;
            color: #000;
            border: 2px solid #000;
            border-bottom: 2px solid #000;
        }

        td {
            padding: 12px 15px;
            font-size: 14px;
            border: 2px solid #000;
            color: #000;
        }

        tbody tr:nth-child(even) {
            background: #fafafa;
        }

        tbody tr:hover {
            background: #f0f0f0;
        }

        /* Dark mode table */
        .dark-mode table {
            border: 2px solid var(--primary-gold);
            background: #1a1a1a;
        }

        .dark-mode thead {
            background: #252525;
            border-bottom: 2px solid var(--primary-gold);
        }

        .dark-mode th {
            border: 2px solid var(--primary-gold);
            color: #ffffff;
            background: #252525;
        }

        .dark-mode td {
            border: 2px solid var(--primary-gold);
            color: #ffffff;
        }

        .dark-mode tbody tr:nth-child(even) {
            background: #2a2a2a;
        }

        .dark-mode tbody tr:hover {
            background: #3a3a3a;
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
        
        .status-pending {
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
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }

        .btn-primary {
            background: var(--primary-orange);
            color: white;
        }

        .btn-primary:hover {
            background: #e55a2b;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(255, 107, 53, 0.4);
        }

        .btn-sm {
            padding: 6px 12px;
            font-size: 12px;
            width: auto;
        }

        .btn-action {
            background: #f1f3f4;
            color: #202124;
            border: 1px solid #000;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-action:hover {
            background: #e9ecef;
            transform: translateY(-2px);
        }

        /* Dark mode buttons */
        .dark-mode .btn-action {
            background: #252525;
            color: #ffffff;
            border: 1px solid var(--primary-gold);
        }

        .dark-mode .btn-action:hover {
            background: #3a3a3a;
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
<body class="dashboard-page">
    <!-- Header -->
    <?php require_once __DIR__ . '/../includes/header.php'; ?>
    
    <!-- Sidebar -->
    <?php
    $active_page = 'schools';
    $in_subdirectory = true;
    // Force absolute URLs to avoid path issues
    $base_url = '/kenyaeduhub/admin/';
    require __DIR__ . '/../includes/sidebar.php';
    ?>
    
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
                    <span class="material-symbols-outlined">edit</span> Edit School
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

            if (window.innerWidth <= 768) {
                sidebar.classList.toggle('show');
            } else {
                sidebar.classList.toggle('collapsed');
                mainContent.classList.toggle('expanded');
            }
        }

        function toggleSidebarSection(element) {
            element.classList.toggle('collapsed');
            const links = element.nextElementSibling;
            links.classList.toggle('collapsed');
        }
    </script>

    <!-- Footer -->
    <?php require_once __DIR__ . '/../../includes/copywrite.php'; ?>
</body>
</html>
