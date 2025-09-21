<?php
session_start();
require_once 'config/database.php'; // Must create $pdo (PDO connection)

// Redirect if not logged in
if (!isset($_SESSION['id']) || !isset($_SESSION['companyname'])) {
    header("Location: assets/component/login.php");
    exit;
}

$user_id = (int)$_SESSION['id'];
$company_name = $_SESSION['companyname'];
$success = "";
$error = "";

// ---------- Fetch agents for this user ----------
$agents = [];
try {
    $stmtAgents = $pdo->prepare("SELECT id, platform, plan FROM agents WHERE user_id = :user_id ORDER BY platform ASC");
    $stmtAgents->execute([':user_id' => $user_id]);
    $agents = $stmtAgents->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Could not load agents list.";
}

// ---------- Handle form submission ----------
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $product_name = trim($_POST['product_name'] ?? '');
    $offer_price  = $_POST['offer_price'] ?? '';
    $color        = trim($_POST['color'] ?? '');
    $warranty     = trim($_POST['warranty'] ?? '');
    $availability = $_POST['availability'] ?? '';
    $description  = trim($_POST['description'] ?? '');
    $status       = isset($_POST['status']) ? (int)$_POST['status'] : 1;
    $agent_name   = trim($_POST['agent_name'] ?? ''); // safe access

    // Validate required fields
    if ($agent_name === '') {
        $error = "Please select an agent.";
    } elseif ($product_name === '' || $offer_price === '' || $color === '' || $warranty === '' || $availability === '' || $description === '') {
        $error = "All fields are required.";
    } elseif (!is_numeric($offer_price)) {
        $error = "Offer price must be a number.";
    }

    // Handle multiple image upload
    $uploadedImages = [];
    $uploadDir = 'uploads/';

    if (!$error) {
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

        if (isset($_FILES['photos']) && !empty($_FILES['photos']['name']) && is_array($_FILES['photos']['name'])) {
            foreach ($_FILES['photos']['tmp_name'] as $key => $tmpName) {
                if ($_FILES['photos']['error'][$key] === UPLOAD_ERR_OK) {
                    $originalName = basename($_FILES['photos']['name'][$key]);
                    $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
                    $allowed = ['jpg','jpeg','png','gif'];

                    if (!in_array($extension, $allowed, true)) {
                        $error = "Only JPG, JPEG, PNG & GIF files are allowed.";
                        break;
                    }

                    $newFileName = time() . '_' . uniqid('', true) . '.' . $extension;
                    $targetFile = $uploadDir . $newFileName;

                    if (move_uploaded_file($tmpName, $targetFile)) {
                        $uploadedImages[] = $targetFile;
                    } else {
                        $error = "Failed to upload image.";
                        break;
                    }
                } else {
                    $error = "Error uploading file.";
                    break;
                }
            }
        } else {
            $error = "Please upload at least one image.";
        }
    }

    // Insert into DB
    if (!$error) {
        $photo_1 = $uploadedImages[0] ?? '';
        $photo_2 = $uploadedImages[1] ?? '';
        $offer_price_int = (int)$offer_price;

        try {
            $sql = "INSERT INTO products 
                (user_id, company_name, agent_name, product_name, offer_price, color, warranty, availability, description, status, photo_1, photo_2)
                VALUES (:user_id, :company_name, :agent_name, :product_name, :offer_price, :color, :warranty, :availability, :description, :status, :photo_1, :photo_2)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':user_id'      => $user_id,
                ':company_name' => $company_name,
                ':agent_name'   => $agent_name,
                ':product_name' => $product_name,
                ':offer_price'  => $offer_price_int,
                ':color'        => $color,
                ':warranty'     => $warranty,
                ':availability' => $availability,
                ':description'  => $description,
                ':status'       => $status,
                ':photo_1'      => $photo_1,
                ':photo_2'      => $photo_2
            ]);

            header("Location: agent.php");
            exit;
        } catch (PDOException $e) {
            $error = "❌ Failed to add product: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Product</title>
    <link rel="stylesheet" href="/assets/css/main.css">
    <link rel="stylesheet" href="assets/css/navbar.css">
    <link rel="stylesheet" href="assets/css/footer.css">
    <style>
       .form-container {
    max-width: 600px;
    margin: 20px auto;
    background: white;
    padding: 25px 30px;
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

h2 { text-align: center; }

.form-container input,
textarea,
select {
    width: 100%;
    margin: 10px 0 20px;
    padding: 12px;
    border-radius: 6px;
    border: 1px solid #ccc;
    font-size: 1rem;
    box-sizing: border-box;
}

.form-container button {
    width: 100%;
    background: #006bdeff;
    color: white;
    border: none;
    padding: 12px;
    border-radius: 6px;
    font-size: 16px;
    cursor: pointer;
    transition: background 0.3s;
}

.form-container button:hover { background: #0056b3; }

.cancel-btn {
    display: block;
    text-align: center;
    background: #e93a00ff;
    color: white;
    padding: 12px;
    border-radius: 6px;
    font-size: 16px;
    margin-top: 10px;
    text-decoration: none;
    transition: background 0.3s;
}

.cancel-btn:hover { background: #a52b02ff; }

.msg { text-align: center; margin-bottom: 20px; font-weight: bold; }
.msg.success { color: green; }
.msg.error { color: red; }

/* -------------------- Responsive Styles -------------------- */

/* Tablets (768px - 1024px) */
@media (max-width: 1024px) {
    .form-container {
        max-width: 500px;
        padding: 20px;
        margin: 20px auto;
    }
    .form-container input,
    .form-container textarea,
    .form-container select {
        font-size: 0.95rem;
        padding: 10px;
    }
    .form-container button, .cancel-btn {
        font-size: 15px;
        padding: 10px;
    }
}

/* Mobile (up to 767px) */
@media (max-width: 767px) {
    .form-container {
        max-width: 90%;
        padding: 15px;
        margin: 15px auto;
    }
    h2 { font-size: 1.5rem; }
    .form-container input,
    .form-container textarea,
    .form-container select {
        font-size: 0.9rem;
        padding: 8px;
    }
    .form-container button, .cancel-btn {
        font-size: 14px;
        padding: 10px;
    }
}
    </style>
</head>
<body>
<?php include_once 'assets/component/navbar.php'; ?>
<div class="form-container">
    <h2>Add New Product</h2>

    <?php if ($success): ?>
        <div class="msg success"><?= htmlspecialchars($success) ?></div>
    <?php elseif ($error): ?>
        <div class="msg error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">
        <input type="text" name="product_name" placeholder="Product Name" required>
        <input type="number" name="offer_price" placeholder="Offer Price" required>
        <input type="text" name="color" placeholder="Color" required>
        <input type="text" name="warranty" placeholder="Warranty" required>

        <select name="availability" required>
            <option value="">-- Select Availability --</option>
            <option value="Yes">Yes</option>
            <option value="No">No</option>
        </select>

        <textarea name="description" placeholder="Description" rows="4" required></textarea>

        <select name="status" required>
            <option value="1">Enabled</option>
            <option value="0">Disabled</option>
        </select>

        <!-- Agent dropdown -->
        <select name="agent_name" required>
            <option value="">-- Select Agent --</option>
            <?php foreach ($agents as $agent): ?>
                <option value="<?= htmlspecialchars($agent['platform'] . ' (' . ucfirst($agent['plan']) . ')') ?>">
                    <?= htmlspecialchars($agent['platform'] . ' (' . ucfirst($agent['plan']) . ')') ?>
                </option>
            <?php endforeach; ?>
        </select>

        <input type="file" name="photos[]" multiple required accept="image/*">

        <button type="submit">+ Add Product</button>
        <a href="agent.php" class="cancel-btn">Cancel</a>
    </form>
</div>

<?php include_once 'assets/component/footer.php'; ?>
</body>
</html>
