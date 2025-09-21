<?php
session_start();
require_once 'config/database.php';

$user_id = $_SESSION['id'] ?? 0;
$platform = $_GET['platform'] ?? '';

$response = ['exists' => false];
if ($user_id && $platform) {
    $stmt = $pdo->prepare("SELECT * FROM agents WHERE user_id=? AND platform=? AND status=1");
    $stmt->execute([$user_id, $platform]);
    if ($stmt->fetch(PDO::FETCH_ASSOC)) {
        $response['exists'] = true;
    }
}

header('Content-Type: application/json');
echo json_encode($response);
