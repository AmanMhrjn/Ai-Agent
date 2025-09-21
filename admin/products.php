<?php
require_once '../config/database.php'; // should provide $pdo

$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$start = ($page - 1) * $limit;

$company_filter = isset($_GET['company']) ? $_GET['company'] : '';

// Fetch all unique companies from DB
$stmt = $pdo->query("SELECT DISTINCT company_name FROM products");
$companies = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Build unique company list and handle empty as 'Empty'
$company_list = [];
foreach ($companies as $row) {
    $company = $row['company_name'] ?: 'Empty';
    if (!in_array($company, $company_list)) {
        $company_list[] = $company;
    }
}

// Count total products for pagination
$count_sql = "SELECT COUNT(*) as total FROM products";
$params = [];
if ($company_filter === 'Empty') {
    $count_sql .= " WHERE company_name = ''";
} elseif ($company_filter) {
    $count_sql .= " WHERE company_name = :company";
    $params[':company'] = $company_filter;
}
$stmt = $pdo->prepare($count_sql);
$stmt->execute($params);
$total_products = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
$total_pages = ceil($total_products / $limit);

// Fetch products with filter and pagination
$sql = "SELECT * FROM products";
if ($company_filter === 'Empty') {
    $sql .= " WHERE company_name = ''";
} elseif ($company_filter) {
    $sql .= " WHERE company_name = :company";
}
$sql .= " ORDER BY id DESC LIMIT :start, :limit";

$stmt = $pdo->prepare($sql);
if ($company_filter && $company_filter !== 'Empty') $stmt->bindValue(':company', $company_filter);
$stmt->bindValue(':start', $start, PDO::PARAM_INT);
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->execute();
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Products</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f5f5f5; margin:0; }
        .main-container { margin-left: 220px; padding: 3px 30px 30px 30px; flex:1; }
        .header { text-align:center; margin-bottom:30px; color:#333; }
        form { margin-bottom:15px; text-align:center; }
        select { padding:8px 12px; border-radius:5px; border:1px solid #ccc; font-size:14px; }
        .table-container { max-height:500px; overflow-y:auto; border:1px solid #ccc; border-radius:5px; background:#fff; }
        table.responsive-table { width:100%; border-collapse:collapse; table-layout:fixed; }
        thead { position:sticky; top:0; background:#007bff; color:#fff; z-index:10; }
        th, td { padding:10px; border-bottom:1px solid #ddd; font-size:14px; text-align:center; vertical-align:top; overflow-wrap: break-word; }
        th.id, td.id { width:50px; }
        th.name, td.name { width:150px; text-align:left; }
        th.price, td.price { width:90px; }
        th.color, td.color { width:90px; }
        th.warranty, td.warranty { width:100px; }
        th.availability, td.availability { width:100px; }
        th.description, td.description { width:400px; text-align:left; }
        th.photo1, td.photo1, th.photo2, td.photo2 { width:120px; }
        th.status, td.status { width:80px; }
        th.user, td.user { width:60px; }
        th.company, td.company { width:120px; text-align:left; }
        th.agent, td.agent { width:150px; text-align:left; }
        th.actions, td.actions { width:180px; white-space:nowrap; }
        tr:hover { background-color:#f1f1f1; }
        thead tr:hover { background-color:#007bff; }
        .btn { padding:4px 8px; margin:2px; text-decoration:none; color:#fff; border-radius:4px; font-size:12px; display:inline-block; }
        .edit { background-color:#28a745; }
        .delete { background-color:#dc3545; }
        .disable { background-color:#ffc107; color:#000; }
        .pagination { margin-top:15px; text-align:center; }
        .pagination a { padding:6px 12px; margin:0 3px; border:1px solid #ccc; text-decoration:none; color:#007bff; border-radius:3px; font-size:14px; }
        .pagination a.active { background:#007bff; color:#fff; border-color:#007bff; }

        /* Responsive adjustments */
        @media screen and (max-width:1200px){ th.description, td.description{ width:300px; } }
        @media screen and (max-width:992px){ .table-container{ max-height:400px; } th.description, td.description{ width:250px; } }
        @media screen and (max-width:768px){ .table-container{ overflow-x:auto; } table.responsive-table{ display:block; min-width:1000px; } }
        @media screen and (max-width:576px){ th.description, td.description{ width:200px; } .btn{ font-size:11px; padding:3px 6px; } }
    </style>
</head>
<body>
<?php include_once 'component/sidebar.php'; ?>
<div class="main-container">
    <h2 class="header">Product List</h2>

    <form method="GET" action="">
        <label for="company">Filter by Company:</label>
        <select name="company" onchange="this.form.submit()">
            <option value="">All Companies</option>
            <?php foreach ($company_list as $company_name): ?>
                <option value="<?= $company_name === 'Empty' ? '' : htmlspecialchars($company_name) ?>"
                    <?= ($company_filter === $company_name || ($company_filter === '' && $company_name === 'Empty')) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($company_name) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </form>

    <div class="table-container">
        <table class="responsive-table">
            <thead>
                <tr>
                    <th class="id">ID</th>
                    <th class="name">Product Name</th>
                    <th class="price">Offer Price</th>
                    <th class="color">Color</th>
                    <th class="warranty">Warranty</th>
                    <th class="availability">Availability</th>
                    <th class="description">Description</th>
                    <th class="photo1">Photo 1</th>
                    <th class="photo2">Photo 2</th>
                    <th class="status">Status</th>
                    <!-- <th class="user">User ID</th> -->
                    <th class="company">Company Name</th>
                    <!-- <th class="agent">Agent Name</th> -->
                    <th class="actions">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if(count($products) > 0): ?>
                    <?php foreach($products as $row): ?>
                        <tr>
                            <td class="id"><?= $row['id'] ?></td>
                            <td class="name"><?= htmlspecialchars($row['product_name']) ?></td>
                            <td class="price"><?= $row['offer_price'] ?></td>
                            <td class="color"><?= htmlspecialchars($row['color']) ?></td>
                            <td class="warranty"><?= htmlspecialchars($row['warranty']) ?></td>
                            <td class="availability"><?= htmlspecialchars($row['availability']) ?></td>
                            <td class="description"><?= htmlspecialchars($row['description']) ?></td>
                            <td class="photo1"><?= htmlspecialchars($row['photo_1']) ?></td>
                            <td class="photo2"><?= htmlspecialchars($row['photo_2']) ?></td>
                            <td class="status"><?= $row['status'] ? 'Active' : 'Disabled' ?></td>
                            <!-- <td class="user"><?= $row['user_id'] ?></td> -->
                            <td class="company"><?= $row['company_name'] ?: 'Empty' ?></td>
                            <!-- <td class="agent"><?= htmlspecialchars($row['agent_name']) ?></td> -->
                            <td class="actions">
                                <a class="btn edit" href="edit_products.php?id=<?= $row['id'] ?>">Edit</a>
                                <a class="btn delete" href="delete_products.php?id=<?= $row['id'] ?>" onclick="return confirm('Are you sure?')">Delete</a>
                                <a class="btn disable" href="toggle_status.php?id=<?= $row['id'] ?>"><?= $row['status'] ? 'Disable' : 'Enable' ?></a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="14" style="text-align:center;">No products found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="pagination">
        <?php for($i=1; $i<=$total_pages; $i++): ?>
            <a href="?page=<?= $i ?><?= $company_filter ? '&company='.$company_filter : '' ?>" class="<?= ($i==$page)?'active':'' ?>"><?= $i ?></a>
        <?php endfor; ?>
    </div>
</div>
</body>
</html>
