<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['id'];
$company_name = $_SESSION['companyname'] ?? '';

// Fetch unique agents for dropdown (avoid duplicates)
$stmt = $conn->prepare("
    SELECT MIN(id) as id, page_name, platform
    FROM agents
    WHERE user_id = ? AND status = 1
    GROUP BY page_name, platform
    ORDER BY page_name ASC
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$agents = [];
while ($row = $result->fetch_assoc()) {
    $agents[] = $row;
}

if (empty($agents)) {
    die("No agents found. Please create an agent first.");
}

// Handle CSV upload
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $agent_id = (int)$_POST['agent_id'];

    if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== 0) {
        die("Please upload a valid CSV file.");
    }

    // Check file extension
    $file_ext = pathinfo($_FILES['csv_file']['name'], PATHINFO_EXTENSION);
    if (strtolower($file_ext) !== 'csv') {
        die("Invalid file type. Please upload a CSV file.");
    }

    $file_tmp = $_FILES['csv_file']['tmp_name'];
    $file_name = $_FILES['csv_file']['name'];
    $file_size = $_FILES['csv_file']['size'];
    $file_content = file_get_contents($file_tmp);
    $file_type = 'csv';

    // Store uploaded file in uploaded_files table
    $stmt = $conn->prepare("
        INSERT INTO uploaded_files (user_id, company_name, file_name, file_type, file_size, file_content)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->bind_param("isssis", $user_id, $company_name, $file_name, $file_type, $file_size, $file_content);
    $stmt->execute();

    // Parse CSV
    if (($handle = fopen($file_tmp, "r")) !== FALSE) {
        $header = fgetcsv($handle, 1000, ","); // Skip header row
        while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
            // Skip empty rows
            if (empty(array_filter($data))) continue;

            // Map CSV columns to products table
            $product_name = $data[0] ?? '';
            $offer_price = $data[1] ?? 0;
            $color = $data[2] ?? '';
            $warranty = $data[3] ?? '';
            $availability = $data[4] ?? '';
            $description = $data[5] ?? '';

            // Insert into products table
            $stmt = $conn->prepare("
                INSERT INTO products 
                (product_name, offer_price, color, warranty, availability, description, company_name, agent_id)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->bind_param(
                "sdsssssi",
                $product_name,
                $offer_price,
                $color,
                $warranty,
                $availability,
                $description,
                $company_name,
                $agent_id
            );
            $stmt->execute();
        }
        fclose($handle);
        header("Location: dashboard.php?msg=products_uploaded");
        exit;
    } else {
        die("Error reading CSV file.");
    }
}
?>

<!DOCTYPE html>
<html>

<head>
    <title>Upload Products via CSV</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f6f8;
            margin: 0;
            padding: 20px;
            display: flex;
            justify-content: center;
            align-items: flex-start;
            min-height: 100vh;
        }

        .form-box {
            background: #fff;
            padding: 30px 40px;
            border-radius: 10px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            width: 600px;
            max-width: 100%;
        }

        .form-box h2 {
            text-align: center;
            margin-bottom: 25px;
            font-size: 24px;
        }

        form label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
        }

        form input,
        form select {
            width: 100%;
            padding: 10px;
            margin-bottom: 20px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 14px;
            box-sizing: border-box;
        }

        button[type="submit"] {
            width: 100%;
            padding: 12px;
            background: #007BFF;
            color: #fff;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            cursor: pointer;
            transition: 0.2s;
        }

        button[type="submit"]:hover {
            background: #0056b3;
        }
    </style>
</head>

<body>
    <div class="form-box">
        <h2>Upload Products CSV</h2>
        <form method="POST" enctype="multipart/form-data">
            <label>Select Agent:</label>
            <select name="agent_id" required>
                <option value="">Select Agent</option>
                <?php foreach ($agents as $agent): ?>
                    <option value="<?= $agent['id'] ?>">
                        <?= htmlspecialchars($agent['page_name'] . " (" . $agent['platform'] . ")") ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label>CSV File:</label>
            <input type="file" name="csv_file" accept=".csv" required>

            <button type="submit">Upload CSV</button>
        </form>
    </div>
</body>

</html>