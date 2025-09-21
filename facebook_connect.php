<?php
session_start();
require_once __DIR__ . '/config/database.php';

$agent_id = (int)$_GET['agent_id'];
$platform = $_GET['platform'] ?? 'Facebook';

// Validate agent platform
$stmt = $pdo->prepare("SELECT platform FROM agents WHERE id = :id AND user_id = :user_id");
$stmt->execute([':id'=>$agent_id, ':user_id'=>$_SESSION['id']]);
$agent = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$agent || strtolower($agent['platform']) !== strtolower($platform)) die('Platform mismatch');

// Facebook App credentials
$app_id = 'YOUR_APP_ID';
$redirect_uri = 'https://yourdomain.com/connect_callback.php';
$state = base64_encode(json_encode(['agent_id'=>$agent_id,'platform'=>$platform]));

$scope = 'pages_show_list,pages_read_engagement';
if ($platform === 'Instagram') $scope .= ',instagram_basic';

$oauth_url = "https://www.facebook.com/v17.0/dialog/oauth?client_id={$app_id}&redirect_uri={$redirect_uri}&state={$state}&scope={$scope}";
header("Location: $oauth_url");
exit;
