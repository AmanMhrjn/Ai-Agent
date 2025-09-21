<?php
session_start();
require_once 'config/database.php';

// --- Check login ---
if (!isset($_SESSION['id']) || !isset($_SESSION['companyname'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['id'];
$company_name = $_SESSION['companyname'];

$default_platform = 'free'; // default for payments without a platform

// --- Handle platform update from modal ---
if (isset($_POST['update_platform'])) {
    $agent_id = $_POST['agent_id'];
    $platform = $_POST['platform'];

    if (!empty($platform)) {
        $stmt = $pdo->prepare("
            UPDATE agents 
            SET platform = :platform 
            WHERE id = :agent_id AND user_id = :user_id
        ");
        $stmt->execute([
            ':platform' => $platform,
            ':agent_id' => $agent_id,
            ':user_id' => $user_id
        ]);
    }

    // After updating, remove from needsPlatform to prevent modal from showing again
    header("Location: maindashboard.php");
    exit;
}



// --- Auto-create agents for approved payments ---
$stmt_new_agents = $pdo->prepare("
    SELECT id, platform, plan
    FROM payment_requests
    WHERE user_id = :user_id
      AND status = 'approved'
      AND id NOT IN (SELECT payment_id FROM agents)
");
$stmt_new_agents->execute([':user_id' => $user_id]);
$new_agents = $stmt_new_agents->fetchAll(PDO::FETCH_ASSOC);

$needsPlatform = []; // Array to track agents needing platform selection

foreach ($new_agents as $payment) {
    $platform_to_use = !empty($payment['platform']) ? $payment['platform'] : $default_platform;

    $insertAgent = $pdo->prepare("
        INSERT INTO agents (user_id, company_name, platform, plan, payment_id, created_at)
        VALUES (:user_id, :company_name, :platform, :plan, :payment_id, NOW())
    ");
    $insertAgent->execute([
        ':user_id' => $user_id,
        ':company_name' => $company_name,
        ':platform' => $platform_to_use,
        ':plan' => $payment['plan'],
        ':payment_id' => $payment['id']
    ]);

    // If platform is default, add to modal list
    if ($platform_to_use === $default_platform) {
        $needsPlatform[] = $pdo->lastInsertId();
    }
}

// --- Fetch all agents for this user ---
$stmt_agents = $pdo->prepare("
    SELECT id, platform, plan, created_at, page_name
    FROM agents
    WHERE user_id = :user_id
    ORDER BY created_at ASC
");

$stmt_agents->execute([':user_id' => $user_id]);
$agents = $stmt_agents->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Dashboard</title>
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="assets/css/navbar.css">
    <link rel="stylesheet" href="assets/css/footer.css">
    <style>
        .section {
            max-width: 1200px;
            margin: 20px 150px;
            padding: 15px;
        }

        h2 {
            text-align: center;
            margin-bottom: 12px;
        }

        /* Agents List */
        .agents-list {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin: 15px 150px;
        }

        .agent-card {
            background: #fff;
            padding: 15px 20px;
            border-radius: 8px;
            box-shadow: 0 3px 8px rgba(0, 0, 0, 0.1);
            text-align: center;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .agent-card img {
            width: 100%;
            height: auto;
            object-fit: cover;
            opacity: 50%;
            margin-bottom: 10px;
        }

        .agent-card a {
            text-decoration: none;
            color: #006bde;
            font-weight: bold;
            display: block;
            margin-bottom: 5px;
        }

        .agent-card p {
            font-size: 0.9rem;
            color: #555;
            margin: 2px 0;
        }

        .add-agent-btn {
            margin-top: 20px;
            padding: 12px 25px;
            cursor: pointer;
            border-radius: 5px;
            border: none;
            background: #006bde;
            color: #fff;
            font-weight: bold;
        }

        .add-agent-btn:hover {
            background: #0056b3;
        }

        /* Table */
        table {
            width: 100%;
            border-collapse: collapse;
            background: #fff;
            border-radius: 8px;
            overflow: hidden;
        }

        th,
        td {
            text-align: left;
            padding: 12px;
            border-bottom: 1px solid #ddd;
            font-size: 0.95rem;
        }

        th {
            background: #0056b3;
            font-weight: bold;
            color: #fff;
        }

        tr:hover {
            background: #f1f1f1;
        }

        /* Modal */
        #platformModal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 9999;
        }

        #platformModal .modal-content {
            background: #fff;
            padding: 30px 25px;
            border-radius: 12px;
            width: 320px;
            max-width: 90%;
            text-align: center;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.3);
        }

        #platformModal h3 {
            margin-bottom: 20px;
            font-size: 1.2rem;
            color: #333;
        }

        #platformModal select {
            width: 100%;
            padding: 10px 12px;
            margin-bottom: 20px;
            border-radius: 6px;
            border: 1px solid #ccc;
            font-size: 1rem;
        }

        #platformModal button {
            padding: 10px 25px;
            border: none;
            border-radius: 6px;
            background-color: #006bde;
            color: #fff;
            font-weight: bold;
            cursor: pointer;
            transition: background 0.3s;
        }

        #platformModal button:hover {
            background-color: #0056b3;
        }

        @media(max-width:767px) {
            .agents-list {
                flex-direction: column;
                align-items: center;
            }

            .agent-card {
                width: 90%;
            }
        }
    </style>
</head>

<body>
    <?php include_once 'assets/component/navbar.php'; ?>

    <?php if (!empty($needsPlatform)): ?>
        <div id="platformModal">
            <div class="modal-content">
                <h3>Select Platform</h3>
                <form method="post">
                    <input type="hidden" name="agent_id" value="<?= $needsPlatform[0] ?>">
                    <select name="platform" required>
                        <option value="">-- Select Platform --</option>
                        <option value="Instagram">Instagram</option>
                        <option value="Facebook">Facebook</option>
                        <option value="WhatsApp">WhatsApp</option>
                    </select>
                    <br>
                    <button type="submit" name="update_platform">Save</button>
                </form>
            </div>
        </div>
    <?php endif; ?>

    <div style="text-align:center; margin-top:20px;">
        <h1>Welcome, <?= htmlspecialchars($company_name) ?></h1>
        <p>Here is your agent overview:</p>
    </div>

    <div class="agents-list">
        <?php if (!empty($agents)): ?>
            <?php foreach ($agents as $agent): ?>
                <div class="agent-card">
                    <img src="images/background.jpg" alt="Agent" class="agent-image">
                    <a href="dashboard.php?agent_id=<?= $agent['id'] ?>" style="text-transform: uppercase;">
                        <?= !empty($agent['page_name']) ? htmlspecialchars($agent['page_name']) : (htmlspecialchars($agent['platform']) ?: 'Unnamed Platform') ?>
                    </a>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p style="text-align:center;">You have no agents yet.</p>
        <?php endif; ?>
    </div>

    <div style="text-align:center; gap: 10px; display:flex; justify-content:center; flex-wrap:wrap;">
        <button class="add-agent-btn" onclick="window.location.href='pricing.php'">Buy New Agent</button>
        <button class="add-agent-btn" onclick="openManageAgents()">Manage Agents</button>
    </div>



    <div class="section">
        <h2>Your Purchased Agents</h2>
        <table style="overflow-x:auto;">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Payment Method</th>
                    <th>Amount</th>
                    <th>Plan</th>
                    <th>Status</th>
                    <th>Payment Date</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $stmt = $pdo->prepare("SELECT id, payment_method, amount, plan, status, created_at
                                   FROM payment_requests
                                   WHERE company_name = :company_name
                                   ORDER BY created_at DESC");
                $stmt->execute([':company_name' => $company_name]);
                $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);

                if (empty($payments)) {
                    echo '<tr><td colspan="6" style="text-align:center;">No payments found for your company.</td></tr>';
                } else {
                    $counter = 1;
                    foreach ($payments as $row) {
                        $status_text = ucfirst($row['status']);
                        echo '<tr>';
                        echo '<td>' . $counter . '</td>';
                        echo '<td>' . htmlspecialchars($row['payment_method']) . '</td>';
                        echo '<td>' . htmlspecialchars($row['amount']) . '</td>';
                        echo '<td>' . htmlspecialchars($row['plan']) . '</td>';
                        echo '<td>' . htmlspecialchars($status_text) . '</td>';
                        echo '<td>' . htmlspecialchars($row['created_at']) . '</td>';
                        echo '</tr>';
                        $counter++;
                    }
                }
                ?>
            </tbody>
        </table>
    </div>
    <!-- Manage Agents Modal -->
    <div id="manageAgentsModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:10000; justify-content:center; align-items:center;">
        <div style="background:#fff; padding:25px; border-radius:12px; width:90%; max-width:800px; max-height:80vh; overflow-y:auto; box-shadow:0 8px 20px rgba(0,0,0,0.3);">
            <h2 style="text-align:center; margin-bottom:20px;">Manage Agents</h2>
            <table style="width:100%; border-collapse:collapse;">
                <thead>
                    <tr>
                        <th style="background:#006bde; color:#fff; padding:10px;">#</th>
                        <th style="background:#006bde; color:#fff; padding:10px;">Platform</th>
                        <th style="background:#006bde; color:#fff; padding:10px;">Page Name</th>
                        <th style="background:#006bde; color:#fff; padding:10px;">Plan</th>
                        <th style="background:#006bde; color:#fff; padding:10px;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($agents)): ?>
                        <?php $count = 1;
                        foreach ($agents as $agent): ?>
                            <tr>
                                <td style="padding:10px;"><?= $count++ ?></td>
                                <td style="padding:10px;"><?= htmlspecialchars($agent['platform']) ?></td>
                                <td style="padding:10px;">
                                    <form method="post" style="display:flex; gap:5px; align-items:center;">
                                        <input type="hidden" name="agent_id" value="<?= $agent['id'] ?>">
                                        <input type="text" name="page_name" value="<?= htmlspecialchars($agent['page_name'] ?? '') ?>" placeholder="Enter Page Name" style="padding:6px; width:100%; border:1px solid #ccc; border-radius:5px;">
                                </td>
                                <td style="padding:10px;"><?= htmlspecialchars($agent['plan']) ?></td>
                                <td style="padding:10px;">
                                    <button type="submit" name="update_page_name" style="background:#006bde; color:#fff; padding:6px 15px; border:none; border-radius:5px; cursor:pointer;">Save</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" style="text-align:center; padding:15px;">No agents found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
            <br>
            <div style="text-align:center;">
                <button onclick="closeManageAgents()" style="background:#999; color:#fff; padding:8px 20px; border:none; border-radius:5px; cursor:pointer;">Close</button>
            </div>
        </div>
    </div>

    <script>
        function openManageAgents() {
            document.getElementById("manageAgentsModal").style.display = "flex";
        }

        function closeManageAgents() {
            document.getElementById("manageAgentsModal").style.display = "none";
        }
    </script>


    <?php include_once 'assets/component/footer.php'; ?>
</body>

</html>