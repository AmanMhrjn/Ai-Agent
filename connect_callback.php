<?php
session_start();
require_once __DIR__ . '/config/database.php';

$code = $_GET['code'] ?? null;
$state = json_decode(base64_decode($_GET['state']), true);
$agent_id = $state['agent_id'];
$platform = $state['platform'];

// Facebook App credentials
$app_id = 'YOUR_APP_ID';
$app_secret = 'YOUR_APP_SECRET';
$redirect_uri = 'https://yourdomain.com/connect_callback.php';

// Exchange code for access token
$token_url = "https://graph.facebook.com/v17.0/oauth/access_token?client_id={$app_id}&redirect_uri={$redirect_uri}&client_secret={$app_secret}&code={$code}";
$resp = json_decode(file_get_contents($token_url), true);
$access_token = $resp['access_token'] ?? null;
if (!$access_token) die('Error getting access token');

// Fetch pages
$pages_url = "https://graph.facebook.com/me/accounts?access_token={$access_token}";
$pages = json_decode(file_get_contents($pages_url), true)['data'] ?? [];

foreach ($pages as $page) {
    $page_id = $page['id'];
    $page_name = $page['name'];
    $page_url = $page['link'] ?? "https://facebook.com/{$page_id}";

    // Instagram special handling
    if ($platform === 'Instagram' && !empty($page['instagram_business_account']['id'])) {
        $insta_id = $page['instagram_business_account']['id'];
        $insta_info_url = "https://graph.facebook.com/{$insta_id}?fields=username&access_token={$access_token}";
        $insta_info = json_decode(file_get_contents($insta_info_url), true);
        if (!empty($insta_info['username'])) {
            $page_name = $insta_info['username'];
            $page_url = "https://www.instagram.com/{$insta_info['username']}/";
            $page_id = $insta_id;
        }
    }

    // Insert / update
    $stmt = $pdo->prepare("
        INSERT INTO agent_connections (user_id, agent_id, platform, page_id, page_name, link, access_token)
        VALUES (:user_id, :agent_id, :platform, :page_id, :page_name, :link, :access_token)
        ON DUPLICATE KEY UPDATE access_token = VALUES(access_token), page_name = VALUES(page_name), link = VALUES(link)
    ");
    $stmt->execute([
        ':user_id'=>$_SESSION['id'],
        ':agent_id'=>$agent_id,
        ':platform'=>$platform,
        ':page_id'=>$page_id,
        ':page_name'=>$page_name,
        ':link'=>$page_url,
        ':access_token'=>$access_token
    ]);
}

header("Location: manageagents.php?success=1");
exit;
