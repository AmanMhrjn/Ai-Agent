<?php
session_start();
require_once 'config/database.php'; // contains $pdo

// --- Restrict access if not logged in ---
if (!isset($_SESSION['id']) || !isset($_SESSION['companyname'])) {
    echo '<!DOCTYPE html>
    <html lang="en"><head><meta charset="UTF-8"><title>Access Denied</title></head>
    <body><h2>Please login to access profile</h2></body></html>';
    exit;
}

$user_id = $_SESSION['id'];

// --- Fetch user details ---
$stmt_user = $pdo->prepare("SELECT * FROM users WHERE user_id = :user_id LIMIT 1");
$stmt_user->execute(['user_id' => $user_id]);
$user = $stmt_user->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    die("User not found.");
}

// --- Handle profile update ---
$update_msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $new_username = trim($_POST['username']);
    $new_company  = trim($_POST['company_name']);

    if ($new_username && $new_company) {
        $stmt_update = $pdo->prepare("UPDATE users SET username = :username, company_name = :company_name WHERE user_id = :user_id");
        $stmt_update->execute([
            'username' => $new_username,
            'company_name' => $new_company,
            'user_id' => $user_id
        ]);

        // update session too
        $_SESSION['companyname'] = $new_company;

        $update_msg = "Profile updated successfully!";
        // Refresh user data
        $stmt_user->execute(['user_id' => $user_id]);
        $user = $stmt_user->fetch(PDO::FETCH_ASSOC);
    } else {
        $update_msg = "All fields are required.";
    }
}

// --- Fetch purchased plans ---
$stmt_plans = $pdo->prepare("SELECT * FROM payment_requests WHERE user_id = :user_id ORDER BY created_at DESC");
$stmt_plans->execute(['user_id' => $user_id]);
$plans = $stmt_plans->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>User Profile</title>
    <link rel="stylesheet" href="assets/css/navbar.css">
    <link rel="stylesheet" href="assets/css/footer.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f6f9;
            margin: 0;
            padding: 0;
        }

        .profile-container {
            max-width: 900px;
            margin: 85px auto 20px auto;
            padding: 20px;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.1);
            box-sizing: border-box;
        }

        .profile-container h1 {
            text-align: center;
            margin-bottom: 20px;
        }

        form {
            margin-bottom: 30px;
        }

        label {
            display: block;
            margin: 10px 0 5px;
            font-weight: 600;
        }

        input[type="text"],
        input[type="email"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 6px;
            box-sizing: border-box;
        }

        button {
            margin-top: 15px;
            padding: 10px 20px;
            background: #28a745;
            color: #fff;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            width: 100%;
            box-sizing: border-box;
        }

        button:hover {
            background: #218838;
        }

        .msg {
            margin: 10px 0;
            font-weight: bold;
            color: green;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            overflow-x: auto;
        }

        th,
        td {
            padding: 10px;
            border: 1px solid #ddd;
            text-align: center;
            word-break: break-word;
        }

        th {
            background: #f4f4f4;
        }

        /* Responsive styles */

        /* Tablets */
        @media (max-width: 1024px) {
            .profile-container {
                margin: 60px auto 20px auto;
                padding: 18px;
            }

            input[type="text"],
            input[type="email"],
            button {
                padding: 9px;
            }

            table th,
            table td {
                padding: 8px;
                font-size: 14px;
            }
        }

        /* Mobile Portrait and small tablets */
        @media (max-width: 768px) {
            .profile-container {
                margin: 40px 10px 20px 10px;
                padding: 15px;
            }

            h1 {
                font-size: 20px;
            }

            input[type="text"],
            input[type="email"],
            button {
                font-size: 14px;
                padding: 8px;
            }

            table th,
            table td {
                padding: 6px;
                font-size: 13px;
            }
        }

        /* Small mobile devices */
        @media (max-width: 480px) {
            .profile-container {
                margin: 30px 5px 20px 5px;
                padding: 12px;
            }

            h1 {
                font-size: 18px;
            }

            label {
                font-size: 13px;
            }

            input[type="text"],
            input[type="email"],
            button {
                font-size: 13px;
                padding: 7px;
            }

            table th,
            table td {
                padding: 5px;
                font-size: 11px;
            }

            table {
                display: block;
                overflow-x: auto;
            }
        }
    </style>
</head>

<body>
    <?php include_once 'assets/component/navbar.php'; ?>

    <div class="profile-container">
        <h1>User Profile</h1>

        <?php if ($update_msg): ?>
            <div class="msg"><?= htmlspecialchars($update_msg) ?></div>
        <?php endif; ?>

        <form method="post">
            <label>Username</label>
            <input type="text" name="username" value="<?= htmlspecialchars($user['username']) ?>" required>

            <label>Email (read-only)</label>
            <input type="email" value="<?= htmlspecialchars($user['email']) ?>" readonly>

            <label>Company Name</label>
            <input type="text" name="company_name" value="<?= htmlspecialchars($user['company_name']) ?>" required>

            <button type="submit" name="update_profile">Update Profile</button>
        </form>

        <h2>Purchased Plans</h2>
        <table>
            <thead>
                <tr>
                    <th>Plan ID</th>
                    <th>Platform</th>
                    <th>Amount</th>
                    <th>Status</th>
                    <th>Purchased At</th>
                </tr>
            </thead>
            <tbody>
                <?php $count = 1; ?>
                <?php if (!$plans): ?>
                    <tr>
                        <td colspan="5">No plans purchased yet.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($plans as $plan): ?>
                        <tr>
                            <td><?= $count++ ?></td>
                            <td><?= htmlspecialchars($plan['platform']) ?></td>
                            <td><?= htmlspecialchars($plan['amount'] ?? 'N/A') ?></td>
                            <td><?= htmlspecialchars($plan['status'] ?? 'Pending') ?></td>
                            <td><?= htmlspecialchars($plan['created_at']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php include_once 'assets/component/footer.php'; ?>
</body>

</html>