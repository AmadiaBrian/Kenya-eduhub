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
    <title>Schools Management - Kenya EduHub</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
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
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: transparent;
            padding: 25px;
            border-radius: 8px;
            border: 1px solid #e8eaed;
        }
        
        .stat-card h3 {
            font-size: 36px;
            font-weight: 400;
            color: var(--primary-color);
            margin-bottom: 5px;
        }
        
        .stat-card p {
            font-size: 14px;
            color: var(--secondary-color);
        }
        
        .card {
            background: transparent;
            border-radius: 8px;
            border: 1px solid #e8eaed;
            overflow: hidden;
        }
        
        .card-header {
            background: transparent;
            padding: 20px 25px;
            border-bottom: 1px solid #e8eaed;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .card-header h2 {
            font-size: 20px;
            font-weight: 500;
            color: #202124;
        }
        
        .btn {
            padding: 10px 24px;
            border: none;
            border-radius: 25px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            transition: background 0.2s;
        }
        
        .btn-primary {
            background: #FF6B35;
            color: white;
        }
        
        .btn-primary:hover {
            background: #e55a2b;
        }
        
        .btn-sm {
            padding: 6px 12px;
            font-size: 12px;
        }
        
        .btn-action {
            background: #f8f9fa;
            color: #000;
            border: 1px solid #000;
            cursor: pointer;
        }
        
        .btn-action:hover {
            background: #e9ecef;
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
                <a class="nav-link active" href="../schools">
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
        <h1 class="page-title">Schools Management</h1>
        
        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <h3><?php echo $total_schools; ?></h3>
                <p>Total Schools</p>
            </div>
            <div class="stat-card">
                <h3><?php echo $active_schools; ?></h3>
                <p>Active Schools</p>
            </div>
            <div class="stat-card">
                <h3><?php echo $total_students; ?></h3>
                <p>Total Students</p>
            </div>
            <div class="stat-card">
                <h3><?php echo $total_teachers; ?></h3>
                <p>Total Teachers</p>
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
