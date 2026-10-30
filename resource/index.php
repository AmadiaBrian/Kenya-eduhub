<?php
// Resource Router - Handles resource routes
session_start();
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/helpers.php';

// Get route parameter (the ID after /resource/)
$route = $_GET['route'] ?? '';

// Extract ID from route (e.g., "5" from "5")
$resource_id = intval($route);

if ($resource_id === 0) {
    header('HTTP/1.0 404 Not Found');
    echo '<h1>404 - Resource Not Found</h1>';
    exit;
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    // Store the intended URL for redirect after login
    $_SESSION['redirect_after_login'] = '/resource/' . $resource_id;
    header("Location: ../auth/login");
    exit();
} else {
    // User is logged in - redirect to dashboard (all downloads happen from dashboard)
    header("Location: ../dashboard/dashboard.php");
    exit();
}

// Get resource details
try {
    $stmt = $conn->prepare("SELECT * FROM resources WHERE id = ?");
    $stmt->execute([$resource_id]);
    $resource = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    die("Error fetching resource: " . $e->getMessage());
}

if (!$resource) {
    header('HTTP/1.0 404 Not Found');
    echo '<h1>404 - Resource Not Found</h1>';
    exit;
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
    <title><?php echo htmlspecialchars($title); ?> - Free Download | Kenya EduHub</title>
    <meta name="description" content="<?php echo htmlspecialchars(substr($description, 0, 160)); ?>">
    <meta name="keywords" content="<?php echo htmlspecialchars($subject); ?>, <?php echo htmlspecialchars($type); ?>, educational resources, Kenya EduHub, CBC curriculum, KCSE past papers, free download, study materials">
    <meta name="author" content="Kenya EduHub">
    <meta name="robots" content="index, follow">
    <meta name="googlebot" content="index, follow">
    <meta name="language" content="English">
    <meta name="geo.region" content="KE">
    <meta name="geo.placename" content="Kenya">

    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="https://kenyaeduhub.kesug.com/assets/favicon.ico" />
    <link rel="apple-touch-icon" href="https://kenyaeduhub.kesug.com/assets/favicon.ico">

    <!-- Canonical URL -->
    <link rel="canonical" href="https://kenyaeduhub.kesug.com/resource/<?php echo $resource_id; ?>">
    
    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="article">
    <meta property="og:url" content="https://kenyaeduhub.kesug.com/resource/<?php echo $resource_id; ?>">
    <meta property="og:title" content="<?php echo htmlspecialchars($title); ?> - Kenya EduHub">
    <meta property="og:description" content="<?php echo htmlspecialchars(substr($description, 0, 160)); ?>">
    <meta property="og:image" content="https://kenyaeduhub.kesug.com/assets/favicon.ico">
    <meta property="og:site_name" content="Kenya EduHub">
    <meta property="og:locale" content="en_KE">
    <meta property="og:locale:alternate" content="sw_KE">

    <!-- Twitter -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:url" content="https://kenyaeduhub.kesug.com/resource/<?php echo $resource_id; ?>">
    <meta name="twitter:title" content="<?php echo htmlspecialchars($title); ?> - Kenya EduHub">
    <meta name="twitter:description" content="<?php echo htmlspecialchars(substr($description, 0, 160)); ?>">
    <meta name="twitter:image" content="https://kenyaeduhub.kesug.com/assets/favicon.ico">
    <meta name="twitter:site" content="@KenyaEduHub">
    <meta name="twitter:creator" content="@KenyaEduHub">
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0" />
    
    <!-- Breadcrumb Schema -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "BreadcrumbList",
        "itemListElement": [
            {
                "@type": "ListItem",
                "position": 1,
                "name": "Home",
                "item": "https://kenyaeduhub.kesug.com/"
            },
            {
                "@type": "ListItem",
                "position": 2,
                "name": "Resources",
                "item": "https://kenyaeduhub.kesug.com/dashboard"
            },
            {
                "@type": "ListItem",
                "position": 3,
                "name": "<?php echo htmlspecialchars($title); ?>",
                "item": "https://kenyaeduhub.kesug.com/resource/<?php echo $resource_id; ?>"
            }
        ]
    }
    </script>
    
    <!-- Structured Data for Individual Resource -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "DigitalDocument",
        "name": "<?php echo htmlspecialchars($title); ?>",
        "description": "<?php echo htmlspecialchars($description); ?>",
        "url": "https://kenyaeduhub.kesug.com/resource/" . $resource_id,
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
        "learningResourceType": "<?php echo htmlspecialchars($type); ?>",
        "educationalLevel": "Secondary School",
        "educationalUse": "Study Material",
        "audience": {
            "@type": "Audience",
            "audienceType": "Students"
        },
        "teaches": "<?php echo htmlspecialchars($subject); ?>"
    }
    </script>
    
    <!-- FAQ Schema for Featured Snippets -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "FAQPage",
        "mainEntity": [
            {
                "@type": "Question",
                "name": "How do I download <?php echo htmlspecialchars($title); ?>?",
                "acceptedAnswer": {
                    "@type": "Answer",
                    "text": "Register or login to Kenya EduHub, then visit your dashboard to download <?php echo htmlspecialchars($title); ?> and other educational resources."
                }
            },
            {
                "@type": "Question",
                "name": "What is <?php echo htmlspecialchars($title); ?> about?",
                "acceptedAnswer": {
                    "@type": "Answer",
                    "text": "<?php echo htmlspecialchars($description); ?>"
                }
            },
            {
                "@type": "Question",
                "name": "Is <?php echo htmlspecialchars($title); ?> free?",
                "acceptedAnswer": {
                    "@type": "Answer",
                    "text": "Yes, <?php echo htmlspecialchars($title); ?> is available for free download on Kenya EduHub after registration."
                }
            }
        ]
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

        .breadcrumbs {
            background: #ffffff;
            padding: 12px 20px;
            border-bottom: 1px solid #e0e0e0;
        }

        .breadcrumb {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            color: var(--secondary-color);
            max-width: 1200px;
            margin: 0 auto;
        }

        .breadcrumb a {
            color: var(--primary-color);
            text-decoration: none;
        }

        .breadcrumb a:hover {
            text-decoration: underline;
        }

        .breadcrumb-separator {
            color: #dadce0;
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
    <div class="breadcrumbs">
        <div class="breadcrumb">
            <a href="../">Home</a>
            <span class="breadcrumb-separator">›</span>
            <a href="../dashboard">Resources</a>
            <span class="breadcrumb-separator">›</span>
            <span><?php echo htmlspecialchars($title); ?></span>
        </div>
    </div>

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
            <a href="../dashboard" class="back-link">← Back to Dashboard</a>
        </div>
    </div>
</body>
</html>
