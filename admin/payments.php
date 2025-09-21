<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit;
}
require_once '../config/database.php';

// Fetch payment requests with user email
$stmt = $pdo->query("SELECT pr.*, u.email 
                     FROM payment_requests pr 
                     JOIN users u ON pr.user_id = u.user_id 
                     ORDER BY pr.created_at DESC");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch token payments
$stmt = $pdo->query("SELECT tp.*, a.platform AS agent_platform
                     FROM token_payments tp
                     LEFT JOIN agents a ON tp.agent_id = a.id
                     ORDER BY tp.created_at DESC");
$payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>User Payment Requests</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f6f9;
            margin: 0;
        }

        .main-container {
            margin-left: 220px;
            padding: 30px;
            flex: 1;
            box-sizing: border-box;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
            color: #333;
            font-size: 1.8rem;
        }

        /* Table wrapper for horizontal scroll */
        .table-wrapper {
            overflow-x: auto;
            margin-bottom: 40px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: #fff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            min-width: 800px;
        }

        th,
        td {
            padding: 12px 15px;
            text-align: center;
            border-bottom: 1px solid #eee;
            word-break: break-word;
        }

        th {
            background-color: #007bff;
            color: #fff;
            font-weight: bold;
        }

        tr:hover {
            background-color: #f1f5ff;
        }

        td a {
            color: #007bff;
            text-decoration: none;
            font-weight: bold;
        }

        td a:hover {
            text-decoration: underline;
        }

        td img {
            max-width: 80px;
            border-radius: 6px;
        }

        .status-pending {
            color: #ffc107;
            font-weight: bold;
        }

        .status-approved {
            color: #28a745;
            font-weight: bold;
        }

        .status-rejected {
            color: #dc3545;
            font-weight: bold;
        }
    </style>
</head>

<body>
    <?php include_once 'component/sidebar.php' ?>
    <div class="main-container">

        <h2 class="header">User Payment Requests</h2>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>User</th>
                        <th>Plan</th>
                        <th>Amount</th>
                        <th>Method</th>
                        <!-- <th>Platform</th> -->
                        <th>Screenshot</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $count = 1; ?>
                    <?php foreach ($rows as $r): ?>
                        <tr>
                            <td data-label="ID"><?= $count++ ?></td>
                            <td data-label="User"><?= htmlspecialchars($r['company_name']) ?><br><?= htmlspecialchars($r['email']) ?></td>
                            <td data-label="Plan"><?= ucfirst($r['plan']) ?></td>
                            <td data-label="Amount"><?= number_format($r['amount'], 2) ?></td>
                            <td data-label="Method"><?= htmlspecialchars($r['payment_method']) ?></td>
                            <!-- <td data-label="Platform"><?= htmlspecialchars($r['platform']) ?></td> -->
                            <td data-label="Screenshot">
                                <?php if ($r['screenshot'] && file_exists(__DIR__ . '/../' . $r['screenshot'])): ?>
                                    <a href="<?= '../' . $r['screenshot'] ?>" target="_blank">
                                        <img src="<?= '../' . $r['screenshot'] ?>" alt="Screenshot">
                                    </a>
                                <?php else: ?>
                                    Not Found
                                <?php endif; ?>
                            </td>
                            <td data-label="Status" class="status-<?= strtolower($r['status']) ?>"><?= ucfirst($r['status']) ?></td>
                            <td data-label="Action">
                                <?php if (strtolower($r['status']) == 'pending'): ?>
                                    <a href="approve_payment.php?id=<?= $r['id'] ?>">Approve</a>
                                    <a href="reject_payment.php?id=<?= $r['id'] ?>">Reject</a>
                                <?php else: ?> - <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <h2 class="header">Token Payments</h2>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Company Name</th>
                        <th>Agent ID</th>
                        <th>Tokens</th>
                        <th>Platform</th>
                        <th>Amount (USD)</th>
                        <th>Screenshot</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $count = 1; ?>
                    <?php foreach ($payments as $pay): ?>
                        <tr>
                            <td data-label="ID"><?= $count++ ?></td>
                            <td data-label="Company Name"><?= htmlspecialchars($pay['company_name']) ?></td>
                            <td data-label="Agent ID"><?= $pay['agent_id'] ?></td>
                            <td data-label="Tokens"><?= $pay['tokens'] ?></td>
                            <td data-label="Platform"><?= htmlspecialchars($pay['platform']) ?></td>
                            <td data-label="Amount (USD)"><?= $pay['amount_usd'] ?> USD</td>
                            <td data-label="Screenshot">
                                <?php if ($pay['screenshot'] && file_exists(__DIR__ . '/../' . $pay['screenshot'])): ?>
                                    <a href="../<?= $pay['screenshot'] ?>" target="_blank">
                                        <img src="../<?= $pay['screenshot'] ?>" alt="Screenshot">
                                    </a>
                                <?php else: ?>
                                    Not Found
                                <?php endif; ?>
                            </td>
                            <td data-label="Status" class="status-<?= strtolower($pay['status']) ?>"><?= ucfirst($pay['status']) ?></td>
                            <td data-label="Action">
                                <?php if (strtolower($pay['status']) == 'pending'): ?>
                                    <form method="post" action="token_approve_payment.php" style="display:inline;">
                                        <input type="hidden" name="payment_id" value="<?= $pay['id'] ?>">
                                        <button type="submit" name="approve">Approve</button>
                                    </form>
                                    <form method="post" action="token_approve_payment.php" style="display:inline;">
                                        <input type="hidden" name="payment_id" value="<?= $pay['id'] ?>">
                                        <button type="submit" name="reject" style="background:red;color:white;">Reject</button>
                                    </form>
                                <?php else: ?> - <?php endif; ?>
                            </td>
                            
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

    </div>
</body>

</html>