<?php
// Invoice Generation System
session_start();
require_once '../../config.php';

// Function to generate invoice number
function generateInvoiceNumber($school_id, $pdo) {
    $prefix = "INV";
    $year = date('Y');
    $month = date('m');
    
    // Get school code for prefix
    try {
        $stmt = $pdo->prepare("SELECT school_code FROM schools WHERE id = ?");
        $stmt->execute([$school_id]);
        $school = $stmt->fetch();
        if ($school && $school['school_code']) {
            $prefix = strtoupper(substr($school['school_code'], 0, 3));
        }
    } catch (PDOException $e) {
        error_log("Error getting school code: " . $e->getMessage());
    }
    
    // Get last invoice number for this school/month
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM invoices WHERE school_id = ? AND YEAR(created_at) = ? AND MONTH(created_at) = ?");
        $stmt->execute([$school_id, $year, $month]);
        $result = $stmt->fetch();
        $sequence = $result['count'] + 1;
    } catch (PDOException $e) {
        $sequence = 1;
    }
    
    return sprintf("%s-%s%s-%04d", $prefix, $year, $month, $sequence);
}

// Function to generate invoice for a student
function generateStudentInvoice($student_id, $term, $year, $school_id, $pdo) {
    try {
        // Get student information
        $stmt = $pdo->prepare("SELECT s.*, c.id as class_id, c.class_name 
                              FROM students s 
                              JOIN classes c ON s.class_id = c.id 
                              WHERE s.id = ? AND s.school_id = ?");
        $stmt->execute([$student_id, $school_id]);
        $student = $stmt->fetch();
        
        if (!$student) {
            return ['success' => false, 'message' => 'Student not found'];
        }
        
        // Check if invoice already exists for this student/term/year
        $stmt = $pdo->prepare("SELECT id FROM invoices WHERE student_id = ? AND term = ? AND year = ?");
        $stmt->execute([$student_id, $term, $year]);
        if ($stmt->fetch()) {
            return ['success' => false, 'message' => 'Invoice already exists for this term/year'];
        }
        
        // Get fee structure for this student's class (Tuition fees only)
        $stmt = $pdo->prepare("SELECT * FROM fee_structure WHERE school_id = ? AND class_id = ? AND term = ? AND year = ? AND fee_type = 'Tuition'");
        $stmt->execute([$school_id, $student['class_id'], $term, $year]);
        $fee_structures = $stmt->fetchAll();
        
        if (empty($fee_structures)) {
            return ['success' => false, 'message' => 'No fee structure found for this class/term/year'];
        }
        
        // Calculate total amount from tuition fees
        $total_amount = 0;
        foreach ($fee_structures as $fee) {
            $total_amount += $fee['amount'];
        }
        
        // Get existing tuition payments for this student/term/year
        $stmt = $pdo->prepare("SELECT SUM(amount) as total_paid FROM fee_payments WHERE student_id = ? AND term = ? AND year = ? AND fee_type = 'Tuition' AND status = 'completed'");
        $stmt->execute([$student_id, $term, $year]);
        $payment_result = $stmt->fetch();
        $paid_amount = $payment_result['total_paid'] ?? 0;
        $balance_amount = $total_amount - $paid_amount;
        
        // Determine initial status based on payments
        if ($balance_amount <= 0) {
            $status = 'paid';
            $balance_amount = 0;
        } elseif ($paid_amount > 0) {
            $status = 'partial';
        } elseif (strtotime(date('Y-m-d', strtotime('+30 days'))) < time()) {
            $status = 'overdue';
        } else {
            $status = 'pending';
        }
        
        // Generate invoice number
        $invoice_number = generateInvoiceNumber($school_id, $pdo);
        
        // Set issue date and due date (30 days from issue date)
        $issue_date = date('Y-m-d');
        $due_date = date('Y-m-d', strtotime('+30 days'));
        
        // Create invoice
        $stmt = $pdo->prepare("INSERT INTO invoices (invoice_number, school_id, student_id, class_id, term, year, total_amount, paid_amount, balance_amount, status, issue_date, due_date, description) 
                              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$invoice_number, $school_id, $student_id, $student['class_id'], $term, $year, $total_amount, $paid_amount, $balance_amount, $status, $issue_date, $due_date, 'Tuition fee invoice for ' . $term . ' ' . $year]);
        
        $invoice_id = $pdo->lastInsertId();
        
        // Create invoice items (tuition fees only)
        foreach ($fee_structures as $fee) {
            $stmt = $pdo->prepare("INSERT INTO invoice_items (invoice_id, fee_structure_id, fee_type, amount, description) 
                                  VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$invoice_id, $fee['id'], $fee['fee_type'], $fee['amount'], $fee['description'] ?? $fee['fee_type']]);
        }
        
        // Link existing tuition payments to this invoice
        if ($paid_amount > 0) {
            $stmt = $pdo->prepare("SELECT id, amount FROM fee_payments WHERE student_id = ? AND term = ? AND year = ? AND fee_type = 'Tuition' AND status = 'completed'");
            $stmt->execute([$student_id, $term, $year]);
            $payments = $stmt->fetchAll();
            
            foreach ($payments as $payment) {
                $stmt = $pdo->prepare("INSERT INTO invoice_payments (invoice_id, payment_id, amount) VALUES (?, ?, ?)");
                $stmt->execute([$invoice_id, $payment['id'], $payment['amount']]);
            }
        }
        
        return ['success' => true, 'invoice_id' => $invoice_id, 'invoice_number' => $invoice_number, 'total_amount' => $total_amount];
        
    } catch (PDOException $e) {
        error_log("Error generating invoice: " . $e->getMessage());
        return ['success' => false, 'message' => 'Error generating invoice: ' . $e->getMessage()];
    }
}

// Function to generate invoices for all students in a class
function generateClassInvoices($class_id, $term, $year, $school_id, $pdo) {
    try {
        // Get all students in this class
        $stmt = $pdo->prepare("SELECT id FROM students WHERE class_id = ? AND school_id = ? AND status = 'active'");
        $stmt->execute([$class_id, $school_id]);
        $students = $stmt->fetchAll();
        
        if (empty($students)) {
            return ['success' => false, 'message' => 'No active students found in this class'];
        }
        
        $results = [];
        $success_count = 0;
        $error_count = 0;
        
        foreach ($students as $student) {
            $result = generateStudentInvoice($student['id'], $term, $year, $school_id, $pdo);
            $results[] = [
                'student_id' => $student['id'],
                'result' => $result
            ];
            
            if ($result['success']) {
                $success_count++;
            } else {
                $error_count++;
            }
        }
        
        return [
            'success' => true,
            'total' => count($students),
            'success_count' => $success_count,
            'error_count' => $error_count,
            'results' => $results
        ];
        
    } catch (PDOException $e) {
        error_log("Error generating class invoices: " . $e->getMessage());
        return ['success' => false, 'message' => 'Error generating class invoices: ' . $e->getMessage()];
    }
}

// Function to update invoice status based on payments
function updateInvoiceStatus($invoice_id, $pdo) {
    try {
        // Get invoice details
        $stmt = $pdo->prepare("SELECT total_amount, paid_amount FROM invoices WHERE id = ?");
        $stmt->execute([$invoice_id]);
        $invoice = $stmt->fetch();
        
        if (!$invoice) {
            return false;
        }
        
        // Calculate new paid amount from linked payments
        $stmt = $pdo->prepare("SELECT SUM(ip.amount) as total_paid FROM invoice_payments ip WHERE ip.invoice_id = ?");
        $stmt->execute([$invoice_id]);
        $payment_result = $stmt->fetch();
        
        $new_paid_amount = $payment_result['total_paid'] ?? 0;
        $new_balance = $invoice['total_amount'] - $new_paid_amount;
        
        // Determine status
        $status = 'pending';
        if ($new_balance <= 0) {
            $status = 'paid';
        } elseif ($new_paid_amount > 0) {
            $status = 'partial';
        }
        
        // Check if overdue
        $stmt = $pdo->prepare("SELECT due_date FROM invoices WHERE id = ?");
        $stmt->execute([$invoice_id]);
        $invoice_date = $stmt->fetch();
        
        if ($invoice_date && $invoice_date['due_date'] < date('Y-m-d') && $status === 'pending') {
            $status = 'overdue';
        }
        
        // Update invoice
        $stmt = $pdo->prepare("UPDATE invoices SET paid_amount = ?, balance_amount = ?, status = ? WHERE id = ?");
        $stmt->execute([$new_paid_amount, $new_balance, $status, $invoice_id]);
        
        return true;
        
    } catch (PDOException $e) {
        error_log("Error updating invoice status: " . $e->getMessage());
        return false;
    }
}

// Function to link payment to invoice
function linkPaymentToInvoice($payment_id, $invoice_id, $amount, $pdo) {
    try {
        // Check if payment already linked to this invoice
        $stmt = $pdo->prepare("SELECT id FROM invoice_payments WHERE payment_id = ? AND invoice_id = ?");
        $stmt->execute([$payment_id, $invoice_id]);
        if ($stmt->fetch()) {
            return ['success' => false, 'message' => 'Payment already linked to this invoice'];
        }
        
        // Create link
        $stmt = $pdo->prepare("INSERT INTO invoice_payments (invoice_id, payment_id, amount) VALUES (?, ?, ?)");
        $stmt->execute([$invoice_id, $payment_id, $amount]);
        
        // Update invoice status
        updateInvoiceStatus($invoice_id, $pdo);
        
        return ['success' => true, 'message' => 'Payment linked to invoice successfully'];
        
    } catch (PDOException $e) {
        error_log("Error linking payment to invoice: " . $e->getMessage());
        return ['success' => false, 'message' => 'Error linking payment: ' . $e->getMessage()];
    }
}

// API endpoint for generating invoice
if (isset($_GET['action']) && $_GET['action'] === 'generate_invoice') {
    header('Content-Type: application/json');
    
    $student_id = $_POST['student_id'] ?? '';
    $term = $_POST['term'] ?? '';
    $year = $_POST['year'] ?? date('Y');
    $school_id = $_SESSION['school_id'] ?? '';
    
    if (empty($student_id) || empty($term) || empty($school_id)) {
        echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
        exit;
    }
    
    $result = generateStudentInvoice($student_id, $term, $year, $school_id, $pdo);
    echo json_encode($result);
    exit;
}

// API endpoint for generating class invoices
if (isset($_GET['action']) && $_GET['action'] === 'generate_class_invoices') {
    header('Content-Type: application/json');
    
    $class_id = $_POST['class_id'] ?? '';
    $term = $_POST['term'] ?? '';
    $year = $_POST['year'] ?? date('Y');
    $school_id = $_SESSION['school_id'] ?? '';
    
    // Debug logging
    error_log("Invoice generation request - class_id: " . $class_id . ", term: " . $term . ", year: " . $year . ", school_id: " . $school_id);
    
    if (empty($class_id) || empty($term) || empty($school_id)) {
        echo json_encode(['success' => false, 'message' => 'Missing required parameters: ' . 
            (empty($class_id) ? 'class_id ' : '') . 
            (empty($term) ? 'term ' : '') . 
            (empty($school_id) ? 'school_id ' : '')]);
        exit;
    }
    
    $result = generateClassInvoices($class_id, $term, $year, $school_id, $pdo);
    echo json_encode($result);
    exit;
}
// API endpoint for updating invoice statuses based on payments
if (isset($_GET['action']) && $_GET['action'] === 'update_invoice_statuses') {
    header('Content-Type: application/json');
    
    $school_id = $_SESSION['school_id'] ?? '';
    
    if (empty($school_id)) {
        echo json_encode(['success' => false, 'message' => 'Missing school_id']);
        exit;
    }
    
    try {
        // Get all invoices for this school
        $stmt = $pdo->prepare("SELECT * FROM invoices WHERE school_id = ?");
        $stmt->execute([$school_id]);
        $invoices = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $updated = 0;
        foreach ($invoices as $invoice) {
            // Get actual payments
            $stmt = $pdo->prepare("SELECT SUM(amount) as total_paid FROM fee_payments WHERE student_id = ? AND term = ? AND year = ? AND fee_type = 'Tuition' AND status = 'completed'");
            $stmt->execute([$invoice['student_id'], $invoice['term'], $invoice['year']]);
            $payment_result = $stmt->fetch();
            $paid_amount = $payment_result['total_paid'] ?? 0;
            $balance_amount = $invoice['total_amount'] - $paid_amount;
            
            // Determine status
            if ($balance_amount <= 0) {
                $status = 'paid';
                $balance_amount = 0;
            } elseif ($paid_amount > 0) {
                $status = 'partial';
            } elseif (strtotime($invoice['due_date']) < time()) {
                $status = 'overdue';
            } else {
                $status = 'pending';
            }
            
            // Update invoice if status changed
            if ($invoice['status'] !== $status || $invoice['paid_amount'] != $paid_amount || $invoice['balance_amount'] != $balance_amount) {
                $stmt = $pdo->prepare("UPDATE invoices SET status = ?, paid_amount = ?, balance_amount = ? WHERE id = ?");
                $stmt->execute([$status, $paid_amount, $balance_amount, $invoice['id']]);
                $updated++;
            }
        }
        
        echo json_encode(['success' => true, 'message' => "Updated $updated invoices"]);
    } catch (PDOException $e) {
        error_log("Error updating invoice statuses: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Error updating invoice statuses: ' . $e->getMessage()]);
    }
    exit;
}

// API endpoint for deleting invoice
if (isset($_GET['action']) && $_GET['action'] === 'delete_invoice') {
    header('Content-Type: application/json');
    
    $data = json_decode(file_get_contents('php://input'), true);
    $invoice_id = $data['invoice_id'] ?? '';
    $school_id = $_SESSION['school_id'] ?? '';
    
    if (empty($invoice_id) || empty($school_id)) {
        echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
        exit;
    }
    
    try {
        // Verify invoice belongs to this school
        $stmt = $pdo->prepare("SELECT id FROM invoices WHERE id = ? AND school_id = ?");
        $stmt->execute([$invoice_id, $school_id]);
        if (!$stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Invoice not found or access denied']);
            exit;
        }
        
        // Delete invoice payments
        $stmt = $pdo->prepare("DELETE FROM invoice_payments WHERE invoice_id = ?");
        $stmt->execute([$invoice_id]);
        
        // Delete invoice items
        $stmt = $pdo->prepare("DELETE FROM invoice_items WHERE invoice_id = ?");
        $stmt->execute([$invoice_id]);
        
        // Delete invoice
        $stmt = $pdo->prepare("DELETE FROM invoices WHERE id = ?");
        $stmt->execute([$invoice_id]);
        
        echo json_encode(['success' => true, 'message' => 'Invoice deleted successfully']);
    } catch (PDOException $e) {
        error_log("Error deleting invoice: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Error deleting invoice: ' . $e->getMessage()]);
    }
    exit;
}

?>
