<?php
// Teacher Profile Page
// Authentication is handled by index.php router
$teacher_id = $_SESSION['teacher_id'];
$teacher_name = $_SESSION['teacher_name'] ?? 'Teacher';

// Get teacher details
$teacher = null;
try {
    $stmt = $pdo->prepare("SELECT t.*, s.school_name FROM teachers t JOIN schools s ON t.school_id = s.id WHERE t.id = ?");
    $stmt->execute([$teacher_id]);
    $teacher = $stmt->fetch();
} catch (PDOException $e) {
    error_log("Failed to fetch teacher details: " . $e->getMessage());
}

// Get subjects from database for the teacher's school
$subjects = [];
if ($teacher) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM subjects WHERE school_id = ? AND status = 'active' ORDER BY subject_name");
        $stmt->execute([$teacher['school_id']]);
        $subjects = $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Failed to fetch subjects: " . $e->getMessage());
    }
}

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $id_number = trim($_POST['id_number'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    
    // Validation
    $errors = [];
    if (empty($first_name)) $errors[] = 'First name is required';
    if (empty($last_name)) $errors[] = 'Last name is required';
    if (empty($email)) $errors[] = 'Email is required';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Invalid email format';
    if (empty($phone)) $errors[] = 'Phone is required';
    if (empty($id_number)) $errors[] = 'ID number is required';
    
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("UPDATE teachers SET first_name = ?, last_name = ?, email = ?, phone = ?, id_number = ?, address = ?, subject = ? WHERE id = ?");
            $stmt->execute([$first_name, $last_name, $email, $phone, $id_number, $address, $subject, $teacher_id]);
            
            // Update session
            $_SESSION['teacher_name'] = $first_name . ' ' . $last_name;
            
            $success = 'Profile updated successfully!';
            
            // Refresh teacher data
            $stmt = $pdo->prepare("SELECT t.*, s.school_name FROM teachers t JOIN schools s ON t.school_id = s.id WHERE t.id = ?");
            $stmt->execute([$teacher_id]);
            $teacher = $stmt->fetch();
        } catch (PDOException $e) {
            error_log("Failed to update teacher profile: " . $e->getMessage());
            $errors[] = 'Failed to update profile. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#FF6B35">
    <title>Profile - <?php echo htmlspecialchars($teacher_name); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/notifications.css">
    <style>
        :root {
            --primary-color: #FF6B35;
        }

        .page-title {
            font-size: 22px;
            font-weight: 400;
            color: #202124;
            margin-bottom: 24px;
            text-align: center;
        }

        .card {
            background: #f8f9fa;
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

        .form-label {
            font-weight: 500;
            color: #5f6368;
            margin-bottom: 8px;
        }

        .form-control {
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 10px 12px;
            font-size: 14px;
            background: #f8f9fa;
        }

        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 2px rgba(255, 107, 53, 0.2);
        }

        .btn-primary {
            background: var(--primary-color);
            color: white;
            border: none;
            padding: 10px 24px;
            border-radius: 4px;
            font-weight: 500;
        }

        .btn-primary:hover {
            background: #e55a2b;
        }

        .alert {
            padding: 12px 16px;
            border-radius: 4px;
            margin-bottom: 16px;
        }

        .alert-success {
            background: #e8f5e9;
            color: #1e8e3e;
            border: 1px solid #c8e6c9;
        }

        .alert-danger {
            background: #ffebee;
            color: #c62828;
            border: 1px solid #ffcdd2;
        }
    </style>
</head>
<body>
    <?php require_once 'includes/header.php'; ?>
    <?php $active_page = 'profile'; require_once 'includes/sidebar.php'; ?>
    
    <!-- Main Content -->
    <main class="main-content" id="mainContent">
        <h1 class="page-title">My Profile</h1>
        
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
        
        <?php if ($teacher): ?>
        <div class="card">
            <h2 class="card-title">Personal Information</h2>
            <form method="POST">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">First Name</label>
                        <input type="text" class="form-control" name="first_name" value="<?php echo htmlspecialchars($teacher['first_name']); ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Last Name</label>
                        <input type="text" class="form-control" name="last_name" value="<?php echo htmlspecialchars($teacher['last_name']); ?>" required>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($teacher['email']); ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Phone</label>
                        <input type="text" class="form-control" name="phone" value="<?php echo htmlspecialchars($teacher['phone']); ?>" required>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">ID Number</label>
                        <input type="text" class="form-control" name="id_number" value="<?php echo htmlspecialchars($teacher['id_number']); ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Subject</label>
                        <select class="form-control" name="subject">
                            <option value="">Select Subject</option>
                            <?php foreach ($subjects as $subj): ?>
                                <option value="<?php echo htmlspecialchars($subj['subject_name']); ?>" <?php echo ($teacher['subject'] ?? '') === $subj['subject_name'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($subj['subject_name']); ?>
                                    <?php if ($subj['subject_code']): ?>
                                        (<?php echo htmlspecialchars($subj['subject_code']); ?>)
                                    <?php endif; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Address</label>
                    <textarea class="form-control" name="address" rows="3"><?php echo htmlspecialchars($teacher['address'] ?? ''); ?></textarea>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">School</label>
                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($teacher['school_name']); ?>" readonly>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Teacher Type</label>
                    <input type="text" class="form-control" value="<?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $teacher['teacher_type']))); ?>" readonly>
                </div>
                
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-2"></i> Update Profile
                </button>
            </form>
        </div>
        <?php else: ?>
            <div class="alert alert-danger">
                Unable to load profile information.
            </div>
        <?php endif; ?>
    </main>
    
    <script src="../assets/js/notifications.js"></script>
    <?php require_once '../includes/copywrite.php'; ?>
</body>
</html>
