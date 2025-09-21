<?php
// Start session at the very top
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'config/database.php'; // PDO connection

// Use companyname from session instead of user_id
$companyname = $_SESSION['companyname'] ?? null;
$hasFreePlan = false;

if ($companyname) {
    // Check if this company already purchased an approved Free plan
    $stmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM payment_requests 
        WHERE company_name = :company_name
          AND LOWER(TRIM(plan)) = 'free' 
          AND LOWER(TRIM(status)) = 'approved'
    ");
    $stmt->execute(['company_name' => $companyname]);
    $count = $stmt->fetchColumn();

    if ($count > 0) {
        $hasFreePlan = true;
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agent | Pricing</title>
    <link rel="stylesheet" href="assets/css/navbar.css">
    <link rel="stylesheet" href="assets/css/login.css">
    <link rel="stylesheet" href="assets/css/footer.css">
    <link rel="stylesheet" href="assets/css/pricing.css">
</head>

<body>
<?php include_once 'assets/Component/navbar.php'; ?>

<h1 class="text-center">Pick the best plan for you</h1>

<div class="pricing-box-container">
    <?php if (!$hasFreePlan): ?>
        <!-- Free Plan -->
        <div class="pricing-box text-center">
            <h5>Free Add on Agent</h5>
            
            <p class="price"><sup>$</sup>0<sub>/mo</sub></p>
            <ul class="features-list">
                <li><strong>1</strong> Free Agent</li>
                <li><strong>5</strong> Team Members</li>
                <!-- <li><strong>50</strong> Personal Projects</li> -->
                <li><strong>5,000</strong> Messages</li>
            </ul>
            <a href="payment.php?plan=free&amount=0" class="btn-primary">Get Started</a>
        </div>
    <?php else: ?>
        <!-- Basic Plan instead of Free -->
        <div class="pricing-box text-center">
            <h5>Add on Agent</h5>
            <p class="price"><sup>$</sup>19<sub>/mo</sub></p>
            <ul class="features-list">
                <li><strong>3</strong> Agents</li>
                <li><strong>10</strong> Team Members</li>
                <!-- <li><strong>75</strong> Personal Projects</li> -->
                <li><strong>10,000</strong> Messages</li>
            </ul>
            <a href="payment.php?plan=basic&amount=19" class="btn-primary">Get Started</a>
        </div>
    <?php endif; ?>

    <!-- Premium Plan -->
    <div class="pricing-box pricing-box-bg-image text-center">
        <h5>Add on Message</h5>
        <p class="price"><sup>$</sup>39<sub>/mo</sub></p>
        <ul class="features-list">
            <li><strong>5</strong> Agents</li>
            <li><strong>20</strong> Team Members</li>
            <!-- <li><strong>100</strong> Personal Projects</li> -->
            <li><strong>15,000</strong> Messages</li>
        </ul>
        <a href="payment.php?plan=premium&amount=39" class="btn-primary">Get Started</a>
    </div>
<!-- 
    Platinum Plan
    <div class="pricing-box text-center gold">
        <h5>Platinum</h5>
        <p class="price"><sup>$</sup>89<sub>/mo</sub></p>
        <ul class="features-list">
            <li><strong>Unlimited</strong> Agents</li>
            <li><strong>50</strong> Team Members</li> -->
            <!-- <li><strong>500</strong> Personal Projects</li> -->
            <!-- <li><strong>50,000</strong> Messages</li>
        </ul>
        <a href="payment.php?plan=platinum&amount=89" class="btn-primary">Get Started</a>
    </div> -->
</div>

<?php include_once 'assets/Component/footer.php'; ?>
</body>
</html>
