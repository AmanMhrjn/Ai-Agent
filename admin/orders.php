<?php
session_start();
require_once '../config/database.php';

// Query: count delivered orders per company
$sql = "SELECT company_name, COUNT(*) AS delivered_count
        FROM orders 
        WHERE status = 'Delivered'
        GROUP BY company_name";

$stmt = $pdo->prepare($sql);
$stmt->execute();
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Admin Orders Summary</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f7f8;
            margin: 0;
        }

        .main-container {
            margin-left: 220px;
            padding: 30px;
            flex: 1;
            box-sizing: border-box;
        }

        .header {
            margin: 0;
            text-align: center;
            margin-bottom: 30px;
            color: #333;
            font-size: 1.8rem;
        }

        /* Table wrapper for horizontal scroll */
        .table-wrapper {
            overflow-x: auto;
            margin: 0 auto;
        }

        table {
            width: 80%;
            margin: 20px auto;
            background: #fff;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
            border-collapse: collapse;
            min-width: 400px;
        }

        th,
        td {
            padding: 12px 20px;
            text-align: center;
            word-break: break-word;
        }

        th {
            background-color: #007BFF;
            color: #fff;
            font-weight: bold;
        }

        tr:nth-child(even) {
            background-color: #f2f2f2;
        }

        tr:hover {
            background-color: #e0f0ff;
        }

        td {
            color: #555;
        }

        /* Tablet */
        @media (max-width: 1024px) {
            .main-container {
                margin-left: 0;
                padding: 20px;
            }

            table {
                width: 100%;
            }

            th,
            td {
                padding: 10px 12px;
                font-size: 0.95rem;
            }

            .header {
                font-size: 1.5rem;
            }
        }

        /* Mobile */
        @media (max-width: 600px) {
            table {
                min-width: 300px;
            }

            th,
            td {
                padding: 8px 10px;
                font-size: 0.9rem;
            }

            .header {
                font-size: 1.3rem;
            }
        }
    </style>
</head>

<body>
    <?php include_once 'component/sidebar.php'; ?>
    <div class="main-container">
        <h2 class="header">Delivered Orders Count by Company</h2>
        <div class="table-wrapper">
            <table>
                <tr>
                    <th>ID</th>
                    <th>Company Name</th>
                    <th>Delivered Orders</th>
                </tr>
                <?php
                if ($results) {
                    $id = 1;
                    foreach ($results as $row) {
                        echo "<tr>
                                <td>{$id}</td>
                                <td>{$row['company_name']}</td>
                                <td>{$row['delivered_count']}</td>
                              </tr>";
                        $id++;
                    }
                } else {
                    echo "<tr><td colspan='3'>No delivered orders found</td></tr>";
                }
                ?>
            </table>
        </div>
    </div>
</body>
</html>
