<?php
// Book Reservations Management Page
// Authentication is handled by index.php router
$librarian_id = $_SESSION['librarian_id'];
$librarian_name = $_SESSION['librarian_name'] ?? 'Librarian';
$school_id = $_SESSION['school_id'];
$school_name = $_SESSION['school_name'] ?? '';

// Handle reservation creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_reservation'])) {
    $book_id = $_POST['book_id'] ?? '';
    $borrower_type = $_POST['borrower_type'] ?? '';
    $borrower_id = $_POST['borrower_id'] ?? '';
    
    $errors = [];
    if (empty($book_id)) $errors[] = 'Book is required';
    if (empty($borrower_type)) $errors[] = 'Borrower type is required';
    if (empty($borrower_id)) $errors[] = 'Borrower is required';
    
    if (empty($errors)) {
        try {
            // Check if book exists and belongs to school
            $stmt = $pdo->prepare("SELECT b.*, 
                (SELECT COUNT(*) FROM book_borrowings WHERE book_id = b.id AND status = 'borrowed') as active_borrowings
                FROM books b WHERE b.id = ? AND b.school_id = ?");
            $stmt->execute([$book_id, $school_id]);
            $book = $stmt->fetch();
            
            if (!$book) {
                $errors[] = 'Book not found';
            } elseif ($book['available_copies'] > 0 && $book['active_borrowings'] == 0) {
                $errors[] = 'Book is currently available for borrowing. No reservation needed.';
            } else {
                // Check if user already has a reservation for this book
                $stmt = $pdo->prepare("SELECT * FROM book_reservations WHERE book_id = ? AND school_id = ? AND user_id = ? AND user_type = ? AND status IN ('pending', 'fulfilled')");
                $stmt->execute([$book_id, $school_id, $borrower_id, $borrower_type]);
                $existing = $stmt->fetch();
                
                if ($existing) {
                    $errors[] = 'User already has a reservation for this book';
                } else {
                    // Check if user currently has this book borrowed
                    $stmt = $pdo->prepare("SELECT * FROM book_borrowings WHERE book_id = ? AND borrower_id = ? AND borrower_type = ? AND status = 'borrowed'");
                    $stmt->execute([$book_id, $borrower_id, $borrower_type]);
                    $current_borrowing = $stmt->fetch();
                    
                    if ($current_borrowing) {
                        $errors[] = 'User currently has this book borrowed. Cannot reserve.';
                    } else {
                        // Create reservation
                        $expiry_date = date('Y-m-d', strtotime('+7 days'));
                        $stmt = $pdo->prepare("INSERT INTO book_reservations (book_id, school_id, user_id, user_type, reservation_date, expiry_date, status) VALUES (?, ?, ?, ?, NOW(), ?, 'pending')");
                        $stmt->execute([$book_id, $school_id, $borrower_id, $borrower_type, $expiry_date]);
                        
                        // Log the action
                        $stmt = $pdo->prepare("INSERT INTO book_history (book_id, school_id, action, user_id, user_type, details) VALUES (?, ?, 'reserved', ?, 'librarian', ?)");
                        $borrower_name = $borrower_type === 'student' ? 
                            ($pdo->query("SELECT CONCAT(first_name, ' ', last_name) FROM students WHERE id = $borrower_id")->fetchColumn() ?? 'Unknown') :
                            ($pdo->query("SELECT CONCAT(first_name, ' ', last_name) FROM teachers WHERE id = $borrower_id")->fetchColumn() ?? 'Unknown');
                        $stmt->execute([$book_id, $school_id, $librarian_id, "Book reserved by: $borrower_name"]);
                        
                        $success = 'Reservation created successfully!';
                    }
                }
            }
        } catch (PDOException $e) {
            error_log("Failed to create reservation: " . $e->getMessage());
            $errors[] = 'Failed to create reservation. Please try again.';
        }
    }
}

// Handle reservation status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_reservation_status'])) {
    $reservation_id = $_POST['reservation_id'] ?? '';
    $new_status = $_POST['new_status'] ?? '';
    
    try {
        $stmt = $pdo->prepare("UPDATE book_reservations SET status = ? WHERE id = ? AND school_id = ?");
        $stmt->execute([$new_status, $reservation_id, $school_id]);
        $success = 'Reservation status updated successfully!';
    } catch (PDOException $e) {
        error_log("Failed to update reservation status: " . $e->getMessage());
        $errors[] = 'Failed to update reservation status. Please try again.';
    }
}

// Handle reservation deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_reservation'])) {
    $reservation_id = $_POST['reservation_id'] ?? '';
    
    try {
        $stmt = $pdo->prepare("DELETE FROM book_reservations WHERE id = ? AND school_id = ?");
        $stmt->execute([$reservation_id, $school_id]);
        $success = 'Reservation deleted successfully!';
    } catch (PDOException $e) {
        error_log("Failed to delete reservation: " . $e->getMessage());
        $errors[] = 'Failed to delete reservation. Please try again.';
    }
}

// Get all reservations
$reservations = [];
try {
    $stmt = $pdo->prepare("SELECT br.*, b.title, b.author, b.available_copies,
              CASE 
                  WHEN br.user_type = 'student' THEN CONCAT(s.first_name, ' ', s.last_name)
                  WHEN br.user_type = 'teacher' THEN CONCAT(t.first_name, ' ', t.last_name)
              END as user_name,
              CASE 
                  WHEN br.user_type = 'student' THEN s.admission_number
                  WHEN br.user_type = 'teacher' THEN t.email
              END as user_identifier
              FROM book_reservations br
              JOIN books b ON br.book_id = b.id
              LEFT JOIN students s ON br.user_type = 'student' AND br.user_id = s.id
              LEFT JOIN teachers t ON br.user_type = 'teacher' AND br.user_id = t.id
              WHERE b.school_id = ?
              ORDER BY br.reservation_date DESC");
    $stmt->execute([$school_id]);
    $reservations = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Failed to fetch reservations: " . $e->getMessage());
}

// Get books for reservation form (books that are currently borrowed/unavailable)
$books = [];
try {
    $stmt = $pdo->prepare("SELECT DISTINCT b.* FROM books b 
                            LEFT JOIN book_borrowings bb ON b.id = bb.book_id AND bb.status = 'borrowed'
                            WHERE (b.available_copies = 0 OR bb.id IS NOT NULL)
                            ORDER BY b.title ASC");
    $stmt->execute();
    $books = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Failed to fetch books: " . $e->getMessage());
}

// Get students
$students = [];
try {
    $stmt = $pdo->prepare("SELECT id, CONCAT(first_name, ' ', last_name) as name, admission_number FROM students WHERE school_id = ? ORDER BY name ASC");
    $stmt->execute([$school_id]);
    $students = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Failed to fetch students: " . $e->getMessage());
}

// Get teachers
$teachers = [];
try {
    $stmt = $pdo->prepare("SELECT id, CONCAT(first_name, ' ', last_name) as name, email FROM teachers WHERE school_id = ? ORDER BY name ASC");
    $stmt->execute([$school_id]);
    $teachers = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Failed to fetch teachers: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reservations - <?php echo htmlspecialchars($librarian_name); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
            border-radius: 25px;
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
            transition: margin-left 0.3s;
        }
        
        .main-content.expanded {
            margin-left: 0;
        }
        
        /* Cards */
        .card {
            background: transparent;
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
        
        /* Table */
        .table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        .table th,
        .table td {
            padding: 12px 16px;
            text-align: left;
            border: 1px solid #000;
            border-bottom: 1px solid #000;
            border-right: 1px solid #000;
        }
        
        .table th {
            background: #f0f0f0;
            font-weight: 600;
            color: #000;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-top: 2px solid #000;
        }
        
        .table th:last-child {
            border-right: 2px solid #000;
        }
        
        .table td:last-child {
            border-right: 2px solid #000;
        }
        
        .table tbody tr:last-child td {
            border-bottom: 2px solid #000;
        }
        
        .table tbody tr:hover {
            background: #f9f9f9;
        }
        
        /* Buttons */
        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 25px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
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
            background: var(--secondary-color);
            color: white;
        }
        
        .btn-danger {
            background: #c5221f;
            color: white;
        }
        
        .btn-sm {
            padding: 4px 8px;
            font-size: 12px;
        }
        
        /* Form */
        .form-control {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #e8eaed;
            border-radius: 25px;
            font-size: 14px;
        }
        
        .form-control:focus {
            outline: none;
            border-color: var(--primary-color);
        }
        
        .form-label {
            display: block;
            margin-bottom: 4px;
            font-weight: 500;
            color: var(--secondary-color);
        }
        
        .mb-3 {
            margin-bottom: 16px;
        }
        
        /* Status badges */
        .badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .badge-success {
            background: #e6f4ea;
            color: #137333;
        }
        
        .badge-warning {
            background: #fef7e0;
            color: #b06000;
        }
        
        .badge-danger {
            background: #fce8e6;
            color: #c5221f;
        }
        
        .badge-info {
            background: #e8f0fe;
            color: #1967d2;
        }
        
        /* Alerts */
        .alert {
            padding: 12px 16px;
            border-radius: 4px;
            margin-bottom: 16px;
        }
        
        .alert-success {
            background: #e6f4ea;
            color: #137333;
            border: 1px solid #ceead6;
        }
        
        .alert-danger {
            background: #fce8e6;
            color: #c5221f;
            border: 1px solid #fad2cf;
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
                padding: 16px;
            }
            
            .header {
                padding: 0 16px;
            }
            
            .logo span {
                font-size: 16px;
            }
            
            .card {
                padding: 16px;
            }
            
            .card-title {
                font-size: 16px;
            }
            
            .table-responsive {
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
            }
            
            .table {
                font-size: 12px;
            }
            
            .table th,
            .table td {
                padding: 8px 10px;
            }
            
            .btn {
                padding: 10px 16px;
                font-size: 14px;
            }
            
            .btn-sm {
                padding: 6px 10px;
                font-size: 12px;
            }
            
            .form-control {
                padding: 10px 12px;
                font-size: 14px;
            }
            
            .form-label {
                font-size: 12px;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
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
            <span style="font-size: 14px; color: #5f6368; font-weight: 500;"><?php echo htmlspecialchars($school_name); ?></span>
        </div>
    </div>
    
    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-section">
            <a href="dashboard" class="nav-link">
                <i class="fas fa-home"></i>
                <span>Dashboard</span>
            </a>
            <a href="books" class="nav-link">
                <i class="fas fa-book"></i>
                <span>Books</span>
            </a>
            <a href="categories" class="nav-link">
                <i class="fas fa-tags"></i>
                <span>Categories</span>
            </a>
            <a href="borrow" class="nav-link">
                <i class="fas fa-hand-holding"></i>
                <span>Borrow Book</span>
            </a>
            <a href="return" class="nav-link">
                <i class="fas fa-undo"></i>
                <span>Return Book</span>
            </a>
            <a href="reservations" class="nav-link active">
                <i class="fas fa-bookmark"></i>
                <span>Reservations</span>
            </a>
            <a href="reports" class="nav-link">
                <i class="fas fa-chart-bar"></i>
                <span>Reports</span>
            </a>
            <a href="import_export" class="nav-link">
                <i class="fas fa-exchange-alt"></i>
                <span>Import/Export</span>
            </a>
            <a href="profile" class="nav-link">
                <i class="fas fa-user"></i>
                <span>Profile</span>
            </a>
            <button class="nav-link" onclick="logout()">
                <i class="fas fa-sign-out-alt"></i>
                <span>Logout</span>
            </button>
        </div>
    </div>
    
    <!-- Main Content -->
    <div class="main-content" id="mainContent">
        <h1 style="margin-bottom: 24px;">Book Reservations</h1>
        
        <?php if (isset($success)): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <?php foreach ($errors as $error): ?>
                    <?php echo htmlspecialchars($error); ?><br>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <!-- Create Reservation -->
        <div class="card">
            <h2 class="card-title">Create New Reservation</h2>
            <form method="POST">
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Book *</label>
                        <select class="form-control" name="book_id" required>
                            <option value="">Select Book (Only unavailable books)</option>
                            <?php foreach ($books as $book): ?>
                                <option value="<?php echo $book['id']; ?>"><?php echo htmlspecialchars($book['title']); ?> by <?php echo htmlspecialchars($book['author']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Borrower Type *</label>
                        <select class="form-control" name="borrower_type" required onchange="updateBorrowerSelect()">
                            <option value="">Select Type</option>
                            <option value="student">Student</option>
                            <option value="teacher">Teacher</option>
                        </select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Borrower *</label>
                        <select class="form-control" name="borrower_id" required id="borrowerSelect">
                            <option value="">Select borrower type first</option>
                        </select>
                    </div>
                </div>
                <button type="submit" name="create_reservation" class="btn btn-primary">
                    <i class="fas fa-plus me-2"></i> Create Reservation
                </button>
            </form>
        </div>
        
        <!-- Reservations List -->
        <div class="card">
            <h2 class="card-title">Current Reservations</h2>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Book</th>
                            <th>Author</th>
                            <th>User</th>
                            <th>Reservation Date</th>
                            <th>Expiry Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($reservations)): ?>
                            <tr>
                                <td colspan="7" class="text-center">No reservations found</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($reservations as $reservation): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($reservation['title']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($reservation['author']); ?></td>
                                    <td>
                                        <?php echo htmlspecialchars($reservation['user_name']); ?>
                                        <br>
                                        <small style="color: #5f6368;"><?php echo htmlspecialchars($reservation['user_identifier']); ?></small>
                                    </td>
                                    <td><?php echo date('M d, Y', strtotime($reservation['reservation_date'])); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($reservation['expiry_date'])); ?></td>
                                    <td>
                                        <?php if ($reservation['status'] === 'pending'): ?>
                                            <span class="badge badge-warning">Pending</span>
                                        <?php elseif ($reservation['status'] === 'fulfilled'): ?>
                                            <span class="badge badge-success">Fulfilled</span>
                                        <?php elseif ($reservation['status'] === 'cancelled'): ?>
                                            <span class="badge badge-danger">Cancelled</span>
                                        <?php else: ?>
                                            <span class="badge badge-info"><?php echo ucfirst($reservation['status']); ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($reservation['status'] === 'pending'): ?>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="reservation_id" value="<?php echo $reservation['id']; ?>">
                                                <input type="hidden" name="new_status" value="fulfilled">
                                                <button type="submit" name="update_reservation_status" class="btn btn-sm btn-success">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                            </form>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="reservation_id" value="<?php echo $reservation['id']; ?>">
                                                <input type="hidden" name="new_status" value="cancelled">
                                                <button type="submit" name="update_reservation_status" class="btn btn-sm btn-danger">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                        <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this reservation?');">
                                            <input type="hidden" name="reservation_id" value="<?php echo $reservation['id']; ?>">
                                            <button type="submit" name="delete_reservation" class="btn btn-sm btn-secondary">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
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
        
        function logout() {
            fetch('api/logout.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.location.href = 'logout';
                } else {
                    alert('Logout failed');
                }
            })
            .catch(error => {
                console.error('Logout error:', error);
                window.location.href = 'logout';
            });
        }
        
        function updateBorrowerSelect() {
            const borrowerType = document.querySelector('select[name="borrower_type"]').value;
            const borrowerSelect = document.getElementById('borrowerSelect');
            
            // Clear existing options
            borrowerSelect.innerHTML = '<option value="">Select borrower</option>';
            
            if (borrowerType === 'student') {
                <?php foreach ($students as $student): ?>
                    borrowerSelect.innerHTML += '<option value="<?php echo $student['id']; ?>"><?php echo htmlspecialchars($student['name']); ?> (<?php echo htmlspecialchars($student['admission_number']); ?>)</option>';
                <?php endforeach; ?>
            } else if (borrowerType === 'teacher') {
                <?php foreach ($teachers as $teacher): ?>
                    borrowerSelect.innerHTML += '<option value="<?php echo $teacher['id']; ?>"><?php echo htmlspecialchars($teacher['name']); ?> (<?php echo htmlspecialchars($teacher['email']); ?>)</option>';
                <?php endforeach; ?>
            }
        }
    </script>
</body>
</html>
