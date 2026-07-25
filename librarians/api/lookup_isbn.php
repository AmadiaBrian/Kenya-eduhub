<?php
// API endpoint to lookup book details by ISBN using Open Library API
header('Content-Type: application/json');

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Load config if not already loaded
if (!isset($pdo)) {
    require_once __DIR__ . '/../../config.php';
}

// Authentication check
if (!isset($_SESSION['librarian_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$isbn = $_GET['isbn'] ?? '';

if (empty($isbn)) {
    echo json_encode(['success' => false, 'message' => 'ISBN is required']);
    exit;
}

// Clean ISBN (remove hyphens and spaces)
$isbn = preg_replace('/[^0-9X]/i', '', $isbn);

try {
    // Try Open Library API
    $open_library_url = "https://openlibrary.org/api/books?bibkeys=ISBN:" . $isbn . "&format=json&jscmd=data";
    $response = file_get_contents($open_library_url);
    
    if ($response) {
        $data = json_decode($response, true);
        $book_key = "ISBN:" . $isbn;
        
        if (isset($data[$book_key])) {
            $book_data = $data[$book_key];
            
            $result = [
                'success' => true,
                'data' => [
                    'title' => $book_data['title'] ?? '',
                    'authors' => [],
                    'publisher' => '',
                    'publish_year' => $book_data['publish_date'] ?? '',
                    'description' => ''
                ];
            
            // Extract authors
            if (isset($book_data['authors'])) {
                foreach ($book_data['authors'] as $author) {
                    $result['data']['authors'][] = $author['name'] ?? '';
                }
            }
            
            // Extract publisher
            if (isset($book_data['publishers'])) {
                $publishers = [];
                foreach ($book_data['publishers'] as $publisher) {
                    $publishers[] = $publisher['name'] ?? '';
                }
                $result['data']['publisher'] = implode(', ', $publishers);
            }
            
            // Extract description
            if (isset($book_data['notes'])) {
                $result['data']['description'] = strip_tags($book_data['notes']);
            }
            
            echo json_encode($result);
            exit;
        }
    }
    
    // If Open Library didn't work, try Google Books API
    $google_books_url = "https://www.googleapis.com/books/v1/volumes?q=isbn:" . $isbn;
    $response = file_get_contents($google_books_url);
    
    if ($response) {
        $data = json_decode($response, true);
        
        if (isset($data['items']) && !empty($data['items'])) {
            $volume_info = $data['items'][0]['volumeInfo'];
            
            $result = [
                'success' => true,
                'data' => [
                    'title' => $volume_info['title'] ?? '',
                    'authors' => $volume_info['authors'] ?? [],
                    'publisher' => $volume_info['publisher'] ?? '',
                    'publish_year' => isset($volume_info['publishedDate']) ? substr($volume_info['publishedDate'], 0, 4) : '',
                    'description' => isset($volume_info['description']) ? strip_tags($volume_info['description']) : ''
                ]
            ];
            
            echo json_encode($result);
            exit;
        }
    }
    
    // No results found
    echo json_encode(['success' => false, 'message' => 'No book found with this ISBN']);
    
} catch (Exception $e) {
    error_log("ISBN lookup failed: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Failed to lookup ISBN']);
}
