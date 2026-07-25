<?php
// Calendar Helper Functions
// Provides reusable functions for checking school calendar status across all pages

if (!function_exists('getSchoolCalendarStatus')) {
    /**
     * Get current school calendar status
     * @param PDO $pdo Database connection
     * @param int $school_id School ID
     * @return array Calendar status information
     */
    function getSchoolCalendarStatus($pdo, $school_id) {
        $status = [
            'current_year' => date('Y'),
            'current_term' => null,
            'is_holiday' => false,
            'current_holiday' => null,
            'school_status' => 'unknown'
        ];
        
        try {
            $today = date('Y-m-d');
            $current_year = date('Y');
            
            // Get active term for current year
            $stmt = $pdo->prepare("SELECT * FROM terms WHERE school_id = ? AND year = ? AND is_active = 1");
            $stmt->execute([$school_id, $current_year]);
            $status['current_term'] = $stmt->fetch();
            
            // Check if today is a holiday
            $stmt = $pdo->prepare("SELECT * FROM holidays WHERE school_id = ? AND start_date <= ? AND end_date >= ? AND is_active = 1");
            $stmt->execute([$school_id, $today, $today]);
            $status['current_holiday'] = $stmt->fetch();
            $status['is_holiday'] = (bool)$status['current_holiday'];
            
            // Determine school status - holidays override term status
            if ($status['is_holiday']) {
                $status['school_status'] = 'holiday';
            } elseif ($status['current_term']) {
                $status['school_status'] = 'in_session';
            } else {
                $status['school_status'] = 'break';
            }
        } catch (PDOException $e) {
            error_log("Failed to get school calendar status: " . $e->getMessage());
        }
        
        return $status;
    }
}

if (!function_exists('isSchoolInSession')) {
    /**
     * Check if school is currently in session (not on holiday)
     * @param PDO $pdo Database connection
     * @param int $school_id School ID
     * @return bool True if school is in session
     */
    function isSchoolInSession($pdo, $school_id) {
        $status = getSchoolCalendarStatus($pdo, $school_id);
        return $status['school_status'] === 'in_session';
    }
}

if (!function_exists('isSchoolOnHoliday')) {
    /**
     * Check if school is currently on holiday
     * @param PDO $pdo Database connection
     * @param int $school_id School ID
     * @return bool True if school is on holiday
     */
    function isSchoolOnHoliday($pdo, $school_id) {
        $status = getSchoolCalendarStatus($pdo, $school_id);
        return $status['is_holiday'];
    }
}

if (!function_exists('getCurrentTerm')) {
    /**
     * Get the current active term
     * @param PDO $pdo Database connection
     * @param int $school_id School ID
     * @return array|null Current term data or null
     */
    function getCurrentTerm($pdo, $school_id) {
        $status = getSchoolCalendarStatus($pdo, $school_id);
        return $status['current_term'];
    }
}

if (!function_exists('getCurrentHoliday')) {
    /**
     * Get the current holiday if any
     * @param PDO $pdo Database connection
     * @param int $school_id School ID
     * @return array|null Current holiday data or null
     */
    function getCurrentHoliday($pdo, $school_id) {
        $status = getSchoolCalendarStatus($pdo, $school_id);
        return $status['current_holiday'];
    }
}

if (!function_exists('checkDateInHoliday')) {
    /**
     * Check if a specific date falls within a holiday period
     * @param PDO $pdo Database connection
     * @param int $school_id School ID
     * @param string $date Date to check (Y-m-d format)
     * @return bool True if date is in holiday period
     */
    function checkDateInHoliday($pdo, $school_id, $date) {
        try {
            $stmt = $pdo->prepare("SELECT * FROM holidays WHERE school_id = ? AND start_date <= ? AND end_date >= ? AND is_active = 1");
            $stmt->execute([$school_id, $date, $date]);
            return (bool)$stmt->fetch();
        } catch (PDOException $e) {
            error_log("Failed to check holiday date: " . $e->getMessage());
            return false;
        }
    }
}

if (!function_exists('checkDateInTerm')) {
    /**
     * Check if a specific date falls within an active term period
     * @param PDO $pdo Database connection
     * @param int $school_id School ID
     * @param string $date Date to check (Y-m-d format)
     * @return bool True if date is in active term period
     */
    function checkDateInTerm($pdo, $school_id, $date) {
        try {
            $stmt = $pdo->prepare("SELECT * FROM terms WHERE school_id = ? AND is_active = 1 AND start_date <= ? AND end_date >= ?");
            $stmt->execute([$school_id, $date, $date]);
            return (bool)$stmt->fetch();
        } catch (PDOException $e) {
            error_log("Failed to check term date: " . $e->getMessage());
            return false;
        }
    }
}
