<?php
// Teacher Parent Affairs
// Authentication is handled by index.php router
$teacher_id = $_SESSION['teacher_id'];
$teacher_name = $_SESSION['teacher_name'] ?? 'Teacher';
$school_id = $_SESSION['school_id'];
$class_id = $_SESSION['class_id'] ?? null;
$stream_id = $_SESSION['stream_id'] ?? null;
$teacher_type = $_SESSION['teacher_type'] ?? 'class_teacher';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Parents - <?php echo htmlspecialchars($teacher_name); ?></title>
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
            background: var(--bg-color);
            border: none;
            border-radius: 8px;
            padding: 24px;
            margin-bottom: 24px;
            box-shadow: none;
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
            box-shadow: 0 0 0 2px rgba(255, 107, 53, 0.2);
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
            background: var(--primary-color);
            color: white;
        }
        
        .btn-primary:hover {
            background: #e55a2b;
        }
        
        .btn-secondary {
            background: white;
            color: #5f6368;
            border: 1px solid #dadce0;
        }
        
        .btn-secondary:hover {
            background: #f1f3f4;
        }
        
        .btn-info {
            background: #0288d1;
            color: white;
        }
        
        .btn-info:hover {
            background: #01579b;
        }
        
        .table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border: 1px solid #000;
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
            text-align: left;
            padding: 12px;
            font-size: 13px;
            font-weight: 600;
            color: #000;
            border: 1px solid #000;
            border-bottom: 2px solid #000;
        }
        
        .table td {
            padding: 12px;
            font-size: 13px;
            color: #000;
            border: 1px solid #000;
        }
        
        .table tbody tr:nth-child(even) {
            background: #f9f9f9;
        }
        
        .table tbody tr:hover {
            background: #f0f0f0;
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
            
            .table-responsive {
                overflow-x: auto;
                width: 100%;
            }
            
            .table {
                min-width: 100%;
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
                <?php echo strtoupper(substr($teacher_name, 0, 1)); ?>
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
            <a class="nav-link" href="attendance">
                <i class="fas fa-calendar-check"></i> Attendance
            </a>
            <a class="nav-link" href="performance">
                <i class="fas fa-chart-line"></i> Performance
            </a>
            <a class="nav-link" href="students">
                <i class="fas fa-user-graduate"></i> Students
            </a>
            <a class="nav-link active" href="parents">
                <i class="fas fa-users"></i> Parents
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
    
    <!-- Main Content -->
    <main class="main-content" id="mainContent">
        <h1 class="page-title">Parent Affairs</h1>
        
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>Search Parents</span>
                <button class="btn btn-sm btn-info" onclick="exportParents()" style="border-radius: 25px;">
                    <i class="fas fa-download me-1"></i> Export CSV
                </button>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <label class="form-label">Search</label>
                        <input type="text" class="form-control" id="searchQuery" placeholder="Name, email or phone" oninput="filterParents()" style="border-radius: 25px;">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Relationship</label>
                        <select class="form-control" id="filterRelationship" onchange="filterParents()" style="border-radius: 25px;">
                            <option value="">All</option>
                            <option value="Father">Father</option>
                            <option value="Mother">Mother</option>
                            <option value="Guardian">Guardian</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">&nbsp;</label>
                        <button class="btn btn-secondary w-100" onclick="resetFilters()" style="border-radius: 25px;">
                            <i class="fas fa-undo"></i> Reset
                        </button>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">&nbsp;</label>
                        <button class="btn btn-primary w-100" onclick="toggleComposeSection()" style="border-radius: 25px;">
                            <i class="fas fa-envelope"></i> Compose
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Email Composition Section -->
        <div class="card mb-4" id="composeSection" style="display: none;">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="fas fa-edit me-2"></i>Compose Email</span>
                <button class="btn btn-sm btn-secondary" onclick="toggleComposeSection()" style="border-radius: 25px;">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label">To</label>
                    <select class="form-control" id="composeRecipient" style="border-radius: 25px;">
                        <option value="all">All Parents</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Subject</label>
                    <input type="text" class="form-control" id="composeSubject" placeholder="Enter subject" style="border-radius: 25px;">
                </div>
                <div class="mb-3">
                    <label class="form-label">Message</label>
                    <textarea class="form-control" id="composeBody" rows="8" placeholder="Enter your message" style="border-radius: 25px;"></textarea>
                </div>
                <div class="d-flex gap-2">
                    <button class="btn btn-primary" id="sendEmailBtn" onclick="sendComposedEmail()" style="border-radius: 25px;">
                        <i class="fas fa-paper-plane me-2"></i> <span id="sendEmailText">Send Email</span>
                    </button>
                    <button class="btn btn-secondary" onclick="clearComposeForm()" style="border-radius: 25px;">
                        <i class="fas fa-eraser me-2"></i> Clear
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Parent Statistics -->
        <div id="parentStats" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 12px; margin-bottom: 24px;">
            <div style="background: #e8f0fe; padding: 16px; border-radius: 8px; text-align: center;">
                <div style="font-size: 24px; font-weight: 600; color: #1967d2;" id="totalParents">0</div>
                <div style="font-size: 12px; color: #5f6368;">Total Parents</div>
            </div>
            <div style="background: #e6f4ea; padding: 16px; border-radius: 8px; text-align: center;">
                <div style="font-size: 24px; font-weight: 600; color: #137333;" id="linkedStudents">0</div>
                <div style="font-size: 12px; color: #5f6368;">Linked Students</div>
            </div>
            <div style="background: #fce8e6; padding: 16px; border-radius: 8px; text-align: center;">
                <div style="font-size: 24px; font-weight: 600; color: #c5221f;" id="fathersCount">0</div>
                <div style="font-size: 12px; color: #5f6368;">Fathers</div>
            </div>
            <div style="background: #fef7e0; padding: 16px; border-radius: 8px; text-align: center;">
                <div style="font-size: 24px; font-weight: 600; color: #b06000;" id="mothersCount">0</div>
                <div style="font-size: 12px; color: #5f6368;">Mothers</div>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <span>All Parents</span>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>ID Number</th>
                                <th>Relationship</th>
                                <th>Linked Students</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="parentsTable">
                            <tr><td colspan="7" class="text-center">Loading...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Parent Details Modal -->
    <div class="modal fade" id="parentDetailsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Parent Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="parentDetailsContent">
                        <!-- Parent details will be loaded here -->
                    </div>
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
            sidebar.classList.toggle('show');
            mainContent.classList.toggle('expanded');
        }
        
        let allParents = [];
        
        // Load parents
        async function loadParents() {
            try {
                const classId = <?php echo $class_id ? $class_id : 'null'; ?>;
                const streamId = <?php echo $stream_id ? $stream_id : 'null'; ?>;
                const teacherType = '<?php echo $teacher_type; ?>';
                
                let url = '../schools/api/parents.php?teacher_view=true';
                if (classId) url += `&class_id=${classId}`;
                if (streamId) url += `&stream_id=${streamId}`;
                url += `&teacher_type=${teacherType}`;
                
                const response = await fetch(url);
                const data = await response.json();
                if (data.success) {
                    allParents = data.data;
                    updateParentStats(allParents);
                    populateMessageRecipients(allParents);
                    filterParents();
                    
                    // Check for URL parameters to auto-open compose
                    const urlParams = new URLSearchParams(window.location.search);
                    if (urlParams.get('compose') === 'true') {
                        toggleComposeSection();
                        const subject = urlParams.get('subject');
                        const body = urlParams.get('body');
                        if (subject) document.getElementById('composeSubject').value = decodeURIComponent(subject);
                        if (body) document.getElementById('composeBody').value = decodeURIComponent(body);
                        
                        // Clear URL parameters
                        window.history.replaceState({}, document.title, window.location.pathname);
                    }
                }
            } catch (error) {
                console.error('Error loading parents:', error);
            }
        }
        
        function filterParents() {
            const searchTerm = document.getElementById('searchQuery').value.toLowerCase();
            const relationship = document.getElementById('filterRelationship').value;
            
            const filtered = allParents.filter(parent => {
                const matchesSearch = !searchTerm || 
                    parent.email.toLowerCase().includes(searchTerm) ||
                    parent.phone.includes(searchTerm) ||
                    `${parent.first_name} ${parent.last_name}`.toLowerCase().includes(searchTerm);
                
                const matchesRelationship = !relationship || parent.relationship === relationship;
                
                return matchesSearch && matchesRelationship;
            });
            
            renderParents(filtered);
        }
        
        function renderParents(parents) {
            const tbody = document.getElementById('parentsTable');
            tbody.innerHTML = parents.map(parent => `
                <tr>
                    <td>${parent.first_name} ${parent.last_name}</td>
                    <td>${parent.email}</td>
                    <td>${parent.phone}</td>
                    <td>${parent.id_number}</td>
                    <td>${parent.relationship}</td>
                    <td>${parent.linked_students || 'No students linked'}</td>
                    <td>
                        <button class="btn btn-sm btn-success" onclick="sendEmail(${parent.id})" style="border-radius: 25px; margin-right: 4px;" title="Send Email">
                            <i class="fas fa-envelope"></i>
                        </button>
                        <a href="tel:${parent.phone}" class="btn btn-sm btn-primary" style="border-radius: 25px; margin-right: 4px;" title="Call">
                            <i class="fas fa-phone"></i>
                        </a>
                        <button class="btn btn-sm btn-info" onclick="viewParentDetails(${parent.id})" style="border-radius: 25px;" title="View Details">
                            <i class="fas fa-eye"></i>
                        </button>
                    </td>
                </tr>
            `).join('');
        }
        
        function updateParentStats(parents) {
            const total = parents.length;
            const fathers = parents.filter(p => p.relationship === 'Father').length;
            const mothers = parents.filter(p => p.relationship === 'Mother').length;
            
            let linkedStudents = 0;
            parents.forEach(parent => {
                if (parent.linked_students && parent.linked_students !== 'No students linked') {
                    const studentCount = parent.linked_students.split(',').length;
                    linkedStudents += studentCount;
                }
            });
            
            document.getElementById('totalParents').textContent = total;
            document.getElementById('linkedStudents').textContent = linkedStudents;
            document.getElementById('fathersCount').textContent = fathers;
            document.getElementById('mothersCount').textContent = mothers;
        }
        
        function populateMessageRecipients(parents) {
            const select = document.getElementById('composeRecipient');
            select.innerHTML = '<option value="all">All Parents</option>';
            parents.forEach(parent => {
                select.innerHTML += `<option value="${parent.id}">${parent.first_name} ${parent.last_name} (${parent.email})</option>`;
            });
        }
        
        function resetFilters() {
            document.getElementById('searchQuery').value = '';
            document.getElementById('filterRelationship').value = '';
            filterParents();
        }
        
        function sendEmail(parentId) {
            // Find the parent and select them in the compose section
            const select = document.getElementById('composeRecipient');
            select.value = parentId;
            
            // Show compose section if hidden
            const section = document.getElementById('composeSection');
            if (section.style.display === 'none') {
                section.style.display = 'block';
            }
            
            // Scroll to compose section
            section.scrollIntoView({ behavior: 'smooth' });
        }
        
        function toggleComposeSection() {
            const section = document.getElementById('composeSection');
            section.style.display = section.style.display === 'none' ? 'block' : 'none';
        }
        
        function clearComposeForm() {
            document.getElementById('composeRecipient').value = 'all';
            document.getElementById('composeSubject').value = '';
            document.getElementById('composeBody').value = '';
        }
        
        async function sendComposedEmail() {
            const recipient = document.getElementById('composeRecipient').value;
            const subject = document.getElementById('composeSubject').value;
            const body = document.getElementById('composeBody').value;
            
            if (!subject || !body) {
                alert('Please enter subject and message');
                return;
            }
            
            // Show loading state
            const btn = document.getElementById('sendEmailBtn');
            const btnText = document.getElementById('sendEmailText');
            btn.disabled = true;
            btnText.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Sending...';
            
            try {
                const response = await fetch('../schools/api/send_email.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        recipient: recipient,
                        recipient_id: recipient === 'all' ? '' : recipient,
                        subject: subject,
                        body: body
                    })
                });
                const data = await response.json();
                
                if (data.success) {
                    alert('Email sent successfully!');
                    clearComposeForm();
                    toggleComposeSection();
                } else {
                    alert('Failed to send email: ' + (data.error || 'Unknown error'));
                }
            } catch (error) {
                console.error('Error sending email:', error);
                alert('An error occurred while sending the email');
            } finally {
                // Reset button state
                btn.disabled = false;
                btnText.innerHTML = 'Send Email';
            }
        }
        
        function exportParents() {
            if (allParents.length === 0) {
                alert('No parent data to export.');
                return;
            }
            
            // Get current filtered parents
            const searchTerm = document.getElementById('searchQuery').value.toLowerCase();
            const relationship = document.getElementById('filterRelationship').value;
            
            const filtered = allParents.filter(parent => {
                const matchesSearch = !searchTerm || 
                    parent.email.toLowerCase().includes(searchTerm) ||
                    parent.phone.includes(searchTerm) ||
                    `${parent.first_name} ${parent.last_name}`.toLowerCase().includes(searchTerm);
                
                const matchesRelationship = !relationship || parent.relationship === relationship;
                
                return matchesSearch && matchesRelationship;
            });
            
            if (filtered.length === 0) {
                alert('No matching parents to export.');
                return;
            }
            
            // Create CSV content
            let csvContent = 'Name,Email,Phone,ID Number,Relationship,Address,Linked Students\n';
            
            filtered.forEach(parent => {
                const rowData = [
                    `${parent.first_name} ${parent.last_name}`,
                    parent.email,
                    parent.phone,
                    parent.id_number,
                    parent.relationship,
                    parent.address || '-',
                    parent.linked_students || '-'
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
            
            // Create download link
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
        
        // View parent details
        async function viewParentDetails(parentId) {
            try {
                const response = await fetch('../schools/api/parents.php');
                const data = await response.json();
                if (data.success) {
                    const parent = data.data.find(p => p.id === parentId);
                    if (parent) {
                        const content = `
                            <div class="row">
                                <div class="col-md-6">
                                    <p><strong>Name:</strong> ${parent.first_name} ${parent.last_name}</p>
                                    <p><strong>Email:</strong> ${parent.email}</p>
                                    <p><strong>Phone:</strong> ${parent.phone}</p>
                                    <p><strong>ID Number:</strong> ${parent.id_number}</p>
                                </div>
                                <div class="col-md-6">
                                    <p><strong>Relationship:</strong> ${parent.relationship}</p>
                                    <p><strong>Address:</strong> ${parent.address || 'Not provided'}</p>
                                    <p><strong>Linked Students:</strong></p>
                                    <p>${parent.linked_students || 'No students linked'}</p>
                                </div>
                            </div>
                        `;
                        document.getElementById('parentDetailsContent').innerHTML = content;
                        new bootstrap.Modal(document.getElementById('parentDetailsModal')).show();
                    }
                }
            } catch (error) {
                console.error('Error loading parent details:', error);
            }
        }
        
        // Initialize
        loadParents();
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
