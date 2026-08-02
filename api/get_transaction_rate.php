<?php
/**
 * Get Transaction Rate API
 * Fetches a single transaction rate by ID
 */

require_once '../config.php';

header('Content-Type: application/json');

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$id = $_GET['id'] ?? 0;

if (!$id) {
    echo json_encode(['success' => false, 'message' => 'Invalid rate ID']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT * FROM transaction_rates WHERE id = ?");
    $stmt->execute([$id]);
    $rate = $stmt->fetch();
    
    if ($rate) {
        echo json_encode(['success' => true, 'data' => $rate]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Rate not found']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
