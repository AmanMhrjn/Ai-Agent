<?php
session_start();
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header("Location: index.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Admin Login</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f6f9;
            display: flex;
            height: 100vh;
            justify-content: center;
            align-items: center;
            margin: 0;
        }

        .login-box {
            background: #fff;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.15);
            width: 100%;
            max-width: 400px;
            text-align: center;
            box-sizing: border-box;
        }

        h2 {
            margin-bottom: 20px;
            font-size: 1.8rem;
        }

        input {
            width: 100%;
            padding: 12px;
            margin: 10px 0;
            border: 1px solid #ccc;
            border-radius: 8px;
            font-size: 1rem;
            box-sizing: border-box;
        }

        button {
            width: 100%;
            padding: 12px;
            background: #007bff;
            color: #fff;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: bold;
            margin-top: 10px;
            font-size: 1rem;
            transition: background 0.3s ease;
        }

        button:hover {
            background: #0056b3;
        }

        .error {
            color: red;
            margin-bottom: 15px;
            font-size: 0.9rem;
        }

        /* Tablet */
        @media (max-width: 768px) {
            .login-box {
                padding: 25px;
                max-width: 350px;
            }

            h2 {
                font-size: 1.6rem;
            }

            input,
            button {
                padding: 10px;
                font-size: 0.95rem;
            }
        }

        /* Mobile */
        @media (max-width: 480px) {
            body {
                padding: 20px;
                height: auto;
                justify-content: flex-start;
                align-items: center;
            }

            .login-box {
                padding: 20px;
                width: 100%;
                max-width: 300px;
            }

            h2 {
                font-size: 1.4rem;
            }

            input,
            button {
                padding: 10px;
                font-size: 0.9rem;
            }
        }
    </style>
</head>

<body>
    <div class="login-box">
        <h2>Admin Login</h2>
        <?php if (isset($_GET['error'])): ?>
            <div class="error"><?= htmlspecialchars($_GET['error']) ?></div>
        <?php endif; ?>
        <form action="auth.php" method="POST">
            <input type="text" name="username" placeholder="Username" required>
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit">Login</button>
        </form>
    </div>
</body>

</html>