<?php
// Disciplinary Document Generation API - JSON Response
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['school_id'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$school_id = $_SESSION['school_id'];
$record_id = isset($_GET['record_id']) ? (int)$_GET['record_id'] : 0;

if (!$record_id) {
    echo json_encode(['error' => 'Record ID required']);
    exit;
}

require_once __DIR__ . '/../../config.php';

// Get disciplinary record details
try {
    $stmt = $pdo->prepare("SELECT dr.*, s.admission_number, CONCAT(s.first_name, ' ', s.last_name) as student_name, 
             s.gender, c.class_name, st.stream_name, sch.school_name, sch.school_code, sch.address, sch.phone, sch.logo
             FROM disciplinary_records dr
             JOIN students s ON dr.student_id = s.id
             LEFT JOIN classes c ON s.class_id = c.id
             LEFT JOIN streams st ON s.stream_id = st.id
             JOIN schools sch ON dr.school_id = sch.id
             WHERE dr.id = ? AND dr.school_id = ?");
    $stmt->execute([$record_id, $school_id]);
    $record = $stmt->fetch();
    
    if (!$record) {
        echo json_encode(['error' => 'Record not found']);
        exit;
    }
} catch (PDOException $e) {
    echo json_encode(['error' => 'Error fetching record: ' . $e->getMessage()]);
    exit;
}

// Generate document content based on action type
$action_type = ucfirst($record['action_type']);

switch ($record['action_type']) {
    case 'suspension':
        $document_body = "RE: SUSPENSION NOTICE\n\nDear Parent/Guardian,\n\nThis letter serves as formal notification that " . $record['student_name'] . " (Admission No: " . $record['admission_number'] . ") has been suspended from " . $record['school_name'] . " effective from " . date('F j, Y', strtotime($record['action_date'])) . ".\n\nReason for Suspension:\n" . $record['description'] . "\n\nThe suspension will remain in effect until " . ($record['end_date'] ? date('F j, Y', strtotime($record['end_date'])) : 'further notice') . ". During this period, the student is not allowed to attend school or participate in any school activities.\n\nWe request your cooperation in ensuring that the student understands the seriousness of this action and takes necessary steps to improve their behavior.\n\nIf you wish to discuss this matter further, please contact the school administration.\n\nSincerely,\n\nSchool Administration\n" . $record['school_name'];
        break;
        
    case 'expulsion':
        $document_body = "RE: EXPULSION NOTICE\n\nDear Parent/Guardian,\n\nThis letter serves as formal notification that " . $record['student_name'] . " (Admission No: " . $record['admission_number'] . ") has been permanently expelled from " . $record['school_name'] . " effective from " . date('F j, Y', strtotime($record['action_date'])) . ".\n\nReason for Expulsion:\n" . $record['description'] . "\n\nThis decision has been made after careful consideration of the student's conduct and the severity of the incident(s). The disciplinary committee has reviewed the case and determined that expulsion is the appropriate course of action.\n\nThe student is required to collect their personal belongings from the school within 7 days of receiving this notice. We will provide academic records to facilitate transfer to another institution.\n\nIf you wish to appeal this decision, you may do so within 14 days by submitting a written appeal to the school board.\n\nSincerely,\n\nSchool Administration\n" . $record['school_name'];
        break;
        
    case 'transfer':
        $document_body = "RE: TRANSFER NOTICE\n\nDear Parent/Guardian,\n\nThis letter serves as formal notification that " . $record['student_name'] . " (Admission No: " . $record['admission_number'] . ") is being transferred from " . $record['school_name'] . " effective from " . date('F j, Y', strtotime($record['action_date'])) . ".\n\nReason for Transfer:\n" . $record['description'] . "\n\nThis transfer has been initiated in the best interest of the student and the school community. We have prepared the necessary academic records and transfer documents to facilitate a smooth transition to the new institution.\n\nPlease collect the following documents from the school office:\n- Academic transcripts\n- Transfer certificate\n- Character certificate\n- Any other relevant records\n\nWe wish the student success in their future academic endeavors.\n\nSincerely,\n\nSchool Administration\n" . $record['school_name'];
        break;
        
    default:
        $document_body = "RE: DISCIPLINARY ACTION NOTICE\n\nDear Parent/Guardian,\n\nThis letter serves as formal notification that disciplinary action has been taken against " . $record['student_name'] . " (Admission No: " . $record['admission_number'] . ") at " . $record['school_name'] . ".\n\nAction Taken: " . $action_type . "\n\nDetails:\n" . $record['description'] . "\n\nPlease ensure that the student understands the seriousness of this action and takes necessary steps to improve their behavior.\n\nSincerely,\n\nSchool Administration\n" . $record['school_name'];
        break;
}

// Return JSON response
echo json_encode([
    'success' => true,
    'data' => [
        'action_type' => $action_type,
        'document_body' => $document_body,
        'record' => $record
    ]
]);
?>
