<?php
/**
 * Transaction Fee Calculation Helper
 * Calculates and records transaction fees based on configured rates
 */

require_once __DIR__ . '/../config.php';

/**
 * Calculate transaction fee based on configured rates
 * 
 * @param PDO $pdo Database connection
 * @param string $transaction_type Type of transaction (e.g., 'mpesa_stk_push', 'mpesa_b2c_withdrawal')
 * @param float $transaction_amount Amount of the transaction
 * @param int $school_id ID of the school (optional, for custom rates)
 * @return array Fee details including amount, rate, and calculation method
 */
function calculateTransactionFee(PDO $pdo, string $transaction_type, float $transaction_amount, int $school_id = null): array {
    try {
        // Get the rate configuration for this transaction type
        $stmt = $pdo->prepare("SELECT * FROM transaction_rates 
                              WHERE transaction_type = ? AND is_active = 1 
                              LIMIT 1");
        $stmt->execute([$transaction_type]);
        $rate = $stmt->fetch();
        
        if (!$rate) {
            // No rate configured, return zero fee
            return [
                'fee_amount' => 0,
                'rate_value' => 0,
                'rate_type' => 'none',
                'calculation' => 'No rate configured'
            ];
        }
        
        // Check for custom fee adjustments for this school
        $custom_adjustment = null;
        if ($school_id) {
            $stmt = $pdo->prepare("SELECT * FROM fee_adjustments 
                                  WHERE school_id = ? 
                                  AND transaction_type = ? 
                                  AND is_active = 1 
                                  AND valid_from <= CURDATE() 
                                  AND (valid_until IS NULL OR valid_until >= CURDATE())
                                  LIMIT 1");
            $stmt->execute([$school_id, $transaction_type]);
            $custom_adjustment = $stmt->fetch();
        }
        
        $fee_amount = 0;
        $rate_value = $rate['rate_value'];
        $rate_type = $rate['rate_type'];
        $calculation = '';
        
        // Calculate fee based on rate type
        if ($rate_type == 'percentage') {
            $fee_amount = ($transaction_amount * $rate_value) / 100;
            $calculation = "{$transaction_amount} × {$rate_value}% = {$fee_amount}";
            
            // Apply custom adjustment if exists
            if ($custom_adjustment) {
                if ($custom_adjustment['adjustment_type'] == 'waiver') {
                    $fee_amount = 0;
                    $calculation .= ' (Waived)';
                } elseif ($custom_adjustment['adjustment_type'] == 'discount') {
                    $discount = ($fee_amount * $custom_adjustment['adjustment_value']) / 100;
                    $fee_amount -= $discount;
                    $calculation .= " (Discount: {$custom_adjustment['adjustment_value']}%)";
                } elseif ($custom_adjustment['adjustment_type'] == 'surcharge') {
                    $surcharge = ($fee_amount * $custom_adjustment['adjustment_value']) / 100;
                    $fee_amount += $surcharge;
                    $calculation .= " (Surcharge: {$custom_adjustment['adjustment_value']}%)";
                }
            }
            
            // Apply min/max fee constraints
            if ($fee_amount < $rate['min_fee']) {
                $fee_amount = $rate['min_fee'];
                $calculation .= " (Min fee applied: {$rate['min_fee']})";
            }
            
            if ($rate['max_fee'] && $fee_amount > $rate['max_fee']) {
                $fee_amount = $rate['max_fee'];
                $calculation .= " (Max fee applied: {$rate['max_fee']})";
            }
            
        } else { // fixed rate
            $fee_amount = $rate_value;
            $calculation = "Fixed fee: {$rate_value}";
            
            // Apply custom adjustment if exists
            if ($custom_adjustment) {
                if ($custom_adjustment['adjustment_type'] == 'waiver') {
                    $fee_amount = 0;
                    $calculation .= ' (Waived)';
                } elseif ($custom_adjustment['adjustment_type'] == 'discount') {
                    $discount = ($fee_amount * $custom_adjustment['adjustment_value']) / 100;
                    $fee_amount -= $discount;
                    $calculation .= " (Discount: {$custom_adjustment['adjustment_value']}%)";
                } elseif ($custom_adjustment['adjustment_type'] == 'surcharge') {
                    $surcharge = ($fee_amount * $custom_adjustment['adjustment_value']) / 100;
                    $fee_amount += $surcharge;
                    $calculation .= " (Surcharge: {$custom_adjustment['adjustment_value']}%)";
                }
            }
        }
        
        return [
            'fee_amount' => round($fee_amount, 2),
            'rate_value' => $rate_value,
            'rate_type' => $rate_type,
            'calculation' => $calculation,
            'custom_adjustment' => $custom_adjustment
        ];
        
    } catch (PDOException $e) {
        error_log("Fee calculation error: " . $e->getMessage());
        return [
            'fee_amount' => 0,
            'rate_value' => 0,
            'rate_type' => 'error',
            'calculation' => 'Error calculating fee'
        ];
    }
}

/**
 * Record transaction fee in database
 * 
 * @param PDO $pdo Database connection
 * @param int $school_id ID of the school
 * @param string $transaction_type Type of transaction
 * @param string $transaction_id Unique transaction identifier
 * @param float $transaction_amount Amount of the transaction
 * @param float $fee_amount Fee amount charged
 * @param float $fee_rate Rate used for calculation
 * @param string $rate_type Type of rate (percentage/fixed)
 * @param float $balance_before Balance before transaction
 * @param float $balance_after Balance after transaction
 * @return bool Success status
 */
function recordTransactionFee(PDO $pdo, int $school_id, string $transaction_type, string $transaction_id, 
                              float $transaction_amount, float $fee_amount, float $fee_rate, 
                              string $rate_type, float $balance_before, float $balance_after): bool {
    try {
        $stmt = $pdo->prepare("INSERT INTO transaction_fees 
            (school_id, transaction_type, transaction_id, transaction_amount, fee_amount, 
             fee_rate, rate_type, balance_before, balance_after)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        $stmt->execute([
            $school_id,
            $transaction_type,
            $transaction_id,
            $transaction_amount,
            $fee_amount,
            $fee_rate,
            $rate_type,
            $balance_before,
            $balance_after
        ]);
        
        // Also record in system revenue
        recordSystemRevenue($pdo, 'transaction_fee', $fee_amount, $school_id, $transaction_id, 
                           "Fee from {$transaction_type}");
        
        return true;
        
    } catch (PDOException $e) {
        error_log("Failed to record transaction fee: " . $e->getMessage());
        return false;
    }
}

/**
 * Record system revenue
 * 
 * @param PDO $pdo Database connection
 * @param string $revenue_type Type of revenue
 * @param float $amount Amount
 * @param int|null $source_school_id Source school ID (optional)
 * @param string|null $transaction_id Transaction ID (optional)
 * @param string|null $description Description (optional)
 * @return bool Success status
 */
function recordSystemRevenue(PDO $pdo, string $revenue_type, float $amount, ?int $source_school_id = null, 
                             ?string $transaction_id = null, ?string $description = null): bool {
    try {
        $stmt = $pdo->prepare("INSERT INTO system_revenue 
            (revenue_type, amount, source_school_id, transaction_id, description)
            VALUES (?, ?, ?, ?, ?)");
        
        $stmt->execute([
            $revenue_type,
            $amount,
            $source_school_id,
            $transaction_id,
            $description
        ]);
        
        return true;
        
    } catch (PDOException $e) {
        error_log("Failed to record system revenue: " . $e->getMessage());
        return false;
    }
}

/**
 * Get total revenue for a period
 * 
 * @param PDO $pdo Database connection
 * @param string $start_date Start date (Y-m-d format)
 * @param string $end_date End date (Y-m-d format)
 * @param string|null $revenue_type Filter by revenue type (optional)
 * @return array Revenue data
 */
function getRevenueReport(PDO $pdo, string $start_date, string $end_date, ?string $revenue_type = null): array {
    try {
        $sql = "SELECT 
                    DATE(created_at) as date,
                    revenue_type,
                    SUM(amount) as total_amount,
                    COUNT(*) as transaction_count
                FROM system_revenue
                WHERE created_at BETWEEN ? AND ?";
        
        $params = [$start_date . ' 00:00:00', $end_date . ' 23:59:59'];
        
        if ($revenue_type) {
            $sql .= " AND revenue_type = ?";
            $params[] = $revenue_type;
        }
        
        $sql .= " GROUP BY DATE(created_at), revenue_type ORDER BY date DESC";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll();
        
    } catch (PDOException $e) {
        error_log("Failed to get revenue report: " . $e->getMessage());
        return [];
    }
}
