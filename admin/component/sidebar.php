<?php
// Get current page file name
$current_page = basename($_SERVER['PHP_SELF']);
?>
<style>
.sidebar {
    width: 220px;
    background: #343a40;
    color: #fff;
    min-height: 100vh;
    position: fixed;
    top: 0;
    left: 0;
    transition: transform 0.3s ease;
    z-index: 1000;
}

.sidebar .admin-sidebar {
    text-align: center;
    padding: 20px 0;
    margin: 0;
    border-bottom: 1px solid #495057;
    font-size: 18px;
}

.sidebar a {
    display: block;
    padding: 15px 20px;
    color: #fff;
    text-decoration: none;
    border-bottom: 1px solid #495057;
    transition: background 0.3s;
}

.sidebar a:hover,
.sidebar a.active {
    background: #007bff;
    color: #fff;
    font-weight: bold;
}

/* Content adjustment */
.main-container {
    margin-left: 220px; /* matches sidebar width */
    padding: 30px;
    transition: margin-left 0.3s;
}

/* Tablet */
@media (max-width: 1024px) {
    .sidebar { width: 180px; }
    .sidebar .admin-sidebar { font-size: 16px; padding: 15px 0; }
    .sidebar a { padding: 12px 15px; font-size: 14px; }
    .main-container { margin-left: 180px; }
}

/* Mobile */
@media (max-width: 768px) {
    .sidebar {
        width: 100%;
        height: 100%;
        position: fixed;
        top: 0;
        left: 0;
        transform: translateX(-100%);
        z-index: 1100;
    }

    .sidebar.active {
        transform: translateX(0);
    }

    .main-container {
        margin: 0;
        padding-top: 60px; /* space for hamburger */
    }

    /* Hamburger button */
    .hamburger {
        display: block;
        position: fixed;
        top: 15px;
        left: 15px;
        width: 35px;
        height: 30px;
        z-index: 1200;
        cursor: pointer;
    }

    .hamburger span {
        display: block;
        width: 100%;
        height: 4px;
        margin: 5px 0;
        background: #007bff;
        transition: 0.3s;
    }
}

/* Desktop hamburger hidden */
.hamburger { display: none; }
</style>

<!-- Hamburger -->
<div class="hamburger" onclick="toggleSidebar()">
    <span></span>
    <span></span>
    <span></span>
</div>

<!-- Sidebar -->
<div class="sidebar">
    <h2 class="admin-sidebar">Admin Panel</h2>
    <a href="index.php" class="<?= ($current_page == 'index.php') ? 'active' : '' ?>">🏠 Dashboard</a>
    <a href="admin_chat.php" class="<?= ($current_page == 'admin_chat.php') ? 'active' : '' ?>">💬 Chat</a>
    <a href="manage_qr.php" class="<?= ($current_page == 'manage_qr.php') ? 'active' : '' ?>">📷 Manage QR</a>
    <a href="payments.php" class="<?= ($current_page == 'payments.php') ? 'active' : '' ?>">💳 Payments</a>
    <a href="users.php" class="<?= ($current_page == 'users.php') ? 'active' : '' ?>">👥 Users List</a>
    <a href="products.php" class="<?= ($current_page == 'products.php') ? 'active' : '' ?>">🧾 Products</a>
    <a href="orders.php" class="<?= ($current_page == 'orders.php') ? 'active' : '' ?>">📦 Orders</a>
    <a href="logout.php">🚪 Logout</a>
</div>

<script>
function toggleSidebar() {
    document.querySelector('.sidebar').classList.toggle('active');
}
</script>