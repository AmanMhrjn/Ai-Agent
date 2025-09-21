<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in
if (!isset($_SESSION['id']) || !isset($_SESSION['companyname'])) {
    header("Location: login.php");
    exit;
}

// Check if token parameters are provided
if (!isset($_GET['tokens']) || !isset($_GET['price']) || !isset($_GET['platform']) || !isset($_GET['plan'])) {
    header("Location: agent.php");
    exit;
}

$user_id = $_SESSION['id'];
$company_name = $_SESSION['companyname'];
$tokens = (int)$_GET['tokens'];
$price = (float)$_GET['price'];
$platform = $_GET['platform'];
$plan = $_GET['plan'];

// Handle payment form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Process payment here (this would integrate with your payment gateway)
    // For demonstration, we'll assume payment was successful
    
    // Record the transaction
    $stmt = $pdo->prepare("INSERT INTO payment_transactions (user_id, company_name, tokens, amount, platform, plan, status) VALUES (?, ?, ?, ?, ?, ?, 'completed')");
    $stmt->execute([$user_id, $company_name, $tokens, $price, $platform, $plan]);
    $transaction_id = $pdo->lastInsertId();
    
    // Update message balance
    $stmt = $pdo->prepare("INSERT INTO message_balance (user_id, company_name, platform, plan, total_messages, messages_used, last_updated) VALUES (?, ?, ?, ?, ?, 0, NOW())");
    $stmt->execute([$user_id, $company_name, $platform, $plan, $tokens]);
    
    // Redirect to success page
    header("Location: payment_success.php?transaction_id=" . $transaction_id);
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment - Token Purchase</title>
    <link rel="stylesheet" href="assets/css/navbar.css">
    <link rel="stylesheet" href="assets/css/main.css">
    <style>
        .payment-container {
            max-width: 600px;
            margin: 80px auto;
            padding: 20px;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .order-summary {
            background: #f9f9f9;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .payment-form {
            margin-top: 20px;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        
        input[type="text"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        .payment-details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        
        .btn-pay {
            background: #4CAF50;
            color: white;
            padding: 12px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            width: 100%;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <?php include_once 'assets/component/navbar.php'; ?>
    
    <div class="payment-container">
        <h1>Complete Your Purchase</h1>
        
        <div class="order-summary">
            <h2>Order Summary</h2>
            <p><strong>Tokens:</strong> <?= number_format($tokens) ?></p>
            <p><strong>Platform:</strong> <?= htmlspecialchars($platform) ?> (<?= htmlspecialchars($plan) ?>)</p>
            <p><strong>Total Amount:</strong> $<?= number_format($price, 2) ?></p>
        </div>
        
        <form method="POST" class="payment-form">
            <h2>Payment Details</h2>
            
            <div class="form-group">
                <label for="cardnumber">Card Number</label>
                <input type="text" id="cardnumber" name="cardnumber" placeholder="1234 5678 9012 3456" required>
            </div>
            
            <div class="payment-details">
                <div class="form-group">
                    <label for="expiry">Expiry Date</label>
                    <input type="text" id="expiry" name="expiry" placeholder="MM/YY" required>
                </div>
                
                <div class="form-group">
                    <label for="cvv">CVV</label>
                    <input type="text" id="cvv" name="cvv" placeholder="123" required>
                </div>
            </div>
            
            <div class="form-group">
                <label for="cardname">Name on Card</label>
                <input type="text" id="cardname" name="cardname" placeholder="John Doe" required>
            </div>
            
            <button type="submit" class="btn-pay">Pay $<?= number_format($price, 2) ?></button>
        </form>
    </div>
</body>
</html>