<?php
session_start();
require_once '../config/database.php';

$username = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';

if(!$username || !$password){
    header("Location: login.php?error=Please+enter+credentials");
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM admins WHERE username=? LIMIT 1");
$stmt->execute([$username]);
$admin = $stmt->fetch(PDO::FETCH_ASSOC);

if($admin && $admin['password'] === $password){ // 🔴 if you used PASSWORD() in SQL use this
    $_SESSION['admin_logged_in'] = true;
    $_SESSION['admin_username'] = $admin['username'];
    header("Location: index.php");
    exit;
}else{
    header("Location: login.php?error=Invalid+credentials");
    exit;
}
