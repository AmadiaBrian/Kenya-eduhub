<?php
// Admin Logs
// Session is started by index.php router
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/security_lite.php';

// Output CSRF token variable for use in HTML
$csrf_token = generateCSRFLite();

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id'])) {
    header("Location: /login");
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

// Read real log files
$logDir = __DIR__ . '/../logs';
$logs = [];
$currentLog = '';
$error = '';

// Function to recursively get log files
function getLogFiles($dir, $prefix = '') {
    $files = [];
    if (!is_dir($dir)) return $files;
    
    $items = scandir($dir);
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') continue;
        
        $path = $dir . '/' . $item;
        
        if (is_dir($path)) {
            // Recursively get files from subdirectories
            $subFiles = getLogFiles($path, $prefix . $item . '/');
            $files = array_merge($files, $subFiles);
        } elseif (preg_match('/\.log$/', $item)) {
            $files[] = [
                'name' => $prefix . $item,
                'path' => $path,
                'size' => filesize($path),
                'modified' => filemtime($path)
            ];
        }
    }
    
    return $files;
}

// Get all available log files including subdirectories
$logs = getLogFiles($logDir);

// Sort logs by modification time (newest first)
usort($logs, function($a, $b) {
    return $b['modified'] - $a['modified'];
});

// Get available log files and find the most recent one
$mostRecentFile = !empty($logs) ? $logs[0]['name'] : 'activity.log';
$mostRecentTime = !empty($logs) ? $logs[0]['modified'] : 0;

// Handle log file selection - prioritize security.log if it exists
$securityLogExists = false;
foreach ($logs as $log) {
    if ($log['name'] === 'security.log' || strpos($log['name'], 'security/') === 0) {
        $securityLogExists = true;
        break;
    }
}

if ($securityLogExists) {
    $logFile = $_GET['file'] ?? 'security.log';
} else {
    $logFile = $_GET['file'] ?? $mostRecentFile;
}

// Handle subdirectory paths
$logPath = $logDir . '/' . $logFile;

// Security: Validate log file path
if (!file_exists($logPath) || strpos($logFile, '..') !== false || !preg_match('/^[a-zA-Z0-9_\/.-]+$/', $logFile)) {
    if (!empty($logs)) {
        $logFile = $logs[0]['name'];
        $logPath = $logs[0]['path'];
    } else {
        $logPath = $logDir . '/activity.log';
        $logFile = 'activity.log';
    }
}

// Read current log file
if (file_exists($logPath)) {
    $currentLog = file_get_contents($logPath);
} else {
    $error = 'Log file not found. No activity has been logged yet.';
}

// Handle log clearing
if (($_GET['action'] ?? '') === 'clear' && ($_GET['confirm'] ?? '') === 'yes') {
    $fileToClear = $logDir . '/' . $logFile;
    if (file_exists($fileToClear) && preg_match('/^[a-zA-Z0-9_\/.-]+$/', $logFile)) {
        file_put_contents($fileToClear, '');
        header("Location: logs.php?file=" . urlencode($logFile) . "&cleared=1");
        exit();
    }
}

// Handle log download
if (($downloadFile = $_GET['download'] ?? '') && preg_match('/^[a-zA-Z0-9_.-]+$/', $downloadFile)) {
    $downloadPath = $logDir . '/' . $downloadFile;
    if (file_exists($downloadPath)) {
        header('Content-Type: text/plain');
        header('Content-Disposition: attachment; filename="' . $downloadFile . '"');
        header('Content-Length: ' . filesize($downloadPath));
        readfile($downloadPath);
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Logs - Kenya EduHub</title>
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
        
        .card {
            background: transparent;
            border-radius: 8px;
            border: 1px solid #e8eaed;
            overflow: hidden;
            margin-bottom: 24px;
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
        
        .card-body {
            padding: 24px;
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
        
        .log-viewer {
            background: #1e1e1e;
            color: #d4d4d4;
            padding: 20px;
            border-radius: 8px;
            font-family: 'Consolas', 'Monaco', monospace;
            font-size: 13px;
            line-height: 1.6;
            max-height: 500px;
            overflow-y: auto;
            white-space: pre-wrap;
            word-wrap: break-word;
        }
        
        .log-viewer::-webkit-scrollbar {
            width: 8px;
        }
        
        .log-viewer::-webkit-scrollbar-track {
            background: #2d2d2d;
        }
        
        .log-viewer::-webkit-scrollbar-thumb {
            background: #555;
            border-radius: 4px;
        }
        
        .log-viewer::-webkit-scrollbar-thumb:hover {
            background: #666;
        }
        
        .log-entry {
            margin-bottom: 8px;
            padding: 4px 0;
            border-bottom: 1px solid #333;
        }
        
        .log-entry:last-child {
            border-bottom: none;
        }
        
        .log-timestamp {
            color: #569cd6;
        }
        
        .log-level-info {
            color: #4ec9b0;
        }
        
        .log-level-warning {
            color: #dcdcaa;
        }
        
        .log-level-error {
            color: #f14c4c;
        }
        
        .log-level-security {
            color: #ce9178;
        }
        
        .file-selector {
            margin-bottom: 20px;
        }
        
        .file-selector select {
            padding: 10px 16px;
            border: 1px solid #e8eaed;
            border-radius: 8px;
            font-size: 14px;
            min-width: 200px;
        }
        
        .alert {
            padding: 16px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            border: 1px solid;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .alert-success {
            background: rgba(16, 124, 16, 0.1);
            color: #107c10;
            border-color: rgba(16, 124, 16, 0.3);
        }
        
        .alert-danger {
            background: rgba(212, 52, 56, 0.1);
            color: #d13438;
            border-color: rgba(212, 52, 56, 0.3);
        }
        
        .alert-warning {
            background: rgba(255, 140, 0, 0.1);
            color: #ff8c00;
            border-color: rgba(255, 140, 0, 0.3);
        }
        
        .alert i {
            font-size: 20px;
        }
    </style>
</head>
<body>
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
                <?php echo strtoupper(substr($user['username'] ?? 'A', 0, 1)); ?>
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
                <a class="nav-link" href="dashboard">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
                <a class="nav-link" href="schools">
                    <i class="fas fa-school"></i> Schools
                </a>
                <a class="nav-link" href="resources">
                    <i class="fas fa-book"></i> Resources
                </a>
            </div>
        </div>
        
        <div class="sidebar-section">
            <div class="sidebar-title" onclick="toggleSidebarSection(this)">
                Management <i class="fas fa-chevron-down chevron"></i>
            </div>
            <div class="sidebar-links">
                <a class="nav-link" href="users">
                    <i class="fas fa-users"></i> Users
                </a>
                <a class="nav-link" href="resources">
                    <i class="fas fa-book"></i> Resources
                </a>
            </div>
        </div>
        
        <div class="sidebar-section">
            <div class="sidebar-title" onclick="toggleSidebarSection(this)">
                Reports <i class="fas fa-chevron-down chevron"></i>
            </div>
            <div class="sidebar-links">
                <a class="nav-link" href="reports">
                    <i class="fas fa-chart-bar"></i> Reports
                </a>
                <a class="nav-link active" href="logs">
                    <i class="fas fa-file-alt"></i> Logs
                </a>
            </div>
        </div>
        
        <div class="sidebar-section">
            <div class="sidebar-title" onclick="toggleSidebarSection(this)">
                Settings <i class="fas fa-chevron-down chevron"></i>
            </div>
            <div class="sidebar-links">
                <a class="nav-link" href="settings">
                    <i class="fas fa-cog"></i> Settings
                </a>
                <a class="nav-link" href="logout">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>
    </aside>
    
    <!-- Main Content -->
    <div class="main-content" id="mainContent">
        <h1 class="page-title">System Logs</h1>
        
        <?php if (isset($_GET['cleared'])): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <span>Log file cleared successfully.</span>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_GET['cleared_all'])): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <span>All log files cleared successfully.</span>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle"></i>
                <span><?php echo htmlspecialchars($error); ?></span>
            </div>
        <?php endif; ?>
        
        <!-- File Selector -->
        <div class="card">
            <div class="card-header">
                <h2>Select Log File</h2>
            </div>
            <div class="card-body">
                <div class="file-selector">
                    <form method="GET">
                        <select name="file" onchange="this.form.submit()">
                            <?php foreach ($logs as $log): ?>
                                <option value="<?php echo htmlspecialchars($log['name']); ?>" <?php echo $log['name'] === $logFile ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($log['name']); ?> 
                                    (<?php echo number_format($log['size'] / 1024, 2); ?> KB)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </form>
                </div>
                
                <div style="display: flex; gap: 10px; margin-top: 10px;">
                    <?php if ($currentLog): ?>
                        <a href="?download=<?php echo htmlspecialchars($logFile); ?>" class="btn btn-action">
                            <i class="fas fa-download"></i> Download
                        </a>
                        <button type="button" class="btn btn-primary" onclick="showClearLogModal()">
                            <i class="fas fa-trash"></i> Clear Log
                        </button>
                    <?php endif; ?>
                    <?php if (!empty($logs)): ?>
                        <button type="button" class="btn btn-primary" onclick="showClearAllLogsModal()" style="background: #d13438;">
                            <i class="fas fa-trash-alt"></i> Clear All Logs
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Log Viewer -->
        <div class="card">
            <div class="card-header">
                <h2><?php echo htmlspecialchars($logFile); ?></h2>
            </div>
            <div class="card-body">
                <?php if ($currentLog): ?>
                    <div class="log-viewer">
                        <?php
                        // Parse and format log entries
                        $logLines = explode("\n", $currentLog);
                        foreach ($logLines as $line):
                            $line = trim($line);
                            if (empty($line)) continue;
                            
                            // Parse log level
                            $levelClass = 'log-level-info';
                            $level = 'INFO';
                            
                            if (preg_match('/\b(ERROR|CRITICAL|FATAL)\b/i', $line)) {
                                $levelClass = 'log-level-error';
                                $level = 'ERROR';
                            } elseif (preg_match('/\b(WARN|WARNING)\b/i', $line)) {
                                $levelClass = 'log-level-warning';
                                $level = 'WARNING';
                            } elseif (preg_match('/\b(SECURITY|AUTH|LOGIN|LOGOUT|UNAUTHORIZED)\b/i', $line)) {
                                $levelClass = 'log-level-security';
                                $level = 'SECURITY';
                            } elseif (preg_match('/\b(DEBUG)\b/i', $line)) {
                                $levelClass = 'log-level-info';
                                $level = 'DEBUG';
                            }
                            
                            // Parse timestamp
                            $timestamp = '';
                            if (preg_match('/\[\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}\]/', $line, $matches)) {
                                $timestamp = $matches[0];
                            }
                            
                            // Format the line
                            $formattedLine = htmlspecialchars($line);
                            if ($timestamp) {
                                $formattedLine = str_replace($timestamp, '<span class="log-timestamp">' . $timestamp . '</span>', $formattedLine);
                            }
                        ?>
                        <div class="log-entry">
                            <span class="<?php echo $levelClass; ?>">[<?php echo $level; ?>]</span>
                            <?php echo $formattedLine; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p style="color: #666;">No log content available.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

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
        
        function showClearLogModal() {
            try {
                document.getElementById('clearLogMessage').textContent = 'Are you sure you want to clear this log file? This action cannot be undone.';
                
                if (typeof bootstrap !== 'undefined') {
                    const modal = new bootstrap.Modal(document.getElementById('clearLogModal'));
                    modal.show();
                } else {
                    if (confirm('Are you sure you want to clear this log file?')) {
                        clearLogViaAJAX();
                    }
                }
            } catch (error) {
                console.error('Error showing modal:', error);
                if (confirm('Are you sure you want to clear this log file?')) {
                    clearLogViaAJAX();
                }
            }
        }
        
        function confirmClearLog() {
            clearLogViaAJAX();
        }
        
        function clearLogViaAJAX() {
            const logFile = '<?php echo htmlspecialchars($logFile); ?>';
            
            fetch('../api/clear_log.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    file: logFile,
                    csrf_token: window.currentCSRFToken
                })
            })
            .then(response => {
                if (response.status === 401) {
                    // Unauthorized - redirect to login
                    window.location.href = '/login';
                    throw new Error('Unauthorized');
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    // Close modal
                    const modal = bootstrap.Modal.getInstance(document.getElementById('clearLogModal'));
                    if (modal) {
                        modal.hide();
                    }
                    
                    // Show success message and reload
                    window.location.href = 'logs.php?file=' + encodeURIComponent(logFile) + '&cleared=1';
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                if (error.message !== 'Unauthorized') {
                    console.error('Error clearing log:', error);
                    alert('Failed to clear log file. Please try again.');
                }
            });
        }
        
        function showClearAllLogsModal() {
            try {
                if (typeof bootstrap !== 'undefined') {
                    const modal = new bootstrap.Modal(document.getElementById('clearAllLogsModal'));
                    modal.show();
                } else {
                    if (confirm('Are you sure you want to clear ALL log files? This action cannot be undone.')) {
                        clearAllLogsViaAJAX();
                    }
                }
            } catch (error) {
                console.error('Error showing modal:', error);
                if (confirm('Are you sure you want to clear ALL log files? This action cannot be undone.')) {
                    clearAllLogsViaAJAX();
                }
            }
        }
        
        function confirmClearAllLogs() {
            clearAllLogsViaAJAX();
        }
        
        function clearAllLogsViaAJAX() {
            fetch('../api/clear_all_logs.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    csrf_token: window.currentCSRFToken
                })
            })
            .then(response => {
                if (response.status === 401) {
                    // Unauthorized - redirect to login
                    window.location.href = '/login';
                    throw new Error('Unauthorized');
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    // Close modal
                    const modal = bootstrap.Modal.getInstance(document.getElementById('clearAllLogsModal'));
                    if (modal) {
                        modal.hide();
                    }
                    
                    // Show success message and reload
                    window.location.href = 'logs.php?cleared_all=1';
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                if (error.message !== 'Unauthorized') {
                    console.error('Error clearing all logs:', error);
                    alert('Failed to clear all log files. Please try again.');
                }
            });
        }
    </script>

    <!-- Confirmation Modal -->
    <div class="modal fade" id="clearLogModal" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="border: none; border-radius: 24px; box-shadow: 0 24px 38px 3px rgba(0,0,0,0.14), 0 9px 46px 8px rgba(0,0,0,0.12);">
                <div class="modal-header" style="border: none; padding: 24px 32px 0 32px;">
                    <h5 class="modal-title" style="font-size: 22px; font-weight: 400; color: #202124;">Clear Log File</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" style="padding: 24px 32px;">
                    <p id="clearLogMessage" style="font-size: 14px; color: #5f6368;"></p>
                </div>
                <div class="modal-footer" style="border: none; padding: 0 32px 32px 32px;">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" style="background: transparent; color: #5f6368; border: none; border-radius: 25px; padding: 10px 24px; font-size: 14px; font-weight: 500; letter-spacing: 0.25px; text-transform: uppercase;">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="confirmClearLog()" style="background: #FF6B35; color: white; border: none; border-radius: 25px; padding: 10px 24px; font-size: 14px; font-weight: 500; letter-spacing: 0.25px; text-transform: uppercase;">Confirm</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Clear All Logs Modal -->
    <div class="modal fade" id="clearAllLogsModal" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="border: none; border-radius: 24px; box-shadow: 0 24px 38px 3px rgba(0,0,0,0.14), 0 9px 46px 8px rgba(0,0,0,0.12);">
                <div class="modal-header" style="border: none; padding: 24px 32px 0 32px;">
                    <h5 class="modal-title" style="font-size: 22px; font-weight: 400; color: #202124;">Clear All Logs</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" style="padding: 24px 32px;">
                    <p style="font-size: 14px; color: #5f6368;">Are you sure you want to clear ALL log files? This action cannot be undone and will empty all log files in the logs directory including subdirectories.</p>
                </div>
                <div class="modal-footer" style="border: none; padding: 0 32px 32px 32px;">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" style="background: transparent; color: #5f6368; border: none; border-radius: 25px; padding: 10px 24px; font-size: 14px; font-weight: 500; letter-spacing: 0.25px; text-transform: uppercase;">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="confirmClearAllLogs()" style="background: #d13438; color: white; border: none; border-radius: 25px; padding: 10px 24px; font-size: 14px; font-weight: 500; letter-spacing: 0.25px; text-transform: uppercase;">Clear All</button>
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
