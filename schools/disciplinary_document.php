<?php
// Authentication is handled by index.php router

if (!isset($_SESSION['school_id'])) {
    die('Unauthorized');
}

$record_id = isset($_GET['record_id']) ? (int)$_GET['record_id'] : 0;

if (!$record_id) {
    die('Record ID required');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Disciplinary Document</title>
    <link href="https://fonts.googleapis.com/css2?family=Google+Sans:wght@400;500;700&family=Roboto:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/notifications.css">
    <style>
        @media print {
            body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .no-print { display: none !important; }
            .container { box-shadow: none !important; border: none !important; }
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Roboto', sans-serif;
            font-size: 12px;
            line-height: 1.4;
            color: #202124;
            background: #f8f9fa;
            padding: 16px;
        }
        
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: #f8f9fa;
            padding: 16px;
        }
        
        .header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding-bottom: 16px;
            border-bottom: 1px solid #e8eaed;
            margin-bottom: 16px;
        }
        
        .logo-section {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .logo {
            width: 40px;
            height: 40px;
            background: #FFD700;
            border: 3px solid #FF6B35;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }
        
        .logo img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .logo span {
            font-family: 'Google Sans', sans-serif;
            font-weight: 700;
            font-size: 16px;
            color: #FF6B35;
        }
        
        .school-info h1 {
            font-family: 'Google Sans', sans-serif;
            font-size: 18px;
            font-weight: 500;
            color: #202124;
            margin-bottom: 2px;
        }
        
        .school-info p {
            font-size: 11px;
            color: #5f6368;
        }
        
        .document-badge {
            background: #e8f0fe;
            color: #1967d2;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 10px;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .title-section {
            text-align: center;
            margin-bottom: 16px;
        }
        
        .title-section h2 {
            font-family: 'Google Sans', sans-serif;
            font-size: 16px;
            font-weight: 500;
            color: #202124;
            margin-bottom: 4px;
        }
        
        .date {
            text-align: right;
            color: #5f6368;
            font-size: 11px;
            margin-bottom: 16px;
        }
        
        .info-card {
            background: #f8f9fa;
            border: 1px solid #e8eaed;
            border-radius: 8px;
            padding: 12px;
            margin-bottom: 16px;
        }
        
        .info-card h3 {
            font-family: 'Google Sans', sans-serif;
            font-size: 12px;
            font-weight: 500;
            color: #202124;
            margin-bottom: 8px;
        }
        
        .info-row {
            display: flex;
            margin-bottom: 8px;
        }
        
        .info-row:last-child {
            margin-bottom: 0;
        }
        
        .info-label {
            width: 120px;
            color: #5f6368;
            font-size: 11px;
        }
        
        .info-value {
            color: #202124;
            font-size: 11px;
            font-weight: 500;
        }
        
        .content-section {
            margin-bottom: 16px;
        }
        
        .content-section p {
            margin-bottom: 8px;
            color: #202124;
            line-height: 1.4;
        }
        
        .highlight-box {
            background: #fff8e1;
            border-left: 4px solid #f9ab00;
            padding: 8px;
            margin: 8px 0;
            border-radius: 4px;
        }
        
        .highlight-box strong {
            color: #202124;
            display: block;
            margin-bottom: 4px;
            font-weight: 500;
        }
        
        .signature-section {
            display: flex;
            justify-content: space-between;
            margin-top: 24px;
            padding-top: 16px;
            border-top: 1px solid #e8eaed;
        }
        
        .signature-block {
            text-align: center;
        }
        
        .signature-line {
            border-top: 1px solid #dadce0;
            width: 150px;
            margin: 30px auto 4px;
        }
        
        .signature-name {
            font-family: 'Google Sans', sans-serif;
            font-weight: 500;
            color: #202124;
            font-size: 12px;
        }
        
        .signature-title {
            color: #5f6368;
            font-size: 11px;
        }
        
        .print-btn {
            position: fixed;
            top: 24px;
            right: 24px;
            background: #1a73e8;
            color: white;
            border: none;
            padding: 10px 24px;
            border-radius: 4px;
            cursor: pointer;
            font-family: 'Google Sans', sans-serif;
            font-size: 14px;
            font-weight: 500;
            box-shadow: 0 1px 3px rgba(0,0,0,0.12), 0 1px 2px rgba(0,0,0,0.24);
            transition: all 0.2s ease;
            z-index: 1001;
        }
        
        .print-btn:hover {
            background: #1557b0;
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }
        
        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            width: 256px;
            height: 100vh;
            background: white;
            border-right: 1px solid #e8eaed;
            overflow-y: auto;
            z-index: 1000;
        }
        
        .sidebar-section {
            border-bottom: 1px solid #e8eaed;
        }
        
        .sidebar-title {
            padding: 12px 24px;
            font-size: 13px;
            font-weight: 500;
            color: #5f6368;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
            text-transform: uppercase;
            letter-spacing: 0.5px;
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
            margin-left: 256px;
            transition: margin-left 0.3s ease;
        }
        
        .footer {
            text-align: center;
            margin-top: 16px;
            padding-top: 12px;
            border-top: 1px solid #e8eaed;
            font-size: 10px;
            color: #5f6368;
        }
        
        .loading {
            text-align: center;
            padding: 40px;
            color: #5f6368;
        }
        
        .error {
            text-align: center;
            padding: 40px;
            color: #d93025;
        }
        
        @media print {
            body {
                background: white;
                padding: 0;
            }
            
            .container {
                box-shadow: none;
                border: none;
                padding: 12px;
            }
            
            .sidebar {
                display: none !important;
            }
            
            .main-content {
                margin-left: 0;
            }
            
            @page {
                size: A4;
                margin: 0.5cm;
            }
        }
    </style>
</head>
<body>
    <button class="print-btn no-print" onclick="window.print()">
        Print
    </button>
    
    <!-- Sidebar -->
    <aside class="sidebar no-print" id="sidebar">
        <div class="sidebar-section">
            <div class="sidebar-title" onclick="toggleSidebarSection(this)">
                Main <i class="fas fa-chevron-down chevron"></i>
            </div>
            <div class="sidebar-links">
                <a class="nav-link" href="dashboard.php">
                    <i class="fas fa-home"></i> Dashboard
                </a>
            </div>
        </div>
        
        <div class="sidebar-section">
            <div class="sidebar-title" onclick="toggleSidebarSection(this)">
                Academic <i class="fas fa-chevron-down chevron"></i>
            </div>
            <div class="sidebar-links">
                <a class="nav-link" href="students.php">
                    <i class="fas fa-user-graduate"></i> Students
                </a>
                <a class="nav-link" href="teachers.php">
                    <i class="fas fa-chalkboard-teacher"></i> Teachers
                </a>
                <a class="nav-link" href="classes.php">
                    <i class="fas fa-chalkboard"></i> Classes
                </a>
                <a class="nav-link" href="streams.php">
                    <i class="fas fa-layer-group"></i> Streams
                </a>
                <a class="nav-link" href="subjects.php">
                    <i class="fas fa-book"></i> Subjects
                </a>
                <a class="nav-link" href="grading.php">
                    <i class="fas fa-chart-bar"></i> Grading
                </a>
            </div>
        </div>
        
        <div class="sidebar-section">
            <div class="sidebar-title" onclick="toggleSidebarSection(this)">
                Academic Records <i class="fas fa-chevron-down chevron"></i>
            </div>
            <div class="sidebar-links">
                <a class="nav-link" href="performance.php">
                    <i class="fas fa-chart-line"></i> Performance
                </a>
                <a class="nav-link" href="attendance.php">
                    <i class="fas fa-calendar-check"></i> Attendance
                </a>
            </div>
        </div>
        
        <div class="sidebar-section">
            <div class="sidebar-title" onclick="toggleSidebarSection(this)">
                Financial <i class="fas fa-chevron-down chevron"></i>
            </div>
            <div class="sidebar-links">
                <a class="nav-link" href="fees.php">
                    <i class="fas fa-money-bill-wave"></i> Fees
                </a>
                <a class="nav-link" href="finance-managers.php">
                    <i class="fas fa-user-tie"></i> Finance Managers
                </a>
            </div>
        </div>
        
        <div class="sidebar-section">
            <div class="sidebar-title" onclick="toggleSidebarSection(this)">
                Administrative <i class="fas fa-chevron-down chevron"></i>
            </div>
            <div class="sidebar-links">
                <a class="nav-link" href="parents.php">
                    <i class="fas fa-users"></i> Parents
                </a>
                <a class="nav-link active" href="disciplinary.php">
                    <i class="fas fa-shield-alt"></i> Disciplinary
                </a>
                <a class="nav-link" href="librarians.php">
                    <i class="fas fa-book-reader"></i> Librarians
                </a>
            </div>
        </div>
    </aside>
    
    <!-- Main Content -->
    <div class="main-content">
        <div class="container" id="document-container">
            <div class="loading">Loading document...</div>
        </div>
    </div>
    
    <script>
        function toggleSidebarSection(element) {
            element.classList.toggle('collapsed');
            const links = element.nextElementSibling;
            links.classList.toggle('collapsed');
        }
        
        // Fetch document data from API
        async function loadDocument() {
            const recordId = <?php echo $record_id; ?>;
            const container = document.getElementById('document-container');
            
            try {
                const response = await fetch(`api/generate_document.php?record_id=${recordId}`);
                const result = await response.json();
                
                if (result.success) {
                    renderDocument(result.data);
                } else {
                    container.innerHTML = `<div class="error">${result.error}</div>`;
                }
            } catch (error) {
                container.innerHTML = `<div class="error">Error loading document: ${error.message}</div>`;
            }
        }
        
        function renderDocument(data) {
            const container = document.getElementById('document-container');
            const record = data.record;
            const actionType = data.action_type;
            const documentBody = data.document_body;
            
            const paragraphs = documentBody.split('\n\n').map(p => {
                if (p.includes('Reason for') || p.includes('Details:')) {
                    const colonIndex = p.indexOf(':');
                    return `<div class="highlight-box"><strong>${p.substring(0, colonIndex + 1)}</strong>${p.substring(colonIndex + 1)}</div>`;
                } else {
                    return `<p>${p.replace(/\n/g, '<br>')}</p>`;
                }
            }).join('');
            
            const logoHtml = record.logo 
                ? `<img src="${record.logo.replace('../', '../../')}" alt="School Logo" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';"><span style="display:none;">KE</span>`
                : `<span>KE</span>`;
            
            const actionDate = new Date(record.action_date).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
            const endDate = record.end_date ? new Date(record.end_date).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' }) : 'further notice';
            const currentDate = new Date().toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
            
            container.innerHTML = `
                <div class="header">
                    <div class="logo-section">
                        <div class="logo">
                            ${logoHtml}
                        </div>
                        <div class="school-info">
                            <h1>${escapeHtml(record.school_name)}</h1>
                            <p>${escapeHtml(record.address)}</p>
                            <p>${escapeHtml(record.phone)} | Code: ${escapeHtml(record.school_code)}</p>
                        </div>
                    </div>
                    <div class="document-badge">Official Document</div>
                </div>
                
                <div class="title-section">
                    <h2>${actionType} Notice</h2>
                </div>
                
                <div class="date">
                    Date: ${actionDate}
                </div>
                
                <div class="info-card">
                    <h3>Student Information</h3>
                    <div class="info-row">
                        <div class="info-label">Student Name:</div>
                        <div class="info-value">${escapeHtml(record.student_name)}</div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Admission No:</div>
                        <div class="info-value">${escapeHtml(record.admission_number)}</div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Class:</div>
                        <div class="info-value">${escapeHtml(record.class_name || 'N/A')} - ${escapeHtml(record.stream_name || 'N/A')}</div>
                    </div>
                </div>
                
                <div class="content-section">
                    ${paragraphs}
                </div>
                
                <div class="signature-section">
                    <div class="signature-block">
                        <div class="signature-line"></div>
                        <div class="signature-name">Principal/Headmaster</div>
                        <div class="signature-title">${escapeHtml(record.school_name)}</div>
                    </div>
                    
                    <div class="signature-block">
                        <div class="signature-line"></div>
                        <div class="signature-name">Date</div>
                        <div class="signature-title">${currentDate}</div>
                    </div>
                </div>
                
                <div class="footer">
                    This is an official document from ${escapeHtml(record.school_name)}
                </div>
            `;
        }
        
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
        
        // Load document on page load
        loadDocument();
    </script>
    <script src="../assets/js/notifications.js"></script>
</body>
</html>
