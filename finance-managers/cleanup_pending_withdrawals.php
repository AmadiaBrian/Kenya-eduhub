<?php
/**
 * Cleanup Pending Withdrawals Script
 * Deletes pending withdrawals that are older than 2 minutes
 * This helps clean up failed or stuck withdrawal requests
 */

require_once __DIR__ . '/../config.php';

// Delete pending withdrawals older than 2 minutes
$stmt = $pdo->prepare("DELETE FROM school_withdrawals 
                      WHERE status = 'pending' 
                      AND created_at < DATE_SUB(NOW(), INTERVAL 2 MINUTE)");
$stmt->execute();

$deleted_count = $stmt->rowCount();

echo "Deleted $deleted_count pending withdrawals older than 2 minutes.\n";

// Log the cleanup
error_log("Cleanup: Deleted $deleted_count pending withdrawals older than 2 minutes at " . date('Y-m-d H:i:s'));
