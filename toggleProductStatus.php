<?php
session_start();
require_once 'config/database.php'; // PDO connection

// Only logged-in users
if (!isset($_SESSION['id']) || !isset($_SESSION['companyname'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['id'];
$company_name = $_SESSION['companyname'];

// Check if product ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: agent.php?error=invalid_id");
    exit;
}

$product_id = (int) $_GET['id'];

try {
    // Fetch current product status (only for this company)
    $stmt = $pdo->prepare("SELECT status FROM products WHERE id = :id AND company_name = :company_name");
    $stmt->execute(['id' => $product_id, 'company_name' => $company_name]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
        header("Location: agent.php?error=not_found");
        exit;
    }

    // Toggle status
    $new_status = ($product['status'] == 1) ? 0 : 1;

    $stmt_update = $pdo->prepare("UPDATE products SET status = :status WHERE id = :id AND company_name = :company_name");
    $stmt_update->execute([
        'status' => $new_status,
        'id' => $product_id,
        'company_name' => $company_name
    ]);

    // Redirect back with success
    header("Location: agent.php?success=status_updated");
    exit;

} catch (PDOException $e) {
    // In case of DB error
    header("Location: agent.php?error=db_error");
    exit;
}
?>
