<?php
session_start();
if(!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true){
    header("Location: login.php");
    exit;
}

require_once '../config/database.php';

// ✅ Handle Add
if(isset($_POST['add'])){
    $platform = $_POST['platform'];
    $account_name = $_POST['account_name'];
    $account_number = $_POST['account_number'];
    $qr_image = '';

    if(isset($_FILES['qr_image']) && $_FILES['qr_image']['error'] == 0){
        $filename = time().'_'.basename($_FILES['qr_image']['name']);
        $target = '../uploads/qr/'.$filename;
        move_uploaded_file($_FILES['qr_image']['tmp_name'], $target);
        $qr_image = 'uploads/qr/'.$filename;
    }

    $stmt = $pdo->prepare("INSERT INTO payment_qr_details (platform, account_name, account_number, qr_image) VALUES (?,?,?,?)");
    $stmt->execute([$platform, $account_name, $account_number, $qr_image]);
    header("Location: manage_qr.php");
    exit;
}

// ✅ Handle Delete
if(isset($_GET['delete'])){
    $id = $_GET['delete'];

    $stmt = $pdo->prepare("SELECT qr_image FROM payment_qr_details WHERE id=?");
    $stmt->execute([$id]);
    $file = $stmt->fetchColumn();
    if($file && file_exists("../".$file)){
        unlink("../".$file);
    }

    $pdo->prepare("DELETE FROM payment_qr_details WHERE id=?")->execute([$id]);
    header("Location: manage_qr.php");
    exit;
}

// ✅ Handle Update
if(isset($_POST['update'])){
    $id = $_POST['id'];
    $platform = $_POST['platform'];
    $account_name = $_POST['account_name'];
    $account_number = $_POST['account_number'];
    $qr_image = $_POST['old_qr'];

    if(isset($_FILES['qr_image']) && $_FILES['qr_image']['error'] == 0){
        $filename = time().'_'.basename($_FILES['qr_image']['name']);
        $target = '../uploads/qr/'.$filename;
        move_uploaded_file($_FILES['qr_image']['tmp_name'], $target);
        $qr_image = 'uploads/qr/'.$filename;

        if($_POST['old_qr'] && file_exists("../".$_POST['old_qr'])){
            unlink("../".$_POST['old_qr']);
        }
    }

    $stmt = $pdo->prepare("UPDATE payment_qr_details SET platform=?, account_name=?, account_number=?, qr_image=? WHERE id=?");
    $stmt->execute([$platform, $account_name, $account_number, $qr_image, $id]);
    header("Location: manage_qr.php");
    exit;
}

// ✅ Fetch QR list
$stmt = $pdo->query("SELECT * FROM payment_qr_details ORDER BY id DESC");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// check edit mode
$edit_id = isset($_GET['edit']) ? $_GET['edit'] : null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage QR Payment Details</title>
    <style>
    body { 
        font-family: Arial, sans-serif; 
        margin: 0; 
        background: #f4f6f9; 
    }

    /* Sidebar */
    .main-content {
        margin-left: 220px;
        padding: 30px;
        flex: 1;
        box-sizing: border-box;
    }

    .header {
        text-align:center; 
        margin-bottom:20px; 
        color:#333; 
        font-size:1.8rem;
    }

    .form-container {
        background:#fff; 
        padding:20px; 
        border-radius:12px;
        box-shadow:0 4px 12px rgba(0,0,0,0.1);
        max-width:600px; 
        margin:auto; 
        margin-bottom:30px;
        box-sizing: border-box;
    }

    label { 
        font-weight:bold; 
        display:block; 
        margin:8px 0 4px; 
    }

    input[type=text], input[type=file] {
        width:100%; 
        padding:10px; 
        border:1px solid #ccc; 
        border-radius:8px;
        font-size:1rem;
        box-sizing: border-box;
    }

    button { 
        padding:10px 15px; 
        border:none; 
        border-radius:6px; 
        cursor:pointer; 
        font-size:1rem;
        transition: background 0.3s ease;
    }

    .btn-add { 
        background:#007bff; 
        color:#fff; 
        width:100%; 
        margin-top:10px; 
    }
    .btn-add:hover { background:#0056b3; }

    .btn-update { background:#28a745; color:white; }
    .btn-update:hover { background:#218838; }

    .btn-cancel { background:#6c757d; color:white; }
    .btn-cancel:hover { background:#5a6268; }

    a { text-decoration:none; font-weight:bold; }
    .btn-edit { color:#17a2b8; }
    .btn-delete { color:#dc3545; }

    table { 
        width:100%; 
        border-collapse:collapse; 
        background:#fff; 
        box-shadow:0 4px 12px rgba(0,0,0,0.1); 
        margin-bottom:50px;
    }

    th, td { 
        padding:10px; 
        text-align:center; 
        border-bottom:1px solid #eee; 
        word-wrap: break-word;
    }

    th { 
        background:#007bff; 
        color:white; 
    }

    tr:hover { background:#f9fbff; }

    img { border-radius:6px; max-width:60px; height:auto; }

    /* Tablet */
    @media (max-width: 1024px) {
        .main-content {
            margin-left: 0;
            padding: 20px;
        }
        .form-container {
            padding: 15px;
            max-width: 100%;
        }
        th, td {
            padding: 8px;
            font-size: 0.9rem;
        }
        .header {
            font-size: 1.5rem;
        }
        input[type=text], input[type=file], button {
            padding: 8px;
            font-size: 0.95rem;
        }
        img { max-width:50px; }
    }

    /* Mobile */
    @media (max-width: 600px) {
        table, thead, tbody, th, td, tr { 
            display: block; 
            width: 100%;
        }
        tr { margin-bottom: 15px; border-bottom: 2px solid #f0f0f0; }
        th { display: none; }
        td { 
            text-align: right; 
            padding-left: 50%; 
            position: relative;
        }
        td::before { 
            content: attr(data-label); 
            position: absolute; 
            left: 10px; 
            width: 45%; 
            padding-left: 10px;
            font-weight: bold;
            text-align: left;
        }
        img { max-width: 100%; height:auto; }
        .form-container { padding: 15px; margin-bottom: 20px; }
        button { font-size: 0.9rem; padding: 8px; }
        .header { font-size: 1.4rem; }
    }
    </style>
</head>
<body>
    <?php include_once'component/sidebar.php'?>
    <div class="main-content">
    <h2 class="header">Manage QR Payment Details</h2>

    <!-- Add Form -->
    <div class="form-container">
        <form method="post" enctype="multipart/form-data">
            <label>Platform:</label>
            <input type="text" name="platform" required>
            <label>Account Name:</label>
            <input type="text" name="account_name" required>
            <label>Account Number:</label>
            <input type="text" name="account_number" required>
            <label>QR Image:</label>
            <input type="file" name="qr_image" accept="image/*" required>
            <button type="submit" name="add" class="btn-add">➕ Add QR</button>
        </form>
    </div>

    <!-- Table -->
    <table>
        <tr>
            <th>SN</th>
            <th>Platform</th>
            <th>Account Name</th>
            <th>Account No</th>
            <th>QR</th>
            <th>Actions</th>
        </tr>
        <?php $sn=1; foreach($rows as $r): ?>
        <tr>
        <?php if($edit_id == $r['id']): ?>
            <!-- Edit Mode -->
            <form method="post" enctype="multipart/form-data">
                <td><?= $sn++ ?></td>
                <td><input type="text" name="platform" value="<?= htmlspecialchars($r['platform']) ?>"></td>
                <td><input type="text" name="account_name" value="<?= htmlspecialchars($r['account_name']) ?>"></td>
                <td><input type="text" name="account_number" value="<?= htmlspecialchars($r['account_number']) ?>"></td>
                <td>
                    <?php if($r['qr_image']): ?>
                        <img src="../<?= $r['qr_image'] ?>" width="60"><br>
                    <?php endif; ?>
                    <input type="file" name="qr_image" accept="image/*">
                </td>
                <td>
                    <input type="hidden" name="id" value="<?= $r['id'] ?>">
                    <input type="hidden" name="old_qr" value="<?= $r['qr_image'] ?>">
                    <button type="submit" name="update" class="btn-update">💾 Update</button>
                    <a href="manage_qr.php" class="btn-cancel">✖ Cancel</a>
                </td>
            </form>
        <?php else: ?>
            <!-- Normal Row -->
            <td><?= $sn++ ?></td>
            <td><?= htmlspecialchars($r['platform']) ?></td>
            <td><?= htmlspecialchars($r['account_name']) ?></td>
            <td><?= htmlspecialchars($r['account_number']) ?></td>
            <td>
                <?php if($r['qr_image']): ?>
                    <img src="../<?= $r['qr_image'] ?>" width="60">
                <?php endif; ?>
            </td>
            <td>
                <a href="manage_qr.php?edit=<?= $r['id'] ?>" class="btn-edit">✏ Edit</a> |
                <a href="manage_qr.php?delete=<?= $r['id'] ?>" class="btn-delete" onclick="return confirm('Delete this QR?')">🗑 Delete</a>
            </td>
        <?php endif; ?>
        </tr>
        <?php endforeach; ?>
    </table>
    </div>
</body>
</html>
