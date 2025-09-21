<?php
session_start();
require_once '../config/database.php';

if(!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true){
    header("Location: login.php");
    exit;
}

if(!isset($_GET['id']) || !is_numeric($_GET['id'])){
    header("Location: users.php");
    exit;
}

$id = (int)$_GET['id'];

// Delete user
$stmt = $pdo->prepare("DELETE FROM users WHERE user_id=?");
if($stmt->execute([$id])){
    $_SESSION['success'] = "User deleted successfully.";
}else{
    $_SESSION['error'] = "Failed to delete user.";
}

header("Location: users.php");
exit;
