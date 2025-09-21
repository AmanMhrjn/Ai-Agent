<?php
session_start();
include_once '../../config/database.php';

if (!isset($_SESSION['id'])) {
  echo json_encode([]);
  exit;
}

$user_id = $_SESSION['id'];

$stmt = $conn->prepare("SELECT id, file_name, file_size, is_enabled FROM uploaded_files WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$files = [];
while ($row = $result->fetch_assoc()) {
  $files[] = $row;
}

echo json_encode($files);
?>
