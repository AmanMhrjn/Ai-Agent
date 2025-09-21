<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['id'];
$data = json_decode(file_get_contents('php://input'), true);
$agent_id = $data['agent_id'] ?? null;
$agent_name = trim($data['agent_name'] ?? '');

if (!$agent_id || $agent_name === '') {
    echo json_encode(['success' => false, 'message' => 'Invalid data']);
    exit;
}

$stmt = $conn->prepare("UPDATE agent2 SET plan = ? WHERE id = ? AND user_id = ?");
$stmt->bind_param("sii", $agent_name, $agent_id, $user_id);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Database update failed']);
}
