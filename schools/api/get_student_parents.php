<?php
// Get Student Parents API Endpoint
header('Content-Type: application/json');

// Load dependencies
require_once '../../config.php';

// Get POST data
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode([
        'success' => false,
        'error' => 'Invalid request data'
    ]);
    exit;
}

// Validate required fields
$required = ['admission_number', 'school_id'];
foreach ($required as $field) {
    if (empty($input[$field])) {
        echo json_encode([
            'success' => false,
            'error' => "Missing required field: $field"
        ]);
        exit;
    }
}

$admissionNumber = $input['admission_number'];
$schoolId = $input['school_id'];

try {
    // Get student ID from admission number
    $stmt = $pdo->prepare("SELECT id FROM students WHERE admission_number = ? AND school_id = ?");
    $stmt->execute([$admissionNumber, $schoolId]);
    $student = $stmt->fetch();
    
    if (!$student) {
        echo json_encode([
            'success' => false,
            'error' => 'Student not found'
        ]);
        exit;
    }
    
    $studentId = $student['id'];
    
    // Get parents for this student
    $stmt = $pdo->prepare("SELECT p.id, p.first_name, p.last_name, p.phone 
                          FROM parents p
                          JOIN student_parents sp ON p.id = sp.parent_id
                          WHERE sp.student_id = ?");
    $stmt->execute([$studentId]);
    $parents = $stmt->fetchAll();
    
    // Format parent data
    $parentData = [];
    foreach ($parents as $parent) {
        $parentData[] = [
            'id' => $parent['id'],
            'name' => $parent['first_name'] . ' ' . $parent['last_name'],
            'phone' => $parent['phone']
        ];
    }
    
    echo json_encode([
        'success' => true,
        'parents' => $parentData
    ]);
    
} catch (PDOException $e) {
    error_log("Failed to fetch student parents: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Database error'
    ]);
}
?>
