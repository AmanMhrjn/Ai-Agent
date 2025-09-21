<?php
require_once '../config/database.php';

$id = $_GET['id'];

// Fetch product
$stmt = $pdo->prepare("SELECT * FROM products WHERE id = :id");
$stmt->execute([':id' => $id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = $pdo->prepare("UPDATE products SET product_name=:name, offer_price=:price, color=:color, warranty=:warranty, availability=:availability, description=:description WHERE id=:id");
    $stmt->execute([
        ':name' => $_POST['product_name'],
        ':price' => $_POST['offer_price'],
        ':color' => $_POST['color'],
        ':warranty' => $_POST['warranty'],
        ':availability' => $_POST['availability'],
        ':description' => $_POST['description'],
        ':id' => $id
    ]);
    header('Location: products.php');
}
?>

<!DOCTYPE html>
<html>

<head>
    <title>Edit Product</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f5f5f5;
            margin: 0;
            padding: 20px;
            display: flex;
            justify-content: center;
            align-items: flex-start;
            min-height: 100vh;
            box-sizing: border-box;
        }

        .container {
            background: #fff;
            padding: 30px 40px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            max-width: 600px;
            width: 100%;
            box-sizing: border-box;
            margin-top: 50px;
        }

        h2 {
            text-align: center;
            margin-bottom: 25px;
            color: #333;
            font-size: 1.5rem;
        }

        .back-link {
            display: inline-block;
            margin-bottom: 20px;
            color: #007bff;
            text-decoration: none;
            font-size: 14px;
        }

        .back-link:hover {
            text-decoration: underline;
        }

        form {
            display: flex;
            flex-direction: column;
        }

        label {
            margin-bottom: 5px;
            font-weight: bold;
            color: #555;
        }

        input[type="text"],
        input[type="number"],
        textarea {
            padding: 12px;
            margin-bottom: 20px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 14px;
            width: 100%;
            box-sizing: border-box;
        }

        textarea {
            resize: vertical;
            min-height: 100px;
        }

        button {
            padding: 12px;
            background-color: #28a745;
            color: white;
            font-size: 16px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            transition: background 0.3s ease;
        }

        button:hover {
            background-color: #218838;
        }

        /* Tablet: Medium Screens */
        @media (max-width: 1024px) {
            .container {
                padding: 25px 30px;
            }

            h2 {
                font-size: 1.4rem;
            }

            input,
            textarea,
            button {
                font-size: 0.95rem;
                padding: 10px;
            }
        }

        /* Mobile: Small Screens */
        @media (max-width: 768px) {
            body {
                padding: 15px;
            }

            .container {
                padding: 20px 20px;
                margin-top: 30px;
            }

            h2 {
                font-size: 1.3rem;
            }

            input,
            textarea,
            button {
                font-size: 0.9rem;
                padding: 10px;
            }
        }

        /* Extra Small Mobile */
        @media (max-width: 480px) {
            .container {
                padding: 15px 15px;
                margin-top: 20px;
            }

            h2 {
                font-size: 1.2rem;
            }

            input,
            textarea,
            button {
                font-size: 0.85rem;
                padding: 8px;
            }
        }
    </style>
</head>

<body>

    <div class="container">
        <a class="back-link" href="products.php">&larr; Back to Products</a>
        <h2>Edit Product</h2>

        <form method="POST">
            <label for="product_name">Product Name</label>
            <input type="text" name="product_name" id="product_name" value="<?= htmlspecialchars($product['product_name']) ?>" required>

            <label for="offer_price">Offer Price</label>
            <input type="number" name="offer_price" id="offer_price" value="<?= $product['offer_price'] ?>" required>

            <label for="color">Color</label>
            <input type="text" name="color" id="color" value="<?= htmlspecialchars($product['color']) ?>" required>

            <label for="warranty">Warranty</label>
            <input type="text" name="warranty" id="warranty" value="<?= htmlspecialchars($product['warranty']) ?>" required>

            <label for="availability">Availability</label>
            <input type="text" name="availability" id="availability" value="<?= htmlspecialchars($product['availability']) ?>" required>

            <label for="description">Description</label>
            <textarea name="description" id="description"><?= htmlspecialchars($product['description']) ?></textarea>

            <button type="submit">Update Product</button>
        </form>
    </div>

</body>

</html>