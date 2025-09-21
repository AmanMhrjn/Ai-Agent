<?php
session_start();
require_once '../config/database.php';

if(!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true){
    header("Location: login.php");
    exit;
}

if(!isset($_GET['id']) || !is_numeric($_GET['id'])){
    header("Location: users.php");
    exit;
}

$id = (int)$_GET['id'];

// Fetch user
$stmt = $pdo->prepare("SELECT * FROM users WHERE user_id=? LIMIT 1");
$stmt->execute([$id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
if(!$user){
    $_SESSION['error'] = "User not found.";
    header("Location: users.php");
    exit;
}

// Handle form submission
if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $username = trim($_POST['username']);
    $company_name = trim($_POST['company_name']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $status = isset($_POST['status']) ? 1 : 0;
    $tokens = (int)$_POST['tokens'];

    if(empty($username) || empty($email)){
        $error = "Username and email are required.";
    } else {
        $stmt = $pdo->prepare("UPDATE users SET username=?, company_name=?, email=?, password=?, status=?, tokens=? WHERE user_id=?");
        if($stmt->execute([$username,$company_name,$email,$password,$status,$tokens,$id])){
            $_SESSION['success'] = "User updated successfully.";
            header("Location: users.php");
            exit;
        } else {
            $error = "Failed to update user.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit User</title>
    <style>
       body {
    font-family: Arial, sans-serif;
    background: #f4f6f9;
    margin: 0;
    padding: 20px;
    display: flex;
    justify-content: center;
    align-items: flex-start;
    min-height: 100vh;
    box-sizing: border-box;
}

.form-container {
    background: #fff;
    padding: 30px 40px;
    border-radius: 12px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    max-width: 500px;
    width: 100%;
    box-sizing: border-box;
}

h2 {
    text-align: center;
    margin-bottom: 25px;
    color: #333;
    font-size: 1.5rem;
}

form {
    display: flex;
    flex-direction: column;
}

label {
    font-weight: bold;
    margin-bottom: 5px;
    color: #555;
}

input[type="text"],
input[type="email"],
input[type="number"] {
    padding: 12px;
    margin-bottom: 20px;
    border: 1px solid #ccc;
    border-radius: 6px;
    font-size: 14px;
    width: 100%;
    box-sizing: border-box;
}

input[type="checkbox"] {
    margin-right: 8px;
}

button {
    padding: 12px;
    border: none;
    border-radius: 6px;
    background-color: #007bff;
    color: #fff;
    font-size: 16px;
    cursor: pointer;
    transition: background 0.3s ease;
}

button:hover {
    background-color: #0056b3;
}

.error {
    color: red;
    margin-bottom: 10px;
    text-align: center;
}

/* Tablet: Medium Screens */
@media (max-width: 1024px) {
    .form-container {
        padding: 25px 30px;
    }
    h2 {
        font-size: 1.4rem;
    }
    input, button {
        font-size: 0.95rem;
        padding: 10px;
    }
}

/* Mobile: Small Screens */
@media (max-width: 768px) {
    body {
        padding: 15px;
    }
    .form-container {
        padding: 20px 20px;
        margin-top: 20px;
    }
    h2 {
        font-size: 1.3rem;
    }
    input, button {
        font-size: 0.9rem;
        padding: 10px;
    }
}

/* Extra Small Mobile */
@media (max-width: 480px) {
    .form-container {
        padding: 15px 15px;
    }
    h2 {
        font-size: 1.2rem;
    }
    input, button {
        font-size: 0.85rem;
        padding: 8px;
    }
}
    </style>
</head>
<body>
<div class="form-container">
    <h2>Edit User</h2>
    <?php if(!empty($error)) echo "<p class='error'>{$error}</p>"; ?>
    <form method="POST">
        <label>Username</label>
        <input type="text" name="username" value="<?= htmlspecialchars($user['username']) ?>" required>

        <label>Company Name</label>
        <input type="text" name="company_name" value="<?= htmlspecialchars($user['company_name']) ?>">

        <label>Email</label>
        <input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>

        <label>Password</label>
        <input type="text" name="password" value="<?= htmlspecialchars($user['password']) ?>">

        <label>Tokens</label>
        <input type="number" name="tokens" value="<?= $user['tokens'] ?>">

        <label>Status</label>
        <input type="checkbox" name="status" <?= $user['status']==1 ? 'checked' : '' ?>> Active

        <button type="submit">Update User</button>
    </form>
</div>
</body>
</html>
