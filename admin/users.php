<?php
session_start();
require_once '../config/database.php';

// Check if admin is logged in
if(!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true){
    header("Location: login.php");
    exit;
}

// Fetch all users
$users_list = $pdo->query("SELECT * FROM users ORDER BY user_id ASC")->fetchAll(PDO::FETCH_ASSOC);

// For each user, get their plans and remaining messages
foreach($users_list as &$user){
    $user_id = $user['user_id'];
    
    $stmt = $pdo->prepare("
        SELECT a.plan, (mb.total_messages - mb.messages_used) AS remaining
        FROM agents a
        LEFT JOIN message_balance mb ON a.payment_id = mb.payment_id
        WHERE a.user_id = ?
    ");
    $stmt->execute([$user_id]);
    $plans = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $plan_info = [];
    if($plans){
        foreach($plans as $p){
            $remaining = $p['remaining'] ?? 0; // ensure 0 if null
            $plan_info[] = $p['plan'] . " (" . $remaining . ")";
        }
    }

    $user['plan_info'] = !empty($plan_info) ? implode(", ", $plan_info) : '-';
}
unset($user); // break reference
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin - Users List</title>
    <style>
   body {
    font-family: Arial, sans-serif;
    margin: 0;
    padding: 0;
    background: #f4f6f9;
}

/* Sidebar adjustment if using fixed sidebar */
.main-container {
    margin-left: 220px; /* adjust if sidebar width changes */
    padding: 30px;
    flex: 1;
    transition: margin-left 0.3s;
}

/* Header */
.header {
    text-align: center;
    font-size: 1.8rem;
    color: #333;
    margin-bottom: 30px;
}

/* Table wrapper for horizontal scroll */
.table-wrapper {
    overflow-x: auto;
}

/* Table styles */
table {
    width: 100%;
    border-collapse: collapse;
    min-width: 900px; /* ensures scroll on smaller screens */
    background: #fff;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

th, td {
    padding: 12px 15px;
    text-align: left;
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

/* Action buttons */
.action-btn {
    padding: 6px 12px;
    margin-right: 5px;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    color: #fff;
    transition: all 0.3s;
}
.edit-btn { background: #28a745; }
.edit-btn:hover { background: #218838; }
.delete-btn { background: #dc3545; }
.delete-btn:hover { background: #c82333; }

/* Tablet: 768px - 1024px */
@media (max-width: 1024px) {
    .main-container { margin-left: 180px; padding: 20px; }
    th, td { padding: 10px 12px; font-size: 0.95rem; }
    .header { font-size: 1.6rem; }
    table { min-width: 800px; }
}

/* Mobile: 480px - 767px */
@media (max-width: 767px) {
    .main-container { margin-left: 0; padding: 15px; }
    table { min-width: 700px; }
    th, td { padding: 8px 10px; font-size: 0.9rem; }
    .header { font-size: 1.4rem; }
}

/* Small mobile: <480px - stack table rows */
@media (max-width: 479px) {
    table, thead, tbody, th, td, tr {
        display: block;
        width: 100%;
    }
    thead { display: none; }
    tr { 
        margin-bottom: 15px;
        background: #fff;
        box-shadow: 0 2px 6px rgba(0,0,0,0.1);
        border-radius: 8px;
        padding: 10px;
    }
    td {
        text-align: right;
        padding-left: 50%;
        position: relative;
        border-bottom: 1px solid #eee;
    }
    td::before {
        content: attr(data-label);
        position: absolute;
        left: 15px;
        width: 45%;
        font-weight: bold;
        text-align: left;
        color: #333;
    }
    .action-btn { padding: 5px 10px; font-size: 0.85rem; }
}
    </style>
</head>
<body>
<?php include_once 'component/sidebar.php'; ?>
<div class="main-container">
    <h2 class="header">Users List</h2>

    <?php
    if(isset($_SESSION['success'])){
        echo "<p style='color:green'>{$_SESSION['success']}</p>";
        unset($_SESSION['success']);
    }
    if(isset($_SESSION['error'])){
        echo "<p style='color:red'>{$_SESSION['error']}</p>";
        unset($_SESSION['error']);
    }
    ?>

    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Username</th>
                    <th>Company Name</th>
                    <th>Email</th>
                    <th>Password</th>
                    <th>Status</th>
                    <th>Plans (Remaining Messages)</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php $count = 1; ?>
            <?php foreach($users_list as $user): ?>
                <tr>
                    <td data-label="ID"><?= $count++ ?></td>
                    <td data-label="Username"><?= htmlspecialchars($user['username']) ?></td>
                    <td data-label="Company Name"><?= htmlspecialchars($user['company_name']) ?></td>
                    <td data-label="Email"><?= htmlspecialchars($user['email']) ?></td>
                    <td data-label="Password"><?= htmlspecialchars($user['password']) ?></td>
                    <td data-label="Status"><?= $user['status'] == 1 ? 'Active' : 'Inactive' ?></td>
                    <td data-label="Plans"><?= htmlspecialchars($user['plan_info']) ?></td>
                    <td data-label="Actions">
                        <!-- <button class="action-btn edit-btn" onclick="location.href='edit_user.php?id=<?= $user['user_id'] ?>'">Edit</button> -->
                        <button class="action-btn delete-btn" onclick="if(confirm('Delete this user?')) location.href='delete_user.php?id=<?= $user['user_id'] ?>'">Delete</button>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>
