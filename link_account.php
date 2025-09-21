<?php
session_start();
require_once 'config/database.php';

$user_id = $_POST['user_id'] ?? 0;
$platform = $_POST['platform'] ?? '';
$account_id = trim($_POST['account_id'] ?? '');

if(!$user_id || !$platform || !$account_id){
    $_SESSION['flash'] = "❌ All fields are required";
    header("Location: dashboard.php");
    exit;
}

// Prevent duplicate
try{
    $stmt = $pdo->prepare("INSERT INTO linked_accounts (user_id, platform, account_id) VALUES (?,?,?)");
    $stmt->execute([$user_id, $platform, $account_id]);
    $_SESSION['flash'] = "✅ $platform account linked successfully!";
}catch(PDOException $e){
    if($e->getCode() == 23000){
        $_SESSION['flash'] = "❌ This account is already linked";
    } else {
        $_SESSION['flash'] = "❌ Error: ".$e->getMessage();
    }
}

header("Location: dashboard.php");
exit;
