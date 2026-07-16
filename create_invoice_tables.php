<?php
// Create Invoices Table
require_once 'config.php';

// SQL to create invoices table
$sql = "CREATE TABLE IF NOT EXISTS invoices (
    id INT(11) NOT NULL AUTO_INCREMENT,
    invoice_number VARCHAR(50) NOT NULL,
    school_id INT(11) NOT NULL,
    student_id INT(11) NOT NULL,
    class_id INT(11) NOT NULL,
    term VARCHAR(50) NOT NULL,
    year INT(4) NOT NULL,
    total_amount DECIMAL(10,2) NOT NULL,
    paid_amount DECIMAL(10,2) DEFAULT 0.00,
    balance_amount DECIMAL(10,2) NOT NULL,
    status ENUM('pending', 'partial', 'paid', 'overdue', 'cancelled') DEFAULT 'pending',
    issue_date DATE NOT NULL,
    due_date DATE NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY invoice_number (invoice_number),
    KEY school_id (school_id),
    KEY student_id (student_id),
    KEY class_id (class_id),
    KEY status (status),
    KEY issue_date (issue_date),
    KEY due_date (due_date),
    FOREIGN KEY (school_id) REFERENCES schools(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

// SQL to create invoice_items table
$sql_items = "CREATE TABLE IF NOT EXISTS invoice_items (
    id INT(11) NOT NULL AUTO_INCREMENT,
    invoice_id INT(11) NOT NULL,
    fee_structure_id INT(11) NOT NULL,
    fee_type VARCHAR(100) NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY invoice_id (invoice_id),
    KEY fee_structure_id (fee_structure_id),
    FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE CASCADE,
    FOREIGN KEY (fee_structure_id) REFERENCES fee_structure(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

// SQL to create invoice_payments table (link invoices to payments)
$sql_payments = "CREATE TABLE IF NOT EXISTS invoice_payments (
    id INT(11) NOT NULL AUTO_INCREMENT,
    invoice_id INT(11) NOT NULL,
    payment_id INT(11) NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    allocated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY invoice_id (invoice_id),
    KEY payment_id (payment_id),
    FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE CASCADE,
    FOREIGN KEY (payment_id) REFERENCES fee_payments(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

try {
    // Create invoices table
    $pdo->exec($sql);
    echo "✓ invoices table created successfully<br>";
    
    // Create invoice_items table
    $pdo->exec($sql_items);
    echo "✓ invoice_items table created successfully<br>";
    
    // Create invoice_payments table
    $pdo->exec($sql_payments);
    echo "✓ invoice_payments table created successfully<br>";
    
    echo "<h3>Invoice System Tables Created Successfully!</h3>";
    
} catch (PDOException $e) {
    echo "Error creating tables: " . $e->getMessage();
}
?>
