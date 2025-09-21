<?php
session_start();
require_once '../config/database.php';

$id=$_GET['id'] ?? 0;

$stmt=$pdo->prepare("UPDATE payment_requests SET status='rejected' WHERE id=?");
$stmt->execute([$id]);

// Send rejection email
@mail("user@example.com","Payment Rejected","Your payment was rejected. Please contact support.");

header("Location: payments.php");
exit;
