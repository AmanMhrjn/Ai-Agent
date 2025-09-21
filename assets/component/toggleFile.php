<?php
include_once '../../config/database.php';
$id = $_GET['id'];

$stmt = $conn->prepare("UPDATE uploaded_files SET is_enabled = NOT is_enabled WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
?>
