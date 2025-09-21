<?php
session_start();
require_once __DIR__ . '/config/database.php';

$agent_id = (int)$_POST['agent_id'];
$phone = preg_replace('/\D/', '', $_POST['phone_number']);
if (!$phone) die('Invalid phone number');

$chat_url = "https://wa.me/{$phone}";

$stmt = $pdo->prepare("
    INSERT INTO agent_connections (user_id, agent_id, platform, link)
    VALUES (:user_id, :agent_id, 'WhatsApp', :link)
    ON DUPLICATE KEY UPDATE link = VALUES(link)
");
$stmt->execute([
    ':user_id'=>$_SESSION['id'],
    ':agent_id'=>$agent_id,
    ':link'=>$chat_url
]);

header("Location: manageagents.php?whatsapp_connected=1");
exit;
