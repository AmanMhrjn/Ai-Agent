<?php
session_start();
require_once 'config/database.php'; // defines $pdo

// --- Only logged-in users ---
if (!isset($_SESSION['id']) || !isset($_SESSION['companyname'])) {
    echo '<!DOCTYPE html>
    <html lang="en">
    <head><meta charset="UTF-8"><title>Access Denied</title></head>
    <body><h2>Please login to access dashboard</h2></body></html>';
    exit;
}

$user_id = $_SESSION['id'];
$company_name = $_SESSION['companyname'];

// --- Fetch all agents for this user ---
$stmt_agents = $pdo->prepare("
    SELECT * FROM agents 
    WHERE user_id = :user_id 
    ORDER BY created_at DESC
");
$stmt_agents->execute(['user_id' => $user_id]);
$agents = $stmt_agents->fetchAll(PDO::FETCH_ASSOC);

// --- Determine selected agent/page ---
$selected_agent_id = 0;
$selected_page_id = isset($_GET['page_id']) ? (int)$_GET['page_id'] : 0;

$selected_agent = null;
$page_name = '';

if ($selected_page_id) {
    // Fetch agent info from page
    $stmt_page = $pdo->prepare("
        SELECT a.*, ap.page_name 
        FROM agent_pages ap
        JOIN agents a ON a.id = ap.agent_id
        WHERE ap.id = :page_id AND a.user_id = :user_id
    ");
    $stmt_page->execute([
        ':page_id' => $selected_page_id,
        ':user_id' => $user_id
    ]);
    $row = $stmt_page->fetch(PDO::FETCH_ASSOC);

    if ($row) {
        $selected_agent = $row;
        $selected_agent_id = $row['id'];
        $page_name = $row['page_name'];
    }
}

// fallback to agent_id if no page_id provided
if (!$selected_agent) {
    $selected_agent_id = isset($_GET['agent_id']) ? (int)$_GET['agent_id'] : ($agents[0]['id'] ?? 0);
    foreach ($agents as $agent) {
        if ($agent['id'] == $selected_agent_id) {
            $selected_agent = $agent;
            break;
        }
    }
    $page_name = $selected_agent['page_name'] ?? '';
}

if (!$selected_agent) die('No agent/page found for this user.');

// --- Date Filters ---
$from_date = !empty($_GET['from_date']) ? $_GET['from_date'] : '';
$to_date   = !empty($_GET['to_date']) ? $_GET['to_date'] : '';

// --- Fetch message balance ---
$query = "SELECT total_messages, messages_used, last_updated 
          FROM message_balance 
          WHERE agent_id = :agent_id";
$params = ['agent_id' => $selected_agent_id];

if ($from_date && $to_date) {
    $query .= " AND DATE(last_updated) BETWEEN :from_date AND :to_date";
    $params['from_date'] = $from_date;
    $params['to_date']   = $to_date;
}

$query .= " ORDER BY last_updated ASC";
$stmt_balance = $pdo->prepare($query);
$stmt_balance->execute($params);
$balance_rows = $stmt_balance->fetchAll(PDO::FETCH_ASSOC);

// Assign unlinked balances to this agent if company_name matches
$updateStmt = $pdo->prepare("
    UPDATE message_balance 
    SET agent_id = :agent_id 
    WHERE company_name = :company_name AND agent_id IS NULL
");
$updateStmt->execute([
    'agent_id' => $selected_agent_id,
    'company_name' => $company_name
]);

// --- Prepare cumulative data for charts ---
$labels = [];
$messageData = [];
$total_messages = 0;
$total_used = 0;
$cumulative_remaining = 0;

foreach ($balance_rows as $row) {
    $total_messages += (int)$row['total_messages'];
    $total_used     += (int)$row['messages_used'];
    $cumulative_remaining = $total_messages - $total_used;

    $labels[] = date('Y-m-d H:i', strtotime($row['last_updated']));
    $messageData[] = $cumulative_remaining;
}

if (empty($balance_rows)) {
    $labels = [date('Y-m-d')];
    $messageData = [0];
}

$latestBalance = [
    'total_messages' => $total_messages,
    'messages_used'  => $total_used,
    'remaining'      => max(0, $cumulative_remaining)
];

// Pie chart data
$pie_labels = ['Used Messages', 'Remaining Messages'];
$pie_values = [$latestBalance['messages_used'], $latestBalance['remaining']];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agent Dashboard</title>
    <link rel="stylesheet" href="assets/css/navbar.css">
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="assets/css/agent.css">
    <link rel="stylesheet" href="assets/css/footer.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .agent-container {
            margin-top: 60px;
            padding: 0 15px;
        }

        .message-balance-card {
            display: flex;
            gap: 20px;
            justify-content: center;
            margin: 30px 0;
            flex-wrap: wrap;
        }

        .message-balance-card>div {
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            width: 200px;
        }

        .agent-message-usage-section {
            margin-top: 40px;
            padding: 20px;
            overflow-x: auto;
        }

        .agent-message-usage-section table {
            width: 100%;
            border-collapse: collapse;
            font-family: Arial, sans-serif;
            background: #fff;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.05);
            border-radius: 8px;
            overflow: hidden;
        }

        .agent-message-usage-section thead {
            background: #1976d2;
            color: #fff;
        }

        .agent-message-usage-section th,
        .agent-message-usage-section td {
            padding: 12px 15px;
            text-align: center;
            font-size: 14px;
            white-space: nowrap;
        }

        .agent-message-usage-section tbody tr:nth-child(even) {
            background: #f9f9f9;
        }

        .agent-message-usage-section tbody tr:hover {
            background: #e3f2fd;
        }

        .agent-message-usage-section h2 {
            text-align: center;
            margin-bottom: 15px;
            font-size: 20px;
            color: #444;
        }

        @media (max-width:992px) {
            .message-balance-card {
                flex-direction: column;
                align-items: center;
            }

            .message-balance-card>div {
                width: 90%;
            }

            canvas {
                max-width: 100% !important;
            }
        }

        @media (max-width:600px) {

            .agent-table,
            .agent-message-usage-section table {
                font-size: 12px;
            }

            .agent-table th,
            .agent-table td {
                padding: 6px;
            }
        }
    </style>
</head>

<body>
    <?php include_once 'assets/component/navbar.php'; ?>

    <div class="agent-container">
        <h1 style="text-align:center; text-transform: uppercase; margin-top:16px;"><?= htmlspecialchars($page_name ?: $selected_agent['company_name']) ?></h1>

        <!-- Date filter -->
        <form method="get" style="margin:20px 0; text-align:center;">
            <input type="hidden" name="agent_id" value="<?= $selected_agent_id ?>">
            <?php if ($selected_page_id): ?>
                <input type="hidden" name="page_id" value="<?= $selected_page_id ?>">
            <?php endif; ?>
            <label>From: <input type="date" name="from_date" value="<?= htmlspecialchars($from_date) ?>"></label>
            <label>To: <input type="date" name="to_date" value="<?= htmlspecialchars($to_date) ?>"></label>
            <button type="submit">Filter</button>
        </form>

        <!-- Message Balance -->
        <div class="message-balance-card">
            <div style="background:#e0f7fa;">
                <h3>Total Messages</h3>
                <p style="font-size:1.5rem; font-weight:bold;"><?= $latestBalance['total_messages'] ?></p>
            </div>
            <div style="background:#fff3e0;">
                <h3>Messages Used</h3>
                <p style="font-size:1.5rem; font-weight:bold;"><?= $latestBalance['messages_used'] ?></p>
            </div>
            <div style="background:#e8f5e9;">
                <h3>Remaining Messages</h3>
                <p style="font-size:1.5rem; font-weight:bold;"><?= $latestBalance['remaining'] ?></p>
            </div>
        </div>

        <?php if ($latestBalance['remaining'] <= 10): ?>
            <p style="color:red; text-align:center; font-weight:bold; margin-bottom:20px;">
                **Warning:** <span style="font-style:italic;">Low remaining messages. Consider buying more tokens.</span>
            </p>
            <div style="text-align:center; margin-bottom:20px;">
                <a href="agent_payment.php" style="padding:10px 20px; background:#ff5722; color:#fff; border-radius:5px; text-decoration:none; font-weight:bold;">Buy Tokens</a>
            </div>
        <?php endif; ?>

        <!-- Line Chart -->
        <h2 style="text-align:center;">Daily Remaining Messages</h2>
        <canvas id="dailyMessageChart" style="max-width:1000px; margin:20px auto; display:block;"></canvas>

        <!-- Pie Chart -->
        <h2 style="text-align:center;">Message Usage Pie Chart</h2>
        <canvas id="messagePieChart" style="max-width:500px; margin:20px auto; display:block;"></canvas>

        <!-- Message Purchase History Table -->
        <div class="agent-message-usage-section">
            <h2>Message Purchase History</h2>
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Total Messages</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($balance_rows)) : ?>
                        <tr>
                            <td colspan="3">No message purchase records found.</td>
                        </tr>
                        <?php else:
                        $i = 1;
                        foreach ($balance_rows as $row):
                            if ((int)$row['total_messages'] <= 0) continue;
                        ?>
                            <tr>
                                <td><?= $i++ ?></td>
                                <td><?= htmlspecialchars($row['total_messages']) ?></td>
                                <td><?= date("Y-m-d H:i", strtotime($row['last_updated'])) ?></td>
                            </tr>
                    <?php endforeach;
                    endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        const ctx = document.getElementById('dailyMessageChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?= json_encode($labels) ?>,
                datasets: [{
                    label: 'Remaining Messages',
                    data: <?= json_encode($messageData) ?>,
                    backgroundColor: 'rgba(54,162,235,0.2)',
                    borderColor: 'rgba(54,162,235,1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.3
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        const ctxPie = document.getElementById('messagePieChart').getContext('2d');
        new Chart(ctxPie, {
            type: 'pie',
            data: {
                labels: <?= json_encode($pie_labels) ?>,
                datasets: [{
                    data: <?= json_encode($pie_values) ?>,
                    backgroundColor: ['rgba(255,99,132,0.7)', 'rgba(75,192,192,0.7)']
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    </script>

    <?php include_once 'assets/component/footer.php'; ?>
</body>

</html>