<?php
session_start();
require_once __DIR__ . '/../config/database.php';

$selectedUserId = $_GET['user_id'] ?? null;
$messages = [];

if ($selectedUserId) {
    $stmt = $pdo->prepare("SELECT cm.*, u.username 
                           FROM chat_messages cm
                           JOIN users u ON cm.user_id = u.user_id
                           WHERE cm.user_id = :uid
                           ORDER BY cm.created_at ASC");
    $stmt->execute(['uid' => $selectedUserId]);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

foreach ($messages as $msg) {
    $class = $msg['sender'] === 'user' ? 'user-message' : 'admin-message';
    echo "<div class='message $class'>" .
            htmlspecialchars($msg['message']) .
            "<small>{$msg['created_at']}</small>" .
         "</div>";
}
