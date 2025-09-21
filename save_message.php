<?php
session_start();
require_once "config/database.php";

if (!isset($_SESSION["id"]) || !isset($_POST["message"])) exit;

$user_id = $_SESSION["id"];
$message = trim($_POST["message"]);

if ($message) {
    $stmt = $pdo->prepare("INSERT INTO chat_messages (user_id, sender, message, is_read) VALUES (?, 'user', ?, 0)");
    $stmt->execute([$user_id, $message]);
}
