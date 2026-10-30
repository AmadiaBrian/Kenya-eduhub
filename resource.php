<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'config.php';

// Use PDO connection
$conn = $pdo;

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    // Store the resource ID in session for redirect after login
    $_SESSION['redirect_after_login'] = 'resource.php?id=' . ($_GET['id'] ?? 0);
    header("Location: auth/login");
    exit();
} else {
    // User is logged in - redirect to dashboard (all downloads happen from dashboard)
    header("Location: dashboard/dashboard.php");
    exit();
}

// Get resource ID from URL
$resource_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($resource_id === 0) {
    header("Location: index.php");
    exit();
}

// Get resource details
$resource = null;
try {
    $stmt = $conn->prepare("SELECT * FROM resources WHERE id = ?");
    $stmt->execute([$resource_id]);
    $resource = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    die("Error fetching resource: " . $e->getMessage());
}

if (!$resource) {
    header("Location: index.php");
    exit();
}

// Get column names for display
$columns_query = $conn->query("SHOW COLUMNS FROM resources");
$available_columns = [];
while ($col = $columns_query->fetch(PDO::FETCH_ASSOC)) {
    $available_columns[] = $col['Field'];
}

// Extract resource details
$title = $resource['title'] ?? $resource['file_name'] ?? 'Untitled Resource';
$description = $resource['description'] ?? 'No description available.';
$subject = $resource['subject'] ?? '';
$type = $resource['type'] ?? $resource['file_type'] ?? '';
$uploaded_by = $resource['uploaded_by'] ?? '';
$created_at = $resource['created_at'] ?? '';
$file_path = $resource['file_path'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#FF6B35">
    <title><?php echo htmlspecialchars($title); ?> - Kenya EduHub</title>
    <meta name="description" content="<?php echo htmlspecialchars(substr($description, 0, 160)); ?>">
    <meta name="keywords" content="<?php echo htmlspecialchars($subject); ?>, <?php echo htmlspecialchars($type); ?>, educational resources, Kenya EduHub, CBC curriculum, KCSE past papers">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0" />
    
    <!-- Structured Data for Individual Resource -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "DigitalDocument",
        "name": "<?php echo htmlspecialchars($title); ?>",
        "description": "<?php echo htmlspecialchars($description); ?>",
        "url": "https://kenyaeduhub.kesug.com/resource.php?id=<?php echo $resource_id; ?>",
        "author": {
            "@type": "Organization",
            "name": "Kenya EduHub"
        },
        "about": {
            "@type": "Thing",
            "name": "<?php echo htmlspecialchars($subject); ?>"
        },
        "datePublished": "<?php echo date('c', strtotime($created_at)); ?>",
        "inLanguage": "en",
        "learningResourceType": "<?php echo htmlspecialchars($type); ?>"
    }
    </script>
    
    <style>
        :root {
            --primary-color: #FF6B35;
            --secondary-color: #5f6368;
            --bg-color: #f8f9fa;
        }

        body {
            background: var(--bg-color);
            font-family: 'Google Sans', 'Roboto', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #202124;
        }

        .resource-header {
            background: #ffffff;
            padding: 40px 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .resource-title {
            font-size: 32px;
            font-weight: 400;
            color: #202124;
            margin-bottom: 16px;
        }

        .resource-meta {
            display: flex;
            gap: 16px;
            flex-wrap: wrap;
            font-size: 14px;
            color: var(--secondary-color);
            margin-bottom: 24px;
        }

        .resource-meta span {
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .resource-content {
            max-width: 800px;
            margin: 40px auto;
            padding: 0 20px;
        }

        .resource-description {
            background: white;
            padding: 24px;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            margin-bottom: 24px;
        }

        .resource-description h3 {
            font-size: 20px;
            margin-bottom: 16px;
            color: #202124;
        }

        .resource-description p {
            color: var(--secondary-color);
            line-height: 1.6;
        }

        .download-section {
            background: white;
            padding: 24px;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            text-align: center;
        }

        .download-btn {
            background: var(--primary-color);
            color: white;
            border: none;
            padding: 16px 32px;
            border-radius: 25px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: background 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .download-btn:hover {
            background: #e55a2b;
        }

        .back-link {
            display: inline-block;
            margin-top: 20px;
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
        }

        .back-link:hover {
            text-decoration: underline;
        }

        .material-symbols-outlined {
            font-size: 18px;
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
        }

        @media (max-width: 768px) {
            .resource-title {
                font-size: 24px;
            }
        }
    </style>
</head>
<body>
    <div class="resource-header">
        <div style="max-width: 800px; margin: 0 auto;">
            <h1 class="resource-title"><?php echo htmlspecialchars($title); ?></h1>
            <div class="resource-meta">
                <?php if (!empty($subject)): ?>
                    <span>
                        <span class="material-symbols-outlined">school</span>
                        <?php echo htmlspecialchars($subject); ?>
                    </span>
                <?php endif; ?>
                <?php if (!empty($type)): ?>
                    <span>
                        <span class="material-symbols-outlined">category</span>
                        <?php echo htmlspecialchars($type); ?>
                    </span>
                <?php endif; ?>
                <?php if (!empty($uploaded_by)): ?>
                    <span>
                        <span class="material-symbols-outlined">person</span>
                        <?php echo htmlspecialchars($uploaded_by); ?>
                    </span>
                <?php endif; ?>
                <?php if (!empty($created_at)): ?>
                    <span>
                        <span class="material-symbols-outlined">calendar_today</span>
                        <?php echo date('M d, Y', strtotime($created_at)); ?>
                    </span>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="resource-content">
        <div class="resource-description">
            <h3>Description</h3>
            <p><?php echo nl2br(htmlspecialchars($description)); ?></p>
        </div>

        <div class="download-section">
            <?php if (!empty($file_path)): ?>
                <a href="<?php echo htmlspecialchars($file_path); ?>" class="download-btn" download>
                    <span class="material-symbols-outlined" style="font-size: 20px;">download</span>
                    Download Resource
                </a>
            <?php else: ?>
                <p style="color: var(--secondary-color);">File not available for download</p>
            <?php endif; ?>
            <br>
            <a href="dashboard/dashboard.php" class="back-link">← Back to Dashboard</a>
        </div>
    </div>
</body>
</html>
