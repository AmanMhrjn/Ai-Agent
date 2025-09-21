<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['id']) || !isset($_SESSION['companyname'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['id'];

// Fetch all agents of this user (so they can choose which agent to link page to)
$stmt_agents = $pdo->prepare("SELECT id, platform, plan FROM agents WHERE user_id = :user_id");
$stmt_agents->execute([':user_id' => $user_id]);
$agents = $stmt_agents->fetchAll(PDO::FETCH_ASSOC);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $agent_id = $_POST['agent_id'] ?? '';
    $page_name = $_POST['page_name'] ?? '';

    if (!empty($agent_id) && !empty($page_name)) {
        $stmt = $pdo->prepare("INSERT INTO agent_pages (agent_id, page_name) VALUES (:agent_id, :page_name)");
        $stmt->execute([
            ':agent_id' => $agent_id,
            ':page_name' => $page_name
        ]);

        $new_page_id = $pdo->lastInsertId();

        // Redirect to the page dashboard
        header("Location: agent.php?page_id=" . $new_page_id);
        exit;
    } else {
        $error = "Please fill all fields.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Add Page</title>
    <link rel="stylesheet" href="assets/css/main.css">
    <style>
        body {
            font-family: "Segoe UI", Arial, sans-serif;
            background: #f4f6f9;
            margin: 0;
            padding: 0;
        }

        .form-container {
            max-width: 600px;
            margin: 60px auto;
            background: #fff;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 6px 18px rgba(0, 0, 0, 0.08);
        }

        .form-container h2 {
            text-align: center;
            margin-bottom: 25px;
            font-size: 1.8rem;
            color: #333;
        }

        .form-container label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #444;
        }

        .form-container select,
        .form-container input[type="text"] {
            width: 100%;
            padding: 12px 14px;
            margin-bottom: 20px;
            border: 1px solid #ccc;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s;
        }

        .form-container select:focus,
        .form-container input[type="text"]:focus {
            border-color: #006bde;
            outline: none;
        }

        .form-container .add-agent-btn {
            width: 100%;
            padding: 14px;
            border: none;
            border-radius: 8px;
            background: #006bde;
            color: #fff;
            font-weight: bold;
            font-size: 1rem;
            cursor: pointer;
            transition: background 0.3s ease;
        }

        .form-container .add-agent-btn:hover {
            background: #0056b3;
        }

        .form-container p.error {
            color: #e53935;
            font-weight: bold;
            text-align: center;
            margin-bottom: 15px;
        }

        /* Responsive */
        @media (max-width: 767px) {
            .form-container {
                margin: 30px 15px;
                padding: 20px;
            }

            .form-container h2 {
                font-size: 1.5rem;
            }
        }
    </style>
</head>

<body>
    <?php include_once 'assets/component/navbar.php'; ?>

    <div class="form-container">
        <h2>Add a New Page</h2>
        <?php if (!empty($error)): ?>
            <p class="error"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>
        <form method="post">
            <label for="agent_id">Select Agent:</label>
            <select name="agent_id" id="agent_id" required>
                <option value="">-- Select Agent --</option>
                <?php foreach ($agents as $agent): ?>
                    <option value="<?= $agent['id'] ?>">
                        <?= htmlspecialchars($agent['platform'] . " (" . $agent['plan'] . ")") ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label for="page_name">Page Name:</label>
            <input type="text" name="page_name" id="page_name" required>

            <button type="submit" class="add-agent-btn">Create Page</button>
        </form>
    </div>

    <?php include_once 'assets/component/footer.php'; ?>
</body>

</html>