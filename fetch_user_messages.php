<?php
session_start();
require_once "config/database.php";

if (!isset($_SESSION["id"])) {
    echo json_encode([]);
    exit;
}

$user_id = $_SESSION["id"];

$stmt = $pdo->prepare("SELECT sender, message FROM chat_messages WHERE user_id = ? ORDER BY created_at ASC");
$stmt->execute([$user_id]);
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

header('Content-Type: application/json');
echo json_encode($messages);
