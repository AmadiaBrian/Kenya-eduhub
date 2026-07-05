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
