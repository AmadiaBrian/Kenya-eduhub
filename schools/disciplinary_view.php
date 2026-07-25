<?php
// School Disciplinary Record View Page
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

// Get record ID from URL
$record_id = $_GET['id'] ?? 0;

if (!$record_id) {
    header('Location: disciplinary.php');
    exit;
}

// Get disciplinary record details
try {
    $stmt = $pdo->prepare("SELECT dr.*, s.admission_number, CONCAT(s.first_name, ' ', s.last_name) as student_name, 
             s.class_id, s.stream_id, c.class_name, st.stream_name, s.gender, s.date_of_birth
             FROM disciplinary_records dr
             JOIN students s ON dr.student_id = s.id
             LEFT JOIN classes c ON s.class_id = c.id
             LEFT JOIN streams st ON s.stream_id = st.id
             WHERE dr.id = ? AND dr.school_id = ?");
    $stmt->execute([$record_id, $school_id]);
    $record = $stmt->fetch();
    
    if (!$record) {
        header('Location: disciplinary.php');
        exit;
    }
} catch (PDOException $e) {
    error_log("Error fetching record: " . $e->getMessage());
    header('Location: disciplinary.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Disciplinary Record - <?php echo htmlspecialchars($school_name); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/notifications.css">
    <style>
        :root {
            --primary-color: #FF6B35;
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
        
        /* Header */
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
            font-size: 20px;
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
        }
        
        /* Sidebar */
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
        
        /* Main Content */
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
        
        /* Cards */
        .card {
            background: var(--card-bg);
            border: 1px solid #e8eaed;
            border-radius: 8px;
            padding: 24px;
            margin-bottom: 24px;
        }
        
        .card-title {
            font-size: 18px;
            font-weight: 500;
            color: #202124;
            margin-bottom: 16px;
            padding-bottom: 12px;
            border-bottom: 1px solid #e8eaed;
        }
        
        /* Info Grid */
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }
        
        .info-item {
            display: flex;
            flex-direction: column;
        }
        
        .info-label {
            font-size: 12px;
            font-weight: 500;
            color: #5f6368;
            margin-bottom: 4px;
            text-transform: uppercase;
        }
        
        .info-value {
            font-size: 14px;
            color: #202124;
            font-weight: 400;
        }
        
        /* Status Badges */
        .badge {
            padding: 4px 12px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 500;
            display: inline-block;
        }
        
        .badge-pending { background: #6c757d; color: white; }
        .badge-active { background: #198754; color: white; }
        .badge-resolved { background: #0d6efd; color: white; }
        
        /* Action Buttons */
        .btn {
            padding: 8px 16px;
            border-radius: 4px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            border: none;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-primary {
            background: var(--primary-color);
            color: white;
        }
        
        .btn-secondary {
            background: #5f6368;
            color: white;
        }
                
        .btn-outline {
            background: transparent;
            border: 1px solid #e8eaed;
            color: #5f6368;
        }
        
        .btn-outline:hover {
            background: #f1f3f4;
        }
        
        /* Description Box */
        .description-box {
            background: #f8f9fa;
            border: 1px solid #e8eaed;
            border-radius: 8px;
            padding: 16px;
            margin-top: 12px;
        }
        
        .description-box h4 {
            font-size: 14px;
            font-weight: 500;
            margin-bottom: 8px;
            color: #202124;
        }
        
        .description-box p {
            font-size: 14px;
            color: #5f6368;
            line-height: 1.6;
            margin: 0;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-256px);
            }
            
            .sidebar.show {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .header {
                padding: 0 16px;
            }
            
            .main-content {
                padding: 16px;
                padding-top: calc(var(--header-height) + 16px);
            }
            
            .info-grid {
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
                <?php echo strtoupper(substr($school_name, 0, 1)); ?>
            </div>
        </div>
    </header>
    
    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-section">
            <div class="sidebar-title">Main</div>
            <a class="nav-link" href="dashboard.php">
                <i class="fas fa-home"></i> Dashboard
            </a>
            <a class="nav-link" href="students.php">
                <i class="fas fa-users"></i> Students
            </a>
            <a class="nav-link" href="teachers.php">
                <i class="fas fa-chalkboard-teacher"></i> Teachers
            </a>
            <a class="nav-link" href="classes.php">
                <i class="fas fa-school"></i> Classes
            </a>
            <a class="nav-link" href="subjects.php">
                <i class="fas fa-book"></i> Subjects
            </a>
            <a class="nav-link" href="exam-types.php">
                <i class="fas fa-clipboard-list"></i> Exam Types
            </a>
            <a class="nav-link" href="fees.php">
                <i class="fas fa-file-invoice-dollar"></i> Fee Management
            </a>
            <a class="nav-link active" href="disciplinary.php">
                <i class="fas fa-exclamation-triangle"></i> Disciplinary
            </a>
        </div>
        <div class="sidebar-section">
            <div class="sidebar-title">Settings</div>
            <a class="nav-link" href="settings.php">
                <i class="fas fa-cog"></i> Settings
            </a>
            <a class="nav-link" href="api/logout.php">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </aside>
    
    <!-- Main Content -->
    <main class="main-content" id="mainContent">
        <h1 class="page-title">Disciplinary Record Details</h1>
        
        <!-- Student Information -->
        <div class="card">
            <h2 class="card-title">Student Information</h2>
            <div class="info-grid">
                <div class="info-item">
                    <span class="info-label">Admission Number</span>
                    <span class="info-value"><strong><?php echo htmlspecialchars($record['admission_number']); ?></strong></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Student Name</span>
                    <span class="info-value"><?php echo htmlspecialchars($record['student_name']); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Class</span>
                    <span class="info-value"><?php echo htmlspecialchars($record['class_name'] ?? '-'); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Stream</span>
                    <span class="info-value"><?php echo htmlspecialchars($record['stream_name'] ?? '-'); ?></span>
                </div>
            </div>
        </div>
        
        <!-- Incident Details -->
        <div class="card">
            <h2 class="card-title">Incident Details</h2>
            <div class="info-grid">
                <div class="info-item">
                    <span class="info-label">Incident Date</span>
                    <span class="info-value"><?php echo date('F d, Y', strtotime($record['incident_date'])); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Action Type</span>
                    <span class="info-value"><strong><?php echo ucfirst($record['action_type']); ?></strong></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Severity</span>
                    <span class="info-value"><strong><?php echo ucfirst($record['severity']); ?></strong></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Status</span>
                    <span class="info-value">
                        <span class="badge badge-<?php echo $record['status']; ?>">
                            <?php echo ucfirst($record['status']); ?>
                        </span>
                    </span>
                </div>
                <div class="info-item">
                    <span class="info-label">Recorded Date</span>
                    <span class="info-value"><?php echo date('F d, Y g:i A', strtotime($record['created_at'])); ?></span>
                </div>
            </div>
            
            <div class="description-box">
                <h4>Title</h4>
                <p><?php echo htmlspecialchars($record['title']); ?></p>
            </div>
            
            <div class="description-box">
                <h4>Description</h4>
                <p><?php echo nl2br(htmlspecialchars($record['description'] ?? 'No description provided.')); ?></p>
            </div>
        </div>
        
        <!-- Action Taken -->
        <?php if (!empty($record['action_taken'])): ?>
        <div class="card">
            <h2 class="card-title">Action Taken</h2>
            <div class="description-box">
                <p><?php echo nl2br(htmlspecialchars($record['action_taken'])); ?></p>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Notes -->
        <?php if (!empty($record['notes'])): ?>
        <div class="card">
            <h2 class="card-title">Additional Notes</h2>
            <div class="description-box">
                <p><?php echo nl2br(htmlspecialchars($record['notes'])); ?></p>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Actions -->
        <div class="card">
            <h2 class="card-title">Actions</h2>
            <div style="display: flex; gap: 12px; flex-wrap: wrap;">
                <a href="disciplinary.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Records
                </a>
                <a href="disciplinary.php" class="btn btn-outline" onclick="editRecord(<?php echo $record['id']; ?>); return false;">
                    <i class="fas fa-edit"></i> Edit Record
                </a>
                <?php if (in_array($record['action_type'], ['suspension', 'expulsion', 'transfer'])): ?>
                <a href="disciplinary_document.php?id=<?php echo $record['id']; ?>" class="btn btn-primary" target="_blank">
                    <i class="fas fa-file-pdf"></i> Generate PDF
                </a>
                <?php endif; ?>
            </div>
        </div>
    </main>
    
    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const mainContent = document.getElementById('mainContent');
            sidebar.classList.toggle('collapsed');
            mainContent.classList.toggle('expanded');
        }
        
        function editRecord(id) {
            // Redirect to disciplinary page with edit parameter
            window.location.href = 'disciplinary?edit=' + id;
        }
    </script>
    <script src="../assets/js/notifications.js"></script>
</body>
</html>
