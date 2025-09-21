<?php
session_start();
require_once __DIR__ . '/../config/database.php';

$user_id = $_POST["user_id"] ?? "";
$reply = $_POST["reply"] ?? "";

if ($user_id && $reply) {
    $stmt = $pdo->prepare("INSERT INTO chat_messages (user_id, sender, message, is_read) VALUES (?, 'admin', ?, 1)");
    $stmt->execute([$user_id, $reply]);
}

// Redirect back to same user chat
header("Location: admin_chat.php?user_id=" . urlencode($user_id));
exit;
