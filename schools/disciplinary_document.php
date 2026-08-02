<?php
// Authentication is handled by index.php router
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
    die('Unauthorized');
}

$school_name = $_SESSION['school_name'] ?? 'School';

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
    <meta name="theme-color" content="#FF6B35">
    <title>Disciplinary Document - <?php echo htmlspecialchars($school_name); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #1a73e8;
            --secondary-color: #5f6368;
            --bg-color: #f8f9fa;
            --card-bg: #ffffff;
            --sidebar-width: 256px;
            --header-height: 64px;
            --document-bg: #FFD700;
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
        
        @media print {
            * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
            html, body { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; background: var(--document-bg) !important; margin: 0 !important; padding: 0 !important; }
            .no-print { display: none !important; }
            .container { box-shadow: none !important; border: none !important; background: var(--document-bg) !important; }
            .sidebar { display: none !important; }
            .header { display: none !important; }
            .main-content { margin-left: 0 !important; margin-top: 0 !important; padding: 0 !important; background: var(--document-bg) !important; }
            .document-container {
                max-width: 100% !important;
                padding: 15px !important;
                box-shadow: none !important;
                border: none !important;
                margin: 0 !important;
                background: var(--document-bg) !important;
            }
            .doc-header { margin-bottom: 15px !important; padding-bottom: 10px !important; }
            .title-section { margin-bottom: 15px !important; }
            .title-section h2 { font-size: 18px !important; margin-bottom: 5px !important; }
            .date { margin-bottom: 15px !important; font-size: 12px !important; }
            .info-card { padding: 12px !important; margin-bottom: 15px !important; background: transparent !important; }
            .info-card h3 { font-size: 14px !important; margin-bottom: 10px !important; }
            .content-section p { font-size: 11px !important; line-height: 1.4 !important; margin-bottom: 8px !important; }
            .highlight-box { padding: 10px !important; margin: 10px 0 !important; background: transparent !important; }
            .highlight-box strong { font-size: 12px !important; margin-bottom: 5px !important; }
            .signature-section { margin-top: 25px !important; padding-top: 15px !important; }
            .signature-line { margin: 30px auto 5px !important; }
            .signature-name { font-size: 13px !important; }
            .signature-title { font-size: 11px !important; }
            .footer { margin-top: 20px !important; padding-top: 10px !important; font-size: 10px !important; }
            .school-info h1 { font-size: 16px !important; margin-bottom: 2px !important; }
            .school-info p { font-size: 10px !important; }
            .document-badge { font-size: 10px !important; padding: 4px 10px !important; background: transparent !important; }
            @page { size: A4; margin: 0; }
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
        
        /* Sidebar */
        .sidebar {
            position: fixed;
            top: var(--header-height);
            left: 0;
            width: var(--sidebar-width);
            height: calc(100vh - var(--header-height));
            background: var(--bg-color);
            overflow-y: auto;
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
            font-size: 24px;
            font-weight: 400;
            color: #202124;
            margin-bottom: 8px;
        }
        
        /* Document Container */
        .document-container {
            max-width: 800px;
            margin: 0 auto;
            background: var(--document-bg);
            padding: 32px;
        }
        
        .doc-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding-bottom: 16px;
            margin-bottom: 24px;
            border-bottom: 1px solid #FFB085;
        }
        
        .logo-section {
            display: flex;
            align-items: center;
            gap: 16px;
        }
        
        .school-info h1 {
            font-family: 'Google Sans', sans-serif;
            font-size: 20px;
            font-weight: 600;
            color: #202124;
            margin-bottom: 4px;
        }
        
        .school-info p {
            font-size: 13px;
            color: #424242;
            margin-bottom: 0;
        }
        
        .document-badge {
            background: transparent;
            color: #FF6B35;
            border: 1px solid #FF6B35;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .title-section {
            text-align: center;
            margin-bottom: 32px;
        }
        
        .title-section h2 {
            font-family: 'Google Sans', sans-serif;
            font-size: 24px;
            font-weight: 600;
            color: #202124;
            margin-bottom: 8px;
        }
        
        .date {
            text-align: right;
            color: #424242;
            font-size: 14px;
            font-weight: 500;
            margin-bottom: 24px;
        }
        
        .info-card {
            background: transparent;
            border-left: 2px solid #FFB085;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 24px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }
        
        .info-card h3 {
            font-family: 'Google Sans', sans-serif;
            font-size: 16px;
            font-weight: 600;
            color: #202124;
            margin-bottom: 16px;
        }
        
        .info-row {
            display: flex;
            margin-bottom: 8px;
        }
        
        .info-row:last-child {
            margin-bottom: 0;
        }
        
        .info-label {
            width: 140px;
            color: #616161;
            font-size: 13px;
        }
        
        .info-value {
            color: #202124;
            font-size: 13px;
            font-weight: 500;
        }
        
        .content-section {
            margin-bottom: 24px;
        }
        
        .content-section p {
            margin-bottom: 12px;
            color: #202124;
            line-height: 1.7;
            font-size: 14px;
        }
        
        .highlight-box {
            background: transparent;
            border-left: 2px solid #FFB085;
            padding: 16px;
            margin: 16px 0;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }
        
        .highlight-box strong {
            color: #FF6B35;
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            font-size: 15px;
        }
        
        .signature-section {
            display: flex;
            justify-content: space-between;
            margin-top: 40px;
            padding-top: 24px;
            border-top: 1px solid #FFB085;
        }
        
        .signature-block {
            text-align: center;
        }
        
        .signature-line {
            border-top: 1px solid #FFB085;
            width: 180px;
            margin: 40px auto 8px;
        }
        
        .signature-name {
            font-family: 'Google Sans', sans-serif;
            font-weight: 600;
            color: #202124;
            font-size: 15px;
        }
        
        .signature-title {
            color: #FF6B35;
            font-size: 13px;
            font-weight: 500;
        }
        
        .qr-code {
            display: flex;
            justify-content: center;
            align-items: center;
            margin: 10px auto;
        }
        
        .qr-code canvas {
            border: 2px solid #FF6B35;
            border-radius: 4px;
        }
        
        .qr-label {
            text-align: center;
            font-size: 10px;
            color: #424242;
            margin-top: 5px;
        }
        
        .footer {
            text-align: center;
            margin-top: 32px;
            padding-top: 20px;
            border-top: 1px solid #e8eaed;
            font-size: 12px;
            color: #5f6368;
        }
        
        .print-btn {
            background: #f8f9fa;
            color: #202124;
            border: 1px solid #202124;
            padding: 10px 24px;
            border-radius: 4px;
            cursor: pointer;
            font-family: 'Google Sans', sans-serif;
            font-size: 14px;
            font-weight: 500;
            box-shadow: 0 1px 3px rgba(0,0,0,0.12), 0 1px 2px rgba(0,0,0,0.24);
            transition: all 0.2s ease;
        }
        
        .print-btn:hover {
            background: #e8eaed;
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }
        
        .bg-selector {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-right: 16px;
        }
        
        .bg-selector label {
            font-size: 13px;
            color: #5f6368;
            font-weight: 500;
        }
        
        .bg-selector select {
            padding: 6px 12px;
            border: 1px solid #e8eaed;
            border-radius: 4px;
            font-size: 13px;
            background: white;
            cursor: pointer;
        }
        
        .bg-selector select:focus {
            outline: none;
            border-color: #1a73e8;
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
        
        /* Mobile */
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
            
            .document-container {
                padding: 16px;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header no-print">
        <div class="header-left">
            <button class="menu-btn" onclick="toggleSidebar()">
                <i class="fas fa-bars"></i>
            </button>
            <div class="logo">
                <div style="width: 40px; height: 40px; background: #FFD700; border: 3px solid #FF6B35; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-right: 8px;">
                    <span style="font-weight: bold; font-size: 20px;">
                        <span style="color: #FF6B35; font-size: 24px;">K</span><span style="color: #008000; font-size: 20px;">E</span>
                    </span>
                </div>
                <span style="color: #FF6B35; font-weight: bold;">Kenya</span> <span style="color: #008000; font-weight: bold;">EduHub</span>
            </div>
        </div>
        <div class="header-right">
            <div class="bg-selector">
                <label>Background:</label>
                <select id="bgSelector" onchange="changeBackground()">
                    <option value="#FFD700">Golden Yellow</option>
                    <option value="#FFFFFF">White</option>
                </select>
            </div>
            <button class="print-btn" onclick="window.print()">
                <i class="fas fa-print"></i> Print
            </button>
            <div class="school-avatar">
                <?php echo strtoupper(substr($school_name, 0, 1)); ?>
            </div>
        </div>
    </header>
    
    <!-- Sidebar -->
    <aside class="sidebar no-print" id="sidebar">
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
            <a class="nav-link" href="streams">
                <i class="fas fa-layer-group"></i> Streams
            </a>
            <a class="nav-link" href="subjects">
                <i class="fas fa-book"></i> Subjects
            </a>
            <a class="nav-link" href="exam-types">
                <i class="fas fa-clipboard-list"></i> Exam Types
            </a>
            <a class="nav-link" href="timetable">
                <i class="fas fa-calendar-alt"></i> Timetable
            </a>
            <a class="nav-link" href="grading">
                <i class="fas fa-chart-bar"></i> Grading
            </a>
            <a class="nav-link" href="performance">
                <i class="fas fa-chart-line"></i> Performance
            </a>
            <a class="nav-link" href="results">
                <i class="fas fa-clipboard-list"></i> Results
            </a>
            <a class="nav-link" href="attendance">
                <i class="fas fa-calendar-check"></i> Attendance
            </a>
            <a class="nav-link" href="calendar">
                <i class="fas fa-calendar"></i> Calendar
            </a>
            <a class="nav-link" href="fees">
                <i class="fas fa-money-bill-wave"></i> Fees
            </a>
            <a class="nav-link" href="invoices">
                <i class="fas fa-file-invoice-dollar"></i> Invoices
            </a>
            <a class="nav-link" href="finance-managers">
                <i class="fas fa-user-tie"></i> Finance Managers
            </a>
            <a class="nav-link" href="account">
                <i class="fas fa-wallet"></i> Account Balance
            </a>
            <a class="nav-link" href="parents">
                <i class="fas fa-users"></i> Parents
            </a>
            <a class="nav-link active" href="disciplinary">
                <i class="fas fa-shield-alt"></i> Disciplinary
            </a>
            <a class="nav-link" href="disciplinary-action-types">
                <i class="fas fa-list-alt"></i> Disciplinary Types
            </a>
            <a class="nav-link" href="librarians">
                <i class="fas fa-book-reader"></i> Librarians
            </a>
            <a class="nav-link" href="duty-assignments">
                <i class="fas fa-clipboard-list"></i> Duty Assignments
            </a>
            <a class="nav-link" href="examination-heads">
                <i class="fas fa-user-tie"></i> Examination Heads
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
        <div class="document-container" id="document-container">
            <div class="loading">Loading document...</div>
        </div>
    </main>
    
    <script src="https://cdn.jsdelivr.net/npm/qrcode@1.5.3/build/qrcode.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const mainContent = document.getElementById('mainContent');
            sidebar.classList.toggle('collapsed');
            sidebar.classList.toggle('show');
            mainContent.classList.toggle('expanded');
        }
        
        function changeBackground() {
            const bgColor = document.getElementById('bgSelector').value;
            const container = document.getElementById('document-container');
            
            // Update CSS variable for both screen and print
            document.documentElement.style.setProperty('--document-bg', bgColor);
            
            // Also update inline style for immediate visual feedback
            if (container) {
                container.style.background = bgColor;
            }
            
            // Update QR code light color to match background
            const qrCanvas = document.querySelector('.qr-code canvas');
            if (qrCanvas && typeof QRCode !== 'undefined') {
                const qrData = qrCanvas.getAttribute('data-qr-data');
                if (qrData) {
                    const qrElement = document.querySelector('.qr-code');
                    QRCode.toCanvas(qrElement, qrData, {
                        width: 80,
                        margin: 1,
                        color: {
                            dark: '#202124',
                            light: bgColor
                        }
                    });
                }
            }
        }
        
        function toggleSidebarSection(titleElement) {
            const linksContainer = titleElement.nextElementSibling;
            const isCollapsed = linksContainer.classList.contains('collapsed');
            
            titleElement.classList.toggle('collapsed');
            linksContainer.classList.toggle('collapsed');
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
                ? `<img src="../uploads/schools/${record.logo.split('/').pop()}" alt="School Logo" style="width: 60px; height: 60px; object-fit: cover;" onerror="console.log('Image failed to load:', this.src); this.style.display='none'; this.nextElementSibling.style.display='flex';"><div style="display:none; width: 60px; height: 60px; background: #e8eaed; align-items: center; justify-content: center; color: #5f6368; font-weight: bold; font-size: 24px;">${escapeHtml(record.school_name).charAt(0)}</div>`
                : `<div style="width: 60px; height: 60px; background: #e8eaed; display: flex; align-items: center; justify-content: center; color: #5f6368; font-weight: bold; font-size: 24px;">${escapeHtml(record.school_name).charAt(0)}</div>`;
            
            const actionDate = new Date(record.action_date).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
            const endDate = record.end_date ? new Date(record.end_date).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' }) : 'further notice';
            const currentDate = new Date().toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
            
            // Generate QR code data
            const qrData = JSON.stringify({
                record_id: record.id,
                school_code: record.school_code,
                student_name: record.student_name,
                admission_no: record.admission_number,
                action_type: actionType,
                action_date: actionDate
            });
            
            const qrCodeId = 'qrcode-' + record.id;
            
            container.innerHTML = `
                <div class="doc-header">
                    <div class="logo-section">
                        ${logoHtml}
                        <div class="school-info">
                            <h1>${escapeHtml(record.school_name)}</h1>
                            <p>${escapeHtml(record.address)}</p>
                            <p>${escapeHtml(record.phone)}</p>
                        </div>
                    </div>
                    <div class="document-badge">Official Document</div>
                </div>
                
                <div class="title-section">
                    <h2>${actionType} Letter</h2>
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
                        <div id="${qrCodeId}" class="qr-code"></div>
                        <div class="qr-label">Scan to Verify</div>
                    </div>
                </div>
                
                <div class="footer">
                    This is an official document from ${escapeHtml(record.school_name)}
                </div>
            `;
            
            // Generate QR code after DOM is updated
            setTimeout(() => {
                const qrElement = document.getElementById(qrCodeId);
                if (qrElement && typeof QRCode !== 'undefined') {
                    const bgColor = document.getElementById('bgSelector') ? document.getElementById('bgSelector').value : '#FFD700';
                    QRCode.toCanvas(qrElement, qrData, {
                        width: 80,
                        margin: 1,
                        color: {
                            dark: '#202124',
                            light: bgColor
                        }
                    });
                    // Store QR data for background changes
                    qrElement.setAttribute('data-qr-data', qrData);
                }
            }, 100);
        }
        
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
        
        // Load document on page load
        loadDocument();
    </script>
</body>
</html>
