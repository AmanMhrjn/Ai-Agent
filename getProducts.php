<?php
require_once 'config/database.php';

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$sql = "SELECT id, product_name, offer_price, color, warranty, availability, description, photo_1, photo_2 FROM products LIMIT $limit OFFSET $offset";
$result = $conn->query($sql);

$products = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $products[] = $row;
    }
}

$countResult = $conn->query("SELECT COUNT(*) as total FROM products");
$total = $countResult->fetch_assoc()['total'];

echo json_encode([
    'products' => $products,
    'total' => $total,
    'limit' => $limit
]);
?>
