<?php
// Start session only if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database connection for notifications
require_once 'db.php';

// Initialize variables
$user_name = 'User';
$unread_count = 0;
$recent_messages = [];
$user_passport = '';

// Check if user is logged in
if (isset($_SESSION['user'])) {
    $user_id = $_SESSION['user'];
    
    // Fetch user's data including passport
    $stmt = $conn->prepare("SELECT name, passport FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result && $result->num_rows > 0) {
        $user_data = $result->fetch_assoc();
        $user_name = $user_data['name'];
        $user_passport = $user_data['passport'] ?? '';
        $_SESSION['user_name'] = $user_name;
    }
    $stmt->close();
    
    // Get unread message count
    $stmt = $conn->prepare("SELECT COUNT(*) as unread_count FROM messages WHERE user_id = ? AND status = 'unread'");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result && $result->num_rows > 0) {
        $count_data = $result->fetch_assoc();
        $unread_count = $count_data['unread_count'] ?? 0;
        $_SESSION['unread_count'] = $unread_count;
    }
    $stmt->close();
    
    // Fetch recent messages
    $stmt = $conn->prepare("SELECT * FROM messages WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $recent_messages = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Sony Sugar Attachment Portal</title>
  <link rel="shortcut icon" href="favicon.ico" type="image/x-icon">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    .notification-badge {
        position: absolute;
        top: -5px;
        right: -5px;
        font-size: 10px;
        padding: 0.25em 0.5em;
        border-radius: 50%;
        background-color: #ff6b35;
        color: white;
    }
    .navbar {
        background: linear-gradient(135deg, rgb(179, 166, 158), rgb(84, 133, 104));
    }
    .dropdown-menu {
        animation: fadeIn 0.2s ease-in-out;
    }
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to   { opacity: 1; transform: translateY(0); }
    }
    .navbar-dark .navbar-nav .nav-link {
        color: rgba(255, 255, 255, 0.9);
    }
    .navbar-dark .navbar-nav .nav-link:hover {
        color: rgba(255, 255, 255, 1);
    }
  </style>
</head>
<body>
<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark">
  <div class="container-fluid">
    <a class="navbar-brand" href="dashboard.php">
      <img src="sony logo.png" alt="Sony Sugar" height="40" class="d-inline-block align-top">
      <span class="ms-2">Attachment Portal</span>
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav ms-auto">
        <?php if (isset($_SESSION['user'])): ?>
        <!-- Notifications -->
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" href="#" id="notificationsDropdown" role="button" data-bs-toggle="dropdown">
            <i class="fas fa-bell"></i>
            <?php if ($unread_count > 0): ?>
              <span class="badge bg-danger rounded-pill"><?php echo $unread_count; ?></span>
            <?php endif; ?>
          </a>
          <ul class="dropdown-menu dropdown-menu-end">
            <?php if (!empty($recent_messages)): ?>
              <?php foreach ($recent_messages as $message): ?>
                <li><a class="dropdown-item" href="messages.php?view=<?php echo $message['id']; ?>">
                  <?php echo htmlspecialchars($message['subject']); ?>
                </a></li>
              <?php endforeach; ?>
            <?php else: ?>
              <li><a class="dropdown-item" href="#">No new notifications</a></li>
            <?php endif; ?>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item text-center" href="messages.php">View all messages</a></li>
          </ul>
        </li>
        
        <!-- User Dropdown -->
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
            <?php if (!empty($user_passport) && file_exists('uploads/passports/' . $user_passport)): ?>
              <img src="uploads/passports/<?php echo htmlspecialchars($user_passport); ?>" 
                   alt="Profile" style="width: 32px; height: 32px; border-radius: 50%; object-fit: cover; margin-right: 0.5rem;">
            <?php else: ?>
              <div style="width: 32px; height: 32px; border-radius: 50%; background: white; color: #548568; 
                          display: flex; align-items: center; justify-content: center; font-weight: bold; margin-right: 0.5rem;">
                <?= strtoupper(substr($user_name, 0, 1)) ?>
              </div>
            <?php endif; ?>
            <span class="d-none d-md-inline"><?php echo htmlspecialchars(explode(' ', $user_name)[0]); ?></span>
          </a>
          <ul class="dropdown-menu dropdown-menu-end">
            <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user me-2"></i> Profile</a></li>
            <li><a class="dropdown-item" href="update_profile.php"><i class="fas fa-user-edit me-2"></i> Update Profile</a></li>
            <li><a class="dropdown-item" href="settings.php"><i class="fas fa-cog me-2"></i> Settings</a></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item text-danger" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i> Logout</a></li>
          </ul>
        </li>
        <?php else: ?>
        <!-- Login/Register -->
        <li class="nav-item"><a class="nav-link" href="login.php"><i class="fas fa-sign-in-alt me-1"></i> Login</a></li>
        <li class="nav-item"><a class="nav-link" href="register.php"><i class="fas fa-user-plus me-1"></i> Register</a></li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</nav>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
