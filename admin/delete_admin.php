<?php
session_start();
require_once '../config/database.php';

// Check if admin is logged in
if(!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true){
    header("Location: login.php");
    exit;
}

// Validate ID
if(!isset($_GET['id']) || !is_numeric($_GET['id'])){
    header("Location: index.php"); // Redirect to dashboard if invalid
    exit;
}

$id = (int)$_GET['id'];

// Prevent deleting yourself
if($id === $_SESSION['admin_id']){
    $_SESSION['error'] = "You cannot delete your own account.";
    header("Location: index.php");
    exit;
}

// Delete admin
$stmt = $pdo->prepare("DELETE FROM admins WHERE id = ?");
if($stmt->execute([$id])){
    $_SESSION['success'] = "Admin deleted successfully.";
}else{
    $_SESSION['error'] = "Failed to delete admin.";
}

header("Location: index.php");
exit;
