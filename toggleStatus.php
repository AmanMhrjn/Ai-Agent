<?php
    require_once 'config/database.php';
    $id = $_GET['id'] ?? 0;

    $sql = "UPDATE products SET status = IF(status=1, 0, 1) WHERE id = $id";
    $conn->query($sql);

    header("Location: productDashboard.php");
    exit;
?>