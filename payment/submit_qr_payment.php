<?php
session_start();
require_once '../config/database.php';
header('Content-Type: application/json');

$user_id = $_SESSION['id'] ?? 0;
$company_name = $_SESSION['companyname'] ?? '';

if(!$user_id){ echo json_encode(['success'=>false,'message'=>'User not logged in']); exit; }

$plan = $_POST['plan'] ?? '';
$amount = $_POST['amount'] ?? 0;
$payment_method = $_POST['payment_method'] ?? '';
$platform_id = $_POST['platform'] ?? '';
$screenshot = '';

if(isset($_FILES['screenshot']) && $_FILES['screenshot']['error']==0){
    $target = '../uploads/payments/'.time().'_'.basename($_FILES['screenshot']['name']);
    if(move_uploaded_file($_FILES['screenshot']['tmp_name'],$target)){
        $screenshot = $target;
    }
}

try{
    $stmt = $pdo->prepare("INSERT INTO payment_requests (user_id,company_name,plan,platform,amount,payment_method,screenshot,status)
                           VALUES (?,?,?,?,?,?,?,'pending')");
    $stmt->execute([$user_id,$company_name,$plan,$platform_id,$amount,$payment_method,$screenshot]);

    // Send Mail to User (simple PHP mail)
    @mail($_SESSION['email'], "Payment Received", "Your payment request has been submitted. Admin will verify soon.");

    echo json_encode(['success'=>true,'message'=>'Payment submitted. Awaiting admin approval.']);
}catch(Exception $e){
    echo json_encode(['success'=>false,'message'=>'DB Error: '.$e->getMessage()]);
}
