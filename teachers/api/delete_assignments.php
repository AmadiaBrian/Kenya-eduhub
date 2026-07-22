<?php
session_start();
require_once '../../config.php';

// Check authentication
if (!isset($_SESSION['teacher_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

header('Content-Type: application/json');

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}

try {
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    $ids = $input['ids'] ?? [];
    
    if (empty($ids)) {
        echo json_encode(['success' => false, 'error' => 'No assignments selected']);
        exit;
    }
    
    $teacher_id = $_SESSION['teacher_id'];
    $school_id = $_SESSION['school_id'];
    
    // Verify teacher owns these assignments
    $placeholders = str_repeat('?,', count($ids) - 1) . '?';
    $stmt = $pdo->prepare("SELECT id FROM assignments WHERE id IN ($placeholders) AND teacher_id = ? AND school_id = ?");
    $stmt->execute(array_merge($ids, [$teacher_id, $school_id]));
    $owned_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (count($owned_ids) !== count($ids)) {
        echo json_encode(['success' => false, 'error' => 'Some assignments do not belong to you']);
        exit;
    }
    
    // Delete assignment downloads first
    $stmt = $pdo->prepare("DELETE FROM assignment_downloads WHERE assignment_id IN ($placeholders)");
    $stmt->execute($ids);
    
    // Delete assignments
    $stmt = $pdo->prepare("DELETE FROM assignments WHERE id IN ($placeholders)");
    $stmt->execute($ids);
    
    echo json_encode(['success' => true, 'message' => 'Assignments deleted successfully']);
    
} catch (PDOException $e) {
    error_log("Failed to delete assignments: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Database error occurred']);
}
