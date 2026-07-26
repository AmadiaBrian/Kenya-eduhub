<?php
// School Settings API
require_once __DIR__ . '/auth.php';

header('Content-Type: application/json');

$school_id = get_current_school_id();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_school_auth();
    
    $type = $_GET['type'] ?? '';
    
    if ($type === 'logo') {
        // Handle logo upload
        if (!isset($_FILES['logo']) || $_FILES['logo']['error'] !== UPLOAD_ERR_OK) {
            error_response('No file uploaded or upload error');
        }

        $file = $_FILES['logo'];

        // Validate file type
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/jpg'];
        if (!in_array($file['type'], $allowed_types)) {
            error_response('Invalid file type. Only JPG, PNG, and GIF are allowed');
        }

        // Validate file size (2MB max)
        if ($file['size'] > 2 * 1024 * 1024) {
            error_response('File size exceeds 2MB limit');
        }

        // Create uploads directory if it doesn't exist
        $upload_dir = __DIR__ . '/../../uploads/schools/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        // Generate unique filename
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = 'school_' . $school_id . '_' . time() . '.' . $extension;
        $filepath = $upload_dir . $filename;

        // Move uploaded file
        if (!move_uploaded_file($file['tmp_name'], $filepath)) {
            error_response('Failed to save uploaded file');
        }

        // Update database with logo path (relative to project root)
        $logo_path = '../uploads/schools/' . $filename;

        try {
            $stmt = $pdo->prepare("UPDATE schools SET logo = ? WHERE id = ?");
            $stmt->execute([$logo_path, $school_id]);

            log_security_event('SCHOOL_LOGO_UPLOADED', "School logo uploaded (School ID: $school_id)");

            success_response(['logo_path' => $logo_path], 'Logo uploaded successfully');

        } catch (PDOException $e) {
            error_log("Failed to update school logo: " . $e->getMessage());
            error_response('Failed to save logo', 500);
        }
    } elseif ($type === 'subject_limits') {
        // Handle subject limits update
        $input = json_decode(file_get_contents('php://input'), true);

        if (!$input) {
            error_response('Invalid input data');
        }

        $min_subjects = intval($input['min_subjects'] ?? 0);
        $max_subjects = intval($input['max_subjects'] ?? 0);

        if ($min_subjects < 1 || $max_subjects < 1) {
            error_response('Subject limits must be at least 1');
        }

        if ($min_subjects > $max_subjects) {
            error_response('Minimum subjects cannot be greater than maximum subjects');
        }

        try {
            $stmt = $pdo->prepare("UPDATE schools SET min_subjects = ?, max_subjects = ? WHERE id = ?");
            $stmt->execute([$min_subjects, $max_subjects, $school_id]);

            log_security_event('SCHOOL_SUBJECT_LIMITS_UPDATED', "Subject limits updated (School ID: $school_id)");

            success_response([], 'Subject limits updated successfully');

        } catch (PDOException $e) {
            error_log("Failed to update subject limits: " . $e->getMessage());
            error_response('Failed to update subject limits', 500);
        }
    } elseif ($type === 'sms_settings') {
        // Handle SMS settings update
        $input = json_decode(file_get_contents('php://input'), true);

        if (!$input) {
            error_response('Invalid input data');
        }

        $sms_provider = sanitize_input($input['sms_provider'] ?? 'mobitech');
        $mobitech_api_key = sanitize_input($input['mobitech_api_key'] ?? '');
        $mobitech_sender_id = sanitize_input($input['mobitech_sender_id'] ?? '');
        $textsms_api_key = sanitize_input($input['textsms_api_key'] ?? '');
        $textsms_partner_id = sanitize_input($input['textsms_partner_id'] ?? '');
        $textsms_sender_id = sanitize_input($input['textsms_sender_id'] ?? '');
        $sms_enabled = intval($input['sms_enabled'] ?? 0);

        // Validate provider
        if (!in_array($sms_provider, ['mobitech', 'textsms'])) {
            error_response('Invalid SMS provider');
        }

        // Validate sender ID lengths
        if (!empty($mobitech_sender_id) && strlen($mobitech_sender_id) > 11) {
            error_response('Mobitech sender ID must be 11 characters or less');
        }
        if (!empty($textsms_sender_id) && strlen($textsms_sender_id) > 11) {
            error_response('Text SMS sender ID must be 11 characters or less');
        }

        try {
            $stmt = $pdo->prepare("UPDATE schools SET sms_provider = ?, mobitech_api_key = ?, mobitech_sender_id = ?, textsms_api_key = ?, textsms_partner_id = ?, textsms_sender_id = ?, sms_enabled = ? WHERE id = ?");
            $stmt->execute([$sms_provider, $mobitech_api_key, $mobitech_sender_id, $textsms_api_key, $textsms_partner_id, $textsms_sender_id, $sms_enabled, $school_id]);

            log_security_event('SCHOOL_SMS_SETTINGS_UPDATED', "SMS settings updated (School ID: $school_id)");

            success_response([], 'SMS settings updated successfully');

        } catch (PDOException $e) {
            error_log("Failed to update SMS settings: " . $e->getMessage());
            error_response('Failed to update SMS settings', 500);
        }
    } else {
        error_response('Invalid request type', 400);
    }
    
} elseif ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    require_school_auth();
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        error_response('Invalid input data');
    }
    
    $update_fields = [];
    $params = [];
    
    if (isset($input['admission_prefix'])) {
        $update_fields[] = "admission_prefix = ?";
        $params[] = sanitize_input($input['admission_prefix']);
    }
    
    if (empty($update_fields)) {
        error_response('No fields to update');
    }
    
    $params[] = $school_id;
    
    try {
        $query = "UPDATE schools SET " . implode(', ', $update_fields) . " WHERE id = ?";
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        
        log_security_event('SCHOOL_SETTINGS_UPDATED', "School settings updated (School ID: $school_id)");
        
        success_response([], 'Settings updated successfully');
        
    } catch (PDOException $e) {
        error_log("Failed to update school settings: " . $e->getMessage());
        error_response('Failed to update settings', 500);
    }
    
} else {
    error_response('Invalid request method', 405);
}
?>
