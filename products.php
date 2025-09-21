<?php
session_start();
require_once 'config/database.php'; // defines $pdo

// --- Only logged-in users ---
if (!isset($_SESSION['id']) || !isset($_SESSION['companyname'])) {
    echo '<!DOCTYPE html>
    <html lang="en">
    <head><meta charset="UTF-8"><title>Access Denied</title></head>
    <body><h2>Please login to access dashboard</h2></body></html>';
    exit;
}

$user_id = $_SESSION['id'];
$company_name = $_SESSION['companyname'];

// --- Pagination (products) ---
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$records_per_page = 10;
$offset = ($page - 1) * $records_per_page;

// --- Count products ---
$stmt_total = $pdo->prepare("SELECT COUNT(*) AS total FROM products WHERE company_name = :company_name");
$stmt_total->execute(['company_name' => $company_name]);
$total_row = $stmt_total->fetch(PDO::FETCH_ASSOC);
$total_records = $total_row['total'] ?? 0;
$total_pages = ceil($total_records / $records_per_page);

// --- Fetch products ---
$stmt = $pdo->prepare("SELECT * FROM products WHERE company_name = :company_name ORDER BY id DESC LIMIT :limit OFFSET :offset");
$stmt->bindValue(':company_name', $company_name, PDO::PARAM_STR);
$stmt->bindValue(':limit', $records_per_page, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI || Products</title>
    <link rel="stylesheet" href="assets/css/navbar.css">
    <link rel="stylesheet" href="assets/css/footer.css">
    <style>
        .agent-container {
            padding: 15px 30px;
        }

        .agent-product-table-section {
            margin-top: 40px;
            
        }

        /* Table Styling */
        .agent-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 1200px;
            overflow-x: auto;
        }

        .agent-table th,
        .agent-table td {
            border: 1px solid #ddd;
            padding: 8px 12px;
            text-align: center;
            vertical-align: middle;
            white-space: nowrap;
        }

        .agent-table th {
            background-color: #f4f6f8;
            font-weight: bold;
            position: sticky;
            top: 0;
            z-index: 2;
        }

        /* Column-specific widths */
        .agent-table th:nth-child(1),
        .agent-table td:nth-child(1) {
            width: 50px;
        }

        .agent-table th:nth-child(2),
        .agent-table td:nth-child(2) {
            width: 150px;
        }

        .agent-table th:nth-child(3),
        .agent-table td:nth-child(3) {
            width: 120px;
        }

        .agent-table th:nth-child(4),
        .agent-table td:nth-child(4) {
            width: 200px;
        }

        .agent-table th:nth-child(5),
        .agent-table td:nth-child(5) {
            width: 100px;
        }

        .agent-table th:nth-child(6),
        .agent-table td:nth-child(6) {
            width: 100px;
        }

        .agent-table th:nth-child(7),
        .agent-table td:nth-child(7) {
            width: 120px;
        }

        .agent-table th:nth-child(8),
        .agent-table td:nth-child(8) {
            width: 130px;
        }

        .agent-table th:nth-child(9),
        .agent-table td:nth-child(9) {
            width: 300px;
            text-align: left;
        }

        .agent-table th:nth-child(10),
        .agent-table td:nth-child(10) {
            width: 100px;
        }

        .agent-table th:nth-child(11),
        .agent-table td:nth-child(11) {
            width: 180px;
        }

        /* Image styling */
        .agent-table img {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 4px;
            margin-right: 5px;
        }

        /* Buttons in actions column */
        .agent-table td button {
            margin: 2px;
            padding: 5px 10px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        .agent-table td button:nth-child(1) {
            background-color: #0275d8;
            color: white;
        }

        .agent-table td button:nth-child(2) {
            background-color: #d9534f;
            color: white;
        }

        .agent-table td button:nth-child(3) {
            background-color: #5cb85c;
            color: white;
        }

        /* Pagination */
        .pagination {
            text-align: center;
            margin-top: 20px;
        }

        .pagination a {
            margin: 0 5px;
            text-decoration: none;
            color: #0275d8;
        }

        .pagination a.active {
            font-weight: bold;
            text-decoration: underline;
        }

        /* Action buttons below */
        .action-btns {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 20px;
            justify-content: center;
        }

        .action-btns a {
            padding: 10px 20px;
            background: #5cb85c;
            color: #fff;
            text-decoration: none;
            border-radius: 4px;
        }
    </style>
</head>

<body>
    <?php include_once 'assets/component/navbar.php'; ?>
    <div class="agent-container">
        <div class="agent-product-table-section">
            <h2>Product Details</h2>
            <table class="agent-table">
                <thead>
                    <tr>
                        <th>Id</th>
                        <th>Company</th>
                        <th>Images</th>
                        <th>Name</th>
                        <th>Offer Price</th>
                        <th>Color</th>
                        <th>Warranty</th>
                        <th>Availability</th>
                        <th>Description</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!$products): ?>
                        <tr>
                            <td colspan="11" style="text-align:center;">No products found.</td>
                        </tr>
                    <?php else: $counter = $offset + 1;
                        foreach ($products as $row): ?>
                            <tr>
                                <td><?= $counter++ ?></td>
                                <td><?= htmlspecialchars($row['company_name']) ?></td>
                                <td>
                                    <?php if ($row['photo_1']): ?><img src="<?= htmlspecialchars($row['photo_1']) ?>"><?php endif; ?>
                                    <?php if ($row['photo_2']): ?><img src="<?= htmlspecialchars($row['photo_2']) ?>"><?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($row['product_name']) ?></td>
                                <td><?= htmlspecialchars($row['offer_price']) ?></td>
                                <td><?= htmlspecialchars($row['color']) ?></td>
                                <td><?= htmlspecialchars($row['warranty']) ?></td>
                                <td><?= htmlspecialchars($row['availability']) ?></td>
                                <td><?= htmlspecialchars($row['description']) ?></td>
                                <td><?= $row['status'] == 1 ? 'Enabled' : 'Disabled' ?></td>
                                <td>
                                    <button onclick="location.href='updateProduct.php?id=<?= $row['id'] ?>'">Edit</button>
                                    <button onclick="deleteProduct(<?= $row['id'] ?>)">Delete</button>
                                    <button onclick="toggleStatus(<?= $row['id'] ?>)"><?= $row['status'] == 1 ? 'Disable' : 'Enable' ?></button>
                                </td>
                            </tr>
                        <?php endforeach;
                    endif; ?>
                </tbody>
            </table>

            <!-- Pagination -->
            <div class="pagination">
                <?php for ($p = 1; $p <= $total_pages; $p++): ?>
                    <a href="?page=<?= $p ?>" class="<?= $p == $page ? 'active' : '' ?>"><?= $p ?></a>
                <?php endfor; ?>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="action-btns">
            <a href="dashboard.php">Dashboard</a>
            <a href="addProduct.php">Add Products</a>
        </div>
    </div>

    <script>
        function deleteProduct(id) {
            if (confirm('Are you sure you want to delete this product?')) {
                window.location.href = 'deleteProduct.php?id=' + id;
            }
        }

        function toggleStatus(id) {
            window.location.href = 'toggleProductStatus.php?id=' + id;
        }
    </script>

    <?php include_once 'assets/component/footer.php'; ?>
</body>

</html>
