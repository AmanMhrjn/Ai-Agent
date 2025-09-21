<?php
require_once '../config/database.php';
header('Content-Type: application/json');

$id = $_GET['id'] ?? 0;
if(!$id){ echo json_encode(['success'=>false]); exit; }

$stmt = $pdo->prepare("SELECT * FROM payment_qr_details WHERE id=?");
$stmt->execute([$id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if($row){
    echo json_encode([
        'success'=>true,
        'account_name'=>$row['account_name'],
        'account_number'=>$row['account_number'],
        'qr_image'=>$row['qr_image']
    ]);
}else{
    echo json_encode(['success'=>false]);
}
