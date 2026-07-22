<?php
// Streams Management Page
// Authentication is handled by index.php router
$school_id = $_SESSION['school_id'];
$school_name = $_SESSION['school_name'] ?? 'School';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Streams - <?php echo htmlspecialchars($school_name); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/notifications.css">
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
        
        .school-avatar {
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
        
        .card {
            background: var(--bg-color);
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
        }
        
        .form-control {
            border: 1px solid #dadce0;
            border-radius: 8px;
            padding: 10px 12px;
            font-size: 14px;
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 2px rgba(26, 115, 232, 0.2);
        }
        
        .btn {
            padding: 10px 24px;
            border-radius: 4px;
            font-size: 14px;
            font-weight: 500;
            border: none;
            cursor: pointer;
            transition: background 0.2s;
        }
        
        .btn-primary {
            background: #FF6B35;
            color: white;
        }
        
        .btn-primary:hover {
            background: #e55a2b;
        }
        
        .btn-outline-primary {
            background: white;
            color: #FF6B35;
            border: 1px solid #FF6B35;
        }
        
        .btn-outline-primary:hover {
            background: #fff3e0;
        }
        
        .btn-success {
            background: #1e8e3e;
            color: white;
        }
        
        .btn-success:hover {
            background: #137333;
        }
        
        .btn-danger {
            background: #d93025;
            color: white;
        }
        
        .btn-danger:hover {
            background: #b92b20;
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
        
        @media (max-width: 768px) {
            html, body {
                overflow-x: hidden;
                position: relative;
            }
            
            .header {
                position: fixed !important;
                top: 0 !important;
                left: 0 !important;
                right: 0 !important;
                padding: 0 16px;
                box-shadow: 0 2px 4px rgba(0,0,0,0.08);
                transform: none !important;
            }
            
            .logo span {
                font-size: 18px;
            }
            
            .menu-btn {
                padding: 8px;
                border-radius: 50%;
                transition: background 0.2s;
            }
            
            .menu-btn:hover {
                background: rgba(0,0,0,0.04);
            }
            
            .sidebar {
                position: fixed !important;
                transform: translateX(-256px);
                box-shadow: 2px 0 8px rgba(0,0,0,0.15);
            }
            
            .sidebar.show {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0;
                padding: 16px;
            }
            
            .page-title {
                font-size: 22px;
                font-weight: 400;
                margin-bottom: 16px;
            }
            
            .card {
                padding: 16px;
                margin-bottom: 16px;
                border-radius: 12px;
                box-shadow: 0 1px 2px rgba(0,0,0,0.05);
            }
            
            .table-responsive {
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
                border-radius: 8px;
                width: 100%;
            }
            
            .table {
                min-width: 600px;
                width: 100%;
            }
            
            .btn {
                padding: 10px 20px;
                font-size: 14px;
                font-weight: 500;
                border-radius: 8px;
                height: 40px;
            }
            
            .form-control {
                padding: 12px;
                font-size: 16px;
                border-radius: 8px;
                border: 1px solid #dadce0;
            }
            
            .form-control:focus {
                border-color: var(--primary-color);
                box-shadow: 0 0 0 2px rgba(26, 115, 232, 0.2);
            }
            
            .card-header {
                flex-direction: column;
                gap: 12px;
                align-items: flex-start;
                padding-bottom: 12px;
                border-bottom: 1px solid #e8eaed;
            }
            
            .card-header .btn {
                width: 100%;
            }
            
            .card {
                text-align: center;
            }
            
            .card-header {
                justify-content: center;
            }
        }
        
        @media (max-width: 480px) {
            .header {
                padding: 0 12px;
            }
            
            .logo span {
                font-size: 16px;
            }
            
            .main-content {
                padding: 12px;
            }
            
            .page-title {
                font-size: 18px;
                margin-bottom: 16px;
            }
            
            .card {
                padding: 12px;
            }
            
            .menu-btn {
                padding: 8px;
            }
            
            .school-avatar {
                width: 28px;
                height: 28px;
                font-size: 12px;
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
            <div class="school-avatar">
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
                <i class="fas fa-user-graduate"></i> Students
            </a>
            <a class="nav-link" href="teachers">
                <i class="fas fa-chalkboard-teacher"></i> Teachers
            </a>
            <a class="nav-link" href="classes">
                <i class="fas fa-chalkboard"></i> Classes
            </a>
            <a class="nav-link active" href="streams">
                <i class="fas fa-layer-group"></i> Streams
            </a>
            <a class="nav-link" href="subjects">
                <i class="fas fa-book"></i> Subjects
            </a>
            <a class="nav-link" href="parents">
                <i class="fas fa-users"></i> Parents
            </a>
            <a class="nav-link" href="performance">
                <i class="fas fa-chart-line"></i> Performance
            </a>
            <a class="nav-link" href="attendance">
                <i class="fas fa-calendar-check"></i> Attendance
            </a>
            <a class="nav-link" href="fees">
                <i class="fas fa-money-bill-wave"></i> Fees
            </a>
            <a class="nav-link" href="invoices">
                <i class="fas fa-file-invoice-dollar"></i> Invoices
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
        <h1 class="page-title">Streams Management</h1>
        
        <!-- Stream Statistics -->
        <div id="streamStats" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 12px; margin-bottom: 24px;">
            <?php
            $totalStreams = 0;
            $totalStudents = 0;
            $totalCapacity = 0;
            $totalClasses = 0;
            
            try {
                $stmt = $pdo->prepare("SELECT s.id, s.capacity, c.class_name FROM streams s JOIN classes c ON s.class_id = c.id WHERE c.school_id = ?");
                $stmt->execute([$school_id]);
                $streams = $stmt->fetchAll();
                
                $classNames = [];
                foreach ($streams as $stream) {
                    $totalStreams++;
                    $totalCapacity += $stream['capacity'];
                    if (!in_array($stream['class_name'], $classNames)) {
                        $classNames[] = $stream['class_name'];
                    }
                    
                    // Count students for this stream
                    $studentStmt = $pdo->prepare("SELECT COUNT(*) as count FROM students WHERE stream_id = ?");
                    $studentStmt->execute([$stream['id']]);
                    $studentCount = $studentStmt->fetch()['count'];
                    $totalStudents += $studentCount;
                }
                $totalClasses = count($classNames);
            } catch (PDOException $e) {
                error_log("Failed to fetch stream stats: " . $e->getMessage());
            }
            ?>
            <div style="background: #e8f0fe; padding: 16px; border-radius: 8px; text-align: center;">
                <div style="font-size: 24px; font-weight: 600; color: #1967d2;"><?php echo $totalStreams; ?></div>
                <div style="font-size: 12px; color: #5f6368;">Total Streams</div>
            </div>
            <div style="background: #e6f4ea; padding: 16px; border-radius: 8px; text-align: center;">
                <div style="font-size: 24px; font-weight: 600; color: #137333;"><?php echo $totalClasses; ?></div>
                <div style="font-size: 12px; color: #5f6368;">Classes with Streams</div>
            </div>
            <div style="background: #fef7e0; padding: 16px; border-radius: 8px; text-align: center;">
                <div style="font-size: 24px; font-weight: 600; color: #b06000;"><?php echo $totalStudents; ?></div>
                <div style="font-size: 12px; color: #5f6368;">Total Students</div>
            </div>
            <div style="background: #fce8e6; padding: 16px; border-radius: 8px; text-align: center;">
                <div style="font-size: 24px; font-weight: 600; color: #c5221f;"><?php echo $totalCapacity; ?></div>
                <div style="font-size: 12px; color: #5f6368;">Total Capacity</div>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <span>All Streams</span>
                <div class="d-flex gap-2 flex-wrap">
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addStreamModal">
                        <i class="fas fa-plus me-2"></i> Add Stream
                    </button>
                    <button class="btn btn-success" onclick="exportStreams()">
                        <i class="fas fa-download me-2"></i> Export
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-4">
                        <input type="text" class="form-control" id="searchStream" placeholder="Search by stream name or class..." onkeyup="filterStreams()">
                    </div>
                    <div class="col-md-4">
                        <select class="form-control" id="filterClass" onchange="loadStreams()">
                            <option value="">All Classes</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <select class="form-control" id="filterStreamCapacity" onchange="filterStreams()">
                            <option value="">All Capacities</option>
                            <option value="low">Low (&lt;30)</option>
                            <option value="medium">Medium (30-50)</option>
                            <option value="high">High (&gt;50)</option>
                        </select>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Stream Name</th>
                                <th>Class</th>
                                <th>Level</th>
                                <th>Capacity</th>
                                <th>Students</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="streamsTable">
                            <tr><td colspan="6" class="text-center">Loading...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <div class="modal fade" id="addStreamModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Stream</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="addStreamForm">
                        <div class="mb-3">
                            <label class="form-label">Class</label>
                            <select class="form-control" id="streamClassId" required>
                                <option value="">Select Class</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Stream Name</label>
                            <input type="text" class="form-control" id="streamName" placeholder="e.g., East" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Capacity</label>
                            <input type="number" class="form-control" id="streamCapacity" value="30">
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="addStream()">Add Stream</button>
                </div>
            </div>
        </div>
    </div>
    
    <div class="modal fade" id="editStreamModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Stream</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="editStreamForm">
                        <input type="hidden" id="editStreamId">
                        <div class="mb-3">
                            <label class="form-label">Stream Name</label>
                            <input type="text" class="form-control" id="editStreamName" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Capacity</label>
                            <input type="number" class="form-control" id="editStreamCapacity">
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="updateStream()">Update Stream</button>
                </div>
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
        
        async function loadClasses() {
            try {
                const response = await fetch('api/classes.php');
                const data = await response.json();
                if (data.success) {
                    const options = data.data.map(c => `<option value="${c.id}">${c.class_name} (${c.class_level})</option>`).join('');
                    document.getElementById('filterClass').innerHTML = '<option value="">All Classes</option>' + options;
                    document.getElementById('streamClassId').innerHTML = '<option value="">Select Class</option>' + options;
                }
            } catch (error) {
                console.error('Error loading classes:', error);
            }
        }
        
        async function loadStreams() {
            const classId = document.getElementById('filterClass').value;
            const url = classId ? `api/streams.php?class_id=${classId}` : 'api/streams.php';
            
            try {
                const response = await fetch(url);
                const data = await response.json();
                if (data.success) {
                    const tbody = document.getElementById('streamsTable');
                    tbody.innerHTML = data.data.map(stream => `
                        <tr>
                            <td>${stream.stream_name}</td>
                            <td>${stream.class_name}</td>
                            <td>${stream.class_level}</td>
                            <td>${stream.capacity}</td>
                            <td>${stream.student_count}</td>
                            <td>
                                <button class="btn btn-sm btn-primary" onclick="editStream(${stream.id})">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-sm btn-danger" onclick="deleteStream(${stream.id})">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                    `).join('');
                }
            } catch (error) {
                console.error('Error loading streams:', error);
            }
        }
        
        // Filter streams
        function filterStreams() {
            const search = document.getElementById('searchStream').value.toLowerCase();
            const capacity = document.getElementById('filterStreamCapacity').value;
            
            const rows = document.querySelectorAll('#streamsTable tr');
            rows.forEach(row => {
                const cells = row.querySelectorAll('td');
                if (cells.length === 0) return;
                
                const streamName = cells[0].textContent.toLowerCase();
                const className = cells[1].textContent.toLowerCase();
                const capacityValue = parseInt(cells[3].textContent);
                
                const matchesSearch = streamName.includes(search) || className.includes(search);
                
                let matchesCapacity = true;
                if (capacity === 'low') {
                    matchesCapacity = capacityValue < 30;
                } else if (capacity === 'medium') {
                    matchesCapacity = capacityValue >= 30 && capacityValue <= 50;
                } else if (capacity === 'high') {
                    matchesCapacity = capacityValue > 50;
                }
                
                row.style.display = (matchesSearch && matchesCapacity) ? '' : 'none';
            });
        }
        
        // Export streams to CSV
        function exportStreams() {
            const rows = document.querySelectorAll('#streamsTable tr');
            let csvContent = 'Stream Name,Class,Level,Capacity,Students\n';
            
            rows.forEach(row => {
                const cells = row.querySelectorAll('td');
                if (cells.length === 0) return;
                
                const rowData = [
                    cells[0].textContent,
                    cells[1].textContent,
                    cells[2].textContent,
                    cells[3].textContent,
                    cells[4].textContent
                ].map(field => {
                    let text = String(field).trim();
                    text = text.replace(/"/g, '""');
                    if (text.includes(',') || text.includes('"')) {
                        text = `"${text}"`;
                    }
                    return text;
                });
                
                csvContent += rowData.join(',') + '\n';
            });
            
            const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement('a');
            const url = URL.createObjectURL(blob);
            
            const timestamp = new Date().toISOString().split('T')[0];
            link.setAttribute('href', url);
            link.setAttribute('download', `streams_${timestamp}.csv`);
            link.style.visibility = 'hidden';
            
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }
        
        async function addStream() {
            const streamData = {
                class_id: document.getElementById('streamClassId').value,
                stream_name: document.getElementById('streamName').value,
                capacity: document.getElementById('streamCapacity').value
            };
            
            try {
                const response = await fetch('api/streams.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(streamData)
                });
                const data = await response.json();
                if (data.success) {
                    alert('Stream added successfully!');
                    bootstrap.Modal.getInstance(document.getElementById('addStreamModal')).hide();
                    document.getElementById('addStreamForm').reset();
                    loadStreams();
                } else {
                    alert(data.error || 'Failed to add stream');
                }
            } catch (error) {
                alert('An error occurred');
            }
        }
        
        async function editStream(id) {
            try {
                const response = await fetch('api/streams.php');
                const data = await response.json();
                if (data.success) {
                    const stream = data.data.find(s => s.id === id);
                    if (stream) {
                        document.getElementById('editStreamId').value = stream.id;
                        document.getElementById('editStreamName').value = stream.stream_name;
                        document.getElementById('editStreamCapacity').value = stream.capacity;
                        new bootstrap.Modal(document.getElementById('editStreamModal')).show();
                    }
                }
            } catch (error) {
                console.error('Error loading stream:', error);
            }
        }
        
        async function updateStream() {
            const streamData = {
                stream_id: document.getElementById('editStreamId').value,
                stream_name: document.getElementById('editStreamName').value,
                capacity: document.getElementById('editStreamCapacity').value
            };
            
            try {
                const response = await fetch('api/streams.php', {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(streamData)
                });
                const data = await response.json();
                if (data.success) {
                    alert('Stream updated successfully!');
                    bootstrap.Modal.getInstance(document.getElementById('editStreamModal')).hide();
                    loadStreams();
                } else {
                    alert(data.error || 'Failed to update stream');
                }
            } catch (error) {
                alert('An error occurred');
            }
        }
        
        async function deleteStream(id) {
            if (confirm('Are you sure you want to delete this stream?')) {
                try {
                    const response = await fetch(`api/streams.php?id=${id}`, { method: 'DELETE' });
                    const data = await response.json();
                    if (data.success) {
                        alert('Stream deleted successfully!');
                        loadStreams();
                    } else {
                        alert(data.error || 'Failed to delete stream');
                    }
                } catch (error) {
                    alert('An error occurred');
                }
            }
        }
        
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const mainContent = document.getElementById('mainContent');
            sidebar.classList.toggle('collapsed');
            sidebar.classList.toggle('show');
            mainContent.classList.toggle('expanded');
        }
        
        loadClasses();
        loadStreams();
    </script>
    <script src="../assets/js/notifications.js"></script>
    
    <!-- Footer -->
    <footer style="background: transparent; color: white; padding: 2rem; text-align: center; border-top: 1px solid rgba(255, 255, 255, 0.1);">
        <p style="margin: 0;">
            <span style="color: #FF6B35;">&copy; 2026</span> 
            <span style="color: #FF6B35;">Kenya</span> 
            <span style="color: #008000;">EduHub</span>
            <span style="color: #008000;">. All rights reserved.</span>
        </p>
    </footer>
</body>
</html>
