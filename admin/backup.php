<?php
/**
 * Database Backup Script
 * Exports all database tables to SQL file based on backup settings
 */

// Load dependencies
require_once '../config.php';
require_once '../includes/helpers.php';
require_once '../includes/security_lite.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!isset($user['role']) || $user['role'] !== 'admin') {
    header("Location: ../dashboard/index.php");
    exit();
}

// Get backup settings
$backup_settings = [];
try {
    $stmt = $conn->prepare("SELECT * FROM admin_backup_settings LIMIT 1");
    $stmt->execute();
    $result = $stmt->get_result();
    $backup_settings = $result->fetch_assoc();
} catch (Exception $e) {
    $backup_settings = [];
}

// Check if backup is enabled
if (!isset($backup_settings['backup_enabled']) || $backup_settings['backup_enabled'] != 1) {
    die("Backup is not enabled. Please enable it in settings.");
}

// Get backup path
$backup_path = $backup_settings['backup_path'] ?? '../backups';
$backup_path = rtrim($backup_path, '/');

// Create backup directory if it doesn't exist
if (!is_dir($backup_path)) {
    mkdir($backup_path, 0755, true);
}

// Generate backup filename with timestamp
$timestamp = date('Y-m-d_H-i-s');
$backup_file = $backup_path . '/backup_' . $timestamp . '.sql';

// Get database connection details
$db_host = $conn->host_info;
$db_name = $conn->query("SELECT DATABASE()")->fetch_row()[0];
$db_user = $conn->query("SELECT USER()")->fetch_row()[0];
$db_pass = ''; // Password not available from connection

// Extract username from USER() format
$db_user = explode('@', $db_user)[0];

// Try to get password from config if available
if (defined('DB_PASSWORD')) {
    $db_pass = DB_PASSWORD;
}

// Use mysqldump if available
$mysqldump_path = 'mysqldump';
$use_mysqldump = false;

// Check if mysqldump is available
if (function_exists('exec')) {
    @exec('mysqldump --version', $output, $return_code);
    if ($return_code === 0) {
        $use_mysqldump = true;
    }
}

if ($use_mysqldump) {
    // Use mysqldump for faster backup
    $command = sprintf(
        'mysqldump -h%s -u%s -p%s %s > %s 2>&1',
        escapeshellarg($conn->host_info),
        escapeshellarg($db_user),
        escapeshellarg($db_pass),
        escapeshellarg($db_name),
        escapeshellarg($backup_file)
    );
    
    exec($command, $output, $return_code);
    
    if ($return_code !== 0) {
        die("Backup failed using mysqldump: " . implode("\n", $output));
    }
} else {
    // Fallback to PHP-based backup
    $backup_content = '';
    
    // Get all tables
    $tables = [];
    $result = $conn->query("SHOW TABLES");
    while ($row = $result->fetch_row()) {
        $tables[] = $row[0];
    }
    
    // Add header
    $backup_content .= "-- Kenya EduHub Database Backup\n";
    $backup_content .= "-- Generated: " . date('Y-m-d H:i:s') . "\n";
    $backup_content .= "-- Database: " . $db_name . "\n\n";
    
    foreach ($tables as $table) {
        // Get table structure
        $result = $conn->query("SHOW CREATE TABLE `{$table}`");
        $row = $result->fetch_row();
        $backup_content .= "-- Table structure for `{$table}`\n";
        $backup_content .= "DROP TABLE IF EXISTS `{$table}`;\n";
        $backup_content .= $row[1] . ";\n\n";
        
        // Get table data
        $result = $conn->query("SELECT * FROM `{$table}`");
        $num_fields = $result->field_count;
        
        while ($row = $result->fetch_row()) {
            $backup_content .= "INSERT INTO `{$table}` VALUES (";
            for ($i = 0; $i < $num_fields; $i++) {
                $row[$i] = addslashes($row[$i]);
                $row[$i] = preg_replace("/\n/", "\\n", $row[$i]);
                if (isset($row[$i])) {
                    $backup_content .= '"' . $row[$i] . '"';
                } else {
                    $backup_content .= '""';
                }
                if ($i < $num_fields - 1) {
                    $backup_content .= ',';
                }
            }
            $backup_content .= ");\n";
        }
        
        $backup_content .= "\n\n";
    }
    
    // Write backup file
    if (file_put_contents($backup_file, $backup_content) === false) {
        die("Failed to write backup file.");
    }
}

// Compress backup file if possible
if (class_exists('ZipArchive')) {
    $zip_file = $backup_file . '.zip';
    $zip = new ZipArchive();
    if ($zip->open($zip_file, ZipArchive::CREATE) === TRUE) {
        $zip->addFile($backup_file, basename($backup_file));
        $zip->close();
        
        // Delete original SQL file after compression
        unlink($backup_file);
        $backup_file = $zip_file;
    }
}

// Clean up old backups based on retention settings
$retention_days = intval($backup_settings['backup_retention_days'] ?? 7);
$cutoff_time = time() - ($retention_days * 24 * 60 * 60);

$files = glob($backup_path . '/backup_*');
$deleted_count = 0;

foreach ($files as $file) {
    if (filemtime($file) < $cutoff_time) {
        unlink($file);
        $deleted_count++;
    }
}

// Log backup activity
logActivity('BACKUP', "Database backup created: " . basename($backup_file), [
    'file' => basename($backup_file),
    'size' => filesize($backup_file),
    'old_backups_deleted' => $deleted_count
]);

// Return success
if (isset($_GET['ajax'])) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'backup_file' => basename($backup_file),
        'size' => filesize($backup_file),
        'deleted_count' => $deleted_count
    ]);
} else {
    header("Location: settings.php?backup_success=1");
}
?>
