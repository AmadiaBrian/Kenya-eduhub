<?php
// QR Code Generation for Books
// Authentication is handled by index.php router
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Load config if not already loaded
if (!isset($pdo)) {
    require_once __DIR__ . '/../config.php';
}

// Authentication check
if (!isset($_SESSION['librarian_id']) || !isset($_SESSION['librarian_token'])) {
    header('Location: index.php?route=login');
    exit;
}

$librarian_id = $_SESSION['librarian_id'];
$school_id = $_SESSION['school_id'];

$book_id = $_GET['book_id'] ?? 0;

if (!$book_id) {
    die('Invalid book ID');
}

// Get book details
try {
    $stmt = $pdo->prepare("SELECT * FROM books WHERE id = ? AND school_id = ?");
    $stmt->execute([$book_id, $school_id]);
    $book = $stmt->fetch();
    
    if (!$book) {
        die('Book not found');
    }
} catch (PDOException $e) {
    die('Failed to fetch book details');
}

// Generate QR code data
$qr_data = json_encode([
    'book_id' => $book['id'],
    'isbn' => $book['isbn'],
    'title' => $book['title'],
    'school_id' => $school_id
]);

// Use Google Charts API for QR code generation (simple, no library needed)
$qr_url = "https://chart.googleapis.com/chart?cht=qr&chs=300x300&chl=" . urlencode($qr_data);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#FF6B35">
    <title>QR Code - <?php echo htmlspecialchars($book['title']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: #f8f9fa;
            font-family: 'Google Sans', 'Roboto', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            padding: 40px;
        }
        .qr-container {
            max-width: 500px;
            margin: 0 auto;
            background: white;
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
        }
        .qr-image {
            margin: 20px 0;
            border: 2px solid #000;
            padding: 10px;
            background: white;
        }
        .book-info {
            margin: 20px 0;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 4px;
        }
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            margin: 5px;
        }
        .btn-primary {
            background: #FF6B35;
            color: white;
        }
        .btn-secondary {
            background: #5f6368;
            color: white;
        }
    </style>
</head>
<body>
    <div class="qr-container">
        <h2>Book QR Code</h2>
        <div class="qr-image">
            <img src="<?php echo $qr_url; ?>" alt="QR Code" style="width: 300px; height: 300px;">
        </div>
        
        <div class="book-info">
            <h4><?php echo htmlspecialchars($book['title']); ?></h4>
            <p><strong>Author:</strong> <?php echo htmlspecialchars($book['author']); ?></p>
            <p><strong>ISBN:</strong> <?php echo htmlspecialchars($book['isbn'] ?? 'N/A'); ?></p>
            <p><strong>Location:</strong> <?php echo htmlspecialchars($book['shelf_location'] ?? 'N/A'); ?></p>
        </div>
        
        <div style="margin-top: 20px;">
            <button class="btn btn-primary" onclick="window.print()">
                <i class="fas fa-print me-2"></i> Print QR Code
            </button>
            <a href="books" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-2"></i> Back to Books
            </a>
        </div>
    </div>
    
    <script>
        @media print {
            body {
                padding: 0;
                background: white;
            }
            .qr-container {
                box-shadow: none;
                border: 2px solid #000;
            }
            .btn {
                display: none;
            }
        }
    </script>
</body>
</html>
