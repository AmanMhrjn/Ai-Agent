<?php
$host = "localhost";
$db   = "aiagent";
$user = "root";
$pass = "aman";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Can't connect to DB: " . $e->getMessage());
}
?>
