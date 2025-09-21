<?php
session_start();
include_once '../../config/database.php';

if (!isset($_SESSION['id'])) {
  echo "Not logged in";
  exit;
}

$user_id = $_SESSION['id'];

// Get company name
$stmt = $conn->prepare("SELECT company_name FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$company_name = $row['company_name'] ?? '';

// Check if file exists
if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
  echo "Upload error: ";
  if (!isset($_FILES['file'])) {
    echo "No file sent.";
  } else {
    echo "Error Code " . $_FILES['file']['error'];
  }
  exit;
}

$file = $_FILES['file'];
$name = $file['name'];
$type = $file['type'];
$size = $file['size'];
$content = file_get_contents($file['tmp_name']);

// Store file in DB
$stmt = $conn->prepare("INSERT INTO uploaded_files (user_id, company_name, file_name, file_type, file_size, file_content) VALUES (?, ?, ?, ?, ?, ?)");
$stmt->bind_param("isssis", $user_id, $company_name, $name, $type, $size, $content);

if ($stmt->execute()) {
  echo "success";
} else {
  echo "DB error: " . $stmt->error;
}
?>
