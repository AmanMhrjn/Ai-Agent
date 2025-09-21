<?php
session_start();
require_once '../config/database.php'; // <-- Make sure $pdo is defined in this file

// Check if admin is logged in
if(!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true){
    header("Location: /admin/login.php");
    exit;
}

// Fetch admin info (optional: for profile display)
$admin_id = $_SESSION['admin_id'] ?? 0;
$admin_info = null;
if($admin_id){
    $stmt = $pdo->prepare("SELECT * FROM admins WHERE id=? LIMIT 1");
    $stmt->execute([$admin_id]);
    $admin_info = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Fetch all admins for list display
$admins_list = $pdo->query("SELECT * FROM admins ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard</title>
    <style>
       body {
    font-family: Arial, sans-serif;
    margin: 0;
    padding: 0;
    display: flex;
    flex-wrap: nowrap;
    background: #f4f6f9;
}

/* Sidebar (imported) */
.sidebar {
    width: 220px;
    min-height: 100vh;
    background: #343a40;
    color: #fff;
    position: fixed;
}
.main-content {
    margin-left: 220px;
    padding: 30px;
    flex: 1;
    box-sizing: border-box;
}

/* Profile card */
.profile {
    background: #fff;
    padding: 20px;
    border-radius: 12px;
    box-shadow: 0 4px 10px rgba(0,0,0,0.1);
    margin-bottom: 20px;
}

/* Cards (optional dashboard links) */
.card-container {
    display: flex;
    flex-wrap: wrap;
    gap: 20px;
    margin-bottom: 30px;
}
.card {
    flex: 1 1 200px;
    background: #fff;
    padding: 20px;
    border-radius: 12px;
    box-shadow: 0 4px 10px rgba(0,0,0,0.1);
    text-align: center;
    cursor: pointer;
    transition: all 0.3s ease;
}
.card:hover {
    transform: translateY(-5px);
    background: #007bff;
    color: #fff;
}

/* Admins table */
table {
    width: 100%;
    border-collapse: collapse;
    background: #fff;
    border-radius: 12px;
    overflow: hidden;
    margin-top: 20px;
}
table th, table td {
    padding: 12px;
    border-bottom: 1px solid #ddd;
    text-align: left;
    word-wrap: break-word;
}
table th {
    background: #007bff;
    color: #fff;
}
table td .action-btn {
    padding: 6px 12px;
    margin-right: 5px;
    background: #007bff;
    color: #fff;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.3s;
}
table td .action-btn:hover {
    background: #0056b3;
}

/* Responsive: Tablets */
@media (max-width: 1024px) {
    .main-content {
        margin-left: 180px;
        padding: 20px;
    }
    .profile {
        padding: 15px;
    }
    .card {
        flex: 1 1 45%;
        padding: 15px;
    }
    table th, table td {
        padding: 10px;
        font-size: 14px;
    }
    .card-container {
        gap: 15px;
    }
}

/* Responsive: Mobile */
@media (max-width: 768px) {
    body {
        flex-direction: column;
    }
    .sidebar {
        width: 100%;
        position: relative;
        min-height: auto;
    }
    .main-content {
        margin-left: 0;
        padding: 15px;
    }
    .card-container {
        flex-direction: column;
    }
    .card {
        flex: 1 1 100%;
    }
    table, thead, tbody, th, td, tr {
        display: block;
        width: 100%;
    }
    table th {
        display: none;
    }
    table td {
        display: flex;
        justify-content: space-between;
        padding: 10px;
        border-bottom: 1px solid #ddd;
    }
    table td::before {
        content: attr(data-label);
        font-weight: bold;
        text-transform: uppercase;
    }
    table td .action-btn {
        margin: 5px 0 0 0;
        width: 48%;
    }
}

/* Extra small devices */
@media (max-width: 480px) {
    .profile {
        padding: 10px;
    }
    .card {
        padding: 12px;
        font-size: 14px;
    }
    table td {
        flex-direction: column;
        align-items: flex-start;
    }
    table td .action-btn {
        width: 100%;
    }
}
    </style>
</head>
<body>
<?php include_once'component/sidebar.php'?>
<div class="main-content">
    <div class="profile">
        <h2 class="header">Welcome, <?= htmlspecialchars($admin_info['username'] ?? 'Admin') ?>!</h2>
        <?php if($admin_info): ?>
            <p><strong>Username:</strong> <?= htmlspecialchars($admin_info['username']) ?></p>
            <p><strong>Password:</strong> <?= htmlspecialchars($admin_info['password'] ?? '-') ?></p>
        <?php endif; ?>
    </div>

    <!-- <div class="card-container">
        <div class="card" onclick="location.href='manage_qr.php'">📷 Manage QR Details</div>
        <div class="card" onclick="location.href='payments.php'">💳 Payment Requests</div>
        <div class="card" onclick="location.href='users.php'">👥 Users List</div>
        <div class="card" onclick="location.href='manage_users.php'">🛠 Manage Users</div>
    </div> -->

    <!-- Admins List Table -->
    <h2>Admins List</h2>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Username</th>
                <th>Password</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach($admins_list as $admin): ?>
            <tr>
                <td><?= $admin['id'] ?></td>
                <td><?= htmlspecialchars($admin['username']) ?></td>
                <td><?= htmlspecialchars($admin['password'] ?? '-') ?></td>
                <td>
                    <button class="action-btn" onclick="location.href='edit_admin.php?id=<?= $admin['id'] ?>'">Edit</button>
                    <button class="action-btn" onclick="if(confirm('Delete this admin?')) location.href='delete_admin.php?id=<?= $admin['id'] ?>'">Delete</button>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

</body>
</html>
