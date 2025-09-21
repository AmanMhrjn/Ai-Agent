 <?php
    session_start();
    require_once '../config/database.php';

    // Check if admin is logged in
    // if (!isset($_SESSION['admin_id'])) {
    //     header("Location: login.php");
    //     exit();
    // }

    // Handle approval/rejection actions
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $purchase_id = isset($_POST['purchase_id']) ? (int)$_POST['purchase_id'] : 0;
        $action = isset($_POST['action']) ? $_POST['action'] : '';

        if ($purchase_id > 0 && in_array($action, ['approve', 'reject'])) {
            $status = $action === 'approve' ? 'approved' : 'rejected';
            $stmt = $conn->prepare("UPDATE agent_purchases SET admin_status = ? WHERE id = ?");
            $stmt->bind_param("si", $status, $purchase_id);
            $stmt->execute();
        }
    }

    // Fetch pending purchases
    $sql = "SELECT ap.id, u.username, zp.product_name, ap.payment_method, ap.payment_status, ap.admin_status, ap.payment_date
        FROM agent_purchases ap
        JOIN users u ON ap.user_id = u.user_id
        JOIN zeblaze_products zp ON ap.product_id = zp.id
        WHERE ap.admin_status = 'pending'
        ORDER BY ap.payment_date DESC";
    $result = $conn->query($sql);
    ?>
 <!DOCTYPE html>
 <html lang="en">

 <head>
     <meta charset="UTF-8" />
     <title>Agent Purchase Approvals</title>
     <link rel="stylesheet" href="../assets/css/main.css" />
     <style>
         body {
             font-family: Arial, sans-serif;
             background: #f4f6f9;
             margin: 20px;
             color: #222;
         }

         h1 {
             text-align: center;
             margin-bottom: 20px;
         }

         /* Table container for responsive scrolling */
         .table-responsive {
             width: 100%;
             overflow-x: auto;
         }

         table {
             width: 100%;
             border-collapse: collapse;
             margin-top: 20px;
             min-width: 700px;
             /* ensures scroll on small screens */
             background: #fff;
             box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
         }

         th,
         td {
             border: 1px solid #ddd;
             padding: 10px 12px;
             text-align: left;
         }

         th {
             background: #007bff;
             color: #fff;
         }

         tr:nth-child(even) {
             background-color: #f9f9f9;
         }

         /* Buttons */
         button {
             padding: 6px 12px;
             margin: 2px 0;
             border-radius: 5px;
             font-size: 14px;
             border: none;
             cursor: pointer;
             transition: background 0.3s;
         }

         button.approve {
             background-color: #28a745;
             color: #fff;
         }

         button.approve:hover {
             background-color: #218838;
         }

         button.reject {
             background-color: #dc3545;
             color: #fff;
         }

         button.reject:hover {
             background-color: #c82333;
         }

         /* Tablet */
         @media (max-width: 1024px) {

             th,
             td {
                 padding: 8px 10px;
                 font-size: 14px;
             }

             button {
                 font-size: 13px;
                 padding: 5px 10px;
             }
         }

         /* Mobile */
         @media (max-width: 768px) {
             body {
                 margin: 10px;
             }

             table {
                 min-width: 600px;
                 /* allows horizontal scroll */
                 font-size: 12px;
             }

             th,
             td {
                 padding: 6px 8px;
             }

             button {
                 font-size: 12px;
                 padding: 4px 8px;
             }
         }

         /* Optional: make the table scrollable on mobile */
         .table-responsive::-webkit-scrollbar {
             height: 8px;
         }

         .table-responsive::-webkit-scrollbar-thumb {
             background: #007bff;
             border-radius: 4px;
         }
     </style>
 </head>

 <body>
     <h1>Pending Agent Purchase Approvals</h1>
     <div class="table-responsive">
         <table>
             <thead>
                 <tr>
                     <th>Purchase ID</th>
                     <th>User</th>
                     <th>Agent</th>
                     <th>Payment Method</th>
                     <th>Payment Status</th>
                     <th>Admin Status</th>
                     <th>Payment Date</th>
                     <th>Actions</th>
                 </tr>
             </thead>
             <tbody>
                 <?php if ($result->num_rows === 0): ?>
                     <tr>
                         <td colspan="8" style="text-align:center;">No pending approvals.</td>
                     </tr>
                 <?php else: ?>
                     <?php while ($row = $result->fetch_assoc()): ?>
                         <tr>
                             <td><?= htmlspecialchars($row['id']) ?></td>
                             <td><?= htmlspecialchars($row['username']) ?></td>
                             <td><?= htmlspecialchars($row['product_name']) ?></td>
                             <td><?= htmlspecialchars($row['payment_method']) ?></td>
                             <td><?= htmlspecialchars($row['payment_status']) ?></td>
                             <td><?= htmlspecialchars($row['admin_status']) ?></td>
                             <td><?= htmlspecialchars($row['payment_date']) ?></td>
                             <td>
                                 <form method="POST" action="">
                                     <input type="hidden" name="purchase_id" value="<?= $row['id'] ?>" />
                                     <button type="submit" name="action" value="approve" class="approve">Approve</button>
                                     <button type="submit" name="action" value="reject" class="reject">Reject</button>
                                 </form>
                             </td>
                         </tr>
                     <?php endwhile; ?>
                 <?php endif; ?>
             </tbody>
         </table>
     </div>
 </body>

 </html>