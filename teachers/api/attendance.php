<?php
// Teachers Attendance API
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once '../../config.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Get session token from header
$headers = getallheaders();
$session_token = $headers['Authorization'] ?? '';

error_log("Attendance API - Session token received: " . substr($session_token, 0, 20) . "...");

if (empty($session_token)) {
    http_response_code(401);
    echo json_encode(['error' => 'No session token provided']);
    exit;
}

try {
    // Verify session token
    error_log("Attendance API - Querying session for token");
    $stmt = $pdo->prepare("SELECT ts.*, t.*, s.school_name 
                          FROM teacher_sessions ts
                          JOIN teachers t ON ts.teacher_id = t.id
                          JOIN schools s ON t.school_id = s.id
                          WHERE ts.session_token = ? AND ts.expires_at > NOW()");
    $stmt->execute([$session_token]);
    $session = $stmt->fetch();

    error_log("Attendance API - Session found: " . ($session ? 'YES' : 'NO'));

    if (!$session) {
        error_log("Attendance API - Session verification failed for token: " . substr($session_token, 0, 20));
        http_response_code(401);
        echo json_encode(['error' => 'Invalid or expired session']);
        exit;
    }

    $teacher_id = $session['teacher_id'];
    $teacher_name = $session['first_name'] . ' ' . $session['last_name'];
    $school_id = $session['school_id'];
    $class_id = $session['class_id'] ?? null;
    $teacher_type = $session['teacher_type'];

    // Get class name
    $class_name = null;
    if ($class_id) {
        $stmt = $pdo->prepare("SELECT class_name FROM classes WHERE id = ?");
        $stmt->execute([$class_id]);
        $class = $stmt->fetch();
        $class_name = $class['class_name'] ?? null;
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
    exit;
}

// Get calendar status
$calendar_status = [
    'is_holiday' => false,
    'school_status' => 'in_session',
    'current_term' => null,
    'current_year' => date('Y')
];

try {
    $current_date = date('Y-m-d');
    $stmt = $pdo->prepare("SELECT * FROM holidays WHERE school_id = ? AND start_date <= ? AND end_date >= ?");
    $stmt->execute([$school_id, $current_date, $current_date]);
    $holiday = $stmt->fetch();

    if ($holiday) {
        $calendar_status['is_holiday'] = true;
        $calendar_status['current_holiday'] = [
            'holiday_name' => $holiday['holiday_name'],
            'start_date' => $holiday['start_date'],
            'end_date' => $holiday['end_date']
        ];
    } else {
        // Check for active term
        $stmt = $pdo->prepare("SELECT * FROM terms WHERE school_id = ? AND start_date <= ? AND end_date >= ?");
        $stmt->execute([$school_id, $current_date, $current_date]);
        $term = $stmt->fetch();

        if ($term) {
            $calendar_status['school_status'] = 'in_session';
            $calendar_status['current_term'] = [
                'term_name' => $term['term_name'],
                'start_date' => $term['start_date'],
                'end_date' => $term['end_date']
            ];
        } else {
            $calendar_status['school_status'] = 'break';
        }
    }
} catch (PDOException $e) {
    error_log("Failed to fetch calendar status: " . $e->getMessage());
}

// Get subject assignments for subject teachers
$subject_assignments = [];
if ($teacher_type === 'subject_teacher') {
    try {
        $stmt = $pdo->prepare("SELECT ts.*, c.class_name 
                             FROM teacher_subjects ts
                             JOIN classes c ON ts.class_id = c.id
                             WHERE ts.teacher_id = ?");
        $stmt->execute([$teacher_id]);
        $subject_assignments = $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Failed to fetch subject assignments: " . $e->getMessage());
    }
}

// Get available classes for the teacher
$classes = [];
try {
    if ($teacher_type === 'class_teacher' && $class_id) {
        // Class teacher sees only their assigned class
        $stmt = $pdo->prepare("SELECT id, class_name FROM classes WHERE id = ?");
        $stmt->execute([$class_id]);
        $classes = $stmt->fetchAll();
    } elseif ($teacher_type === 'subject_teacher' && !empty($subject_assignments)) {
        // Subject teacher sees classes they teach
        $class_ids = array_unique(array_column($subject_assignments, 'class_id'));
        $placeholders = str_repeat('?,', count($class_ids) - 1) . '?';
        $stmt = $pdo->prepare("SELECT id, class_name FROM classes WHERE id IN ($placeholders)");
        $stmt->execute($class_ids);
        $classes = $stmt->fetchAll();
    }
} catch (PDOException $e) {
    error_log("Failed to fetch classes: " . $e->getMessage());
}

// Get attendance statistics for the current month or custom date range
$attendance_stats = [];
$monthly_summary = null;
$student_attendance_details = [];
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-t');

// Log the date range for debugging
error_log("Attendance API - Date range: $start_date to $end_date");
error_log("Attendance API - Teacher ID: $teacher_id, Class ID: $class_id, Teacher Type: $teacher_type");
try {
    if ($teacher_type === 'class_teacher' && $class_id) {
        error_log("Attendance API - Class teacher query for class_id: $class_id");
        // Get stats for class teacher's class
        // Note: attendance table doesn't have class_id, so we need to join with students
        $stmt = $pdo->prepare("SELECT a.date, a.status, COUNT(*) as count
                              FROM attendance a
                              JOIN students s ON a.student_id = s.id
                              WHERE s.class_id = ? AND a.date BETWEEN ? AND ?
                              GROUP BY a.date, a.status
                              ORDER BY a.date DESC");
        $stmt->execute([$class_id, $start_date, $end_date]);
        $attendance_stats = $stmt->fetchAll();
        error_log("Attendance API - Stats count: " . count($attendance_stats));
        
        // Calculate overall statistics
        $stmt = $pdo->prepare("SELECT 
                              COUNT(CASE WHEN a.status = 'present' THEN 1 END) as total_present,
                              COUNT(CASE WHEN a.status = 'absent' THEN 1 END) as total_absent,
                              COUNT(CASE WHEN a.status = 'late' THEN 1 END) as total_late,
                              COUNT(CASE WHEN a.status = 'excused' THEN 1 END) as total_excused,
                              COUNT(*) as total_records,
                              COUNT(DISTINCT a.date) as days_recorded
                              FROM attendance a
                              JOIN students s ON a.student_id = s.id
                              WHERE s.class_id = ? AND a.date BETWEEN ? AND ?");
        $stmt->execute([$class_id, $start_date, $end_date]);
        $monthly_summary = $stmt->fetch();
        error_log("Attendance API - Monthly summary: " . json_encode($monthly_summary));
        
        // Get student attendance details
        error_log("Attendance API - Executing student attendance details query");
        $stmt = $pdo->prepare("SELECT s.id, s.admission_number, s.first_name, s.last_name,
                              GROUP_CONCAT(
                                CONCAT(a.date, ':', COALESCE(a.status, ''))
                                ORDER BY a.date
                                SEPARATOR '|'
                              ) as attendance_records
                              FROM students s
                              LEFT JOIN attendance a ON s.id = a.student_id AND a.date BETWEEN ? AND ?
                              WHERE s.class_id = ? AND s.status = 'active'
                              GROUP BY s.id
                              ORDER BY s.admission_number");
        $stmt->execute([$start_date, $end_date, $class_id]);
        $student_attendance_details = $stmt->fetchAll();
        error_log("Attendance API - Student details count: " . count($student_attendance_details));
        error_log("Attendance API - First student record: " . json_encode($student_attendance_details[0] ?? null));
    } elseif ($teacher_type === 'subject_teacher' && !empty($subject_assignments)) {
        // Get stats for all classes subject teacher teaches
        $class_ids = array_unique(array_column($subject_assignments, 'class_id'));
        $placeholders = str_repeat('?,', count($class_ids) - 1) . '?';
        
        $stmt = $pdo->prepare("SELECT a.date, a.status, COUNT(*) as count
                              FROM attendance a
                              JOIN students s ON a.student_id = s.id
                              WHERE s.class_id IN ($placeholders) AND a.date BETWEEN ? AND ?
                              GROUP BY a.date, a.status
                              ORDER BY a.date DESC");
        $params = array_merge($class_ids, [$start_date, $end_date]);
        $stmt->execute($params);
        $attendance_stats = $stmt->fetchAll();
        
        // Calculate overall statistics
        $stmt = $pdo->prepare("SELECT 
                              COUNT(CASE WHEN a.status = 'present' THEN 1 END) as total_present,
                              COUNT(CASE WHEN a.status = 'absent' THEN 1 END) as total_absent,
                              COUNT(CASE WHEN a.status = 'late' THEN 1 END) as total_late,
                              COUNT(CASE WHEN a.status = 'excused' THEN 1 END) as total_excused,
                              COUNT(*) as total_records,
                              COUNT(DISTINCT a.date) as days_recorded
                              FROM attendance a
                              JOIN students s ON a.student_id = s.id
                              WHERE s.class_id IN ($placeholders) AND a.date BETWEEN ? AND ?");
        $params = array_merge($class_ids, [$start_date, $end_date]);
        $stmt->execute($params);
        $monthly_summary = $stmt->fetch();
        
        // Get student attendance details
        $stmt = $pdo->prepare("SELECT s.id, s.admission_number, s.first_name, s.last_name, s.class_id, c.class_name,
                              GROUP_CONCAT(
                                CONCAT(a.date, ':', COALESCE(a.status, ''))
                                ORDER BY a.date
                                SEPARATOR '|'
                              ) as attendance_records
                              FROM students s
                              LEFT JOIN attendance a ON s.id = a.student_id AND a.date BETWEEN ? AND ?
                              JOIN classes c ON s.class_id = c.id
                              WHERE s.class_id IN ($placeholders) AND s.status = 'active'
                              GROUP BY s.id
                              ORDER BY c.class_name, s.admission_number");
        $params = array_merge([$start_date, $end_date], $class_ids);
        $stmt->execute($params);
        $student_attendance_details = $stmt->fetchAll();
    }
} catch (PDOException $e) {
    error_log("Failed to fetch attendance statistics: " . $e->getMessage());
}

// Handle GET requests
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? '';
    
    if ($action === 'get_students') {
        // Get students for a class
        $class_id = $_GET['class_id'] ?? '';
        $date = $_GET['date'] ?? date('Y-m-d');
        
        if (empty($class_id)) {
            http_response_code(400);
            echo json_encode(['error' => 'Class ID is required']);
            exit;
        }
        
        // Verify teacher has access to this class
        $has_access = false;
        if ($teacher_type === 'class_teacher' && $class_id == $session['class_id']) {
            $has_access = true;
        } elseif ($teacher_type === 'subject_teacher') {
            $class_ids = array_column($subject_assignments, 'class_id');
            if (in_array($class_id, $class_ids)) {
                $has_access = true;
            }
        }
        
        if (!$has_access) {
            http_response_code(403);
            echo json_encode(['error' => 'You do not have access to this class']);
            exit;
        }
        
        try {
            // Get students
            $stmt = $pdo->prepare("SELECT s.id, s.admission_number, s.first_name, s.last_name, s.stream_id, st.stream_name
                                  FROM students s
                                  LEFT JOIN streams st ON s.stream_id = st.id
                                  WHERE s.class_id = ? AND s.status = 'active'
                                  ORDER BY s.admission_number");
            $stmt->execute([$class_id]);
            $students = $stmt->fetchAll();
            
            // Get existing attendance for this date
            $attendance_records = [];
            $stmt = $pdo->prepare("SELECT student_id, status, remarks 
                                  FROM attendance 
                                  WHERE student_id IN (SELECT id FROM students WHERE class_id = ?) AND date = ?");
            $stmt->execute([$class_id, $date]);
            $records = $stmt->fetchAll();
            
            foreach ($records as $record) {
                $attendance_records[$record['student_id']] = $record;
            }
            
            // Combine student data with attendance
            $students_with_attendance = array_map(function($student) use ($attendance_records) {
                $student_id = $student['id'];
                if (isset($attendance_records[$student_id])) {
                    $student['status'] = $attendance_records[$student_id]['status'];
                    $student['remarks'] = $attendance_records[$student_id]['remarks'];
                } else {
                    $student['status'] = null;
                    $student['remarks'] = null;
                }
                return $student;
            }, $students);
            
            echo json_encode([
                'success' => true,
                'students' => $students_with_attendance,
                'date' => $date
            ]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to fetch students']);
            exit;
        }
    } elseif ($action === 'get_attendance_history') {
        // Get attendance history for a class
        $class_id = $_GET['class_id'] ?? '';
        $start_date = $_GET['start_date'] ?? date('Y-m-01');
        $end_date = $_GET['end_date'] ?? date('Y-m-t');
        
        if (empty($class_id)) {
            http_response_code(400);
            echo json_encode(['error' => 'Class ID is required']);
            exit;
        }
        
        // Verify teacher has access to this class
        $has_access = false;
        if ($teacher_type === 'class_teacher' && $class_id == $session['class_id']) {
            $has_access = true;
        } elseif ($teacher_type === 'subject_teacher') {
            $class_ids = array_column($subject_assignments, 'class_id');
            if (in_array($class_id, $class_ids)) {
                $has_access = true;
            }
        }
        
        if (!$has_access) {
            http_response_code(403);
            echo json_encode(['error' => 'You do not have access to this class']);
            exit;
        }
        
        try {
            $stmt = $pdo->prepare("SELECT a.attendance_date, a.status, COUNT(*) as count
                                  FROM attendance a
                                  WHERE a.class_id = ? AND a.attendance_date BETWEEN ? AND ?
                                  GROUP BY a.attendance_date, a.status
                                  ORDER BY a.attendance_date DESC");
            $stmt->execute([$class_id, $start_date, $end_date]);
            $history = $stmt->fetchAll();
            
            echo json_encode([
                'success' => true,
                'history' => $history
            ]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to fetch attendance history']);
            exit;
        }
    } else {
        // Default: return teacher info and available classes
        echo json_encode([
            'success' => true,
            'teacher' => [
                'id' => $teacher_id,
                'name' => $teacher_name,
                'school_name' => $session['school_name'],
                'teacher_type' => $teacher_type,
                'class_name' => $class_name,
                'class_id' => $class_id
            ],
            'calendar_status' => [
                'is_holiday' => $calendar_status['is_holiday'],
                'school_status' => $calendar_status['school_status'],
                'current_holiday' => $calendar_status['current_holiday'] ?? null,
                'current_term' => $calendar_status['current_term'] ?? null,
                'current_year' => $calendar_status['current_year'] ?? null,
            ],
            'classes' => $classes,
            'subject_assignments' => $subject_assignments,
            'attendance_stats' => $attendance_stats,
            'monthly_summary' => $monthly_summary,
            'student_attendance_details' => $student_attendance_details
        ]);
    }
}

// Handle POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'save_attendance') {
        $class_id = $_POST['class_id'] ?? '';
        $date = $_POST['date'] ?? date('Y-m-d');
        $attendance = $_POST['attendance'] ?? [];
        
        if (empty($class_id) || empty($attendance)) {
            http_response_code(400);
            echo json_encode(['error' => 'Class ID and attendance data are required']);
            exit;
        }
        
        // Verify teacher has access to this class
        $has_access = false;
        if ($teacher_type === 'class_teacher' && $class_id == $session['class_id']) {
            $has_access = true;
        } elseif ($teacher_type === 'subject_teacher') {
            $class_ids = array_column($subject_assignments, 'class_id');
            if (in_array($class_id, $class_ids)) {
                $has_access = true;
            }
        }
        
        if (!$has_access) {
            http_response_code(403);
            echo json_encode(['error' => 'You do not have access to this class']);
            exit;
        }
        
        try {
            $pdo->beginTransaction();
            
            // Delete existing attendance for students in this class for this date
            $stmt = $pdo->prepare("DELETE FROM attendance 
                                  WHERE student_id IN (SELECT id FROM students WHERE class_id = ?) AND date = ?");
            $stmt->execute([$class_id, $date]);
            
            // Insert new attendance records
            $stmt = $pdo->prepare("INSERT INTO attendance (student_id, date, status, remarks, created_at) 
                                  VALUES (?, ?, ?, ?, NOW())");
            
            foreach ($attendance as $record) {
                $student_id = $record['student_id'];
                $status = $record['status'];
                $remarks = $record['remarks'] ?? null;
                
                if (!empty($status)) {
                    $stmt->execute([$student_id, $date, $status, $remarks]);
                }
            }
            
            $pdo->commit();
            
            echo json_encode([
                'success' => true,
                'message' => 'Attendance saved successfully'
            ]);
        } catch (PDOException $e) {
            $pdo->rollBack();
            http_response_code(500);
            echo json_encode(['error' => 'Failed to save attendance']);
            exit;
        }
    } elseif ($action === 'auto_mark_absent') {
        // Auto-mark absent for unmarked days in the past
        $class_id = $_POST['class_id'] ?? '';
        
        if (empty($class_id)) {
            http_response_code(400);
            echo json_encode(['error' => 'Class ID is required']);
            exit;
        }
        
        // Verify teacher has access to this class
        $has_access = false;
        if ($teacher_type === 'class_teacher' && $class_id == $session['class_id']) {
            $has_access = true;
        } elseif ($teacher_type === 'subject_teacher') {
            $class_ids = array_column($subject_assignments, 'class_id');
            if (in_array($class_id, $class_ids)) {
                $has_access = true;
            }
        }
        
        if (!$has_access) {
            http_response_code(403);
            echo json_encode(['error' => 'You do not have access to this class']);
            exit;
        }
        
        try {
            $pdo->beginTransaction();
            
            // Get current term dates
            $current_date = date('Y-m-d');
            $stmt = $pdo->prepare("SELECT start_date, end_date FROM terms WHERE school_id = ? AND start_date <= ? AND end_date >= ?");
            $stmt->execute([$school_id, $current_date, $current_date]);
            $term = $stmt->fetch();
            
            if (!$term) {
                echo json_encode(['success' => false, 'message' => 'No active term found']);
                exit;
            }
            
            $term_start = $term['start_date'];
            $term_end = $term['end_date'];
            
            // Get all active students in the class
            $stmt = $pdo->prepare("SELECT id FROM students WHERE class_id = ? AND status = 'active'");
            $stmt->execute([$class_id]);
            $students = $stmt->fetchAll();
            
            if (empty($students)) {
                echo json_encode(['success' => false, 'message' => 'No active students in this class']);
                exit;
            }
            
            $student_ids = array_column($students, 'id');
            $student_ids_string = implode(',', array_map('intval', $student_ids));
            
            // Get all dates in the term up to yesterday that don't have attendance records
            $stmt = $pdo->prepare("
                SELECT DISTINCT s.date
                FROM (
                    SELECT DATE_ADD(?, INTERVAL seq DAY) as date
                    FROM (
                        SELECT 0 as seq UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4
                        UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9
                        UNION SELECT 10 UNION SELECT 11 UNION SELECT 12 UNION SELECT 13 UNION SELECT 14
                        UNION SELECT 15 UNION SELECT 16 UNION SELECT 17 UNION SELECT 18 UNION SELECT 19
                        UNION SELECT 20 UNION SELECT 21 UNION SELECT 22 UNION SELECT 23 UNION SELECT 24
                        UNION SELECT 25 UNION SELECT 26 UNION SELECT 27 UNION SELECT 28 UNION SELECT 29
                        UNION SELECT 30 UNION SELECT 31 UNION SELECT 32 UNION SELECT 33 UNION SELECT 34
                        UNION SELECT 35 UNION SELECT 36 UNION SELECT 37 UNION SELECT 38 UNION SELECT 39
                        UNION SELECT 40 UNION SELECT 41 UNION SELECT 42 UNION SELECT 43 UNION SELECT 44
                        UNION SELECT 45 UNION SELECT 46 UNION SELECT 47 UNION SELECT 48 UNION SELECT 49
                        UNION SELECT 50 UNION SELECT 51 UNION SELECT 52 UNION SELECT 53 UNION SELECT 54
                        UNION SELECT 55 UNION SELECT 56 UNION SELECT 57 UNION SELECT 58 UNION SELECT 59
                        UNION SELECT 60 UNION SELECT 61 UNION SELECT 62 UNION SELECT 63 UNION SELECT 64
                        UNION SELECT 65 UNION SELECT 66 UNION SELECT 67 UNION SELECT 68 UNION SELECT 69
                        UNION SELECT 70 UNION SELECT 71 UNION SELECT 72 UNION SELECT 73 UNION SELECT 74
                        UNION SELECT 75 UNION SELECT 76 UNION SELECT 77 UNION SELECT 78 UNION SELECT 79
                        UNION SELECT 80 UNION SELECT 81 UNION SELECT 82 UNION SELECT 83 UNION SELECT 84
                        UNION SELECT 85 UNION SELECT 86 UNION SELECT 87 UNION SELECT 88 UNION SELECT 89
                        UNION SELECT 90 UNION SELECT 91 UNION SELECT 92 UNION SELECT 93 UNION SELECT 94
                        UNION SELECT 95 UNION SELECT 96 UNION SELECT 97 UNION SELECT 98 UNION SELECT 99
                        UNION SELECT 100 UNION SELECT 101 UNION SELECT 102 UNION SELECT 103 UNION SELECT 104
                        UNION SELECT 105 UNION SELECT 106 UNION SELECT 107 UNION SELECT 108 UNION SELECT 109
                        UNION SELECT 110 UNION SELECT 111 UNION SELECT 112 UNION SELECT 113 UNION SELECT 114
                        UNION SELECT 115 UNION SELECT 116 UNION SELECT 117 UNION SELECT 118 UNION SELECT 119
                        UNION SELECT 120 UNION SELECT 121 UNION SELECT 122 UNION SELECT 123 UNION SELECT 124
                        UNION SELECT 125 UNION SELECT 126 UNION SELECT 127 UNION SELECT 128 UNION SELECT 129
                        UNION SELECT 130 UNION SELECT 131 UNION SELECT 132 UNION SELECT 133 UNION SELECT 134
                        UNION SELECT 135 UNION SELECT 136 UNION SELECT 137 UNION SELECT 138 UNION SELECT 139
                        UNION SELECT 140 UNION SELECT 141 UNION SELECT 142 UNION SELECT 143 UNION SELECT 144
                        UNION SELECT 145 UNION SELECT 146 UNION SELECT 147 UNION SELECT 148 UNION SELECT 149
                        UNION SELECT 150 UNION SELECT 151 UNION SELECT 152 UNION SELECT 153 UNION SELECT 154
                        UNION SELECT 155 UNION SELECT 156 UNION SELECT 157 UNION SELECT 158 UNION SELECT 159
                        UNION SELECT 160 UNION SELECT 161 UNION SELECT 162 UNION SELECT 163 UNION SELECT 164
                        UNION SELECT 165 UNION SELECT 166 UNION SELECT 167 UNION SELECT 168 UNION SELECT 169
                        UNION SELECT 170 UNION SELECT 171 UNION SELECT 172 UNION SELECT 173 UNION SELECT 174
                        UNION SELECT 175 UNION SELECT 176 UNION SELECT 177 UNION SELECT 178 UNION SELECT 179
                        UNION SELECT 180 UNION SELECT 181 UNION SELECT 182 UNION SELECT 183 UNION SELECT 184
                        UNION SELECT 185 UNION SELECT 186 UNION SELECT 187 UNION SELECT 188 UNION SELECT 189
                        UNION SELECT 190 UNION SELECT 191 UNION SELECT 192 UNION SELECT 193 UNION SELECT 194
                        UNION SELECT 195 UNION SELECT 196 UNION SELECT 197 UNION SELECT 198 UNION SELECT 199
                        UNION SELECT 200
                    ) seq
                    WHERE DATE_ADD(?, INTERVAL seq DAY) <= ?
                ) s
                WHERE s.date NOT IN (
                    SELECT DISTINCT date FROM attendance WHERE student_id IN ($student_ids_string)
                )
                AND s.date >= ?
                AND s.date < ?
                AND DAYOFWEEK(s.date) NOT IN (1, 7) -- Exclude weekends
                ORDER BY s.date
            ");
            $stmt->execute([$term_start, $term_start, $term_end, $term_start, $current_date]);
            $unmarked_dates = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            $marked_count = 0;
            $skipped_count = 0;
            
            foreach ($unmarked_dates as $date) {
                // Check if date is a holiday
                $stmt = $pdo->prepare("SELECT * FROM holidays WHERE school_id = ? AND start_date <= ? AND end_date >= ?");
                $stmt->execute([$school_id, $date, $date]);
                $holiday = $stmt->fetch();
                
                if ($holiday) {
                    $skipped_count++;
                    continue;
                }
                
                // Mark all students as absent for this date
                $insert_stmt = $pdo->prepare("INSERT INTO attendance (student_id, date, status, remarks, created_at) VALUES (?, ?, 'absent', 'Auto-marked absent', NOW())");
                foreach ($student_ids as $student_id) {
                    $insert_stmt->execute([$student_id, $date]);
                    $marked_count++;
                }
            }
            
            $pdo->commit();
            
            echo json_encode([
                'success' => true,
                'message' => "Auto-marked $marked_count attendance records as absent. Skipped $skipped_count dates (holidays/weekends)."
            ]);
        } catch (PDOException $e) {
            $pdo->rollBack();
            error_log("Auto-mark absent error: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['error' => 'Failed to auto-mark absent: ' . $e->getMessage()]);
        }
    } else {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action']);
        exit;
    }
}
