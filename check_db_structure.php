<?php
// Database Structure Checker
require_once 'config.php';

echo "<h1>Database Structure Check</h1>";
echo "<h2>Tables related to Fees</h2>";

// Get all tables
$tables = [];
try {
    $stmt = $pdo->query("SHOW TABLES");
    while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
        $tables[] = $row[0];
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
    exit;
}

// Filter fee-related tables
$fee_tables = array_filter($tables, function($table) {
    return strpos(strtolower($table), 'fee') !== false;
});

echo "<h3>Fee-related tables found:</h3>";
echo "<ul>";
foreach ($fee_tables as $table) {
    echo "<li><strong>$table</strong></li>";
}
echo "</ul>";

// Show structure of each fee table
foreach ($fee_tables as $table) {
    echo "<h3>Structure of $table:</h3>";
    echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    
    try {
        $stmt = $pdo->query("DESCRIBE $table");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['Field']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Type']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Null']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Key']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Default']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Extra']) . "</td>";
            echo "</tr>";
        }
    } catch (PDOException $e) {
        echo "<tr><td colspan='6'>Error: " . htmlspecialchars($e->getMessage()) . "</td></tr>";
    }
    
    echo "</table><br>";
}

// Show all tables for reference
echo "<h2>All Tables in Database:</h2>";
echo "<ul>";
foreach ($tables as $table) {
    echo "<li>$table</li>";
}
echo "</ul>";
?>
