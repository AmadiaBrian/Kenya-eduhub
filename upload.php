<?php
require 'config.php';

$title = $_POST['title'];
$level = $_POST['level'];
$subject = $_POST['subject'];
$type = $_POST['type'];
$description = $_POST['description'];

$filename = basename($_FILES['file']['name']);
$destination = "uploads/" . $filename;

// First, check if the file already exists (case-insensitive)
$check_stmt = $conn->prepare("SELECT id FROM resources WHERE LOWER(filename) = LOWER(?)");
$check_stmt->bind_param("s", $filename);
$check_stmt->execute();
$check_stmt->store_result();

if ($check_stmt->num_rows > 0) {
    echo "❌ Error: A file with this name already exists in the database.";
} else {
    // File not in database, attempt to move and insert
    if (move_uploaded_file($_FILES['file']['tmp_name'], $destination)) {
        $insert_stmt = $conn->prepare("INSERT INTO resources (title, level, subject, type, description, filename) VALUES (?, ?, ?, ?, ?, ?)");
        $insert_stmt->bind_param("ssssss", $title, $level, $subject, $type, $description, $filename);

        if ($insert_stmt->execute()) {
            echo "✅ Resource uploaded successfully.";
        } else {
            echo "❌ Error inserting into database: " . $insert_stmt->error;
        }

        $insert_stmt->close();
    } else {
        echo "❌ Error uploading file.";
    }
}

$check_stmt->close();
$conn->close();
?>
