<?php
// Admin Schools Management
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

// Check if user is admin
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!isset($user['role']) || $user['role'] !== 'admin') {
    header("Location: ../dashboard");
    exit();
}

// Get all schools with statistics
$schools = [];
try {
    $stmt = $conn->prepare("
        SELECT s.*, 
               (SELECT COUNT(*) FROM students WHERE school_id = s.id) as student_count,
               (SELECT COUNT(*) FROM teachers WHERE school_id = s.id) as teacher_count,
               (SELECT COUNT(*) FROM classes WHERE school_id = s.id) as class_count,
               (SELECT COUNT(*) FROM fee_payments WHERE school_id = s.id AND status = 'completed') as payment_count,
               (SELECT COALESCE(sb.balance, 0) FROM school_balances sb WHERE sb.school_id = s.id) as account_balance
        FROM schools s 
        ORDER BY s.created_at DESC
    ");
    $stmt->execute();
    $result = $stmt->get_result();
    $schools = $result->fetch_all(MYSQLI_ASSOC);
} catch (Exception $e) {
    error_log("Failed to fetch schools: " . $e->getMessage());
}

// Calculate overall statistics
$total_schools = count($schools);
$total_students = array_sum(array_column($schools, 'student_count'));
$total_teachers = array_sum(array_column($schools, 'teacher_count'));
$active_schools = count(array_filter($schools, fn($s) => $s['status'] === 'active'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#FF6B35">
    <title>Schools Management - Kenya EduHub</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <script>window.currentCSRFToken = "<?php echo $csrf_token; ?>";</script>
    <style>
        :root {
            --primary-color: #1a73e8;
            --primary-orange: #FF6B35;
            --primary-gold: #FFD700;
            --secondary-color: #5f6368;
            --bg-color: #f8f9fa;
            --card-bg: #f8f9fa;
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

        /* Dark mode toggle button */
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
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 24px;
            margin-bottom: 40px;
        }

        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr) !important;
                gap: 16px !important;
            }
        }

        @media (max-width: 480px) {
            .stats-grid {
                grid-template-columns: 1fr !important;
                gap: 16px !important;
            }
        }

        .stat-card {
            background: #ffffff;
            border: 1px solid #dadce0;
            border-radius: 8px;
            padding: 20px;
            text-align: left;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.12), 0 1px 2px rgba(0, 0, 0, 0.24);
            transition: box-shadow 0.3s ease;
        }

        .stat-card:hover {
            box-shadow: 0 3px 6px rgba(0, 0, 0, 0.16), 0 3px 6px rgba(0, 0, 0, 0.23);
        }

        .stat-card h3 {
            font-size: 32px;
            font-weight: 700;
            color: #202124;
            margin-bottom: 4px;
        }

        .stat-card p {
            font-size: 14px;
            color: #5f6368;
            margin: 0;
            font-weight: 500;
        }

        .stat-card i {
            color: var(--primary-orange);
        }

        /* Dark mode stat cards */
        .dark-mode .stat-card {
            background: var(--card-bg);
            border: 1px solid var(--primary-gold);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.4);
        }

        .dark-mode .stat-card:hover {
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.5);
        }

        .dark-mode .stat-card h3 {
            color: #ffffff;
            text-shadow: none;
        }

        .dark-mode .stat-card p {
            color: rgba(255, 255, 255, 0.8);
        }

        .dark-mode .stat-card i {
            color: var(--primary-orange) !important;
            filter: none;
        }
        
        .card {
            background: var(--card-bg);
            border-radius: 8px;
            border: 1px solid var(--border-color);
            overflow: hidden;
            transition: background 0.3s ease, border-color 0.3s ease;
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
        
        .btn {
            padding: 10px 24px;
            border: none;
            border-radius: 25px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s ease;
            width: 100%;
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

        .table-responsive {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border: 2px solid #000;
            font-family: 'Times New Roman', Times, serif;
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
            border: 1px solid #000;
            border-bottom: 2px solid #000;
            background: #f0f0f0;
        }

        td {
            padding: 12px 15px;
            font-size: 12px;
            border: 1px solid #000;
            color: #000;
            font-family: 'Times New Roman', Times, serif;
        }

        tbody tr:nth-child(even) {
            background: #fafafa;
        }

        tbody tr:hover {
            background: #e8e8e8;
        }

        /* Dark mode PDF table */
        .dark-mode table {
            border: 2px solid var(--primary-gold);
            background: #1a1a1a;
        }

        .dark-mode thead {
            background: #252525;
            border-bottom: 2px solid var(--primary-gold);
        }

        .dark-mode th {
            border: 1px solid var(--primary-gold);
            color: #ffffff;
            background: #252525;
        }

        .dark-mode td {
            border: 1px solid var(--primary-gold);
            color: #ffffff;
        }

        .dark-mode tbody tr:nth-child(even) {
            background: #2a2a2a;
        }

        .dark-mode tbody tr:hover {
            background: #3a3a3a;
        }
        
        .status-badge {
            padding: 4px 12px;
            border-radius: 16px;
            font-size: 12px;
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
    <?php require_once '../includes/header.php'; ?>
    
    <!-- Sidebar -->
    <?php 
    $active_page = 'schools';
    $in_subdirectory = true;
    require_once '../includes/sidebar.php'; 
    ?>
    
    <!-- Main Content -->
    <main class="main-content" id="mainContent">
        <h1 class="page-title">Schools Management</h1>
        
        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 12px;">
                    <i class="fas fa-school" style="color: #FF6B35; font-size: 28px;"></i>
                    <div>
                        <h3><?php echo $total_schools; ?></h3>
                        <p>Total Schools</p>
                    </div>
                </div>
            </div>
            <div class="stat-card">
                <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 12px;">
                    <i class="fas fa-check-circle" style="color: #FF6B35; font-size: 28px;"></i>
                    <div>
                        <h3><?php echo $active_schools; ?></h3>
                        <p>Active Schools</p>
                    </div>
                </div>
            </div>
            <div class="stat-card">
                <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 12px;">
                    <i class="fas fa-user-graduate" style="color: #FF6B35; font-size: 28px;"></i>
                    <div>
                        <h3><?php echo $total_students; ?></h3>
                        <p>Total Students</p>
                    </div>
                </div>
            </div>
            <div class="stat-card">
                <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 12px;">
                    <i class="fas fa-chalkboard-teacher" style="color: #FF6B35; font-size: 28px;"></i>
                    <div>
                        <h3><?php echo $total_teachers; ?></h3>
                        <p>Total Teachers</p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Schools Table -->
        <div class="card">
            <div class="card-header">
                <h2>All Schools</h2>
                <button class="btn btn-primary" onclick="window.location.href='../schools-add'">
                    <i class="fas fa-plus"></i> Add New School
                </button>
            </div>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>School Code</th>
                            <th>School Name</th>
                            <th>Type</th>
                            <th>County</th>
                            <th>Students</th>
                            <th>Teachers</th>
                            <th>Classes</th>
                            <th>Account Balance</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($schools)): ?>
                            <tr>
                                <td colspan="10" style="text-align: center; padding: 40px;">
                                    No schools found. <a href="../schools-add" style="color: var(--primary-color);">Add your first school</a>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($schools as $school): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($school['school_code']); ?></td>
                                    <td><?php echo htmlspecialchars($school['school_name']); ?></td>
                                    <td><?php echo htmlspecialchars($school['school_type']); ?></td>
                                    <td><?php echo htmlspecialchars($school['county']); ?></td>
                                    <td><?php echo $school['student_count']; ?></td>
                                    <td><?php echo $school['teacher_count']; ?></td>
                                    <td><?php echo $school['class_count']; ?></td>
                                    <td><strong>KES <?php echo number_format($school['account_balance'], 2); ?></strong></td>
                                    <td>
                                        <span class="status-badge status-<?php echo $school['status']; ?>">
                                            <?php echo ucfirst($school['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-action" onclick="viewSchool(<?php echo $school['id']; ?>)">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button class="btn btn-sm btn-action" onclick="editSchool(<?php echo $school['id']; ?>)">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-sm btn-action" onclick="managePin(<?php echo $school['id']; ?>, '<?php echo htmlspecialchars($school['school_name']); ?>')" title="Manage PIN">
                                            <i class="fas fa-key"></i>
                                        </button>
                                        <?php if ($school['status'] === 'active'): ?>
                                            <button class="btn btn-sm btn-action" onclick="toggleStatus(<?php echo $school['id']; ?>, 'inactive')" title="Deactivate">
                                                <i class="fas fa-pause"></i>
                                            </button>
                                        <?php else: ?>
                                            <button class="btn btn-sm btn-action" onclick="toggleStatus(<?php echo $school['id']; ?>, 'active')" title="Activate">
                                                <i class="fas fa-play"></i>
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Dark mode toggle
        function toggleDarkMode() {
            document.body.classList.toggle('dark-mode');
            const toggleBtn = document.querySelector('.dark-mode-toggle i');
            if (toggleBtn) {
                toggleBtn.classList.toggle('fa-moon');
                toggleBtn.classList.toggle('fa-sun');
            }
            localStorage.setItem('darkMode', document.body.classList.contains('dark-mode') ? 'dark' : 'light');
        }

        // Load saved dark mode preference
        document.addEventListener('DOMContentLoaded', function() {
            const savedDarkMode = localStorage.getItem('darkMode');
            if (savedDarkMode === 'dark') {
                document.body.classList.add('dark-mode');
                const toggleBtn = document.querySelector('.dark-mode-toggle i');
                if (toggleBtn) {
                    toggleBtn.classList.remove('fa-moon');
                    toggleBtn.classList.add('fa-sun');
                }
            }
        });

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
        
        function viewSchool(id) {
            window.location.href = '../schools-view?id=' + id;
        }

        function editSchool(id) {
            window.location.href = '../schools-edit?id=' + id;
        }

        function toggleStatus(id, status) {
            try {
                console.log('toggleStatus called with id:', id, 'status:', status);
                
                // Set values
                document.getElementById('confirmStatusId').value = id;
                document.getElementById('confirmStatusValue').value = status;
                document.getElementById('confirmStatusMessage').textContent = 'Are you sure you want to change this school status to ' + status + '?';
                
                console.log('Modal element:', document.getElementById('confirmStatusModal'));
                console.log('Bootstrap available:', typeof bootstrap !== 'undefined');
                
                // Try to show modal
                if (typeof bootstrap !== 'undefined') {
                    const modal = new bootstrap.Modal(document.getElementById('confirmStatusModal'));
                    modal.show();
                    console.log('Modal shown via Bootstrap');
                } else {
                    console.error('Bootstrap not available');
                    // Fallback to browser confirm
                    if (confirm('Are you sure you want to change this school status to ' + status + '?')) {
                        window.location.href = 'api/toggle_status.php?id=' + id + '&status=' + status + '&csrf_token=' + window.currentCSRFToken;
                    }
                }
            } catch (error) {
                console.error('Error showing modal:', error);
                // Fallback to browser confirm if modal fails
                if (confirm('Are you sure you want to change this school status to ' + status + '?')) {
                    window.location.href = 'api/toggle_status.php?id=' + id + '&status=' + status + '&csrf_token=' + window.currentCSRFToken;
                }
            }
        }

        function confirmStatusChange() {
            const id = document.getElementById('confirmStatusId').value;
            const status = document.getElementById('confirmStatusValue').value;
            window.location.href = 'api/toggle_status.php?id=' + id + '&status=' + status + '&csrf_token=' + window.currentCSRFToken;
        }

        function managePin(id, schoolName) {
            document.getElementById('pinSchoolId').value = id;
            document.getElementById('pinSchoolName').textContent = schoolName;
            
            // Fetch current PIN status
            fetch('../../api/get_pin_status.php?id=' + id + '&csrf_token=' + window.currentCSRFToken)
                .then(response => {
                    console.log('PIN Status Response:', response.status, response.statusText);
                    return response.json();
                })
                .then(data => {
                    console.log('PIN Status Data:', data);
                    if (data.has_pin) {
                        document.getElementById('pinStatus').textContent = 'PIN is set';
                        document.getElementById('pinStatus').className = 'alert alert-info';
                        document.getElementById('currentPinSection').style.display = 'block';
                        document.getElementById('newPinSection').style.display = 'none';
                    } else {
                        document.getElementById('pinStatus').textContent = 'No PIN set';
                        document.getElementById('pinStatus').className = 'alert alert-warning';
                        document.getElementById('currentPinSection').style.display = 'none';
                        document.getElementById('newPinSection').style.display = 'block';
                    }
                    
                    const modal = new bootstrap.Modal(document.getElementById('pinModal'));
                    modal.show();
                })
                .catch(error => {
                    console.error('Error fetching PIN status:', error);
                    alert('Failed to load PIN status: ' + error.message);
                });
        }

        function showChangePinForm() {
            document.getElementById('changePinSection').style.display = 'block';
            document.getElementById('changePinBtn').style.display = 'none';
        }

        function savePin() {
            const id = document.getElementById('pinSchoolId').value;
            const currentPin = document.getElementById('currentPinInput').value;
            const newPin = document.getElementById('newPinInput').value;
            const confirmPin = document.getElementById('confirmPinInput').value;
            
            const formData = new FormData();
            formData.append('school_id', id);
            formData.append('current_pin', currentPin);
            formData.append('new_pin', newPin);
            formData.append('confirm_pin', confirmPin);
            formData.append('csrf_token', window.currentCSRFToken);
            
            fetch('../../api/update_pin.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    bootstrap.Modal.getInstance(document.getElementById('pinModal')).hide();
                    location.reload();
                } else {
                    alert(data.message);
                }
            })
            .catch(error => {
                console.error('Error updating PIN:', error);
                alert('Failed to update PIN');
            });
        }
    </script>

    <!-- PIN Management Modal -->
    <div class="modal fade" id="pinModal" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="border: none; border-radius: 24px; box-shadow: 0 24px 38px 3px rgba(0,0,0,0.14), 0 9px 46px 8px rgba(0,0,0,0.12);">
                <div class="modal-header" style="border: none; padding: 24px 32px 0 32px;">
                    <h5 class="modal-title" style="font-size: 22px; font-weight: 400; color: #202124;">Manage Withdrawal PIN</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" style="padding: 24px 32px;">
                    <p style="font-size: 14px; color: #5f6368; margin-bottom: 16px;">
                        School: <strong id="pinSchoolName"></strong>
                    </p>
                    
                    <div id="pinStatus" style="margin-bottom: 20px;"></div>
                    
                    <input type="hidden" id="pinSchoolId">
                    
                    <!-- New PIN Section (for schools without PIN) -->
                    <div id="newPinSection" style="display: none;">
                        <div class="mb-3">
                            <label class="form-label">New PIN</label>
                            <input type="password" class="form-control" id="newPinInput" placeholder="Enter 4+ digit PIN" minlength="4" pattern="[0-9]+" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Confirm PIN</label>
                            <input type="password" class="form-control" id="confirmPinInput" placeholder="Confirm PIN" minlength="4" pattern="[0-9]+" required>
                        </div>
                        <button type="button" class="btn btn-primary" onclick="savePin()">
                            <i class="fas fa-save me-2"></i> Set PIN
                        </button>
                    </div>
                    
                    <!-- Current PIN Section (for schools with PIN) -->
                    <div id="currentPinSection" style="display: none;">
                        <div id="changePinSection" style="display: none;">
                            <div class="mb-3">
                                <label class="form-label">Current PIN</label>
                                <input type="password" class="form-control" id="currentPinInput" placeholder="Enter current PIN" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">New PIN</label>
                                <input type="password" class="form-control" id="newPinInput" placeholder="Enter 4+ digit PIN" minlength="4" pattern="[0-9]+" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Confirm PIN</label>
                                <input type="password" class="form-control" id="confirmPinInput" placeholder="Confirm PIN" minlength="4" pattern="[0-9]+" required>
                            </div>
                            <button type="button" class="btn btn-primary" onclick="savePin()">
                                <i class="fas fa-save me-2"></i> Update PIN
                            </button>
                        </div>
                        
                        <button type="button" id="changePinBtn" class="btn btn-primary" onclick="showChangePinForm()">
                            <i class="fas fa-key me-2"></i> Change PIN
                        </button>
                    </div>
                </div>
                <div class="modal-footer" style="border: none; padding: 0 32px 32px 32px;">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" style="background: transparent; color: #5f6368; border: none; border-radius: 25px; padding: 10px 24px; font-size: 14px; font-weight: 500; letter-spacing: 0.25px; text-transform: uppercase;">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Confirmation Modal -->
    <div class="modal fade" id="confirmStatusModal" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="border: none; border-radius: 24px; box-shadow: 0 24px 38px 3px rgba(0,0,0,0.14), 0 9px 46px 8px rgba(0,0,0,0.12);">
                <div class="modal-header" style="border: none; padding: 24px 32px 0 32px;">
                    <h5 class="modal-title" style="font-size: 22px; font-weight: 400; color: #202124;">Confirm Status Change</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" style="padding: 24px 32px;">
                    <p id="confirmStatusMessage" style="font-size: 14px; color: #5f6368;"></p>
                    <input type="hidden" id="confirmStatusId">
                    <input type="hidden" id="confirmStatusValue">
                </div>
                <div class="modal-footer" style="border: none; padding: 0 32px 32px 32px;">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" style="background: transparent; color: #5f6368; border: none; border-radius: 25px; padding: 10px 24px; font-size: 14px; font-weight: 500; letter-spacing: 0.25px; text-transform: uppercase;">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="confirmStatusChange()" style="background: #FF6B35; color: white; border: none; border-radius: 25px; padding: 10px 24px; font-size: 14px; font-weight: 500; letter-spacing: 0.25px; text-transform: uppercase;">Confirm</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <?php require_once '../../includes/copywrite.php'; ?>
</body>
</html>
