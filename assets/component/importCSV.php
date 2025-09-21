<?php
session_start();
if (!isset($_SESSION['id'])) {
    echo "error|You must be logged in to upload CSV.";
    exit;
}

include_once '../../config/database.php'; // Ensure this defines $pdo (PDO connection)

if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
    echo "error|No file uploaded or upload error.";
    exit;
}

$csvFile = $_FILES['csv_file']['tmp_name'];
$csvType = $_POST['csv_type'] ?? '';

if (empty($csvType)) {
    echo "error|CSV type not specified.";
    exit;
}

if (($handle = fopen($csvFile, "r")) === FALSE) {
    echo "error|Failed to open uploaded file.";
    exit;
}

// Detect delimiter automatically (tab or comma)
$firstLine = fgets($handle);
$delimiter = strpos($firstLine, "\t") !== false ? "\t" : ",";
rewind($handle);

// Read header row
$header = fgetcsv($handle, 1000, $delimiter);
if (!$header) {
    echo "error|CSV is empty or invalid.";
    exit;
}

try {
    $pdo->beginTransaction();

    while (($row = fgetcsv($handle, 1000, $delimiter)) !== FALSE) {
        $data = array_combine($header, $row);

        if ($csvType === 'details') {
            // Convert date to YYYY-MM-DD (supports multiple formats)
            $dateStr = trim($data['Time'] ?? '');
            $timeFormatted = null;
            if ($dateStr) {
                $formats = ['m/d/Y', 'm-d-Y', 'Y-m-d', 'Y/m/d'];
                foreach ($formats as $format) {
                    $dt = DateTime::createFromFormat($format, $dateStr);
                    if ($dt) {
                        $timeFormatted = $dt->format('Y-m-d');
                        break;
                    }
                }
            }

            $stmt = $pdo->prepare("
                INSERT INTO details 
                (`Model`, `Time`, `Excution_id/message send`, `Workflow name/Brand Name`, `Total token`, `Platform`, `Workflow Id`, `Status`)
                VALUES (:Model, :Time, :Excution_id, :WorkflowName, :TotalToken, :Platform, :WorkflowId, :Status)
            ");
            $stmt->execute([
                ':Model'        => $data['Model'] ?? null,
                ':Time'         => $timeFormatted,
                ':Excution_id'  => $data['Excution_id/message send'] ?? null,
                ':WorkflowName' => $data['Workflow name/Brand Name'] ?? null,
                ':TotalToken'   => $data['Total token'] ?? null,
                ':Platform'     => $data['Platform'] ?? null,
                ':WorkflowId'   => $data['Workflow Id'] ?? null,
                ':Status'       => $data['Status'] ?? null
            ]);

        } elseif ($csvType === 'products') {
            $stmt = $pdo->prepare("
                INSERT INTO products 
                (product_name, offer_price, color, warranty, availability, description, photo_1, photo_2, status, user_id, company_name)
                VALUES (:product_name, :offer_price, :color, :warranty, :availability, :description, :photo_1, :photo_2, :status, :user_id, :company_name)
            ");
            $stmt->execute([
                ':product_name' => $data['Product Name'] ?? null,
                ':offer_price'  => $data['Offer Price'] ?? null,
                ':color'        => $data['Color'] ?? null,
                ':warranty'     => $data['Warranty'] ?? null,
                ':availability' => $data['Availability'] ?? null,
                ':description'  => $data['Description'] ?? null,
                ':photo_1'      => $data['Photo 1'] ?? null,
                ':photo_2'      => $data['Photo 2'] ?? null,
                ':status'       => $data['Status'] ?? 1,
                ':user_id'      => $_SESSION['id'],
                ':company_name' => $data['Company Name'] ?? null
            ]);
        } else {
            continue; // Unknown CSV type, skip row
        }
    }

    $pdo->commit();
    echo "success|CSV imported successfully!";
} catch (PDOException $e) {
    $pdo->rollBack();
    echo "error|Database error: " . $e->getMessage();
} finally {
    fclose($handle);
}
?>
