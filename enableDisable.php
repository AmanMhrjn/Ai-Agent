<?php
require_once 'config/database.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Validate product ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Invalid product ID");
}

$id = (int)$_GET['id'];

// Step 1: Get current status
$sql = "SELECT status FROM products WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Product not found");
}

$row = $result->fetch_assoc();
$current_status = (int)$row['status'];
$new_status = $current_status === 1 ? 0 : 1;

// Step 2: Update to the new status
$update_sql = "UPDATE products SET status = ? WHERE id = ?";
$update_stmt = $conn->prepare($update_sql);
$update_stmt->bind_param("ii", $new_status, $id);

if ($update_stmt->execute()) {
    header("Location: dashboard.php?msg=status_updated");
    exit;
} else {
    die("Status update failed");
}
