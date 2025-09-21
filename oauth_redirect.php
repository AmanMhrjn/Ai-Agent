<?php
session_start();

$platform = $_GET['platform'] ?? '';
$redirect_uri = urlencode('https://yourdomain.com/oauth_callback.php');

if($platform == 'facebook'){
    $app_id = 'YOUR_FB_APP_ID';
    $auth_url = "https://www.facebook.com/v17.0/dialog/oauth?client_id=$app_id&redirect_uri=$redirect_uri&scope=email,public_profile";
}elseif($platform=='instagram'){
    $app_id = 'YOUR_IG_APP_ID';
    $auth_url = "https://api.instagram.com/oauth/authorize?client_id=$app_id&redirect_uri=$redirect_uri&scope=user_profile,user_media&response_type=code";
}else{
    die("Invalid platform");
}

$_SESSION['oauth_platform'] = $platform;
header("Location: $auth_url");
exit;
