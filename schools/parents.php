<?php
// Parents Management Page
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

// Get school admission prefix
try {
    $stmt = $pdo->prepare("SELECT admission_prefix FROM schools WHERE id = ?");
    $stmt->execute([$school_id]);
    $school = $stmt->fetch();
    $admission_prefix = $school['admission_prefix'] ?? '';
} catch (PDOException $e) {
    error_log("Failed to fetch school prefix: " . $e->getMessage());
    $admission_prefix = '';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#FF6B35">
    <title>Parents - <?php echo htmlspecialchars($school_name); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/notifications.css">
    <style>
        :root {
            --primary-color: #1a73e8;
            --secondary-color: #5f6368;
            --bg-color: #F0F4F9;
            --card-bg: #F0F4F9;
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

        .stat-card {
            background: var(--bg-color);
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 24px;
            transition: box-shadow 0.2s;
        }

        .stat-card:hover {
            box-shadow: 0 1px 3px rgba(0,0,0,0.12), 0 1px 2px rgba(0,0,0,0.24);
        }

        .stat-label {
            font-size: 14px;
            color: #5f6368;
            margin-bottom: 8px;
        }

        .stat-value {
            font-size: 32px;
            font-weight: 400;
            color: #202124;
            margin-bottom: 8px;
        }

        .stat-icon {
            width: 64px;
            height: 64px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 12px auto;
            font-size: 28px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 24px;
            margin-bottom: 24px;
        }

        .card {
            background: var(--bg-color);
            border: 1px solid #e0e0e0;
            border-radius: 25px;
            padding: 24px;
            margin: 0 0 24px 0;
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
        }
        
        .btn {
            padding: 10px 24px;
            border-radius: 25px;
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
            border: 2px solid #000;
            width: 100%;
            margin: 0;
            font-family: 'Times New Roman', Times, serif;
        }

        .table-responsive {
            width: 100%;
            overflow-x: auto;
            margin: 0;
            padding: 0;
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
            font-size: 13px;
            white-space: nowrap;
        }

        .table td:nth-child(1) {
            white-space: nowrap;
        }

        .table td:nth-child(6) {
            white-space: nowrap;
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

        .main-action-buttons {
            display: flex;
            gap: 12px;
            margin-bottom: 24px;
        }

        .main-action-buttons .btn {
            flex: 1;
            padding: 12px 24px;
            border-radius: 25px;
        }

        @media (max-width: 768px) {
            .main-action-buttons {
                flex-direction: column;
                margin: 0 16px 16px 16px;
            }

            .main-action-buttons .btn {
                width: auto;
                padding: 10px 20px;
            }

            .action-buttons-cell {
                flex-direction: row;
            }
        }

        .filter-section {
            background: var(--bg-color);
            border-radius: 25px;
            padding: 24px;
            margin: 0 0 24px 0;
            border: 1px solid #e0e0e0;
        }
        
        @media (max-width: 768px) {
            .main-content {
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

            .filter-section {
                padding: 16px;
                margin: 0 4px 16px 4px;
            }

            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 8px;
            }

            .stat-card {
                padding: 12px;
                text-align: center;
            }

            .stat-icon {
                width: 48px;
                height: 48px;
                font-size: 20px;
                margin-bottom: 8px;
            }

            .stat-value {
                font-size: 20px;
                margin-bottom: 4px;
            }

            .stat-label {
                font-size: 12px;
            }

            .table-responsive {
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
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

            .btn {
                padding: 8px 16px;
                font-size: 13px;
                font-weight: 500;
                border-radius: 25px;
                height: 36px;
                flex: 1;
            }

            .form-control {
                padding: 12px;
                font-size: 16px;
                border-radius: 25px;
                border: 1px solid #dadce0;
                margin-bottom: 12px;
            }

            .form-control:focus {
                border-color: var(--primary-color);
                box-shadow: 0 0 0 2px rgba(26, 115, 232, 0.2);
            }

            .card-header {
                flex-direction: column !important;
                gap: 12px;
                align-items: stretch !important;
                padding-bottom: 12px;
                border-bottom: 1px solid #e8eaed;
            }

            .card-header .d-flex {
                flex-direction: column !important;
                width: 100% !important;
            }

            .card-header .btn {
                width: 100% !important;
            }

            .card {
                text-align: center;
            }

            .card-header {
                justify-content: center;
            }

            .stat-card {
                text-align: center;
            }

            .stat-icon {
                margin: 0 auto;
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
        
        @media (max-width: 480px) {
            .main-content {
                padding: 0;
            }

            .page-title {
                font-size: 18px;
                padding: 16px;
            }

            .card {
                padding: 12px;
                margin: 0 4px 16px 4px;
            }

            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 8px;
            }

            .stat-card {
                padding: 12px;
                text-align: center;
            }

            .stat-icon {
                width: 48px;
                height: 48px;
                font-size: 20px;
                margin-bottom: 8px;
            }

            .stat-value {
                font-size: 20px;
                margin-bottom: 4px;
            }

            .stat-label {
                font-size: 12px;
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
<?php $active_page = 'parents'; require_once 'includes/sidebar.php'; ?>

<br>
<!-- Main Content -->
<main class="main-content" id="mainContent">
        <h1 class="page-title">Parents Management</h1>
        
        <!-- Parent Statistics -->
        <div id="feeStats" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 12px; margin-bottom: 24px;">
            <?php
            $totalParents = 0;
            $totalLinkedStudents = 0;
            $fatherCount = 0;
            $motherCount = 0;
            $guardianCount = 0;
            $unlinkedParents = 0;

            try {
                $stmt = $pdo->prepare("SELECT p.id, p.relationship FROM parents p WHERE p.school_id = ?");
                $stmt->execute([$school_id]);
                $parents = $stmt->fetchAll();

                foreach ($parents as $parent) {
                    $totalParents++;
                    if ($parent['relationship'] === 'Father') $fatherCount++;
                    if ($parent['relationship'] === 'Mother') $motherCount++;
                    if ($parent['relationship'] === 'Guardian') $guardianCount++;

                    // Count linked students for this parent
                    $studentStmt = $pdo->prepare("SELECT COUNT(*) as count FROM student_parents sp JOIN students s ON sp.student_id = s.id WHERE sp.parent_id = ? AND s.school_id = ?");
                    $studentStmt->execute([$parent['id'], $school_id]);
                    $studentCount = $studentStmt->fetch()['count'];
                    $totalLinkedStudents += $studentCount;

                    if ($studentCount == 0) {
                        $unlinkedParents++;
                    }
                }
            } catch (PDOException $e) {
                error_log("Failed to fetch parent stats: " . $e->getMessage());
            }
            ?>
            <div style="background: #e8f0fe; padding: 16px; border-radius: 8px; text-align: center;">
                <div style="font-size: 24px; font-weight: 600; color: #1967d2;"><?php echo $totalParents; ?></div>
                <div style="font-size: 12px; color: #5f6368;">Total Parents</div>
            </div>
            <div style="background: #e6f4ea; padding: 16px; border-radius: 8px; text-align: center;">
                <div style="font-size: 24px; font-weight: 600; color: #137333;"><?php echo $totalLinkedStudents; ?></div>
                <div style="font-size: 12px; color: #5f6368;">Linked Students</div>
            </div>
            <div style="background: #fef7e0; padding: 16px; border-radius: 8px; text-align: center;">
                <div style="font-size: 24px; font-weight: 600; color: #b06000;"><?php echo $fatherCount; ?></div>
                <div style="font-size: 12px; color: #5f6368;">Fathers</div>
            </div>
            <div style="background: #e8f0fe; padding: 16px; border-radius: 8px; text-align: center;">
                <div style="font-size: 24px; font-weight: 600; color: #1967d2;"><?php echo $motherCount; ?></div>
                <div style="font-size: 12px; color: #5f6368;">Mothers</div>
            </div>
            <div style="background: #f1f3f4; padding: 16px; border-radius: 8px; text-align: center;">
                <div style="font-size: 24px; font-weight: 600; color: #5f6368;"><?php echo $guardianCount; ?></div>
                <div style="font-size: 12px; color: #5f6368;">Guardians</div>
            </div>
            <div style="background: #fce8e6; padding: 16px; border-radius: 8px; text-align: center;">
                <div style="font-size: 24px; font-weight: 600; color: #c5221f;"><?php echo $unlinkedParents; ?></div>
                <div style="font-size: 12px; color: #5f6368;">Unlinked Parents</div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="main-action-buttons">
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addParentModal">
                <span class="material-symbols-outlined" style="vertical-align: middle; margin-right: 8px;">add</span> Add Parent
            </button>
            <button class="btn btn-success" onclick="exportParents()">
                <span class="material-symbols-outlined" style="vertical-align: middle; margin-right: 8px;">download</span> Export
            </button>
        </div>

        <!-- Filter Section -->
        <div class="card filter-section">
            <div class="card-title">Filter Parents</div>
            <div class="row">
                <div class="col-md-4">
                    <input type="text" class="form-control" id="searchParent" placeholder="Search by name, email, or phone..." onkeyup="filterParents()">
                </div>
                <div class="col-md-4">
                    <select class="form-control" id="filterRelationship" onchange="filterParents()">
                        <option value="">All Relationships</option>
                        <option value="Father">Father</option>
                        <option value="Mother">Mother</option>
                        <option value="Guardian">Guardian</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <select class="form-control" id="filterStudents" onchange="filterParents()">
                        <option value="">All Students</option>
                        <option value="has_students">Has Linked Students</option>
                        <option value="no_students">No Linked Students</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Table Section -->
        <div class="card">
            <div class="card-title">All Parents</div>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>ID Number</th>
                            <th>Relationship</th>
                            <th>Students</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="parentsTable">
                        <tr><td colspan="7" class="text-center">Loading...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <div class="modal fade" id="addParentModal" tabindex="-1" style="backdrop-filter: blur(2px);">
        <div class="modal-dialog modal-lg">
            <div class="modal-content" style="border: none; border-radius: 24px; box-shadow: 0 24px 38px 3px rgba(0,0,0,0.14), 0 9px 46px 8px rgba(0,0,0,0.12);">
                <div class="modal-header" style="border: none; padding: 24px 32px 0 32px;">
                    <h5 class="modal-title" style="font-size: 22px; font-weight: 400; color: #202124;">Add New Parent</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" style="padding: 24px 32px;">
                    <form id="addParentForm">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">First Name</label>
                                <input type="text" class="form-control" id="parentFirstName" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Last Name</label>
                                <input type="text" class="form-control" id="parentLastName" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" class="form-control" id="parentEmail" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Phone</label>
                                <input type="tel" class="form-control" id="parentPhone" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">ID Number</label>
                                <input type="text" class="form-control" id="parentIdNumber" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Relationship</label>
                                <select class="form-control" id="parentRelationship" required>
                                    <option value="">Select Relationship</option>
                                    <option value="Father">Father</option>
                                    <option value="Mother">Mother</option>
                                    <option value="Guardian">Guardian</option>
                                </select>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Address</label>
                            <textarea class="form-control" id="parentAddress" rows="2"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Student Admission Number</label>
                            <div class="input-group">
                                <?php if (!empty($admission_prefix)): ?>
                                    <span class="input-group-text"><?php echo htmlspecialchars($admission_prefix); ?>/</span>
                                <?php endif; ?>
                                <input type="text" class="form-control" id="parentAdmissionNumber" placeholder="Enter admission number" required>
                            </div>
                            <small class="text-muted">
                                <?php if (!empty($admission_prefix)): ?>
                                    Your school's admission prefix is: <strong><?php echo htmlspecialchars($admission_prefix); ?></strong>. Enter only the number part (e.g., 7280)
                                <?php else: ?>
                                    Enter the student's admission number to link this parent
                                <?php endif; ?>
                            </small>
                        </div>
                    </form>
                </div>
                <div class="modal-footer" style="border: none; padding: 0 32px 32px 32px;">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" style="background: transparent; color: #5f6368; border: none; border-radius: 25px; padding: 10px 24px; font-size: 14px; font-weight: 500; letter-spacing: 0.25px; text-transform: uppercase;">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="addParent()" style="background: #FF6B35; color: white; border: none; border-radius: 25px; padding: 10px 24px; font-size: 14px; font-weight: 500; letter-spacing: 0.25px; text-transform: uppercase;">Add Parent</button>
                </div>
            </div>
        </div>
    </div>
    
    <div class="modal fade" id="editParentModal" tabindex="-1" style="backdrop-filter: blur(2px);">
        <div class="modal-dialog modal-lg">
            <div class="modal-content" style="border: none; border-radius: 24px; box-shadow: 0 24px 38px 3px rgba(0,0,0,0.14), 0 9px 46px 8px rgba(0,0,0,0.12);">
                <div class="modal-header" style="border: none; padding: 24px 32px 0 32px;">
                    <h5 class="modal-title" style="font-size: 22px; font-weight: 400; color: #202124;">Edit Parent</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" style="padding: 24px 32px;">
                    <form id="editParentForm">
                        <input type="hidden" id="editParentId">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">First Name</label>
                                <input type="text" class="form-control" id="editParentFirstName" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Last Name</label>
                                <input type="text" class="form-control" id="editParentLastName" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" class="form-control" id="editParentEmail" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Phone</label>
                                <input type="tel" class="form-control" id="editParentPhone" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">ID Number</label>
                                <input type="text" class="form-control" id="editParentIdNumber" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Relationship</label>
                                <select class="form-control" id="editParentRelationship" required>
                                    <option value="Father">Father</option>
                                    <option value="Mother">Mother</option>
                                    <option value="Guardian">Guardian</option>
                                </select>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Address</label>
                            <textarea class="form-control" id="editParentAddress" rows="2"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer" style="border: none; padding: 0 32px 32px 32px;">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" style="background: transparent; color: #5f6368; border: none; border-radius: 25px; padding: 10px 24px; font-size: 14px; font-weight: 500; letter-spacing: 0.25px; text-transform: uppercase;">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="updateParent()" style="background: #FF6B35; color: white; border: none; border-radius: 25px; padding: 10px 24px; font-size: 14px; font-weight: 500; letter-spacing: 0.25px; text-transform: uppercase;">Update Parent</button>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        async function loadParents() {
            try {
                const response = await fetch('api/parents.php');
                const data = await response.json();
                if (data.success) {
                    const tbody = document.getElementById('parentsTable');
                    tbody.innerHTML = data.data.map(parent => `
                        <tr>
                            <td>${parent.first_name} ${parent.last_name}</td>
                            <td>${parent.email}</td>
                            <td>${parent.phone}</td>
                            <td>${parent.id_number}</td>
                            <td>${parent.relationship}</td>
                            <td>${parent.linked_students || 'No students linked'}</td>
                            <td>
                                <div class="action-buttons">
                                    <button class="btn-action" onclick="editParent(${parent.id})">
                                        <span class="material-symbols-outlined" style="color: #5f6368;">edit</span>
                                    </button>
                                    <button class="btn-action" onclick="deleteParent(${parent.id})">
                                        <span class="material-symbols-outlined" style="color: #d93025;">delete</span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    `).join('');
                }
            } catch (error) {
                console.error('Error loading parents:', error);
            }
        }
        
        // Filter parents
        function filterParents() {
            const search = document.getElementById('searchParent').value.toLowerCase();
            const relationship = document.getElementById('filterRelationship').value;
            const studentsFilter = document.getElementById('filterStudents').value;
            
            const rows = document.querySelectorAll('#parentsTable tr');
            rows.forEach(row => {
                const cells = row.querySelectorAll('td');
                if (cells.length === 0) return;
                
                const name = cells[0].textContent.toLowerCase();
                const email = cells[1].textContent.toLowerCase();
                const phone = cells[2].textContent.toLowerCase();
                const rowRelationship = cells[4].textContent;
                const linkedStudents = cells[5].textContent.toLowerCase();
                
                const matchesSearch = name.includes(search) || email.includes(search) || phone.includes(search);
                const matchesRelationship = !relationship || rowRelationship.includes(relationship);
                
                let matchesStudents = true;
                if (studentsFilter === 'has_students') {
                    matchesStudents = linkedStudents !== 'no students linked';
                } else if (studentsFilter === 'no_students') {
                    matchesStudents = linkedStudents === 'no students linked';
                }
                
                row.style.display = (matchesSearch && matchesRelationship && matchesStudents) ? '' : 'none';
            });
        }
        
        // Export parents to CSV
        function exportParents() {
            const rows = document.querySelectorAll('#parentsTable tr');
            let csvContent = 'Name,Email,Phone,ID Number,Relationship,Linked Students\n';
            
            rows.forEach(row => {
                const cells = row.querySelectorAll('td');
                if (cells.length === 0) return;
                
                const rowData = [
                    cells[0].textContent,
                    cells[1].textContent,
                    cells[2].textContent,
                    cells[3].textContent,
                    cells[4].textContent,
                    cells[5].textContent
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
            link.setAttribute('download', `parents_${timestamp}.csv`);
            link.style.visibility = 'hidden';
            
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }
        
        async function addParent() {
            const prefix = "<?php echo addslashes($admission_prefix); ?>";
            const admissionNumber = document.getElementById('parentAdmissionNumber').value;
            const fullAdmissionNumber = prefix ? prefix + '/' + admissionNumber : admissionNumber;
            
            const parentData = {
                first_name: document.getElementById('parentFirstName').value,
                last_name: document.getElementById('parentLastName').value,
                email: document.getElementById('parentEmail').value,
                phone: document.getElementById('parentPhone').value,
                id_number: document.getElementById('parentIdNumber').value,
                relationship: document.getElementById('parentRelationship').value,
                address: document.getElementById('parentAddress').value,
                admission_number: fullAdmissionNumber
            };
            
            try {
                const response = await fetch('api/parents.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(parentData)
                });
                const data = await response.json();
                if (data.success) {
                    alert('Parent added successfully!');
                    bootstrap.Modal.getInstance(document.getElementById('addParentModal')).hide();
                    document.getElementById('addParentForm').reset();
                    loadParents();
                } else {
                    alert(data.error || 'Failed to add parent');
                }
            } catch (error) {
                alert('An error occurred');
            }
        }
        
        async function editParent(id) {
            try {
                const response = await fetch('api/parents.php');
                const data = await response.json();
                if (data.success) {
                    const parent = data.data.find(p => p.id === id);
                    if (parent) {
                        document.getElementById('editParentId').value = parent.id;
                        document.getElementById('editParentFirstName').value = parent.first_name;
                        document.getElementById('editParentLastName').value = parent.last_name;
                        document.getElementById('editParentEmail').value = parent.email;
                        document.getElementById('editParentPhone').value = parent.phone;
                        document.getElementById('editParentIdNumber').value = parent.id_number;
                        document.getElementById('editParentRelationship').value = parent.relationship;
                        document.getElementById('editParentAddress').value = parent.address || '';
                        new bootstrap.Modal(document.getElementById('editParentModal')).show();
                    }
                }
            } catch (error) {
                console.error('Error loading parent:', error);
            }
        }
        
        async function updateParent() {
            const parentData = {
                parent_id: document.getElementById('editParentId').value,
                first_name: document.getElementById('editParentFirstName').value,
                last_name: document.getElementById('editParentLastName').value,
                email: document.getElementById('editParentEmail').value,
                phone: document.getElementById('editParentPhone').value,
                id_number: document.getElementById('editParentIdNumber').value,
                relationship: document.getElementById('editParentRelationship').value,
                address: document.getElementById('editParentAddress').value
            };
            
            try {
                const response = await fetch('api/parents.php', {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(parentData)
                });
                const data = await response.json();
                if (data.success) {
                    alert('Parent updated successfully!');
                    bootstrap.Modal.getInstance(document.getElementById('editParentModal')).hide();
                    loadParents();
                } else {
                    alert(data.error || 'Failed to update parent');
                }
            } catch (error) {
                alert('An error occurred');
            }
        }
        
        async function deleteParent(id) {
            if (confirm('Are you sure you want to delete this parent?')) {
                try {
                    const response = await fetch(`api/parents.php?id=${id}`, { method: 'DELETE' });
                    const data = await response.json();
                    if (data.success) {
                        alert('Parent deleted successfully!');
                        loadParents();
                    } else {
                        alert(data.error || 'Failed to delete parent');
                    }
                } catch (error) {
                    alert('An error occurred');
                }
            }
        }

        loadParents();
    </script>
    <script src="../assets/js/notifications.js"></script>

<br>
<br>
<?php require_once '../includes/copywrite.php'; ?>
</body>
</html>
