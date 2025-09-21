<?php
session_start();
require_once '../config/database.php';
header('Content-Type: application/json');

$user_id = $_SESSION['id'] ?? 0;
$company_name = $_SESSION['companyname'] ?? '';

if (!$user_id) {
    echo json_encode(['success'=>false,'message'=>'User not logged in']);
    exit;
}

$plan = $_POST['plan'] ?? '';
$amount = isset($_POST['amount']) ? (float)$_POST['amount'] : null;
$payment_method = $_POST['payment_method'] ?? '';
$platform = $_POST['platform'] ?? '';

if (!$plan || $payment_method === '' || !is_numeric($amount) || !$platform) {
    echo json_encode(['success'=>false,'message'=>'Invalid data']);
    exit;
}

try {
    $stmt = $pdo->prepare("
        INSERT INTO payment_requests 
        (user_id, company_name, payment_method, amount, plan, platform, status, created_at)
        VALUES (:user_id, :company_name, :payment_method, :amount, :plan, :platform, 'pending', NOW())
    ");
    $stmt->execute([
        ':user_id' => $user_id,
        ':company_name' => $company_name,
        ':payment_method' => $payment_method,
        ':amount' => $amount,
        ':plan' => $plan,
        ':platform' => $platform
    ]);

    echo json_encode(['success'=>true,'message'=>'Payment submitted. Tokens will be added after admin approval.']);
} catch (PDOException $e) {
    echo json_encode(['success'=>false,'message'=>'Database error: '.$e->getMessage()]);
}
?>
