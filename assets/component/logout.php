<?php
session_start();
$loggedOut = false;

if (isset($_SESSION['id'])) {
    session_unset();
    session_destroy();
    $loggedOut = true;
    header("refresh:3;url=../../index.php");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Logout</title>
    <link rel="stylesheet" href="../css/login.css">
    <style>
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f5f5;
        }

        .logout-message {
            background-color: #e0f8e9;
            border: 1px solid #07542bff;
            padding: 30px 50px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            color: #4dbf86ff;
            font-size: 1.2rem;
            text-align: center;
        }

        .logout-message span {
            display: block;
            margin-top: 10px;
            font-size: 0.95rem;
            color: #555;
        }
    </style>
</head>
<body>
    <div class="logout-message">
        <?php if ($loggedOut): ?>
            ✅ You have been successfully logged out.
            <span>Redirecting to home page in 3 seconds...</span>
        <?php else: ?>
            ⚠️ You are not logged in.
        <?php endif; ?>
    </div>
</body>
</html>
