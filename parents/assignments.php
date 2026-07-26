<?php
// Parent Assignments Page
// Authentication is handled by index.php router
$parent_id = $_SESSION['parent_id'];
$parent_name = $_SESSION['parent_name'] ?? 'Parent';
$school_id = $_SESSION['school_id'];
$school_name = $_SESSION['school_name'] ?? '';

// Get children of this parent
try {
    $stmt = $pdo->prepare("SELECT s.*, c.class_name, st.stream_name 
                           FROM students s
                           JOIN student_parents sp ON s.id = sp.student_id
                           LEFT JOIN classes c ON s.class_id = c.id
                           LEFT JOIN streams st ON s.stream_id = st.id
                           WHERE sp.parent_id = ? AND s.status = 'active'
                           ORDER BY s.first_name, s.last_name");
    $stmt->execute([$parent_id]);
    $children = $stmt->fetchAll();
    
    // Get all class IDs and subject IDs for the children
    $class_ids = array_column($children, 'class_id');
    $class_ids = array_filter($class_ids);
} catch (PDOException $e) {
    error_log("Failed to fetch children: " . $e->getMessage());
    $children = [];
    $class_ids = [];
}

// Get assignments for the children's classes
$assignments = [];
if (!empty($class_ids)) {
    try {
        $placeholders = str_repeat('?,', count($class_ids) - 1) . '?';
        $query = "SELECT a.*, c.class_name, s.subject_name, t.first_name, t.last_name
                  FROM assignments a
                  LEFT JOIN classes c ON a.class_id = c.id
                  LEFT JOIN subjects s ON a.subject_id = s.id
                  JOIN teachers t ON a.teacher_id = t.id
                  WHERE a.school_id = ? AND (a.class_id IN ($placeholders) OR a.class_id IS NULL)
                  ORDER BY a.created_at DESC LIMIT 50";
        
        $params = array_merge([$school_id], $class_ids);
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $assignments = $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Failed to fetch assignments: " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assignments - <?php echo htmlspecialchars($parent_name); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/notifications.css">
    <style>
        :root {
            --primary-color: #FF6B35;
            --secondary-color: #5f6368;
            --bg-color: #f8f9fa;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: var(--bg-color);
            color: #202124;
        }
        
        .header {
            background: white;
            height: 64px;
            display: flex;
            align-items: center;
            padding: 0 24px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
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
            padding: 8px;
            border-radius: 50%;
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
        
        .sidebar {
            position: fixed;
            top: 64px;
            left: 0;
            width: 256px;
            height: calc(100vh - 64px);
            background: white;
            overflow-y: auto;
            transition: transform 0.3s ease;
            z-index: 999;
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
            margin-left: 256px;
            margin-top: 64px;
            padding: 24px;
            transition: margin-left 0.3s ease;
        }
        
        .main-content.expanded {
            margin-left: 0;
        }
        
        .page-title {
            font-size: 24px;
            font-weight: 400;
            color: #202124;
            margin-bottom: 8px;
        }
        
        .page-subtitle {
            font-size: 14px;
            color: #5f6368;
            margin-bottom: 24px;
        }
        
        .card {
            background: var(--bg-color);
            border-radius: 8px;
            box-shadow: none;
            padding: 24px;
            margin-bottom: 24px;
            border: none;
        }
        
        .card-title {
            font-size: 18px;
            font-weight: 500;
            color: #202124;
            margin-bottom: 16px;
        }
        
        .badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 500;
        }
        
        .badge-syllabus {
            background: #e8f0fe;
            color: #1a73e8;
        }
        
        .badge-sentiment {
            background: #fce8e6;
            color: #c5221f;
        }
        
        .badge-notes {
            background: #e6f4ea;
            color: #137333;
        }
        
        .badge-holiday {
            background: #fef7e0;
            color: #b06000;
        }
        
        .btn {
            padding: 10px 24px;
            border-radius: 4px;
            border: none;
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
        
        /* Assignment Cards */
        .assignments-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }
        
        .assignment-card {
            background: var(--bg-color);
            border: 1px solid #e8eaed;
            border-radius: 8px;
            padding: 20px;
            transition: none;
        }
        
        .assignment-card:hover {
            box-shadow: none;
            transform: none;
        }
        
        .assignment-card-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 12px;
        }
        
        .assignment-title {
            font-size: 16px;
            font-weight: 500;
            color: #202124;
            margin-bottom: 4px;
        }
        
        .assignment-description {
            font-size: 13px;
            color: #5f6368;
            margin-bottom: 12px;
            line-height: 1.4;
        }
        
        .assignment-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-bottom: 12px;
        }
        
        .assignment-meta-item {
            font-size: 12px;
            color: #5f6368;
            display: flex;
            align-items: center;
            gap: 4px;
        }
        
        .assignment-meta-item i {
            color: #FF6B35;
            font-size: 12px;
        }
        
        .assignment-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 12px;
            border-top: none;
        }
        
        .assignment-uploader {
            font-size: 12px;
            color: #5f6368;
        }
        
        .assignment-date {
            font-size: 12px;
            color: #5f6368;
        }
        
        /* Modal Styles */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 9999;
        }
        
        .custom-modal {
            background: white;
            border-radius: 8px;
            max-width: 500px;
            width: 90%;
            max-height: 90vh;
            overflow: hidden;
        }
        
        .custom-modal-body {
            padding: 24px;
        }
        
        .custom-modal-footer {
            padding: 16px 24px;
            border-top: 1px solid #e8eaed;
            display: flex;
            justify-content: flex-end;
            gap: 12px;
        }
        
        .custom-modal-btn {
            padding: 10px 24px;
            border-radius: 4px;
            border: none;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
        }
        
        .custom-modal-btn-primary {
            background: #FF6B35;
            color: white;
        }
        
        .custom-modal-btn-secondary {
            background: white;
            color: #5f6368;
            border: 1px solid #dadce0;
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
            }
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
                <?php echo strtoupper(substr($parent_name, 0, 1)); ?>
            </div>
        </div>
    </header>
    
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-section">
            <div class="sidebar-title">Main</div>
            <a class="nav-link" href="dashboard">
                <i class="fas fa-home"></i> Dashboard
            </a>
            <a class="nav-link" href="children">
                <i class="fas fa-child"></i> My Children
            </a>
            <a class="nav-link" href="performance">
                <i class="fas fa-chart-line"></i> Performance
            </a>
            <a class="nav-link" href="results">
                <i class="fas fa-award"></i> Results
            </a>
            <a class="nav-link" href="attendance">
                <i class="fas fa-calendar-check"></i> Attendance
            </a>
            <a class="nav-link active" href="assignments">
                <i class="fas fa-tasks"></i> Assignments
            </a>
            <a class="nav-link" href="fines">
                <i class="fas fa-book"></i> Library Fines
            </a>
            <a class="nav-link" href="fees">
                <i class="fas fa-money-bill-wave"></i> Fee Payments
            </a>
        </div>
        <div class="sidebar-section">
            <div class="sidebar-title">Account</div>
            <a class="nav-link" href="profile">
                <i class="fas fa-user"></i> Profile
            </a>
            <a class="nav-link" href="logout">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </aside>
    
    <main class="main-content" id="mainContent">
        <h1 class="page-title">Assignments</h1>
        <p class="page-subtitle">
            View syllabus, sentiments, notes, and holiday assignments for your children
        </p>
        
        <div class="card">
            <h2 class="card-title">Recent Assignments</h2>
            
            <!-- Search and Filter -->
            <div style="display: flex; gap: 12px; margin-bottom: 20px; flex-wrap: wrap;">
                <div style="flex: 1; min-width: 200px;">
                    <input type="text" id="searchInput" class="form-control" placeholder="Search assignments..." style="width: 100%; padding: 10px; border: 1px solid #dadce0; border-radius: 8px;">
                </div>
                <?php if (count($children) > 1): ?>
                    <div style="min-width: 150px;">
                        <select id="filterChild" class="form-control" style="padding: 10px; border: 1px solid #dadce0; border-radius: 8px;">
                            <option value="">All Children</option>
                            <?php foreach ($children as $child): ?>
                                <option value="<?php echo $child['class_id']; ?>"><?php echo htmlspecialchars($child['first_name'] . ' ' . $child['last_name']); ?> (<?php echo htmlspecialchars($child['class_name']); ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php endif; ?>
                <div style="min-width: 150px;">
                    <select id="filterType" class="form-control" style="padding: 10px; border: 1px solid #dadce0; border-radius: 8px;">
                        <option value="">All Types</option>
                        <option value="syllabus">Syllabus</option>
                        <option value="sentiment">Sentiment</option>
                        <option value="notes">Notes</option>
                        <option value="holiday">Holiday</option>
                    </select>
                </div>
                <div style="min-width: 150px;">
                    <select id="sortBy" class="form-control" style="padding: 10px; border: 1px solid #dadce0; border-radius: 8px;">
                        <option value="date-desc">Newest First</option>
                        <option value="date-asc">Oldest First</option>
                        <option value="title-asc">Title A-Z</option>
                        <option value="title-desc">Title Z-A</option>
                    </select>
                </div>
            </div>
            
            <?php if (empty($assignments)): ?>
                <p class="text-muted">No assignments found for your children.</p>
            <?php else: ?>
                <div class="assignments-grid" id="assignmentsGrid">
                    <?php foreach ($assignments as $assignment): ?>
                        <?php
                        $badge_class = 'badge-syllabus';
                        $badge_text = 'Syllabus';
                        if ($assignment['assignment_type'] === 'sentiment') {
                            $badge_class = 'badge-sentiment';
                            $badge_text = 'Sentiment';
                        } elseif ($assignment['assignment_type'] === 'notes') {
                            $badge_class = 'badge-notes';
                            $badge_text = 'Notes';
                        } elseif ($assignment['assignment_type'] === 'holiday') {
                            $badge_class = 'badge-holiday';
                            $badge_text = 'Holiday';
                        }
                        ?>
                        <div class="assignment-card" data-id="<?php echo $assignment['id']; ?>" data-title="<?php echo htmlspecialchars($assignment['title']); ?>" data-type="<?php echo $assignment['assignment_type']; ?>" data-date="<?php echo $assignment['created_at']; ?>" data-file-name="<?php echo htmlspecialchars($assignment['file_name']); ?>" data-class-id="<?php echo $assignment['class_id'] ?? ''; ?>">
                            <div class="assignment-card-header">
                                <div>
                                    <h3 class="assignment-title"><?php echo htmlspecialchars($assignment['title']); ?></h3>
                                    <span class="badge <?php echo $badge_class; ?>"><?php echo $badge_text; ?></span>
                                </div>
                            </div>
                            
                            <?php if ($assignment['description']): ?>
                                <p class="assignment-description"><?php echo htmlspecialchars($assignment['description']); ?></p>
                            <?php endif; ?>
                            
                            <div class="assignment-meta">
                                <?php if ($assignment['class_name']): ?>
                                    <div class="assignment-meta-item">
                                        <i class="fas fa-users"></i>
                                        <?php echo htmlspecialchars($assignment['class_name']); ?>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if ($assignment['subject_name']): ?>
                                    <div class="assignment-meta-item">
                                        <i class="fas fa-book"></i>
                                        <?php echo htmlspecialchars($assignment['subject_name']); ?>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if ($assignment['due_date']): ?>
                                    <div class="assignment-meta-item">
                                        <i class="fas fa-calendar"></i>
                                        Due: <?php echo date('M d, Y', strtotime($assignment['due_date'])); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="assignment-footer">
                                <div class="assignment-uploader">
                                    <?php echo htmlspecialchars($assignment['first_name'] . ' ' . $assignment['last_name']); ?>
                                </div>
                                <div class="assignment-date">
                                    <?php echo date('M d, Y', strtotime($assignment['created_at'])); ?>
                                </div>
                            </div>
                            
                            <div style="margin-top: 12px;">
                                <a href="../api/download_assignment.php?assignment_id=<?php echo $assignment['id']; ?>" class="btn btn-sm btn-primary" style="width: 100%; border-radius: 25px;">
                                    <i class="fas fa-download me-2"></i> Download
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <p style="color: #666; font-size: 12px; margin-top: 10px;">Total assignments: <span id="totalCount"><?php echo count($assignments); ?></span></p>
            <?php endif; ?>
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
        
        // Search and Filter functionality
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('searchInput');
            const filterType = document.getElementById('filterType');
            const filterChild = document.getElementById('filterChild');
            const sortBy = document.getElementById('sortBy');
            const assignmentsGrid = document.getElementById('assignmentsGrid');
            const totalCount = document.getElementById('totalCount');
            
            if (searchInput && filterType && sortBy && assignmentsGrid) {
                function filterAndSortAssignments() {
                    const searchTerm = searchInput.value.toLowerCase();
                    const typeFilter = filterType.value;
                    const childFilter = filterChild ? filterChild.value : '';
                    const sortValue = sortBy.value;
                    
                    const cards = Array.from(assignmentsGrid.querySelectorAll('.assignment-card'));
                    let visibleCount = 0;
                    
                    cards.forEach(card => {
                        const title = card.dataset.title.toLowerCase();
                        const type = card.dataset.type;
                        const date = card.dataset.date;
                        const classId = card.dataset.classId;
                        
                        // Filter by search, type, and child
                        const matchesSearch = title.includes(searchTerm);
                        const matchesType = !typeFilter || type === typeFilter;
                        const matchesChild = !childFilter || classId === childFilter;
                        
                        if (matchesSearch && matchesType && matchesChild) {
                            card.style.display = 'block';
                            visibleCount++;
                        } else {
                            card.style.display = 'none';
                        }
                    });
                    
                    // Sort visible cards
                    const visibleCards = cards.filter(card => card.style.display !== 'none');
                    
                    visibleCards.sort((a, b) => {
                        switch(sortValue) {
                            case 'date-desc':
                                return new Date(b.dataset.date) - new Date(a.dataset.date);
                            case 'date-asc':
                                return new Date(a.dataset.date) - new Date(b.dataset.date);
                            case 'title-asc':
                                return a.dataset.title.localeCompare(b.dataset.title);
                            case 'title-desc':
                                return b.dataset.title.localeCompare(a.dataset.title);
                            default:
                                return 0;
                        }
                    });
                    
                    // Reorder in DOM
                    visibleCards.forEach(card => assignmentsGrid.appendChild(card));
                    
                    // Update count
                    if (totalCount) {
                        totalCount.textContent = visibleCount;
                    }
                }
                
                searchInput.addEventListener('input', filterAndSortAssignments);
                filterType.addEventListener('change', filterAndSortAssignments);
                sortBy.addEventListener('change', filterAndSortAssignments);
                if (filterChild) {
                    filterChild.addEventListener('change', filterAndSortAssignments);
                }
            }
        });
        
        // Preview functionality
        function previewAssignment(assignmentId, fileName) {
            document.getElementById('previewFileName').textContent = fileName;
            document.getElementById('previewModal').style.display = 'flex';
            
            const fileExt = fileName.split('.').pop().toLowerCase();
            const previewContent = document.getElementById('previewContent');
            previewContent.innerHTML = '<div style="display: flex; align-items: center; justify-content: center; height: 100%; color: #5f6368;"><i class="fas fa-spinner fa-spin"></i> Loading...</div>';
            
            if (['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(fileExt)) {
                // Image preview
                const img = new Image();
                img.onload = function() {
                    previewContent.innerHTML = `<img src="../api/get_assignment_file.php?assignment_id=${assignmentId}" style="max-width: 100%; height: auto; border-radius: 8px;">`;
                };
                img.onerror = function() {
                    previewContent.innerHTML = '<p style="color: #c5221f; text-align: center;">Failed to load image</p>';
                };
                img.src = `../api/get_assignment_file.php?assignment_id=${assignmentId}`;
            } else if (fileExt === 'pdf') {
                // PDF preview
                previewContent.innerHTML = `
                    <object data="../api/get_assignment_file.php?assignment_id=${assignmentId}" type="application/pdf" style="width: 100%; height: 100%; border: none; border-radius: 8px;">
                        <p style="color: #5f6368; text-align: center; padding: 40px;">
                            Unable to display PDF inline. <a href="../api/download_assignment.php?assignment_id=${assignmentId}" style="color: #1967d2;">Click here to download</a>
                        </p>
                    </object>
                `;
            } else if (['txt'].includes(fileExt)) {
                // Text file preview
                fetch(`../api/get_assignment_file.php?assignment_id=${assignmentId}`)
                    .then(response => {
                        if (!response.ok) throw new Error('Failed to load');
                        return response.text();
                    })
                    .then(text => {
                        previewContent.innerHTML = `<pre style="white-space: pre-wrap; word-wrap: break-word; font-family: monospace; font-size: 13px; color: #202124;">${text}</pre>`;
                    })
                    .catch(() => {
                        previewContent.innerHTML = '<p style="color: #c5221f; text-align: center;">Failed to load preview</p>';
                    });
            } else {
                previewContent.innerHTML = `
                    <div style="text-align: center; padding: 40px;">
                        <i class="fas fa-file" style="font-size: 48px; color: #dadce0; margin-bottom: 16px;"></i>
                        <p style="color: #5f6368; margin-bottom: 12px;">Preview not available for .${fileExt} files</p>
                        <a href="../api/download_assignment.php?assignment_id=${assignmentId}" class="btn btn-primary" style="display: inline-flex; align-items: center; gap: 8px;">
                            <i class="fas fa-download"></i> Download to View
                        </a>
                    </div>
                `;
            }
        }
        
        function closePreviewModal() {
            document.getElementById('previewModal').style.display = 'none';
            document.getElementById('previewContent').innerHTML = '';
        }
    </script>
</body>
</html>
