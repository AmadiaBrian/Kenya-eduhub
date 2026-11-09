<?php
// Parent Calendar API
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../config.php';

header('Content-Type: application/json');

// Allow all origins for mobile app
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Credentials: true");

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    header("Access-Control-Allow-Credentials: true");
    http_response_code(200);
    exit();
}

// Check if parent is logged in
if (!isset($_SESSION['parent_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$parent_id = $_SESSION['parent_id'];
$school_id = $_SESSION['school_id'];

// Load calendar helpers
require_once __DIR__ . '/../../includes/calendar_helpers.php';

try {
    // Update active term based on current date
    updateActiveTerm($pdo, $school_id);

    // Get calendar status
    $calendar_status = getSchoolCalendarStatus($pdo, $school_id);

    // Get Terms
    $terms = [];
    $stmt = $pdo->prepare("SELECT * FROM terms WHERE school_id = ? ORDER BY year DESC, term_number ASC");
    $stmt->execute([$school_id]);
    $terms = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get Holidays
    $holidays = [];
    $stmt = $pdo->prepare("SELECT * FROM holidays WHERE school_id = ? AND is_active = 1 ORDER BY start_date ASC");
    $stmt->execute([$school_id]);
    $holidays = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get School Events
    $school_events = [];
    $stmt = $pdo->prepare("SELECT * FROM school_events WHERE school_id = ? AND is_active = 1 ORDER BY event_date ASC");
    $stmt->execute([$school_id]);
    $school_events = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'calendar_status' => $calendar_status,
        'terms' => $terms,
        'holidays' => $holidays,
        'school_events' => $school_events
    ]);
} catch (PDOException $e) {
    error_log("Calendar API error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'An error occurred. Please try again.']);
} catch (Exception $e) {
    error_log("Calendar API error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}