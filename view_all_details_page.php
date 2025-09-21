<?php
session_start();
require_once "config/database.php"; // assumes $pdo is available

// Check if user is logged in
if (!isset($_SESSION['id']) || !isset($_SESSION['companyname'])) {
  echo "<p>You must log in first.</p>";
  exit;
}

$user_id = $_SESSION['id'];
$company_name = $_SESSION['companyname'];

// --- Fetch agent2 data (by user_id + company_name)
$stmt = $pdo->prepare("SELECT * FROM agent2 WHERE user_id = ? AND company_name = ?");
$stmt->execute([$user_id, $company_name]);
$agents = $stmt->fetchAll(PDO::FETCH_ASSOC);

// --- Fetch details data (matching company_name in Workflow name/Brand Name)
$stmt = $pdo->prepare("SELECT * FROM details WHERE `Workflow name/Brand Name` = ?");
$stmt->execute([$company_name]);
$details = $stmt->fetchAll(PDO::FETCH_ASSOC);

// --- Fetch products data (by user_id + company_name)
$stmt = $pdo->prepare("SELECT * FROM products WHERE user_id = ? AND company_name = ?");
$stmt->execute([$user_id, $company_name]);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// --- Fetch uploaded files data (by user_id + company_name)
$stmt = $pdo->prepare("SELECT * FROM uploaded_files WHERE user_id = ? AND company_name = ?");
$stmt->execute([$user_id, $company_name]);
$files = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title><?= htmlspecialchars($company_name) ?> - Dashboard</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      margin: 0;
      padding: 20px;
      background: #f4f6f9;
      box-sizing: border-box;
    }

    /* Headings */
    h1 {
      color: #222;
      text-align: center;
      margin-bottom: 30px;
    }

    h2 {
      margin-top: 40px;
      background: #333;
      color: white;
      padding: 10px;
      border-radius: 5px;
      font-size: 18px;
    }

    /* Table styling */
    table {
      width: 100%;
      border-collapse: collapse;
      margin: 15px 0;
      background: white;
      box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
      table-layout: fixed;
      word-wrap: break-word;
    }

    table th,
    table td {
      border: 1px solid #ddd;
      padding: 8px;
      text-align: left;
      font-size: 14px;
    }

    table th {
      background-color: #444;
      color: white;
      font-weight: 600;
    }

    tr:nth-child(even) {
      background-color: #f9f9f9;
    }

    /* Images */
    img {
      max-width: 60px;
      height: auto;
      display: block;
      margin: 0 auto;
    }

    /* --- Responsive Styles --- */

    /* Tablets */
    @media (max-width: 1024px) {
      body {
        padding: 15px;
      }

      h2 {
        font-size: 16px;
        padding: 8px;
      }

      table th,
      table td {
        font-size: 13px;
        padding: 6px;
      }

      img {
        max-width: 50px;
      }
    }

    /* Mobile devices */
    @media (max-width: 768px) {
      h1 {
        font-size: 20px;
        margin-bottom: 20px;
      }

      h2 {
        font-size: 15px;
        padding: 6px;
      }

      table th,
      table td {
        font-size: 12px;
        padding: 5px;
      }

      /* Make tables scrollable horizontally */
      table {
        display: block;
        overflow-x: auto;
        white-space: nowrap;
      }

      img {
        max-width: 40px;
      }
    }

    /* Small mobile screens */
    @media (max-width: 480px) {
      body {
        padding: 10px;
      }

      h1 {
        font-size: 18px;
      }

      h2 {
        font-size: 14px;
        padding: 5px;
      }

      table th,
      table td {
        font-size: 11px;
        padding: 4px;
      }

      img {
        max-width: 30px;
      }

      /* Make tables scrollable horizontally */
      table {
        display: block;
        overflow-x: auto;
        white-space: nowrap;
      }
    }

    /* Optional: add smooth scrolling for tables on mobile */
    table::-webkit-scrollbar {
      height: 6px;
    }

    table::-webkit-scrollbar-thumb {
      background-color: #888;
      border-radius: 3px;
    }
  </style>
</head>

<body>

  <h1>Welcome, <?= htmlspecialchars($company_name) ?>!</h1>

  <!-- Agent Section -->
  <h2>Agent Information</h2>
  <?php if ($agents): ?>
    <table>
      <tr>
        <th>ID</th>
        <th>Company</th>
        <th>Payment Method</th>
        <th>Amount</th>
        <th>Created At</th>
        <th>Plan</th>
        <th>Platform</th>
        <th>Status</th>
        <th>Agent Page</th>
      </tr>
      <?php foreach ($agents as $a): ?>
        <tr>
          <td><?= $a['id'] ?></td>
          <td><?= htmlspecialchars($a['company_name']) ?></td>
          <td><?= htmlspecialchars($a['payment_method']) ?></td>
          <td><?= $a['amount'] ?></td>
          <td><?= $a['created_at'] ?></td>
          <td><?= $a['plan'] ?></td>
          <td><?= $a['platform'] ?></td>
          <td><?= $a['status'] ? 'Active' : 'Inactive' ?></td>
          <td><?= $a['agent_page'] ?></td>
        </tr>
      <?php endforeach; ?>
    </table>
  <?php else: ?>
    <p>No agent records found.</p>
  <?php endif; ?>

  <!-- Details Section -->
  <h2>Workflow / Brand Details</h2>
  <?php if ($details): ?>
    <table>
      <tr>
        <th>Model</th>
        <th>Time</th>
        <th>Execution ID / Msg</th>
        <th>Workflow Name</th>
        <th>Total Token</th>
        <th>Platform</th>
        <th>Workflow Id</th>
        <th>Status</th>
      </tr>
      <?php foreach ($details as $d): ?>
        <tr>
          <td><?= htmlspecialchars($d['Model']) ?></td>
          <td><?= $d['Time'] ?></td>
          <td><?= $d['Excution_id/message send'] ?></td>
          <td><?= htmlspecialchars($d['Workflow name/Brand Name']) ?></td>
          <td><?= $d['Total token'] ?></td>
          <td><?= htmlspecialchars($d['Platform']) ?></td>
          <td><?= htmlspecialchars($d['Workflow Id']) ?></td>
          <td><?= htmlspecialchars($d['Status']) ?></td>
        </tr>
      <?php endforeach; ?>
    </table>
  <?php else: ?>
    <p>No workflow details found.</p>
  <?php endif; ?>

  <!-- Products Section -->
  <h2>Products</h2>
  <?php if ($products): ?>
    <table>
      <tr>
        <th>ID</th>
        <th>Name</th>
        <th>Price</th>
        <th>Color</th>
        <th>Warranty</th>
        <th>Availability</th>
        <th>Description</th>
        <th>Photo 1</th>
        <th>Photo 2</th>
        <th>Status</th>
        <th>Agent</th>
      </tr>
      <?php foreach ($products as $p): ?>
        <tr>
          <td><?= $p['id'] ?></td>
          <td><?= htmlspecialchars($p['product_name']) ?></td>
          <td><?= $p['offer_price'] ?></td>
          <td><?= htmlspecialchars($p['color']) ?></td>
          <td><?= htmlspecialchars($p['warranty']) ?></td>
          <td><?= $p['availability'] ?></td>
          <td><?= htmlspecialchars($p['description']) ?></td>
          <td><?php if ($p['photo_1']): ?><img src="<?= $p['photo_1'] ?>"><?php endif; ?></td>
          <td><?php if ($p['photo_2']): ?><img src="<?= $p['photo_2'] ?>"><?php endif; ?></td>
          <td><?= $p['status'] ? 'Enabled' : 'Disabled' ?></td>
          <td><?= htmlspecialchars($p['agent_name']) ?></td>
        </tr>
      <?php endforeach; ?>
    </table>
  <?php else: ?>
    <p>No products found.</p>
  <?php endif; ?>

  <!-- Uploaded Files Section -->
  <h2>Uploaded Files</h2>
  <?php if ($files): ?>
    <table>
      <tr>
        <th>ID</th>
        <th>File Name</th>
        <th>Type</th>
        <th>Size (KB)</th>
        <th>Status</th>
        <th>Uploaded At</th>
      </tr>
      <?php foreach ($files as $f): ?>
        <tr>
          <td><?= $f['id'] ?></td>
          <td><?= htmlspecialchars($f['file_name']) ?></td>
          <td><?= htmlspecialchars($f['file_type']) ?></td>
          <td><?= round($f['file_size'] / 1024, 2) ?></td>
          <td><?= $f['is_enabled'] ? 'Enabled' : 'Disabled' ?></td>
          <td><?= $f['uploaded_at'] ?></td>
        </tr>
      <?php endforeach; ?>
    </table>
  <?php else: ?>
    <p>No uploaded files found.</p>
  <?php endif; ?>

</body>

</html>