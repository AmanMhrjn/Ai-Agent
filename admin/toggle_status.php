<?php
require_once '../config/database.php';

$id = $_GET['id'];

// Get current status
$stmt = $pdo->prepare("SELECT status FROM products WHERE id=:id");
$stmt->execute([':id'=>$id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

$new_status = $product['status'] ? 0 : 1;

// Update status
$stmt = $pdo->prepare("UPDATE products SET status=:status WHERE id=:id");
$stmt->execute([':status'=>$new_status, ':id'=>$id]);

header('Location: products.php');
