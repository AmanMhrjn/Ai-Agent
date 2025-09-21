<?php
session_start();
require_once 'config/database.php';

header('Content-Type: application/json');

// Check login
if(!isset($_SESSION['id']) || !isset($_SESSION['companyname'])) {
    echo json_encode(['success'=>false, 'message'=>'Login required']);
    exit;
}

$user_id = $_SESSION['id'];
$company_name = $_SESSION['companyname'];

// Generate a unique agent page
$agent_id = uniqid(); // unique identifier
$agent_name = "Agent " . $agent_id;
$agent_link = "agent.php?agent_id=" . $agent_id;

// Insert into agent2 table
$stmt = $conn->prepare("INSERT INTO agent2 (user_id, plan, company_name) VALUES (?, ?, ?)");
if(!$stmt){
    echo json_encode(['success'=>false, 'message'=>'Prepare failed: ' . $conn->error]);
    exit;
}

$stmt->bind_param("iss", $user_id, $agent_name, $company_name);

if($stmt->execute()) {
    echo json_encode(['success'=>true, 'agentName'=>$agent_name, 'link'=>$agent_link]);
} else {
    echo json_encode(['success'=>false, 'message'=>'Execute failed: ' . $stmt->error]);
}
?>
