<?php
require 'config.php';

$level = $_GET['level'] ?? 'all';
$subject = $_GET['subject'] ?? 'all';
$type = $_GET['type'] ?? 'all';

$query = "SELECT * FROM resources WHERE 1=1";
if ($level !== 'all') $query .= " AND level='$level'";
if ($subject !== 'all') $query .= " AND subject='$subject'";
if ($type !== 'all') $query .= " AND type='$type'";

$result = $conn->query($query);
$data = [];

while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}

header('Content-Type: application/json');
echo json_encode($data);
?>
