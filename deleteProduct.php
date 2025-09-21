<?php
    require_once 'config/database.php';
    $id = $_GET['id'] ?? 0;

    $sql = "DELETE FROM products WHERE id = $id";
    $conn->query($sql);

    header("Location: agent.php");
    exit;
?>