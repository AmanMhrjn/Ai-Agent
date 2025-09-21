<?php
include_once '../../config/database.php';
$id = $_GET['id'];

$stmt = $conn->prepare("DELETE FROM uploaded_files WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
?>
