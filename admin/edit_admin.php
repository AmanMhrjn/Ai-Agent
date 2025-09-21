<?php
session_start();
require_once '../config/database.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit;
}

// Validate ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: index.php");
    exit;
}

$id = (int)$_GET['id'];

// Fetch admin info
$stmt = $pdo->prepare("SELECT * FROM admins WHERE id = ? LIMIT 1");
$stmt->execute([$id]);
$admin = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$admin) {
    header("Location: index.php");
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    if (empty($username)) {
        $error = "Username cannot be empty.";
    } else {
        // Update admin
        $stmt = $pdo->prepare("UPDATE admins SET username = ?, password = ? WHERE id = ?");
        if ($stmt->execute([$username, $password, $id])) {
            $_SESSION['success'] = "Admin updated successfully.";
            header("Location: index.php");
            exit;
        } else {
            $error = "Failed to update admin.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Edit Admin</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f6f9;
            margin: 0;
            padding: 20px;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            box-sizing: border-box;
        }

        .form-container {
            background: #fff;
            padding: 30px 40px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            max-width: 400px;
            width: 100%;
            box-sizing: border-box;
        }

        h2 {
            text-align: center;
            margin-bottom: 20px;
            font-size: 1.5rem;
            color: #333;
        }

        input {
            width: 100%;
            padding: 12px;
            margin-bottom: 15px;
            border-radius: 6px;
            border: 1px solid #ccc;
            font-size: 1rem;
            box-sizing: border-box;
        }

        button {
            width: 100%;
            padding: 12px;
            border: none;
            border-radius: 6px;
            background: #007bff;
            color: #fff;
            font-size: 1rem;
            cursor: pointer;
            transition: background 0.3s ease;
        }

        button:hover {
            background: #0056b3;
        }

        .error {
            color: red;
            margin-bottom: 15px;
            font-size: 0.95rem;
            text-align: center;
        }

        /* Tablet: Medium Screens */
        @media (max-width: 1024px) {
            .form-container {
                padding: 25px 30px;
            }

            h2 {
                font-size: 1.4rem;
            }

            input,
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

            .form-container {
                padding: 20px 20px;
            }

            h2 {
                font-size: 1.3rem;
            }

            input,
            button {
                font-size: 0.9rem;
                padding: 10px;
            }
        }

        /* Extra Small Mobile */
        @media (max-width: 480px) {
            .form-container {
                padding: 15px 15px;
            }

            h2 {
                font-size: 1.2rem;
            }

            input,
            button {
                font-size: 0.85rem;
                padding: 8px;
            }
        }
    </style>
</head>

<body>
    <div class="form-container">
        <h2>Edit Admin</h2>
        <?php if (!empty($error)) echo "<p class='error'>{$error}</p>"; ?>
        <form method="POST">
            <label>Username:</label>
            <input type="text" name="username" value="<?= htmlspecialchars($admin['username']) ?>" required>

            <label>Password:</label>
            <input type="text" name="password" value="<?= htmlspecialchars($admin['password']) ?>" required>

            <button type="submit">Update Admin</button>
        </form>
    </div>
</body>

</html>