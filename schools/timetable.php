<?php
// Timetable Management Page
// Ensure session is started and config is loaded
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Load config if not already loaded
if (!isset($pdo)) {
    require_once __DIR__ . '/../config.php';
}

// Load calendar helpers
require_once __DIR__ . '/../includes/calendar_helpers.php';

// Authentication check
if (!isset($_SESSION['school_id']) || !isset($_SESSION['school_token'])) {
    header('Location: login');
    exit;
}

$school_id = $_SESSION['school_id'];
$school_name = $_SESSION['school_name'] ?? 'School';

// Get calendar status
$calendar_status = getSchoolCalendarStatus($pdo, $school_id);

// Handle AJAX teacher conflict check
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['check_teacher_conflict'])) {
    header('Content-Type: application/json');
    $teacher_id = (int)($_POST['teacher_id'] ?? 0);
    $slot_id = (int)($_POST['slot_id'] ?? 0);
    
    $response = ['conflict' => false, 'message' => ''];
    
    if ($teacher_id > 0 && $slot_id > 0) {
        try {
            // Get time slot details
            $slotStmt = $pdo->prepare("SELECT * FROM timetable_slots WHERE id = ? AND school_id = ?");
            $slotStmt->execute([$slot_id, $school_id]);
            $slot = $slotStmt->fetch();
            
            if ($slot) {
                // Check if teacher is already assigned at this time slot
                $teacherConflictStmt = $pdo->prepare("
                    SELECT ta.*, ts.day_of_week, ts.start_time, ts.end_time, c.class_name, st.stream_name, s.subject_name
                    FROM timetable_assignments ta
                    JOIN timetable_slots ts ON ta.slot_id = ts.id
                    JOIN classes c ON ta.class_id = c.id
                    JOIN streams st ON ta.stream_id = st.id
                    JOIN subjects s ON ta.subject_id = s.id
                    WHERE ta.teacher_id = ? AND ta.school_id = ? AND ts.day_of_week = ? AND ts.start_time = ? AND ts.end_time = ?
                ");
                $teacherConflictStmt->execute([$teacher_id, $school_id, $slot['day_of_week'], $slot['start_time'], $slot['end_time']]);
                $teacher_conflict = $teacherConflictStmt->fetch();
                
                if ($teacher_conflict) {
                    $response = [
                        'conflict' => true,
                        'message' => "Teacher is already assigned at this time to: {$teacher_conflict['class_name']} - {$teacher_conflict['stream_name']} ({$teacher_conflict['subject_name']}) on {$teacher_conflict['day_of_week']} at {$teacher_conflict['start_time']}-{$teacher_conflict['end_time']}"
                    ];
                }
            }
        } catch (PDOException $e) {
            $response = ['conflict' => false, 'message' => 'Error checking conflict'];
        }
    }
    
    echo json_encode($response);
    exit;
}

// Handle AJAX stream conflict check
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['check_stream_conflict'])) {
    header('Content-Type: application/json');
    $stream_id = (int)($_POST['stream_id'] ?? 0);
    $slot_id = (int)($_POST['slot_id'] ?? 0);
    $class_id = (int)($_POST['class_id'] ?? 0);
    
    $response = ['conflict' => false, 'message' => ''];
    
    if ($stream_id > 0 && $slot_id > 0 && $class_id > 0) {
        try {
            // Get time slot details
            $slotStmt = $pdo->prepare("SELECT * FROM timetable_slots WHERE id = ? AND school_id = ?");
            $slotStmt->execute([$slot_id, $school_id]);
            $slot = $slotStmt->fetch();
            
            if ($slot) {
                // Check if stream is already assigned at this time slot
                $streamConflictStmt = $pdo->prepare("
                    SELECT ta.*, ts.day_of_week, ts.start_time, ts.end_time, c.class_name, st.stream_name, s.subject_name, CONCAT(t.first_name, ' ', t.last_name) as teacher_name
                    FROM timetable_assignments ta
                    JOIN timetable_slots ts ON ta.slot_id = ts.id
                    JOIN classes c ON ta.class_id = c.id
                    JOIN streams st ON ta.stream_id = st.id
                    JOIN subjects s ON ta.subject_id = s.id
                    JOIN teachers t ON ta.teacher_id = t.id
                    WHERE ta.stream_id = ? AND ta.class_id = ? AND ta.school_id = ? AND ts.day_of_week = ? AND ts.start_time = ? AND ts.end_time = ?
                ");
                $streamConflictStmt->execute([$stream_id, $class_id, $school_id, $slot['day_of_week'], $slot['start_time'], $slot['end_time']]);
                $stream_conflict = $streamConflictStmt->fetch();
                
                if ($stream_conflict) {
                    $response = [
                        'conflict' => true,
                        'message' => "Stream is already having a lesson at this time: {$stream_conflict['subject_name']} by {$stream_conflict['teacher_name']} on {$stream_conflict['day_of_week']} at {$stream_conflict['start_time']}-{$stream_conflict['end_time']}"
                    ];
                }
            }
        } catch (PDOException $e) {
            $response = ['conflict' => false, 'message' => 'Error checking conflict'];
        }
    }
    
    echo json_encode($response);
    exit;
}

// Handle AJAX time slot break conflict check
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['check_slot_break_conflict'])) {
    header('Content-Type: application/json');
    $start_time = $_POST['start_time'] ?? '';
    $end_time = $_POST['end_time'] ?? '';
    
    $response = ['conflict' => false, 'message' => ''];
    
    if (!empty($start_time) && !empty($end_time)) {
        try {
            // Check if this time slot conflicts with any school break
            $breakStmt = $pdo->prepare("SELECT * FROM school_breaks WHERE school_id = ? AND is_active = 1 AND ((start_time <= ? AND end_time >= ?) OR (start_time <= ? AND end_time >= ?) OR (start_time >= ? AND end_time <= ?))");
            $breakStmt->execute([$school_id, $start_time, $start_time, $end_time, $end_time, $start_time, $end_time]);
            $conflicting_break = $breakStmt->fetch();
            
            if ($conflicting_break) {
                $response = [
                    'conflict' => true,
                    'message' => "Time slot conflicts with school break: {$conflicting_break['break_name']} ({$conflicting_break['start_time']} - {$conflicting_break['end_time']})"
                ];
            }
        } catch (PDOException $e) {
            $response = ['conflict' => false, 'message' => 'Error checking conflict'];
        }
    }
    
    echo json_encode($response);
    exit;
}

// Handle AJAX holiday check for assignment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['check_holiday_conflict'])) {
    header('Content-Type: application/json');
    $slot_id = (int)($_POST['slot_id'] ?? 0);
    
    $response = ['conflict' => false, 'message' => ''];
    
    try {
        // Get the time slot details
        $stmt = $pdo->prepare("SELECT * FROM timetable_slots WHERE id = ? AND school_id = ?");
        $stmt->execute([$slot_id, $school_id]);
        $slot = $stmt->fetch();
        
        if ($slot) {
            // Check if this day/time falls during a holiday
            $stmt = $pdo->prepare("SELECT * FROM holidays WHERE school_id = ? AND start_date <= ? AND end_date >= ? AND is_active = 1");
            $stmt->execute([$school_id, date('Y-m-d'), date('Y-m-d')]);
            $holiday = $stmt->fetch();
            
            if ($holiday) {
                $response = [
                    'conflict' => true,
                    'message' => "Cannot assign during holiday: {$holiday['holiday_name']} ({$holiday['start_date']} - {$holiday['end_date']})"
                ];
            }
        }
    } catch (PDOException $e) {
        $response = ['conflict' => false, 'message' => 'Error checking holiday conflict'];
    }
    
    echo json_encode($response);
    exit;
}

// Handle timetable creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_timetable'])) {
    $name = trim($_POST['name'] ?? '');
    $timetable_type = $_POST['timetable_type'] ?? 'weekly';
    $year = (int)($_POST['year'] ?? date('Y'));
    $term = trim($_POST['term'] ?? '');
    $class_id = (int)($_POST['class_id'] ?? 0);
    
    $errors = [];
    if (empty($name)) $errors[] = 'Timetable name is required';
    if (empty($term)) $errors[] = 'Term is required';
    if ($class_id === 0) $errors[] = 'Class is required';
    
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO timetables (school_id, name, timetable_type, year, term, class_id, status, created_by) VALUES (?, ?, ?, ?, ?, ?, 'draft', ?)");
            $stmt->execute([$school_id, $name, $timetable_type, $year, $term, $class_id, $_SESSION['school_id']]);
            $success = 'Timetable created successfully!';
        } catch (PDOException $e) {
            error_log("Failed to create timetable: " . $e->getMessage());
            $errors[] = 'Failed to create timetable. Please try again.';
        }
    }
}

// Handle timetable deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_timetable'])) {
    $timetable_id = (int)($_POST['timetable_id'] ?? 0);
    
    try {
        $stmt = $pdo->prepare("DELETE FROM timetables WHERE id = ? AND school_id = ?");
        $stmt->execute([$timetable_id, $school_id]);
        $success = 'Timetable deleted successfully!';
    } catch (PDOException $e) {
        error_log("Failed to delete timetable: " . $e->getMessage());
        $errors[] = 'Failed to delete timetable. Please try again.';
    }
}

// Handle time slot creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_slot'])) {
    $days_of_week = $_POST['days_of_week'] ?? [];
    $start_time = $_POST['start_time'] ?? '';
    $end_time = $_POST['end_time'] ?? '';
    
    $errors = [];
    if (empty($days_of_week)) $errors[] = 'At least one day is required';
    if (empty($start_time)) $errors[] = 'Start time is required';
    if (empty($end_time)) $errors[] = 'End time is required';
    
    if (empty($errors)) {
        // Check if this time slot conflicts with any school break
        try {
            $breakStmt = $pdo->prepare("SELECT * FROM school_breaks WHERE school_id = ? AND is_active = 1 AND ((start_time <= ? AND end_time >= ?) OR (start_time <= ? AND end_time >= ?) OR (start_time >= ? AND end_time <= ?))");
            $breakStmt->execute([$school_id, $start_time, $start_time, $end_time, $end_time, $start_time, $end_time]);
            $conflicting_break = $breakStmt->fetch();
            
            if ($conflicting_break) {
                $errors[] = "Time slot conflicts with school break: {$conflicting_break['break_name']} ({$conflicting_break['start_time']} - {$conflicting_break['end_time']})";
            }
        } catch (PDOException $e) {
            error_log("Failed to check break conflict: " . $e->getMessage());
        }
    }
    
    if (empty($errors)) {
        try {
            $created_count = 0;
            foreach ($days_of_week as $day_of_week) {
                $stmt = $pdo->prepare("INSERT INTO timetable_slots (school_id, day_of_week, start_time, end_time) VALUES (?, ?, ?, ?)");
                $stmt->execute([$school_id, $day_of_week, $start_time, $end_time]);
                $created_count++;
            }
            $success = "Time slot created successfully for {$created_count} day(s)!";
        } catch (PDOException $e) {
            error_log("Failed to create time slot: " . $e->getMessage());
            $errors[] = 'Failed to create time slot. Please try again.';
        }
    }
}

// Handle time slot update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_slot'])) {
    $slot_id = (int)($_POST['slot_id'] ?? 0);
    $day_of_week = $_POST['day_of_week'] ?? '';
    $start_time = $_POST['start_time'] ?? '';
    $end_time = $_POST['end_time'] ?? '';
    
    $errors = [];
    if (empty($day_of_week)) $errors[] = 'Day of week is required';
    if (empty($start_time)) $errors[] = 'Start time is required';
    if (empty($end_time)) $errors[] = 'End time is required';
    
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("UPDATE timetable_slots SET day_of_week = ?, start_time = ?, end_time = ? WHERE id = ? AND school_id = ?");
            $stmt->execute([$day_of_week, $start_time, $end_time, $slot_id, $school_id]);
            $success = 'Time slot updated successfully!';
        } catch (PDOException $e) {
            error_log("Failed to update time slot: " . $e->getMessage());
            $errors[] = 'Failed to update time slot. Please try again.';
        }
    }
}

// Handle time slot deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_slot'])) {
    $slot_id = (int)($_POST['slot_id'] ?? 0);
    
    try {
        $stmt = $pdo->prepare("DELETE FROM timetable_slots WHERE id = ? AND school_id = ?");
        $stmt->execute([$slot_id, $school_id]);
        $success = 'Time slot deleted successfully!';
    } catch (PDOException $e) {
        error_log("Failed to delete time slot: " . $e->getMessage());
        $errors[] = 'Failed to delete time slot. Please try again.';
    }
}

// Handle timetable assignment creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_assignment'])) {
    $timetable_id = (int)($_POST['timetable_id'] ?? 0);
    $slot_id = (int)($_POST['slot_id'] ?? 0);
    $class_id = (int)($_POST['class_id'] ?? 0);
    $stream_id = (int)($_POST['stream_id'] ?? 0);
    $subject_id = (int)($_POST['subject_id'] ?? 0);
    $teacher_id = (int)($_POST['teacher_id'] ?? 0);
    $notes = $_POST['notes'] ?? '';
    
    $errors = [];
    if ($timetable_id === 0) $errors[] = 'Timetable is required';
    if ($slot_id === 0) $errors[] = 'Time slot is required';
    if ($class_id === 0) $errors[] = 'Class is required';
    if ($stream_id === 0) $errors[] = 'Stream is required';
    if ($subject_id === 0) $errors[] = 'Subject is required';
    if ($teacher_id === 0) $errors[] = 'Teacher is required';
    
    if (empty($errors)) {
        try {
            // Get time slot details to check for break conflicts
            $slotStmt = $pdo->prepare("SELECT * FROM timetable_slots WHERE id = ? AND school_id = ?");
            $slotStmt->execute([$slot_id, $school_id]);
            $slot = $slotStmt->fetch();
            
            if ($slot) {
                // Check if this time slot conflicts with any school break
                $breakStmt = $pdo->prepare("SELECT * FROM school_breaks WHERE school_id = ? AND is_active = 1 AND ((start_time <= ? AND end_time >= ?) OR (start_time <= ? AND end_time >= ?) OR (start_time >= ? AND end_time <= ?))");
                $breakStmt->execute([$school_id, $slot['start_time'], $slot['start_time'], $slot['end_time'], $slot['end_time'], $slot['start_time'], $slot['end_time']]);
                $conflicting_break = $breakStmt->fetch();
                
                if ($conflicting_break) {
                    $errors[] = "Cannot assign to this time slot - it conflicts with school break: {$conflicting_break['break_name']} ({$conflicting_break['start_time']} - {$conflicting_break['end_time']})";
                }
                
                // Check if teacher is already assigned at this time slot
                $teacherConflictStmt = $pdo->prepare("
                    SELECT ta.*, ts.day_of_week, ts.start_time, ts.end_time, c.class_name, st.stream_name, s.subject_name
                    FROM timetable_assignments ta
                    JOIN timetable_slots ts ON ta.slot_id = ts.id
                    JOIN classes c ON ta.class_id = c.id
                    JOIN streams st ON ta.stream_id = st.id
                    JOIN subjects s ON ta.subject_id = s.id
                    WHERE ta.teacher_id = ? AND ta.school_id = ? AND ts.day_of_week = ? AND ts.start_time = ? AND ts.end_time = ? AND ta.id != ?
                ");
                $teacherConflictStmt->execute([$teacher_id, $school_id, $slot['day_of_week'], $slot['start_time'], $slot['end_time'], $existing['id'] ?? 0]);
                $teacher_conflict = $teacherConflictStmt->fetch();
                
                if ($teacher_conflict) {
                    $errors[] = "Teacher is already assigned at this time to: {$teacher_conflict['class_name']} - {$teacher_conflict['stream_name']} ({$teacher_conflict['subject_name']}) on {$teacher_conflict['day_of_week']} at {$teacher_conflict['start_time']}-{$teacher_conflict['end_time']}";
                }
            }
            
            if (empty($errors)) {
                // Check if assignment already exists
                $checkStmt = $pdo->prepare("SELECT id FROM timetable_assignments WHERE timetable_id = ? AND slot_id = ? AND class_id = ? AND stream_id = ?");
                $checkStmt->execute([$timetable_id, $slot_id, $class_id, $stream_id]);
                $existing = $checkStmt->fetch();
                
                if ($existing) {
                    // Update existing assignment
                    $stmt = $pdo->prepare("UPDATE timetable_assignments SET subject_id = ?, teacher_id = ?, notes = ? WHERE id = ?");
                    $stmt->execute([$subject_id, $teacher_id, $notes, $existing['id']]);
                    $success = 'Assignment updated successfully!';
                } else {
                    // Create new assignment
                    $stmt = $pdo->prepare("INSERT INTO timetable_assignments (timetable_id, school_id, slot_id, class_id, stream_id, subject_id, teacher_id, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$timetable_id, $school_id, $slot_id, $class_id, $stream_id, $subject_id, $teacher_id, $notes]);
                    $success = 'Assignment created successfully!';
                }
            }
        } catch (PDOException $e) {
            error_log("Failed to create assignment: " . $e->getMessage());
            $errors[] = 'Failed to create assignment. Please try again.';
        }
    }
}

// Handle assignment deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_assignment'])) {
    $assignment_id = (int)($_POST['assignment_id'] ?? 0);
    
    try {
        $stmt = $pdo->prepare("DELETE FROM timetable_assignments WHERE id = ? AND school_id = ?");
        $stmt->execute([$assignment_id, $school_id]);
        $success = 'Assignment deleted successfully!';
    } catch (PDOException $e) {
        error_log("Failed to delete assignment: " . $e->getMessage());
        $errors[] = 'Failed to delete assignment. Please try again.';
    }
}

// Handle school break creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_break'])) {
    $break_name = trim($_POST['break_name'] ?? '');
    $break_type = $_POST['break_type'] ?? 'short_break';
    $start_time = $_POST['start_time'] ?? '';
    $end_time = $_POST['end_time'] ?? '';
    
    $errors = [];
    if (empty($break_name)) $errors[] = 'Break name is required';
    if (empty($start_time)) $errors[] = 'Start time is required';
    if (empty($end_time)) $errors[] = 'End time is required';
    
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO school_breaks (school_id, break_name, break_type, start_time, end_time) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$school_id, $break_name, $break_type, $start_time, $end_time]);
            $success = 'School break created successfully!';
        } catch (PDOException $e) {
            error_log("Failed to create school break: " . $e->getMessage());
            $errors[] = 'Failed to create school break. Please try again.';
        }
    }
}

// Handle school break deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_break'])) {
    $break_id = (int)($_POST['break_id'] ?? 0);
    
    try {
        $stmt = $pdo->prepare("DELETE FROM school_breaks WHERE id = ? AND school_id = ?");
        $stmt->execute([$break_id, $school_id]);
        $success = 'School break deleted successfully!';
    } catch (PDOException $e) {
        error_log("Failed to delete school break: " . $e->getMessage());
        $errors[] = 'Failed to delete school break. Please try again.';
    }
}

// Get timetables
$timetables = [];
try {
    $stmt = $pdo->prepare("SELECT t.*, c.class_name FROM timetables t LEFT JOIN classes c ON t.class_id = c.id WHERE t.school_id = ? ORDER BY t.created_at DESC");
    $stmt->execute([$school_id]);
    $timetables = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Failed to fetch timetables: " . $e->getMessage());
}

// Get time slots
$time_slots = [];
try {
    $stmt = $pdo->prepare("SELECT * FROM timetable_slots WHERE school_id = ? ORDER BY FIELD(day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'), start_time");
    $stmt->execute([$school_id]);
    $time_slots = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Failed to fetch time slots: " . $e->getMessage());
}

// Get school breaks
$school_breaks = [];
try {
    $stmt = $pdo->prepare("SELECT * FROM school_breaks WHERE school_id = ? AND is_active = 1 ORDER BY start_time");
    $stmt->execute([$school_id]);
    $school_breaks = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Failed to fetch school breaks: " . $e->getMessage());
}

// Get timetable assignments for a specific timetable (if viewing)
$timetable_assignments = [];
$selected_timetable_id = $_GET['view_id'] ?? null;
$selected_timetable = null;
if ($selected_timetable_id) {
    // First get the selected timetable details
    try {
        $stmt = $pdo->prepare("SELECT t.*, c.class_name FROM timetables t LEFT JOIN classes c ON t.class_id = c.id WHERE t.id = ? AND t.school_id = ?");
        $stmt->execute([$selected_timetable_id, $school_id]);
        $selected_timetable = $stmt->fetch();
    } catch (PDOException $e) {
        error_log("Failed to fetch timetable: " . $e->getMessage());
    }
    
    // Then get assignments
    if ($selected_timetable) {
        try {
            $stmt = $pdo->prepare("
                SELECT ta.*, ts.day_of_week, ts.start_time, ts.end_time, ts.break_type,
                       s.subject_name, CONCAT(t.first_name, ' ', t.last_name) as teacher_name,
                       c.class_name, st.stream_name
                FROM timetable_assignments ta
                JOIN timetable_slots ts ON ta.slot_id = ts.id
                JOIN subjects s ON ta.subject_id = s.id
                JOIN teachers t ON ta.teacher_id = t.id
                JOIN classes c ON ta.class_id = c.id
                JOIN streams st ON ta.stream_id = st.id
                WHERE ta.timetable_id = ? AND ta.school_id = ?
                ORDER BY FIELD(ts.day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'), ts.start_time
            ");
            $stmt->execute([$selected_timetable_id, $school_id]);
            $timetable_assignments = $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Failed to fetch timetable assignments: " . $e->getMessage());
        }
    }
}

// Get classes
$classes = [];
try {
    $stmt = $pdo->prepare("SELECT * FROM classes WHERE school_id = ? ORDER BY class_name");
    $stmt->execute([$school_id]);
    $classes = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Failed to fetch classes: " . $e->getMessage());
}

// Get subjects
$subjects = [];
try {
    $stmt = $pdo->prepare("SELECT * FROM subjects WHERE school_id = ? AND status = 'active' ORDER BY subject_name");
    $stmt->execute([$school_id]);
    $subjects = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Failed to fetch subjects: " . $e->getMessage());
}

// Get teachers
$teachers = [];
try {
    $stmt = $pdo->prepare("SELECT * FROM teachers WHERE school_id = ? AND status = 'active' ORDER BY first_name, last_name");
    $stmt->execute([$school_id]);
    $teachers = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Failed to fetch teachers: " . $e->getMessage());
}

// Get streams - filtered by the selected timetable's class if viewing, otherwise get all for school
$streams = [];
$selected_class_id = $selected_timetable ? ($selected_timetable['class_id'] ?? 0) : 0;
try {
    if ($selected_class_id) {
        // Get streams for the specific class
        $stmt = $pdo->prepare("SELECT * FROM streams WHERE class_id = ? ORDER BY stream_name");
        $stmt->execute([$selected_class_id]);
    } else {
        // Get all streams for the school (when not viewing a specific timetable)
        $stmt = $pdo->prepare("SELECT s.* FROM streams s JOIN classes c ON s.class_id = c.id WHERE c.school_id = ? ORDER BY s.stream_name");
        $stmt->execute([$school_id]);
    }
    $streams = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Failed to fetch streams: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Timetable - <?php echo htmlspecialchars($school_name); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/notifications.css">
    <style>
        :root {
            --primary-color: #1a73e8;
            --secondary-color: #5f6368;
            --bg-color: #f8f9fa;
            --card-bg: #ffffff;
            --sidebar-width: 256px;
            --header-height: 64px;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            background: var(--bg-color);
            font-family: 'Google Sans', 'Roboto', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            font-size: 14px;
            color: #202124;
        }
        
        /* Google Search Console Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 10000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.32);
            font-family: 'Roboto', 'Segoe UI', Arial, sans-serif;
        }
        
        .modal-content {
            background: #ffffff;
            padding: 20px 24px;
            border-radius: 24px;
            max-width: 400px;
            width: 90%;
            margin: 15% auto;
            text-align: center;
            box-shadow: 0 24px 38px 3px rgba(0,0,0,0.14), 0 9px 46px 8px rgba(0,0,0,0.12);
            animation: modalSlideIn 0.3s ease-out;
            border: none;
            position: relative;
        }
        
        @keyframes modalSlideIn {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .modal-header {
            margin-bottom: 12px;
        }
        
        .modal-header h3 {
            margin: 0 0 8px 0;
            font-size: 20px;
            font-weight: 400;
            color: #202124;
            line-height: 24px;
        }
        
        .close {
            position: absolute;
            top: 12px;
            right: 12px;
            background: none;
            border: none;
            font-size: 20px;
            color: #5f6368;
            cursor: pointer;
            width: 28px;
            height: 28px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: background 0.2s;
        }
        
        .close:hover {
            background: #f1f3f4;
        }
        
        .modal-body {
            margin-bottom: 16px;
        }
        
        .modal-body p {
            margin: 0;
            font-size: 14px;
            color: #5f6368;
            line-height: 20px;
        }
        
        .modal-footer {
            display: flex;
            justify-content: flex-end;
            margin-top: 16px;
            padding-top: 16px;
            gap: 8px;
        }
        
        .modal-footer button {
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            letter-spacing: 0.25px;
            text-transform: uppercase;
            transition: background 0.2s;
        }
        
        .modal-footer .btn-cancel {
            background: transparent;
            color: #1a73e8;
        }
        
        .modal-footer .btn-cancel:hover {
            background: #f1f3f4;
        }
        
        .modal-footer .btn-confirm {
            background: #1a73e8;
            color: white;
        }
        
        .modal-footer .btn-confirm:hover {
            background: #1557b0;
        }
        
        /* Header */
        .header {
            position: fixed !important;
            top: 0;
            left: 0;
            right: 0;
            height: var(--header-height);
            background: var(--bg-color);
            border-bottom: 1px solid #e8eaed;
            display: flex;
            align-items: center;
            padding: 0 24px;
            z-index: 1000;
        }
        
        .header-left {
            display: flex;
            align-items: center;
            gap: 16px;
        }
        
        .menu-btn {
            background: none;
            border: none;
            cursor: pointer;
            padding: 12px;
            border-radius: 50%;
            color: #5f6368;
            transition: background 0.2s;
        }
        
        .menu-btn:hover {
            background: #f1f3f4;
        }
        
        .logo {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 20px;
            font-weight: 400;
            color: #202124;
        }
        
        .logo i {
            color: var(--primary-color);
        }
        
        .header-right {
            margin-left: auto;
            display: flex;
            align-items: center;
            gap: 16px;
        }
        
        .school-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: var(--primary-color);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 500;
            font-size: 14px;
        }
        
        /* Sidebar */
        .sidebar {
            position: fixed;
            top: var(--header-height);
            left: 0;
            width: var(--sidebar-width);
            height: calc(100vh - var(--header-height));
            background: var(--bg-color);
            overflow-y: auto;
            transition: transform 0.3s ease;
            z-index: 999;
            scrollbar-width: none; /* Firefox */
            -ms-overflow-style: none; /* IE and Edge */
        }
        
        .sidebar::-webkit-scrollbar {
            display: none; /* Chrome, Safari, Opera */
        }
        
        .sidebar.collapsed {
            transform: translateX(-256px);
        }
        
        .sidebar-section {
            padding: 12px 0;
        }
        
        .sidebar-title {
            padding: 8px 24px;
            font-size: 12px;
            font-weight: 500;
            color: #5f6368;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            cursor: pointer;
            user-select: none;
        }
        
        .sidebar-title:hover {
            background: #f1f3f4;
        }
        
        .sidebar-title .chevron {
            transition: transform 0.3s ease;
        }
        
        .sidebar-title.collapsed .chevron {
            transform: rotate(-90deg);
        }
        
        .sidebar-links {
            max-height: 1000px;
            overflow: hidden;
            transition: max-height 0.3s ease;
        }
        
        .sidebar-links.collapsed {
            max-height: 0;
        }
        
        .nav-link {
            display: flex;
            align-items: center;
            padding: 10px 24px;
            color: #5f6368;
            text-decoration: none;
            transition: background 0.2s;
            border: none;
            background: none;
            width: 100%;
            text-align: left;
            cursor: pointer;
            font-size: 14px;
        }
        
        .nav-link:hover {
            background: #f1f3f4;
        }
        
        .nav-link.active {
            background: #e8f0fe;
            color: var(--primary-color);
        }
        
        .nav-link i {
            margin-right: 12px;
            font-size: 18px;
            width: 24px;
            text-align: center;
            color: #FF6B35;
        }
        
        /* Main Content */
        .main-content {
            margin-left: var(--sidebar-width);
            margin-top: var(--header-height);
            padding: 24px;
            transition: margin-left 0.3s ease;
        }
        
        .main-content.expanded {
            margin-left: 0;
        }
        
        .page-title {
            font-size: 22px;
            font-weight: 400;
            color: #202124;
            margin-bottom: 24px;
        }
        
        /* Cards */
        .card {
            background: var(--bg-color);
            border: 1px solid rgba(0, 0, 0, 0.05);
            border-radius: 8px;
            padding: 24px;
            margin-bottom: 24px;
            text-align: center;
        }
        
        .card-title {
            font-size: 18px;
            font-weight: 500;
            color: #202124;
            margin-bottom: 16px;
            text-align: left;
        }
        
        .form-control {
            border: 1px solid #dadce0;
            border-radius: 8px;
            padding: 10px 12px;
            font-size: 14px;
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 2px rgba(26, 115, 232, 0.2);
        }
        
        .btn {
            padding: 10px 24px;
            border-radius: 4px;
            font-size: 14px;
            font-weight: 500;
            border: none;
            cursor: pointer;
            transition: background 0.2s;
        }
        
        .btn-primary {
            background: #FF6B35;
            color: white;
        }
        
        .btn-primary:hover {
            background: #e55a2b;
        }
        
        .btn-outline-primary {
            background: white;
            color: #FF6B35;
            border: 1px solid #FF6B35;
        }
        
        .btn-outline-primary:hover {
            background: #fff3e0;
        }
        
        .table {
            border-collapse: collapse;
            background: white;
            border: 1px solid #000;
            width: 100%;
            margin: 0;
        }
        
        .table-responsive {
            width: 100%;
            overflow-x: auto;
        }
        
        .table thead {
            background: #f0f0f0;
            border-bottom: 2px solid #000;
        }
        
        .table th {
            border: 1px solid #000;
            border-bottom: 2px solid #000;
            padding: 12px;
            font-weight: 600;
            color: #000;
            font-size: 13px;
            text-transform: uppercase;
        }
        
        .table td {
            padding: 12px;
            border: 1px solid #000;
            color: #000;
            font-size: 13px;
        }
        
        .table tbody tr:nth-child(even) {
            background: #f9f9f9;
        }
        
        .table tbody tr:hover {
            background: #f0f0f0;
        }
        
        .form-label {
            display: block;
            margin-bottom: 4px;
            font-weight: 500;
            color: var(--secondary-color);
        }
        
        .mb-3 {
            margin-bottom: 16px;
        }
        
        .alert {
            padding: 12px 16px;
            border-radius: 4px;
            margin-bottom: 16px;
        }
        
        .alert-success {
            background: #e6f4ea;
            color: #137333;
            border: 1px solid rgba(19, 115, 51, 0.1);
        }
        
        .alert-danger {
            background: #fce8e6;
            color: #c5221f;
            border: 1px solid rgba(197, 34, 31, 0.1);
        }
        
        .badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 500;
        }
        
        .badge-draft {
            background: #f1f3f4;
            color: #5f6368;
        }
        
        .badge-active {
            background: #e6f4ea;
            color: #137333;
        }
        
        .badge-archived {
            background: #fce8e6;
            color: #c5221f;
        }
        
        .btn-sm {
            padding: 4px 8px;
            font-size: 12px;
        }
        
        .btn-secondary {
            background: var(--secondary-color);
            color: white;
        }
        
        .btn-danger {
            background: #c5221f;
            color: white;
        }
        
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-256px);
            }
            
            .sidebar.show {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0;
                padding: 16px;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="header-left">
            <button class="menu-btn" onclick="toggleSidebar()">
                <i class="fas fa-bars"></i>
            </button>
            <div class="logo">
                <div style="width: 32px; height: 32px; background: #FFD700; border: 2px solid #FF6B35; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-right: 8px;">
                    <span style="font-weight: bold; font-size: 16px;">
                        <span style="color: #FF6B35; font-size: 20px;">K</span><span style="color: #008000; font-size: 16px;">E</span>
                    </span>
                </div>
                <span style="color: #FF6B35; font-weight: bold;">Kenya</span> <span style="color: #008000; font-weight: bold;">EduHub</span>
            </div>
        </div>
        <div class="header-right">
            <div class="school-avatar">
                <?php echo strtoupper(substr($school_name, 0, 1)); ?>
            </div>
        </div>
    </header>
    
    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-section">
            <div class="sidebar-title" onclick="toggleSidebarSection(this)">
                Main <i class="fas fa-chevron-down chevron"></i>
            </div>
            <div class="sidebar-links">
                <a class="nav-link" href="dashboard">
                    <i class="fas fa-home"></i> Dashboard
                </a>
            </div>
        </div>
        
        <div class="sidebar-section">
            <div class="sidebar-title" onclick="toggleSidebarSection(this)">
                Academic <i class="fas fa-chevron-down chevron"></i>
            </div>
            <div class="sidebar-links">
                <a class="nav-link" href="students">
                    <i class="fas fa-user-graduate"></i> Students
                </a>
                <a class="nav-link" href="teachers">
                    <i class="fas fa-chalkboard-teacher"></i> Teachers
                </a>
                <a class="nav-link" href="classes">
                    <i class="fas fa-chalkboard"></i> Classes
                </a>
                <a class="nav-link" href="streams">
                    <i class="fas fa-layer-group"></i> Streams
                </a>
                <a class="nav-link" href="subjects">
                    <i class="fas fa-book"></i> Subjects
                </a>
                <a class="nav-link" href="exam-types">
                    <i class="fas fa-clipboard-list"></i> Exam Types
                </a>
                <a class="nav-link active" href="timetable">
                    <i class="fas fa-calendar-alt"></i> Timetable
                </a>
                <a class="nav-link" href="calendar">
                    <i class="fas fa-calendar"></i> Calendar
                </a>
                <a class="nav-link" href="grading">
                    <i class="fas fa-chart-bar"></i> Grading
                </a>
            </div>
        </div>
        
        <div class="sidebar-section">
            <div class="sidebar-title" onclick="toggleSidebarSection(this)">
                Academic Records <i class="fas fa-chevron-down chevron"></i>
            </div>
            <div class="sidebar-links">
                <a class="nav-link" href="performance">
                    <i class="fas fa-chart-line"></i> Performance
                </a>
                <a class="nav-link" href="attendance">
                    <i class="fas fa-calendar-check"></i> Attendance
                </a>
            </div>
        </div>
        
        <div class="sidebar-section">
            <div class="sidebar-title" onclick="toggleSidebarSection(this)">
                Financial <i class="fas fa-chevron-down chevron"></i>
            </div>
            <div class="sidebar-links">
                <a class="nav-link" href="fees">
                    <i class="fas fa-money-bill-wave"></i> Fees
                </a>
                <a class="nav-link" href="invoices">
                    <i class="fas fa-file-invoice-dollar"></i> Invoices
                </a>
                <a class="nav-link" href="finance-managers">
                    <i class="fas fa-user-tie"></i> Finance Managers
                </a>
                <a class="nav-link" href="account">
                    <i class="fas fa-wallet"></i> Account Balance
                </a>
            </div>
        </div>
        
        <div class="sidebar-section">
            <div class="sidebar-title" onclick="toggleSidebarSection(this)">
                Administrative <i class="fas fa-chevron-down chevron"></i>
            </div>
            <div class="sidebar-links">
                <a class="nav-link" href="parents">
                    <i class="fas fa-users"></i> Parents
                </a>
                <a class="nav-link" href="disciplinary">
                    <i class="fas fa-exclamation-triangle"></i> Discipline
                </a>
                <a class="nav-link" href="librarians">
                    <i class="fas fa-book-reader"></i> Librarians
                </a>
            </div>
        </div>
        
        <div class="sidebar-section">
            <div class="sidebar-title" onclick="toggleSidebarSection(this)">
                Settings <i class="fas fa-chevron-down chevron"></i>
            </div>
            <div class="sidebar-links">
                <a class="nav-link" href="settings">
                    <i class="fas fa-cog"></i> Settings
                </a>
                <button class="nav-link" onclick="logout()">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </button>
            </div>
        </div>
    </aside>
    
    <!-- Google Search Console Confirmation Modal -->
    <div id="confirmModal" class="modal">
        <div class="modal-content">
            <button class="close" onclick="closeModal()">&times;</button>
            <div class="modal-header">
                <h3 id="modalTitle">Confirm Action</h3>
            </div>
            <div class="modal-body">
                <p id="modalMessage">Are you sure you want to proceed?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-cancel" onclick="closeModal()">Cancel</button>
                <button type="button" class="btn-confirm" id="confirmBtn">Confirm</button>
            </div>
        </div>
    </div>
    
    <!-- Main Content -->
    <div class="main-content" id="mainContent">
        <!-- Calendar Status -->
        <div style="margin-bottom: 24px;">
            <?php if ($calendar_status['is_holiday']): ?>
                <div style="background: #fce8e6; border: 1px solid #c5221f; padding: 16px; border-radius: 8px;">
                    <div style="display: flex; align-items: center; gap: 12px;">
                        <i class="fas fa-exclamation-triangle" style="color: #c5221f; font-size: 20px;"></i>
                        <div>
                            <strong style="color: #c5221f;">School is on Holiday</strong>
                            <p style="margin: 4px 0 0 0; color: #5f6368; font-size: 14px;">
                                <?php echo htmlspecialchars($calendar_status['current_holiday']['holiday_name']); ?> 
                                (<?php echo date('M j, Y', strtotime($calendar_status['current_holiday']['start_date'])); ?> - 
                                <?php echo date('M j, Y', strtotime($calendar_status['current_holiday']['end_date'])); ?>)
                            </p>
                        </div>
                    </div>
                </div>
            <?php elseif ($calendar_status['school_status'] === 'break'): ?>
                <div style="background: #fef7e0; border: 1px solid #f9ab00; padding: 16px; border-radius: 8px;">
                    <div style="display: flex; align-items: center; gap: 12px;">
                        <i class="fas fa-info-circle" style="color: #f9ab00; font-size: 20px;"></i>
                        <div>
                            <strong style="color: #b06000;">School is on Break</strong>
                            <p style="margin: 4px 0 0 0; color: #5f6368; font-size: 14px;">No active term is currently set. Please activate a term in the Calendar.</p>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div style="background: #e6f4ea; border: 1px solid #137333; padding: 16px; border-radius: 8px;">
                    <div style="display: flex; align-items: center; gap: 12px;">
                        <i class="fas fa-check-circle" style="color: #137333; font-size: 20px;"></i>
                        <div>
                            <strong style="color: #137333;">School is In Session</strong>
                            <p style="margin: 4px 0 0 0; color: #5f6368; font-size: 14px;">
                                <?php if ($calendar_status['current_term']): ?>
                                    Active Term: <?php echo htmlspecialchars($calendar_status['current_term']['term_name']); ?> 
                                    (<?php echo date('M j, Y', strtotime($calendar_status['current_term']['start_date'])); ?> - 
                                    <?php echo date('M j, Y', strtotime($calendar_status['current_term']['end_date'])); ?>)
                                <?php else: ?>
                                    Year: <?php echo $calendar_status['current_year']; ?>
                                <?php endif; ?>
                            </p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        
        <?php if (isset($success)): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <?php foreach ($errors as $error): ?>
                    <?php echo htmlspecialchars($error); ?><br>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <?php if ($selected_timetable_id): ?>
            <!-- View Timetable -->
            <div class="card">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <h2 class="card-title" style="margin-bottom: 0;">Timetable View</h2>
                    <a href="timetable" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-2"></i> Back to Timetables
                    </a>
                </div>
                
                <?php if ($selected_timetable): ?>
                    <div style="margin-bottom: 20px; padding: 16px; background: #f8f9fa; border-radius: 8px;">
                        <h3 style="margin: 0 0 8px 0;"><?php echo htmlspecialchars($selected_timetable['name']); ?></h3>
                        <p style="margin: 0; color: #5f6368;">
                            <strong>Class:</strong> <?php echo htmlspecialchars($selected_timetable['class_name'] ?? '-'); ?> | 
                            <strong>Year:</strong> <?php echo $selected_timetable['year']; ?> | 
                            <strong>Term:</strong> <?php echo htmlspecialchars($selected_timetable['term']); ?> | 
                            <strong>Type:</strong> <?php echo ucfirst($selected_timetable['timetable_type']); ?>
                        </p>
                    </div>
                    
                    <!-- Assignment Form -->
                    <div style="margin-bottom: 24px; padding: 16px; background: #fff; border: 1px solid rgba(0, 0, 0, 0.05); border-radius: 8px;">
                        <h4 style="margin: 0 0 16px 0; font-size: 16px; font-weight: 500;">Assign Subject & Teacher to Time Slot</h4>
                        <form method="POST" onsubmit="return validateAssignmentForm(event)">
                            <input type="hidden" name="timetable_id" value="<?php echo $selected_timetable_id; ?>">
                            <input type="hidden" name="class_id" value="<?php echo $selected_timetable['class_id']; ?>">
                            <div class="row">
                                <div class="col-md-3 mb-3">
                                    <label class="form-label">Time Slot *</label>
                                    <select class="form-control" name="slot_id" required onchange="checkAllConflicts()">
                                        <option value="">Select Time Slot</option>
                                        <?php foreach ($time_slots as $slot): ?>
                                            <option value="<?php echo $slot['id']; ?>">
                                                <?php echo htmlspecialchars($slot['day_of_week']); ?> - 
                                                <?php echo htmlspecialchars($slot['start_time']); ?> to 
                                                <?php echo htmlspecialchars($slot['end_time']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label class="form-label">Stream *</label>
                                    <select class="form-control" name="stream_id" required onchange="checkStreamConflict(this)">
                                        <option value="">Select Stream</option>
                                        <?php foreach ($streams as $stream): ?>
                                            <option value="<?php echo $stream['id']; ?>"><?php echo htmlspecialchars($stream['stream_name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div id="stream-conflict-error" class="text-danger mt-1" style="font-size: 0.875rem;"></div>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label class="form-label">Subject *</label>
                                    <select class="form-control" name="subject_id" required>
                                        <option value="">Select Subject</option>
                                        <?php foreach ($subjects as $subject): ?>
                                            <option value="<?php echo $subject['id']; ?>"><?php echo htmlspecialchars($subject['subject_name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label class="form-label">Teacher *</label>
                                    <select class="form-control" name="teacher_id" required onchange="checkTeacherConflict(this)">
                                        <option value="">Select Teacher</option>
                                        <?php foreach ($teachers as $teacher): ?>
                                            <option value="<?php echo $teacher['id']; ?>">
                                                <?php echo htmlspecialchars($teacher['first_name'] . ' ' . $teacher['last_name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div id="teacher-conflict-error" class="text-danger mt-1" style="font-size: 0.875rem;"></div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-12 mb-3">
                                    <label class="form-label">Notes</label>
                                    <textarea class="form-control" name="notes" rows="2" placeholder="Optional notes..."></textarea>
                                </div>
                            </div>
                            <button type="submit" name="create_assignment" class="btn btn-primary">
                                <i class="fas fa-plus me-2"></i> Add Assignment
                            </button>
                        </form>
                    </div>
                <?php endif; ?>
                
                <?php if (empty($timetable_assignments)): ?>
                    <div class="alert alert-info">
                        No assignments found for this timetable. Create time slots and assign subjects/teachers to them.
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Day</th>
                                    <th>Time</th>
                                    <th>Stream</th>
                                    <th>Subject</th>
                                    <th>Teacher</th>
                                    <th>Notes</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($timetable_assignments as $assignment): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($assignment['day_of_week']); ?></td>
                                        <td><?php echo htmlspecialchars($assignment['start_time']); ?> - <?php echo htmlspecialchars($assignment['end_time']); ?></td>
                                        <td><?php echo htmlspecialchars($assignment['stream_name']); ?></td>
                                        <td><?php echo htmlspecialchars($assignment['subject_name']); ?></td>
                                        <td><?php echo htmlspecialchars($assignment['teacher_name']); ?></td>
                                        <td><?php echo htmlspecialchars($assignment['notes'] ?? '-'); ?></td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-danger" onclick="confirmDeleteAssignment(<?php echo $assignment['id']; ?>)">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        <?php else: ?>
        
        <!-- Create Timetable -->
        <div class="card">
            <h2 class="card-title">Create New Timetable</h2>
            <form method="POST">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Timetable Name *</label>
                        <input type="text" class="form-control" name="name" required placeholder="e.g., Grade 10 Weekly Timetable">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Timetable Type</label>
                        <select class="form-control" name="timetable_type">
                            <option value="weekly">Weekly</option>
                            <option value="daily">Daily</option>
                            <option value="exam">Exam</option>
                        </select>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Year *</label>
                        <input type="number" class="form-control" name="year" value="<?php echo date('Y'); ?>" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Term *</label>
                        <select class="form-control" name="term" required>
                            <option value="">Select Term</option>
                            <option value="Term 1">Term 1</option>
                            <option value="Term 2">Term 2</option>
                            <option value="Term 3">Term 3</option>
                        </select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Class *</label>
                        <select class="form-control" name="class_id" required>
                            <option value="">Select Class</option>
                            <?php foreach ($classes as $class): ?>
                                <option value="<?php echo $class['id']; ?>"><?php echo htmlspecialchars($class['class_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <button type="submit" name="create_timetable" class="btn btn-primary">
                    <i class="fas fa-plus me-2"></i> Create Timetable
                </button>
            </form>
        </div>
        
        <!-- Timetables List -->
        <div class="card">
            <h2 class="card-title">Timetables</h2>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Type</th>
                            <th>Year</th>
                            <th>Term</th>
                            <th>Class</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($timetables)): ?>
                            <tr>
                                <td colspan="7" class="text-center">No timetables found</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($timetables as $timetable): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($timetable['name']); ?></strong></td>
                                    <td><?php echo ucfirst($timetable['timetable_type']); ?></td>
                                    <td><?php echo $timetable['year']; ?></td>
                                    <td><?php echo htmlspecialchars($timetable['term']); ?></td>
                                    <td><?php echo htmlspecialchars($timetable['class_name'] ?? '-'); ?></td>
                                    <td>
                                        <span class="badge badge-<?php echo $timetable['status']; ?>">
                                            <?php echo ucfirst($timetable['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-primary" onclick="viewTimetable(<?php echo $timetable['id']; ?>)">
                                            <i class="fas fa-eye"></i> View
                                        </button>
                                        <button class="btn btn-sm btn-secondary" onclick="editTimetable(<?php echo $timetable['id']; ?>)">
                                            <i class="fas fa-edit"></i> Edit
                                        </button>
                                        <button type="button" class="btn btn-sm btn-danger" onclick="confirmDeleteTimetable(<?php echo $timetable['id']; ?>)">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Time Slots Management -->
        <div class="card">
            <h2 class="card-title">Time Slots</h2>
            <div id="slot-break-conflict-error" class="text-danger mb-3" style="font-size: 0.875rem;"></div>
            <form method="POST" style="margin-bottom: 20px;">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Days of Week *</label>
                        <div style="display: flex; flex-wrap: wrap; gap: 10px;">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="days_of_week[]" value="Monday" id="day-monday">
                                <label class="form-check-label" for="day-monday">Monday</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="days_of_week[]" value="Tuesday" id="day-tuesday">
                                <label class="form-check-label" for="day-tuesday">Tuesday</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="days_of_week[]" value="Wednesday" id="day-wednesday">
                                <label class="form-check-label" for="day-wednesday">Wednesday</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="days_of_week[]" value="Thursday" id="day-thursday">
                                <label class="form-check-label" for="day-thursday">Thursday</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="days_of_week[]" value="Friday" id="day-friday">
                                <label class="form-check-label" for="day-friday">Friday</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="days_of_week[]" value="Saturday" id="day-saturday">
                                <label class="form-check-label" for="day-saturday">Saturday</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="days_of_week[]" value="Sunday" id="day-sunday">
                                <label class="form-check-label" for="day-sunday">Sunday</label>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Start Time *</label>
                        <input type="time" class="form-control" name="start_time" required onchange="checkSlotBreakConflict()">
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">End Time *</label>
                        <input type="time" class="form-control" name="end_time" required onchange="checkSlotBreakConflict()">
                    </div>
                </div>
                <button type="submit" name="create_slot" class="btn btn-primary" onclick="return validateSlotForm(event)">
                    <i class="fas fa-plus me-2"></i> Add Time Slot
                </button>
            </form>
            
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Day</th>
                            <th>Start Time</th>
                            <th>End Time</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($time_slots)): ?>
                            <tr>
                                <td colspan="4" class="text-center">No time slots found</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($time_slots as $slot): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($slot['day_of_week']); ?></td>
                                    <td><?php echo htmlspecialchars($slot['start_time']); ?></td>
                                    <td><?php echo htmlspecialchars($slot['end_time']); ?></td>
                                    <td>
                                        <button class="btn btn-sm btn-secondary" onclick="editSlot(<?php echo $slot['id']; ?>, '<?php echo htmlspecialchars($slot['day_of_week']); ?>', '<?php echo htmlspecialchars($slot['start_time']); ?>', '<?php echo htmlspecialchars($slot['end_time']); ?>')">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-sm btn-danger" onclick="deleteSlot(<?php echo $slot['id']; ?>)">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- School Breaks Management -->
        <div class="card">
            <h2 class="card-title">School-Wide Breaks</h2>
            <p style="color: #5f6368; margin-bottom: 20px;">These breaks apply to all timetables in the school (e.g., lunch, short break, recess)</p>
            <form method="POST" style="margin-bottom: 20px;">
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Break Name *</label>
                        <input type="text" class="form-control" name="break_name" placeholder="e.g., Morning Break" required>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Break Type *</label>
                        <select class="form-control" name="break_type" required>
                            <option value="short_break">Short Break</option>
                            <option value="lunch_break">Lunch Break</option>
                            <option value="recess">Recess</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Start Time *</label>
                        <input type="time" class="form-control" name="start_time" required>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">End Time *</label>
                        <input type="time" class="form-control" name="end_time" required>
                    </div>
                </div>
                <button type="submit" name="create_break" class="btn btn-primary">
                    <i class="fas fa-plus me-2"></i> Add School Break
                </button>
            </form>
            
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Break Name</th>
                            <th>Type</th>
                            <th>Start Time</th>
                            <th>End Time</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($school_breaks)): ?>
                            <tr>
                                <td colspan="5" class="text-center">No school breaks found</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($school_breaks as $break): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($break['break_name']); ?></td>
                                    <td><?php echo ucfirst(str_replace('_', ' ', $break['break_type'])); ?></td>
                                    <td><?php echo htmlspecialchars($break['start_time']); ?></td>
                                    <td><?php echo htmlspecialchars($break['end_time']); ?></td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-danger" onclick="confirmDeleteBreak(<?php echo $break['id']; ?>)">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const mainContent = document.getElementById('mainContent');
            sidebar.classList.toggle('collapsed');
            sidebar.classList.toggle('show');
            mainContent.classList.toggle('expanded');
        }
        
        function toggleSidebarSection(titleElement) {
            const linksContainer = titleElement.nextElementSibling;
            const isCollapsed = linksContainer.classList.contains('collapsed');
            
            titleElement.classList.toggle('collapsed');
            linksContainer.classList.toggle('collapsed');
        }
        
        // Only enable collapsible sections on large screens
        function handleResize() {
            const sidebarTitles = document.querySelectorAll('.sidebar-title');
            const sidebarLinks = document.querySelectorAll('.sidebar-links');
            
            if (window.innerWidth <= 768) {
                // On mobile, expand all sections
                sidebarTitles.forEach(title => title.classList.remove('collapsed'));
                sidebarLinks.forEach(links => links.classList.remove('collapsed'));
            }
        }
        
        window.addEventListener('resize', handleResize);
        handleResize();
        
        function logout() {
            if (confirm('Are you sure you want to logout?')) {
                window.location.href = 'logout';
            }
        }
        
        function viewTimetable(id) {
            window.location.href = 'timetable/' + id;
        }
        
        function editTimetable(id) {
            // TODO: Implement edit timetable functionality
            alert('Edit timetable functionality coming soon');
        }
        
        function editSlot(id, day, startTime, endTime) {
            // Find the row and replace it with an edit form
            const row = document.querySelector(`button[onclick*="editSlot(${id}"]`).closest('tr');
            if (row) {
                row.innerHTML = `
                    <td>
                        <select class="form-control" name="day_of_week" required>
                            <option value="Monday" ${day === 'Monday' ? 'selected' : ''}>Monday</option>
                            <option value="Tuesday" ${day === 'Tuesday' ? 'selected' : ''}>Tuesday</option>
                            <option value="Wednesday" ${day === 'Wednesday' ? 'selected' : ''}>Wednesday</option>
                            <option value="Thursday" ${day === 'Thursday' ? 'selected' : ''}>Thursday</option>
                            <option value="Friday" ${day === 'Friday' ? 'selected' : ''}>Friday</option>
                            <option value="Saturday" ${day === 'Saturday' ? 'selected' : ''}>Saturday</option>
                            <option value="Sunday" ${day === 'Sunday' ? 'selected' : ''}>Sunday</option>
                        </select>
                    </td>
                    <td>
                        <input type="time" class="form-control" name="start_time" value="${startTime}" required>
                    </td>
                    <td>
                        <input type="time" class="form-control" name="end_time" value="${endTime}" required>
                    </td>
                    <td>
                        <button type="button" class="btn btn-sm btn-success" onclick="saveSlot(${id}, this)">
                            <i class="fas fa-save"></i>
                        </button>
                        <button type="button" class="btn btn-sm btn-secondary" onclick="location.reload()">
                            <i class="fas fa-times"></i>
                        </button>
                    </td>
                `;
            }
        }
        
        function saveSlot(id, button) {
            const row = button.closest('tr');
            const dayOfWeek = row.querySelector('select[name="day_of_week"]').value;
            const startTime = row.querySelector('input[name="start_time"]').value;
            const endTime = row.querySelector('input[name="end_time"]').value;
            
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type="hidden" name="slot_id" value="${id}">
                <input type="hidden" name="day_of_week" value="${dayOfWeek}">
                <input type="hidden" name="start_time" value="${startTime}">
                <input type="hidden" name="end_time" value="${endTime}">
                <input type="hidden" name="update_slot" value="1">
            `;
            document.body.appendChild(form);
            form.submit();
        }
        
        function deleteSlot(id) {
            if (confirm('Are you sure you want to delete this time slot?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="slot_id" value="${id}">
                    <input type="hidden" name="delete_slot" value="1">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        function checkAllConflicts() {
            const teacherSelect = document.querySelector('select[name="teacher_id"]');
            const streamSelect = document.querySelector('select[name="stream_id"]');
            const slotSelect = document.querySelector('select[name="slot_id"]');
            
            if (teacherSelect && teacherSelect.value) {
                checkTeacherConflict(teacherSelect);
            }
            if (streamSelect && streamSelect.value) {
                checkStreamConflict(streamSelect);
            }
            if (slotSelect && slotSelect.value) {
                checkHolidayConflict();
            }
        }
        
        function checkHolidayConflict() {
            const slotId = document.querySelector('select[name="slot_id"]').value;
            const errorDiv = document.getElementById('holiday-conflict-error') || createHolidayErrorDiv();
            
            if (!slotId) {
                errorDiv.textContent = '';
                return;
            }
            
            // Make AJAX request to check for holiday conflicts
            fetch('timetable', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `check_holiday_conflict=1&slot_id=${slotId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.conflict) {
                    errorDiv.textContent = data.message;
                } else {
                    errorDiv.textContent = '';
                }
            })
            .catch(error => {
                console.error('Error checking holiday conflict:', error);
            });
        }
        
        function createHolidayErrorDiv() {
            const slotSelect = document.querySelector('select[name="slot_id"]');
            const errorDiv = document.createElement('div');
            errorDiv.id = 'holiday-conflict-error';
            errorDiv.className = 'text-danger mt-1';
            errorDiv.style.fontSize = '0.875rem';
            slotSelect.parentNode.appendChild(errorDiv);
            return errorDiv;
        }
        
        function validateAssignmentForm(event) {
            const teacherError = document.getElementById('teacher-conflict-error');
            const streamError = document.getElementById('stream-conflict-error');
            const holidayError = document.getElementById('holiday-conflict-error');
            
            if (teacherError && teacherError.textContent.trim() !== '') {
                event.preventDefault();
                showModal('Teacher Conflict', teacherError.textContent.trim());
                return false;
            }
            
            if (streamError && streamError.textContent.trim() !== '') {
                event.preventDefault();
                showModal('Stream Conflict', streamError.textContent.trim());
                return false;
            }
            
            if (holidayError && holidayError.textContent.trim() !== '') {
                event.preventDefault();
                showModal('Holiday Conflict', holidayError.textContent.trim());
                return false;
            }
            
            return true;
        }
        
        function checkTeacherConflict(selectElement) {
            const teacherId = selectElement.value;
            const slotId = document.querySelector('select[name="slot_id"]').value;
            const errorDiv = document.getElementById('teacher-conflict-error');
            
            if (!teacherId || !slotId) {
                errorDiv.textContent = '';
                return;
            }
            
            // Make AJAX request to check for conflicts
            fetch('index.php?route=timetable', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `check_teacher_conflict=1&teacher_id=${teacherId}&slot_id=${slotId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.conflict) {
                    errorDiv.textContent = data.message;
                    selectElement.classList.add('is-invalid');
                } else {
                    errorDiv.textContent = '';
                    selectElement.classList.remove('is-invalid');
                }
            })
            .catch(error => {
                console.error('Error checking teacher conflict:', error);
            });
        }
        
        function checkStreamConflict(selectElement) {
            const streamId = selectElement.value;
            const slotId = document.querySelector('select[name="slot_id"]').value;
            const classId = document.querySelector('input[name="class_id"]')?.value || document.querySelector('select[name="class_id"]')?.value;
            const errorDiv = document.getElementById('stream-conflict-error');
            
            if (!streamId || !slotId || !classId) {
                errorDiv.textContent = '';
                return;
            }
            
            // Make AJAX request to check for conflicts
            fetch('index.php?route=timetable', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `check_stream_conflict=1&stream_id=${streamId}&slot_id=${slotId}&class_id=${classId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.conflict) {
                    errorDiv.textContent = data.message;
                    selectElement.classList.add('is-invalid');
                } else {
                    errorDiv.textContent = '';
                    selectElement.classList.remove('is-invalid');
                }
            })
            .catch(error => {
                console.error('Error checking stream conflict:', error);
            });
        }
        
        function checkSlotBreakConflict() {
            const startTime = document.querySelector('input[name="start_time"]').value;
            const endTime = document.querySelector('input[name="end_time"]').value;
            const errorDiv = document.getElementById('slot-break-conflict-error');
            
            if (!startTime || !endTime) {
                errorDiv.textContent = '';
                return;
            }
            
            // Make AJAX request to check for break conflicts
            fetch('index.php?route=timetable', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `check_slot_break_conflict=1&start_time=${startTime}&end_time=${endTime}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.conflict) {
                    errorDiv.textContent = data.message;
                } else {
                    errorDiv.textContent = '';
                }
            })
            .catch(error => {
                console.error('Error checking slot break conflict:', error);
            });
        }
        
        function validateSlotForm(event) {
            const errorDiv = document.getElementById('slot-break-conflict-error');
            if (errorDiv && errorDiv.textContent.trim() !== '') {
                event.preventDefault();
                return false;
            }
            return true;
        }
        
        // Simple Modal Functions
        let confirmCallback = null;
        
        function showModal(title, message, onConfirm) {
            document.getElementById('modalTitle').textContent = title;
            document.getElementById('modalMessage').textContent = message;
            document.getElementById('confirmModal').style.display = 'block';
            confirmCallback = onConfirm;
        }
        
        function closeModal() {
            document.getElementById('confirmModal').style.display = 'none';
            confirmCallback = null;
        }
        
        // Confirm button click handler
        document.getElementById('confirmBtn').onclick = function() {
            if (confirmCallback) {
                confirmCallback();
            }
            closeModal();
        };
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('confirmModal');
            if (event.target == modal) {
                closeModal();
            }
        };
        
        // Logout function
        function logout() {
            showModal('Logout', 'Are you sure you want to logout?', function() {
                window.location.href = 'index.php?route=logout';
            });
        }
        
        // Delete slot function
        function deleteSlot(id) {
            showModal('Delete Time Slot', 'Are you sure you want to delete this time slot?', function() {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="slot_id" value="${id}">
                    <input type="hidden" name="delete_slot" value="1">
                `;
                document.body.appendChild(form);
                form.submit();
            });
        }
        
        // Delete assignment function
        function confirmDeleteAssignment(id) {
            showModal('Delete Assignment', 'Are you sure you want to delete this assignment?', function() {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="assignment_id" value="${id}">
                    <input type="hidden" name="delete_assignment" value="1">
                `;
                document.body.appendChild(form);
                form.submit();
            });
        }
        
        // Delete timetable function
        function confirmDeleteTimetable(id) {
            showModal('Delete Timetable', 'Are you sure you want to delete this timetable?', function() {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="timetable_id" value="${id}">
                    <input type="hidden" name="delete_timetable" value="1">
                `;
                document.body.appendChild(form);
                form.submit();
            });
        }
        
        // Delete school break function
        function confirmDeleteBreak(id) {
            showModal('Delete School Break', 'Are you sure you want to delete this school break?', function() {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="break_id" value="${id}">
                    <input type="hidden" name="delete_break" value="1">
                `;
                document.body.appendChild(form);
                form.submit();
            });
        }
    </script>
</body>
</html>
