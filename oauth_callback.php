<?php
session_start();
require_once 'config/database.php';

$platform = $_SESSION['oauth_platform'] ?? '';
$code = $_GET['code'] ?? '';
$user_id = $_SESSION['id'] ?? 0;

if(!$platform || !$code) die("Invalid request");

$account_id = '';
$access_token = '';

if($platform == 'facebook'){
    $app_id = 'YOUR_FB_APP_ID';
    $app_secret = 'YOUR_FB_APP_SECRET';
    $redirect_uri = urlencode('https://yourdomain.com/oauth_callback.php');

    $token_resp = file_get_contents("https://graph.facebook.com/v17.0/oauth/access_token?client_id=$app_id&redirect_uri=$redirect_uri&client_secret=$app_secret&code=$code");
    $token_data = json_decode($token_resp,true);
    $access_token = $token_data['access_token'] ?? '';

    $user_info = file_get_contents("https://graph.facebook.com/me?access_token=$access_token");
    $user_info = json_decode($user_info,true);
    $account_id = $user_info['id'] ?? '';

}elseif($platform=='instagram'){
    $app_id = 'YOUR_IG_APP_ID';
    $app_secret = 'YOUR_IG_APP_SECRET';
    $redirect_uri = 'https://yourdomain.com/oauth_callback.php';

    $post_fields = [
        'client_id'=>$app_id,
        'client_secret'=>$app_secret,
        'grant_type'=>'authorization_code',
        'redirect_uri'=>$redirect_uri,
        'code'=>$code
    ];

    $ch = curl_init('https://api.instagram.com/oauth/access_token');
    curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
    curl_setopt($ch,CURLOPT_POSTFIELDS,$post_fields);
    $resp = curl_exec($ch);
    curl_close($ch);

    $data = json_decode($resp,true);
    $access_token = $data['access_token'] ?? '';
    $account_id = $data['user_id'] ?? '';
}

// Insert into DB with duplicate prevention
try{
    $stmt = $pdo->prepare("INSERT INTO linked_accounts (user_id, platform, account_id, token) VALUES (?,?,?,?)");
    $stmt->execute([$user_id, $platform, $account_id, $access_token]);
    $_SESSION['flash'] = "✅ $platform account linked successfully!";
}catch(PDOException $e){
    if($e->getCode()==23000){
        $_SESSION['flash'] = "❌ This account is already linked";
    }else{
        $_SESSION['flash'] = "❌ Error: ".$e->getMessage();
    }
}

header("Location: dashboard.php");
exit;
