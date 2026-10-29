<?php
// Classes Management Page
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#FF6B35">
    <title>Classes - <?php echo htmlspecialchars($school_name); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/notifications.css">
    <style>
        :root {
            --primary-color: #1a73e8;
            --secondary-color: #5f6368;
            --bg-color: #f8f9fa;
            --card-bg: #f8f9fa;
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
            margin-bottom: 20px;
        }

        .page-title {
            font-size: 22px;
            font-weight: 400;
            color: #202124;
            margin-bottom: 40px;
            text-align: center;
        }

        .card {
            background: var(--bg-color);
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

        .form-control {
            border: 1px solid #dadce0;
            border-radius: 25px;
            padding: 10px 12px;
            font-size: 14px;
            background: var(--bg-color);
        }

        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 2px rgba(26, 115, 232, 0.2);
            background: var(--bg-color);
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

        /* Action Buttons */
        .action-buttons {
            display: flex;
            gap: 12px;
            margin-bottom: 24px;
        }

        .action-buttons .btn {
            flex: 1;
            padding: 12px 24px;
            border-radius: 25px;
        }

        @media (max-width: 768px) {
            .action-buttons {
                flex-direction: column;
            }
        }

        /* Filter Section */
        .filter-section {
            background: var(--bg-color);
            border-radius: 8px;
            padding: 24px;
            margin-bottom: 24px;
            border: 1px solid #e8eaed;
        }

        .filter-section .card-title {
            font-size: 18px;
            font-weight: 500;
            color: #202124;
            margin-bottom: 16px;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border: 2px solid #000;
            margin: 0;
            font-family: 'Times New Roman', Times, serif;
        }

        .table thead {
            background: #f0f0f0;
            border-bottom: 2px solid #000;
        }

        .table thead th {
            background: #f0f0f0;
            border: 2px solid #000;
            border-bottom: 2px solid #000;
            padding: 12px;
            font-weight: bold;
            color: #000;
        }

        .table td {
            padding: 12px;
            border: 2px solid #000;
            color: #000;
            font-size: 13px;
        }

        .table tbody tr:nth-child(even) {
            background: #fafafa;
        }

        .table tbody tr:hover {
            background: #f8f9fa;
        }

        .table-responsive {
            width: 100%;
            overflow-x: auto;
            margin: 0;
            padding: 0;
        }
        
        @media (max-width: 768px) {
            html, body {
                overflow-x: hidden;
                position: relative;
            }

            .main-content {
                margin-left: 0;
                padding: 16px;
            }

            .page-title {
                font-size: 22px;
                font-weight: 400;
                margin-bottom: 24px;
            }

            .card {
                padding: 16px;
                margin-bottom: 16px;
                border-radius: 12px;
                box-shadow: 0 1px 2px rgba(0,0,0,0.05);
            }

            .card[style*="padding: 0"] {
                padding: 0 !important;
            }

            .btn {
                width: 100%;
                padding: 12px 20px;
                font-size: 14px;
                font-weight: 500;
                border-radius: 8px;
                height: 40px;
            }

            .card-header .btn {
                width: 100%;
            }

            .table-responsive {
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
                border-radius: 8px;
                width: 100%;
                margin: 0 -16px;
                padding: 0 16px;
            }

            .table {
                min-width: 600px;
                width: 100%;
                font-size: 12px;
            }
            
            .table th,
            .table td {
                padding: 8px 6px;
                font-size: 11px;
            }
            
            .card-header {
                flex-direction: column;
                gap: 12px;
                align-items: flex-start;
                padding-bottom: 12px;
                border-bottom: 1px solid #e0e0e0;
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
</br>
</br>
</br>
</br>
</br>
    <?php require_once 'includes/header.php'; ?>
    <?php $active_page = 'classes'; require_once 'includes/sidebar.php'; ?>
    
    <!-- Main Content -->
    <main class="main-content" id="mainContent">
        <h1 class="page-title">Classes Management</h1>
        
        <!-- Class Statistics -->
        <div class="card">
            <h2 class="card-title">Class Statistics</h2>
            <div id="classStats" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 12px;">
                <?php
                $totalClasses = 0;
                $totalStreams = 0;
                $totalStudents = 0;
                $totalCapacity = 0;

                try {
                    $stmt = $pdo->prepare("SELECT id, capacity FROM classes WHERE school_id = ?");
                    $stmt->execute([$school_id]);
                    $classes = $stmt->fetchAll();

                    foreach ($classes as $class) {
                        $totalClasses++;
                        $totalCapacity += $class['capacity'];

                        // Count streams for this class
                        $streamStmt = $pdo->prepare("SELECT COUNT(*) as count FROM streams WHERE class_id = ?");
                        $streamStmt->execute([$class['id']]);
                        $streamCount = $streamStmt->fetch()['count'];
                        $totalStreams += $streamCount;

                        // Count students for this class
                        $studentStmt = $pdo->prepare("SELECT COUNT(*) as count FROM students WHERE class_id = ?");
                        $studentStmt->execute([$class['id']]);
                        $studentCount = $studentStmt->fetch()['count'];
                        $totalStudents += $studentCount;
                    }
                } catch (PDOException $e) {
                    error_log("Failed to fetch class stats: " . $e->getMessage());
                }
                ?>
                <div style="background: #e8f0fe; padding: 16px; border-radius: 8px; text-align: center;">
                    <div style="font-size: 24px; font-weight: 600; color: #1967d2;"><?php echo $totalClasses; ?></div>
                    <div style="font-size: 12px; color: #5f6368;">Total Classes</div>
                </div>
                <div style="background: #e6f4ea; padding: 16px; border-radius: 8px; text-align: center;">
                    <div style="font-size: 24px; font-weight: 600; color: #137333;"><?php echo $totalStreams; ?></div>
                    <div style="font-size: 12px; color: #5f6368;">Total Streams</div>
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
        </div>

        <!-- Action Buttons -->
        <div class="d-flex flex-column gap-2 mb-3">
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addClassModal" style="border-radius: 25px;">
                <span class="material-icons" style="vertical-align: middle; margin-right: 8px;">add</span> Add Class
            </button>
            <button class="btn btn-success" onclick="exportClasses()" style="border-radius: 25px;">
                <span class="material-icons" style="vertical-align: middle; margin-right: 8px;">download</span> Export
            </button>
        </div>

        <!-- Search and Filter Section -->
        <div class="card">
            <h2 class="card-title">Search & Filter</h2>
            <div class="row mb-3" style="gap: 12px;">
                <div class="col-md-4">
                    <input type="text" class="form-control" id="searchClass" placeholder="Search by class name or level..." onkeyup="filterClasses()">
                </div>
                <div class="col-md-4">
                    <select class="form-control" id="filterClassLevel" onchange="filterClasses()">
                        <option value="">All Levels</option>
                        <option value="Primary">Primary</option>
                        <option value="Secondary">Secondary</option>
                        <option value="College">College</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <select class="form-control" id="filterClassCapacity" onchange="filterClasses()">
                        <option value="">All Capacities</option>
                        <option value="low">Low (&lt;30)</option>
                        <option value="medium">Medium (30-50)</option>
                        <option value="high">High (&gt;50)</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="card" style="padding: 0;">
            <h2 class="card-title" style="padding: 24px 24px 16px 24px;">All Classes</h2>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Class Name</th>
                            <th>Level</th>
                            <th>Capacity</th>
                            <th>Streams</th>
                            <th>Students</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="classesTable">
                            <tr><td colspan="6" class="text-center">Loading...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <div class="modal fade" id="addClassModal" tabindex="-1" style="backdrop-filter: blur(2px);">
        <div class="modal-dialog">
            <div class="modal-content" style="border: none; border-radius: 24px; box-shadow: 0 24px 38px 3px rgba(0,0,0,0.14), 0 9px 46px 8px rgba(0,0,0,0.12);">
                <div class="modal-header" style="border: none; padding: 24px 32px 0 32px;">
                    <h5 class="modal-title" style="font-size: 22px; font-weight: 400; color: #202124;">Add New Class</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" style="padding: 24px 32px;">
                    <form id="addClassForm">
                        <div class="mb-3">
                            <label class="form-label" style="font-size: 14px; color: #5f6368; font-weight: 500;">Class Name</label>
                            <input type="text" class="form-control" id="className" placeholder="e.g., Grade 1" required style="border-radius: 8px; border: 1px solid #dadce0;">
                        </div>
                        <div class="mb-3">
                            <label class="form-label" style="font-size: 14px; color: #5f6368; font-weight: 500;">Capacity</label>
                            <input type="number" class="form-control" id="classCapacity" value="40" style="border-radius: 8px; border: 1px solid #dadce0;">
                        </div>
                    </form>
                </div>
                <div class="modal-footer" style="border: none; padding: 0 32px 32px 32px;">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" style="background: transparent; color: #5f6368; border: none; border-radius: 25px; padding: 10px 24px; font-size: 14px; font-weight: 500; letter-spacing: 0.25px; text-transform: uppercase;">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="addClass()" style="background: #FF6B35; color: white; border: none; border-radius: 25px; padding: 10px 24px; font-size: 14px; font-weight: 500; letter-spacing: 0.25px; text-transform: uppercase;">Add Class</button>
                </div>
            </div>
        </div>
    </div>
    
    <div class="modal fade" id="editClassModal" tabindex="-1" style="backdrop-filter: blur(2px);">
        <div class="modal-dialog">
            <div class="modal-content" style="border: none; border-radius: 24px; box-shadow: 0 24px 38px 3px rgba(0,0,0,0.14), 0 9px 46px 8px rgba(0,0,0,0.12);">
                <div class="modal-header" style="border: none; padding: 24px 32px 0 32px;">
                    <h5 class="modal-title" style="font-size: 22px; font-weight: 400; color: #202124;">Edit Class</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" style="padding: 24px 32px;">
                    <form id="editClassForm">
                        <input type="hidden" id="editClassId">
                        <div class="mb-3">
                            <label class="form-label" style="font-size: 14px; color: #5f6368; font-weight: 500;">Class Name</label>
                            <input type="text" class="form-control" id="editClassName" required style="border-radius: 8px; border: 1px solid #dadce0;">
                        </div>
                        <div class="mb-3">
                            <label class="form-label" style="font-size: 14px; color: #5f6368; font-weight: 500;">Capacity</label>
                            <input type="number" class="form-control" id="editClassCapacity" style="border-radius: 8px; border: 1px solid #dadce0;">
                        </div>
                    </form>
                </div>
                <div class="modal-footer" style="border: none; padding: 0 32px 32px 32px;">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" style="background: transparent; color: #5f6368; border: none; border-radius: 25px; padding: 10px 24px; font-size: 14px; font-weight: 500; letter-spacing: 0.25px; text-transform: uppercase;">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="updateClass()" style="background: #FF6B35; color: white; border: none; border-radius: 25px; padding: 10px 24px; font-size: 14px; font-weight: 500; letter-spacing: 0.25px; text-transform: uppercase;">Update Class</button>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        async function loadClasses() {
            try {
                const response = await fetch('api/classes.php');
                const data = await response.json();
                if (data.success) {
                    const tbody = document.getElementById('classesTable');
                    tbody.innerHTML = data.data.map(cls => `
                        <tr>
                            <td>${cls.class_name}</td>
                            <td>${cls.class_level}</td>
                            <td>${cls.capacity}</td>
                            <td>${cls.stream_count || 0}</td>
                            <td>${cls.student_count}</td>
                            <td>
                                <div class="action-buttons-cell">
                                    <button class="btn btn-sm btn-action" onclick="editClass(${cls.id})">
                                        <span class="material-icons" style="color: #5f6368;">edit</span>
                                    </button>
                                    <button class="btn btn-sm btn-action" onclick="deleteClass(${cls.id})">
                                        <span class="material-icons" style="color: #d93025;">delete</span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    `).join('');
                }
            } catch (error) {
                console.error('Error loading classes:', error);
            }
        }
        
        // Filter classes
        function filterClasses() {
            const search = document.getElementById('searchClass').value.toLowerCase();
            const level = document.getElementById('filterClassLevel').value;
            const capacity = document.getElementById('filterClassCapacity').value;
            
            const rows = document.querySelectorAll('#classesTable tr');
            rows.forEach(row => {
                const cells = row.querySelectorAll('td');
                if (cells.length === 0) return;
                
                const className = cells[0].textContent.toLowerCase();
                const classLevel = cells[1].textContent.toLowerCase();
                const capacityValue = parseInt(cells[2].textContent);
                
                const matchesSearch = className.includes(search) || classLevel.includes(search);
                const matchesLevel = !level || classLevel.includes(level.toLowerCase());
                
                let matchesCapacity = true;
                if (capacity === 'low') {
                    matchesCapacity = capacityValue < 30;
                } else if (capacity === 'medium') {
                    matchesCapacity = capacityValue >= 30 && capacityValue <= 50;
                } else if (capacity === 'high') {
                    matchesCapacity = capacityValue > 50;
                }
                
                row.style.display = (matchesSearch && matchesLevel && matchesCapacity) ? '' : 'none';
            });
        }
        
        // Export classes to CSV
        function exportClasses() {
            const rows = document.querySelectorAll('#classesTable tr');
            let csvContent = 'Class Name,Level,Capacity,Streams,Students\n';
            
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
            link.setAttribute('download', `classes_${timestamp}.csv`);
            link.style.visibility = 'hidden';
            
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }
        
        async function addClass() {
            const classData = {
                class_name: document.getElementById('className').value,
                capacity: document.getElementById('classCapacity').value
            };
            
            try {
                const response = await fetch('api/classes.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(classData)
                });
                const data = await response.json();
                if (data.success) {
                    alert('Class added successfully!');
                    bootstrap.Modal.getInstance(document.getElementById('addClassModal')).hide();
                    document.getElementById('addClassForm').reset();
                    loadClasses();
                } else {
                    alert(data.error || 'Failed to add class');
                }
            } catch (error) {
                alert('An error occurred');
            }
        }
        
        async function editClass(id) {
            try {
                const response = await fetch('api/classes.php');
                const data = await response.json();
                if (data.success) {
                    const cls = data.data.find(c => c.id === id);
                    if (cls) {
                        document.getElementById('editClassId').value = cls.id;
                        document.getElementById('editClassName').value = cls.class_name;
                        document.getElementById('editClassCapacity').value = cls.capacity;
                        new bootstrap.Modal(document.getElementById('editClassModal')).show();
                    }
                }
            } catch (error) {
                console.error('Error loading class:', error);
            }
        }
        
        async function updateClass() {
            const classData = {
                class_id: document.getElementById('editClassId').value,
                class_name: document.getElementById('editClassName').value,
                class_level: document.getElementById('editClassLevel').value,
                capacity: document.getElementById('editClassCapacity').value
            };
            
            try {
                const response = await fetch('api/classes.php', {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(classData)
                });
                const data = await response.json();
                if (data.success) {
                    alert('Class updated successfully!');
                    bootstrap.Modal.getInstance(document.getElementById('editClassModal')).hide();
                    loadClasses();
                } else {
                    alert(data.error || 'Failed to update class');
                }
            } catch (error) {
                alert('An error occurred');
            }
        }
        
        async function deleteClass(id) {
            if (confirm('Are you sure you want to delete this class?')) {
                try {
                    const response = await fetch(`api/classes.php?id=${id}`, { method: 'DELETE' });
                    const data = await response.json();
                    if (data.success) {
                        alert('Class deleted successfully!');
                        loadClasses();
                    } else {
                        alert(data.error || 'Failed to delete class');
                    }
                } catch (error) {
                    alert('An error occurred');
                }
            }
        }
        
        loadClasses();
    </script>
    <script src="../assets/js/notifications.js"></script>

    <?php require_once '../includes/copywrite.php'; ?>
</body>
</html>
