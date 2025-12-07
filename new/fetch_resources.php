<?php
include 'config.php';
$result = $conn->query("SELECT * FROM resources ORDER BY created_at DESC");
$data = [];

while ($row = $result->fetch_assoc()) {
  $data[] = $row;
}

echo json_encode($data);
?>
