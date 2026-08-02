<?php
/**
 * Save Transaction Rate API
 * Creates or updates a transaction rate, or bulk uploads via CSV
 */

require_once '../config.php';

header('Content-Type: application/json');

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Check if this is a CSV bulk upload
if (isset($_FILES['csv_file']) && $_FILES['csv_file']['error'] === UPLOAD_ERR_OK) {
    $file = $_FILES['csv_file'];
    
    // Validate file type
    $allowed_types = ['text/csv', 'application/csv', 'text/plain'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mime, $allowed_types) && strtolower(pathinfo($file['name'], PATHINFO_EXTENSION)) !== 'csv') {
        echo json_encode(['success' => false, 'message' => 'Invalid file type. Please upload a CSV file.']);
        exit;
    }
    
    // Read and parse CSV
    $csvData = [];
    if (($handle = fopen($file['tmp_name'], 'r')) !== false) {
        $headers = fgetcsv($handle);
        $rowNumber = 1;
        
        while (($row = fgetcsv($handle)) !== false) {
            $rowNumber++;
            if (count($row) != count($headers)) {
                echo json_encode(['success' => false, 'message' => "Invalid CSV format at row $rowNumber. Column count mismatch."]);
                fclose($handle);
                exit;
            }
            
            $csvData[] = array_combine($headers, $row);
        }
        fclose($handle);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to read CSV file.']);
        exit;
    }
    
    if (empty($csvData)) {
        echo json_encode(['success' => false, 'message' => 'CSV file is empty or has no data rows.']);
        exit;
    }
    
    try {
        $pdo->beginTransaction();
        $successCount = 0;
        $errorCount = 0;
        $errors = [];
        
        foreach ($csvData as $index => $row) {
            $rowNumber = $index + 2; // +2 because header is row 1
            
            // Validate required fields
            if (empty($row['transaction_type']) || empty($row['rate_type']) || !isset($row['rate_value'])) {
                $errors[] = "Row $rowNumber: Missing required fields (transaction_type, rate_type, rate_value)";
                $errorCount++;
                continue;
            }
            
            // Validate rate_type
            if (!in_array($row['rate_type'], ['percentage', 'fixed'])) {
                $errors[] = "Row $rowNumber: Invalid rate_type. Must be 'percentage' or 'fixed'.";
                $errorCount++;
                continue;
            }
            
            // Check if transaction_type already exists
            $stmt = $pdo->prepare("SELECT id FROM transaction_rates WHERE transaction_type = ?");
            $stmt->execute([$row['transaction_type']]);
            $existing = $stmt->fetch();
            
            if ($existing) {
                // Update existing rate
                $stmt = $pdo->prepare("UPDATE transaction_rates SET 
                    rate_type = ?,
                    rate_value = ?,
                    min_fee = ?,
                    max_fee = ?,
                    description = ?
                    WHERE id = ?");
                
                $stmt->execute([
                    $row['rate_type'],
                    $row['rate_value'],
                    $row['min_fee'] ?? 0,
                    !empty($row['max_fee']) ? $row['max_fee'] : null,
                    $row['description'] ?? '',
                    $existing['id']
                ]);
            } else {
                // Insert new rate
                $stmt = $pdo->prepare("INSERT INTO transaction_rates 
                    (transaction_type, rate_type, rate_value, min_fee, max_fee, description)
                    VALUES (?, ?, ?, ?, ?, ?)");
                
                $stmt->execute([
                    $row['transaction_type'],
                    $row['rate_type'],
                    $row['rate_value'],
                    $row['min_fee'] ?? 0,
                    !empty($row['max_fee']) ? $row['max_fee'] : null,
                    $row['description'] ?? ''
                ]);
            }
            
            $successCount++;
        }
        
        $pdo->commit();
        
        $message = "Successfully processed $successCount rates.";
        if ($errorCount > 0) {
            $message .= " $errorCount rows had errors: " . implode('; ', array_slice($errors, 0, 3));
            if (count($errors) > 3) {
                $message .= "...";
            }
        }
        
        echo json_encode(['success' => true, 'message' => $message, 'success_count' => $successCount, 'error_count' => $errorCount]);
        
    } catch (PDOException $e) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
    
    exit;
}

// Handle single rate creation/update (JSON)
$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    echo json_encode(['success' => false, 'message' => 'Invalid input data']);
    exit;
}

try {
    $pdo->beginTransaction();
    
    if (!empty($data['rate_id'])) {
        // Update existing rate
        $stmt = $pdo->prepare("UPDATE transaction_rates SET 
            transaction_type = ?,
            rate_type = ?,
            rate_value = ?,
            min_fee = ?,
            max_fee = ?,
            description = ?
            WHERE id = ?");
        
        $stmt->execute([
            $data['transaction_type'],
            $data['rate_type'],
            $data['rate_value'],
            $data['min_fee'],
            $data['max_fee'] ?: null,
            $data['description'],
            $data['rate_id']
        ]);
        
        $message = 'Rate updated successfully';
    } else {
        // Create new rate
        $stmt = $pdo->prepare("INSERT INTO transaction_rates 
            (transaction_type, rate_type, rate_value, min_fee, max_fee, description)
            VALUES (?, ?, ?, ?, ?, ?)");
        
        $stmt->execute([
            $data['transaction_type'],
            $data['rate_type'],
            $data['rate_value'],
            $data['min_fee'],
            $data['max_fee'] ?: null,
            $data['description']
        ]);
        
        $message = 'Rate created successfully';
    }
    
    $pdo->commit();
    echo json_encode(['success' => true, 'message' => $message]);
    
} catch (PDOException $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
