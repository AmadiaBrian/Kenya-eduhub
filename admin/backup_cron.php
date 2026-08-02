<?php
/**
 * Backup Cron Job Script
 * This script should be called by a cron job based on backup frequency settings
 * 
 * Cron job examples:
 * Hourly: 0 * * * * php /path/to/admin/backup_cron.php
 * Daily: 0 0 * * * php /path/to/admin/backup_cron.php
 * Weekly: 0 0 * * 0 php /path/to/admin/backup_cron.php
 * Monthly: 0 0 1 * * php /path/to/admin/backup_cron.php
 */

// Load dependencies
require_once '../config.php';
require_once '../includes/helpers.php';
require_once '../includes/security_lite.php';

// Get backup settings
$backup_settings = [];
try {
    $stmt = $conn->prepare("SELECT * FROM admin_backup_settings LIMIT 1");
    $stmt->execute();
    $result = $stmt->get_result();
    $backup_settings = $result->fetch_assoc();
} catch (Exception $e) {
    // Settings table might not exist
    die("Backup settings not configured.");
}

// Check if backup is enabled and auto_backup is on
if (!isset($backup_settings['backup_enabled']) || $backup_settings['backup_enabled'] != 1) {
    exit("Backup is not enabled.");
}

if (!isset($backup_settings['auto_backup']) || $backup_settings['auto_backup'] != 1) {
    exit("Auto backup is not enabled.");
}

// Check if this script should run based on frequency
$last_backup_file = null;
$backup_path = $backup_settings['backup_path'] ?? '../backups';
$backup_path = rtrim($backup_path, '/');

if (is_dir($backup_path)) {
    $files = glob($backup_path . '/backup_*');
    if (!empty($files)) {
        // Get the most recent backup
        usort($files, function($a, $b) {
            return filemtime($b) - filemtime($a);
        });
        $last_backup_file = $files[0];
    }
}

$should_run = true;
$frequency = $backup_settings['backup_frequency'] ?? 'daily';

if ($last_backup_file) {
    $last_backup_time = filemtime($last_backup_file);
    $current_time = time();
    $time_diff = $current_time - $last_backup_time;
    
    switch ($frequency) {
        case 'hourly':
            // Run if last backup was more than 1 hour ago
            if ($time_diff < 3600) {
                $should_run = false;
            }
            break;
        case 'daily':
            // Run if last backup was more than 24 hours ago
            if ($time_diff < 86400) {
                $should_run = false;
            }
            break;
        case 'weekly':
            // Run if last backup was more than 7 days ago
            if ($time_diff < 604800) {
                $should_run = false;
            }
            break;
        case 'monthly':
            // Run if last backup was more than 30 days ago
            if ($time_diff < 2592000) {
                $should_run = false;
            }
            break;
    }
}

if (!$should_run) {
    exit("Backup not needed yet based on frequency setting.");
}

// Execute backup by including the backup script
$_GET['ajax'] = '1'; // Set to get JSON response
$_SERVER['REQUEST_METHOD'] = 'POST'; // Simulate POST request

// Buffer output to prevent any unwanted output
ob_start();

try {
    include 'backup.php';
    $output = ob_get_clean();
    
    // Log successful cron backup
    logActivity('BACKUP_CRON', 'Scheduled backup completed successfully', [
        'frequency' => $frequency,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
    exit("Backup completed successfully.");
} catch (Exception $e) {
    ob_end_clean();
    
    // Log failed cron backup
    logFailedActivity('BACKUP_CRON', 'Scheduled backup failed', $e->getMessage(), [
        'frequency' => $frequency,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
    exit("Backup failed: " . $e->getMessage());
}
?>
