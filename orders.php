<?php
session_start();
require_once 'config/database.php'; // adjust path if needed

// Make sure the user is logged in
$company_name = $_SESSION['companyname'] ?? '';
if (!$company_name) {
    header("Location: assets/component/login.php");
    exit;
}

// ✅ Handle status update request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id'], $_POST['new_status'])) {
    $orderId = intval($_POST['order_id']);
    $newStatus = $_POST['new_status'];

    if (in_array($newStatus, ['Delivered', 'Cancelled'])) {
        $updateSql = "UPDATE orders SET status = :status WHERE id = :id AND company_name = :company_name";
        $updateStmt = $pdo->prepare($updateSql);
        $updateStmt->execute([
            ':status' => $newStatus,
            ':id' => $orderId,
            ':company_name' => $company_name
        ]);
    }
    // Refresh to see updated status
    header("Location: my_orders.php");
    exit;
}

// ✅ Pagination settings
$limit = 15;

// Helper function for pagination
function fetchOrders($pdo, $company_name, $status, $page, $limit)
{
    $offset = ($page - 1) * $limit;

    $sql = "SELECT * FROM orders WHERE company_name = :company_name AND status = :status 
            ORDER BY Date DESC LIMIT :limit OFFSET :offset";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':company_name', $company_name, PDO::PARAM_STR);
    $stmt->bindValue(':status', $status, PDO::PARAM_STR);
    $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
    $stmt->execute();
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // count total
    $countSql = "SELECT COUNT(*) FROM orders WHERE company_name = :company_name AND status = :status";
    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute([':company_name' => $company_name, ':status' => $status]);
    $totalRows = $countStmt->fetchColumn();
    $totalPages = ceil($totalRows / $limit);

    return [$orders, $totalPages];
}

// ✅ Get page numbers (separate for each table)
$pendingPage   = isset($_GET['pending_page'])   ? max(1, intval($_GET['pending_page']))   : 1;
$deliveredPage = isset($_GET['delivered_page']) ? max(1, intval($_GET['delivered_page'])) : 1;
$cancelledPage = isset($_GET['cancelled_page']) ? max(1, intval($_GET['cancelled_page'])) : 1;

// ✅ Fetch orders for each section
list($pendingOrders, $pendingTotal)     = fetchOrders($pdo, $company_name, "Pending", $pendingPage, $limit);
list($deliveredOrders, $deliveredTotal) = fetchOrders($pdo, $company_name, "Delivered", $deliveredPage, $limit);
list($cancelledOrders, $cancelledTotal) = fetchOrders($pdo, $company_name, "Cancelled", $cancelledPage, $limit);

// ✅ Render table with pagination
function renderTable($title, $orders, $isPending = false, $page = 1, $totalPages = 1, $param = '')
{
    if (!$orders) {
        echo "<p style='text-align:center; margin:20px 0;'>❌ No $title orders found.</p>";
        return;
    }
?>
    <h3 style="margin-top:30px; text-align:center;"><?= $title ?> Orders</h3>
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>SN</th>
                    <th>Order Code</th>
                    <th>Date</th>
                    <th>Product</th>
                    <th>Quantity</th>
                    <th>Size</th>
                    <th>Color</th>
                    <th>Total Price</th>
                    <th>Platform</th>
                    <th>Status</th>
                    <?php if ($isPending): ?>
                        <th>Action</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php $sn = ($page - 1) * 15 + 1; ?>
                <?php foreach ($orders as $order): ?>
                    <tr>
                        <td data-label="SN"><?= $sn++ ?></td>
                        <td data-label="Order Code"><?= htmlspecialchars($order['order_code']) ?></td>
                        <td data-label="Date"><?= htmlspecialchars($order['Date']) ?></td>
                        <td data-label="Product"><?= htmlspecialchars($order['Product Name']) ?></td>
                        <td data-label="Quantity"><?= htmlspecialchars($order['Quantity']) ?></td>
                        <td data-label="Size"><?= htmlspecialchars($order['Size']) ?></td>
                        <td data-label="Color"><?= htmlspecialchars($order['Color']) ?></td>
                        <td data-label="Total Price"><?= htmlspecialchars($order['Total Price']) ?></td>
                        <td data-label="Platform"><?= htmlspecialchars($order['platform'] ?? '-') ?></td>
                        <td data-label="Status">
                            <span class="status <?= strtolower($order['status']) ?>">
                                <?= htmlspecialchars($order['status']) ?>
                            </span>
                        </td>
                        <?php if ($isPending): ?>
                            <td data-label="Action">
                                <form method="post" style="display:inline;">
                                    <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                                    <input type="hidden" name="new_status" value="Delivered">
                                    <button type="submit" class="btn-deliver">Deliver</button>
                                </form>
                                <form method="post" style="display:inline;">
                                    <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                                    <input type="hidden" name="new_status" value="Cancelled">
                                    <button type="submit" class="btn-cancel">Cancel</button>
                                </form>
                            </td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <!-- ✅ Pagination Links -->
    <?php if ($totalPages > 1): ?>
        <div class="pagination">
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <a href="?<?= $param ?>_page=<?= $i ?>" class="<?= $i == $page ? 'active' : '' ?>">
                    <?= $i ?>
                </a>
            <?php endfor; ?>
        </div>
    <?php endif; ?>
<?php
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders</title>
    <link rel="stylesheet" href="assets/css/navbar.css">
    <link rel="stylesheet" href="assets/css/footer.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f6f9;
            margin: 0;
            padding-top: 80px;
            /* space for fixed navbar if any */
        }

        .container {
            margin: 20px auto;
            max-width: 1100px;
            background: #fff;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        h2 {
            text-align: center;
            margin-bottom: 25px;
            font-size: 1.8rem;
        }

        h3 {
            margin-top: 40px;
            font-size: 1.3rem;
            color: #333;
        }

        /* Table */
        .table-responsive {
            max-height: 450px;
            overflow-y: auto;
            margin-top: 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead {
            position: sticky;
            top: 0;
            background: #007bff;
            color: #fff;
            z-index: 10;
        }

        th,
        td {
            padding: 12px 10px;
            text-align: center;
            border-bottom: 1px solid #eee;
            font-size: 0.95rem;
        }

        tr:nth-child(even) {
            background: #f9f9f9;
        }

        .status {
            padding: 5px 10px;
            border-radius: 6px;
            font-weight: bold;
            font-size: 0.85rem;
        }

        .pending {
            background: #fff3cd;
            color: #856404;
        }

        .delivered {
            background: #d4edda;
            color: #155724;
        }

        .cancelled {
            background: #f8d7da;
            color: #721c24;
        }

        /* Buttons */
        .btn-deliver,
        .btn-cancel {
            border: none;
            padding: 6px 12px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.85rem;
            margin: 2px;
            transition: all 0.2s ease;
        }

        .btn-deliver {
            background: #28a745;
            color: #fff;
        }

        .btn-deliver:hover {
            background: #218838;
        }

        .btn-cancel {
            background: #dc3545;
            color: #fff;
        }

        .btn-cancel:hover {
            background: #c82333;
        }

        /* Pagination */
        .pagination {
            margin: 15px 0;
            text-align: center;
        }

        .pagination a {
            margin: 0 5px;
            padding: 6px 12px;
            border: 1px solid #007bff;
            color: #007bff;
            border-radius: 6px;
            text-decoration: none;
            transition: 0.2s;
        }

        .pagination a.active {
            background: #007bff;
            color: #fff;
        }

        .pagination a:hover {
            background: #0056b3;
            color: #fff;
        }

        /* Mobile & Tablet */
        @media (max-width: 1024px) {

            th,
            td {
                font-size: 0.85rem;
                padding: 10px 6px;
            }
        }

        @media (max-width: 768px) {

            .table-responsive table,
            .table-responsive thead,
            .table-responsive tbody,
            .table-responsive th,
            .table-responsive td,
            .table-responsive tr {
                display: block;
            }

            thead tr {
                display: none;
            }

            tr {
                margin-bottom: 15px;
                border: 1px solid #ddd;
                border-radius: 8px;
                padding: 10px;
                background: #fff;
            }

            td {
                text-align: right;
                padding-left: 50%;
                position: relative;
                border: none;
                border-bottom: 1px solid #eee;
            }

            td::before {
                content: attr(data-label);
                position: absolute;
                left: 15px;
                width: 45%;
                text-align: left;
                font-weight: bold;
                color: #555;
                font-size: 0.9rem;
            }

            .btn-deliver,
            .btn-cancel {
                width: 100%;
                margin: 4px 0;
            }
        }
    </style>
</head>

<body>
    <?php include_once 'assets/Component/navbar.php'; ?>

    <div class="container">
        <h2>📦 My Orders - <?= htmlspecialchars($company_name) ?></h2>

        <!-- Pending Orders with Actions + Pagination -->
        <?php renderTable("Pending", $pendingOrders, true, $pendingPage, $pendingTotal, "pending"); ?>

        <!-- Delivered Orders + Pagination -->
        <?php renderTable("Delivered", $deliveredOrders, false, $deliveredPage, $deliveredTotal, "delivered"); ?>

        <!-- Cancelled Orders + Pagination -->
        <?php renderTable("Cancelled", $cancelledOrders, false, $cancelledPage, $cancelledTotal, "cancelled"); ?>
    </div>

    <?php include_once 'assets/Component/footer.php'; ?>
</body>

</html>