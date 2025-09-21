<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in
if (!isset($_SESSION['id']) || !isset($_SESSION['companyname'])) {
    header("Location: login.php");
    exit;
}

// Check if transaction ID is provided
if (!isset($_GET['transaction_id'])) {
    header("Location: agent.php");
    exit;
}

$transaction_id = $_GET['transaction_id'];
$user_id = $_SESSION['id'];

// Get transaction details
$stmt = $pdo->prepare("SELECT * FROM payment_transactions WHERE id = ? AND user_id = ?");
$stmt->execute([$transaction_id, $user_id]);
$transaction = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$transaction) {
    header("Location: agent.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Successful</title>
    <link rel="stylesheet" href="assets/css/navbar.css">
    <link rel="stylesheet" href="assets/css/main.css">
    <style>
        .success-container {
            max-width: 600px;
            margin: 80px auto;
            padding: 30px;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .success-icon {
            font-size: 60px;
            color: #4CAF50;
            margin-bottom: 20px;
        }
        
        .transaction-details {
            background: #f9f9f9;
            padding: 20px;
            border-radius: 5px;
            margin: 20px 0;
            text-align: left;
        }
        
        .btn-back {
            display: inline-block;
            background: #4CAF50;
            color: white;
            padding: 12px 25px;
            text-decoration: none;
            border-radius: 4px;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <?php include_once 'assets/component/navbar.php'; ?>
    
    <div class="success-container">
        <div class="success-icon">✓</div>
        <h1>Payment Successful!</h1>
        <p>Your token purchase has been completed successfully.</p>
        
        <div class="transaction-details">
            <h2>Transaction Details</h2>
            <p><strong>Transaction ID:</strong> <?= $transaction['id'] ?></p>
            <p><strong>Tokens Purchased:</strong> <?= number_format($transaction['tokens']) ?></p>
            <p><strong>Amount Paid:</strong> $<?= number_format($transaction['amount'], 2) ?></p>
            <p><strong>Date:</strong> <?= $transaction['created_at'] ?></p>
        </div>
        
        <p>Your tokens have been added to your account balance.</p>
        <a href="agent.php" class="btn-back">Back to Dashboard</a>
    </div>
</body>
</html>  