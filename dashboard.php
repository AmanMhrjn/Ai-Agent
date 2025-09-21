<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['id']) || !isset($_SESSION['companyname'])) {
    echo '<!DOCTYPE html>
    <html lang="en">
    <head><meta charset="UTF-8"><title>Access Denied</title></head>
    <body><h2>Please login to access dashboard</h2></body>
    </html>';
    exit;
}

$user_id = (int)$_SESSION['id'];
$company_name = $_SESSION['companyname'] ?? '';

$from_date = $_GET['from_date'] ?? '';
$to_date   = $_GET['to_date'] ?? '';

$query = "SELECT SUM(total_messages) as total_messages, SUM(messages_used) as messages_used, DATE(last_updated) as date
          FROM message_balance
          WHERE company_name = :company_name";
$params = [':company_name' => $company_name];

if ($from_date && $to_date) {
    $query .= " AND DATE(last_updated) BETWEEN :from_date AND :to_date";
    $params[':from_date'] = $from_date;
    $params[':to_date'] = $to_date;
}

$query .= " GROUP BY DATE(last_updated) ORDER BY DATE(last_updated) ASC";
$stmt_balance = $pdo->prepare($query);
$stmt_balance->execute($params);
$balance_result = $stmt_balance->fetchAll(PDO::FETCH_ASSOC);

// Initialize variables to prevent undefined warnings
$labels = [];
$messageData = [];
$totalMessages = 0;
$totalUsed = 0;
$latestBalance = [
    'total_messages' => 0,
    'messages_used' => 0,
    'remaining' => 0
];

foreach ($balance_result as $row) {
    $totalMessages += (int)$row['total_messages'];
    $totalUsed += (int)$row['messages_used'];
    $remaining = $totalMessages - $totalUsed;

    $labels[] = $row['date'];
    $messageData[] = $remaining;
    $latestBalance = [
        'total_messages' => $totalMessages,
        'messages_used' => $totalUsed,
        'remaining' => $remaining
    ];
}

// Fetch pages for selected agent
$agent_id_param = isset($_GET['agent_id']) ? (int)$_GET['agent_id'] : 0;

$stmt_pages = $pdo->prepare("
    SELECT ap.id AS page_id, ap.page_name, ap.platform, a.id AS agent_id, a.plan
    FROM agent_pages ap
    JOIN agents a ON a.id = ap.agent_id
    WHERE a.user_id = :user_id
    " . ($agent_id_param ? " AND a.id = :agent_id" : "") . "
    ORDER BY ap.created_at DESC
");

$params_pages = [':user_id' => $user_id];
if ($agent_id_param) $params_pages[':agent_id'] = $agent_id_param;

$stmt_pages->execute($params_pages);
$pages = $stmt_pages->fetchAll(PDO::FETCH_ASSOC);

// Handle Add Page POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['agent_id'], $_POST['platform'], $_POST['page_name'])) {
    $agent_id = (int)$_POST['agent_id'];
    $platform = trim($_POST['platform']);
    $page_name = trim($_POST['page_name']);

    if ($agent_id && $platform && $page_name) {
        $stmt = $pdo->prepare("
            INSERT INTO agent_pages (agent_id, page_name, platform, created_at) 
            VALUES (:agent_id, :page_name, :platform, NOW())
        ");
        $stmt->execute([
            ':agent_id' => $agent_id,
            ':page_name' => $page_name,
            ':platform' => $platform
        ]);

        $new_page_id = $pdo->lastInsertId();
        header("Location: " . $_SERVER['PHP_SELF'] . "?page_id=" . $new_page_id);
        exit;
    }
}

// Fetch agents for modal dropdown
$stmt_agents = $pdo->prepare("SELECT id, platform, plan FROM agents WHERE user_id = :user_id ORDER BY created_at DESC");
$stmt_agents->execute([':user_id' => $user_id]);
$agents = $stmt_agents->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI Agents Dashboard</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="assets/css/navbar.css">
    <link rel="stylesheet" href="assets/css/footer.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f4f4;
            margin: 0;
            padding: 0;
        }

        h1.agent-heading {
            text-align: center;
            margin-top: 20px;
            color: #333;
        }

        .section {
            max-width: 1200px;
            margin: 20px auto;
        }

        .agents-list {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
        }

        .agent-card {
            background: #fff;
            padding: 15px 20px;
            border-radius: 8px;
            box-shadow: 0 3px 8px rgba(0, 0, 0, 0.1);
            text-align: center;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        h3 {
            margin: 10px 0px;
        }

        .agent-image {
            width: 100%;
            border-radius: 10px;
        }

        .add-agent-btn {
            padding: 10px 20px;
            margin: 5px;
            border: none;
            border-radius: 5px;
            background: #1976d2;
            color: #fff;
            cursor: pointer;
        }

        .add-agent-btn:hover {
            background: #1565c0;
        }

        .add-page-container {
            text-align: center;
            margin: 20px 0;
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 999;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.5);
        }

        .modal-content {
            background: #fff;
            margin: 10% auto;
            padding: 20px;
            border-radius: 10px;
            max-width: 500px;
            position: relative;
        }

        .close {
            position: absolute;
            top: 10px;
            right: 15px;
            font-size: 24px;
            cursor: pointer;
        }

        .modal-content label {
            display: block;
            margin: 10px 0 5px;
        }

        .modal-content input,
        .modal-content select {
            width: 100%;
            padding: 8px;
            margin-bottom: 10px;
            border-radius: 5px;
            border: 1px solid #ccc;
        }

        .message-balance-card {
            display: flex;
            gap: 20px;
            justify-content: center;
            margin: 20px 160px;
            flex-wrap: wrap;
        }

        .message-balance-card div {
            flex: 1;
            min-width: 150px;
            text-align: center;
            padding: 20px;
            border-radius: 10px;
        }

        .pie-charts-container {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            justify-content: center;
            margin-bottom: 40px;
        }

        .pie-chart-card {
            background: #fff;
            padding: 20px;
            border-radius: 10px;
            width: 250px;
            text-align: center;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>

<body>
    <?php include_once 'assets/component/navbar.php'; ?>

    <h1 class="agent-heading">Page Dashboard</h1>

    <div class="section">
        <h2 style="text-align:center;">Pages</h2>

        <?php if (!empty($pages)): ?>
            <div class="agents-list">
                <?php foreach ($pages as $page): ?>
                    <div class="agent-card">
                        <img src="images/background.jpg" alt="Page Icon" class="agent-image">
                        <h3><?= htmlspecialchars($page['platform']) ?></h3>
                        <a href="agent.php?page_id=<?= $page['page_id'] ?>" class="add-agent-btn" style="text-decoration:none;">View Page</a>
                        <button class="add-agent-btn" style="background:#ff9800; margin-top:5px;"
                            onclick="openRenameModal('<?= $page['page_id'] ?>', '<?= htmlspecialchars($page['page_name'], ENT_QUOTES) ?>', '<?= $page['platform'] ?>')">
                            Manage
                        </button>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p style="text-align:center;">No pages found for this agent.</p>
        <?php endif; ?>

        <div class="add-page-container">
            <button class="add-agent-btn" onclick="openModal(this)" data-agent-id="<?= $agent_id_param ?>">Add Page</button>
        </div>
    </div>

    <!-- Add Page Modal -->
    <div id="addPageModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal()">&times;</span>
            <h2>Add New Page</h2>
            <form method="post">
                <label for="agent_id">Select Agent:</label>
                <select name="agent_id" id="agent_id" required>
                    <option value="">-- Choose Agent --</option>
                    <?php foreach ($agents as $a): ?>
                        <option value="<?= $a['id'] ?>"><?= htmlspecialchars($a['platform'] . " (" . $a['plan'] . ")") ?></option>
                    <?php endforeach; ?>
                </select>

                <label for="platform">Platform:</label>
                <select name="platform" id="platform" required>
                    <option value="">-- Select Platform --</option>
                    <?php
                    $platforms = ['Facebook', 'Instagram', 'WhatsApp', 'Messenger', 'Telegram'];
                    foreach ($platforms as $plat): ?>
                        <option value="<?= $plat ?>"><?= $plat ?></option>
                    <?php endforeach; ?>
                </select>

                <label for="page_name">Page Name:</label>
                <input type="text" name="page_name" id="page_name" required>

                <button type="submit" class="add-agent-btn">Create Page</button>
            </form>
        </div>
    </div>

    <!-- Rename Page Modal -->
    <div id="renamePageModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeRenameModal()">&times;</span>
            <h2>Rename Page</h2>
            <form method="post">
                <input type="hidden" name="rename_page_id" id="rename_page_id">

                <label for="new_platform">Platform:</label>
                <select name="new_platform" id="new_platform" required>
                    <option value="">-- Select Platform --</option>
                    <option value="Facebook">Facebook</option>
                    <option value="Instagram">Instagram</option>
                    <option value="WhatsApp">WhatsApp</option>
                </select>

                <label for="new_page_name">Page Name:</label>
                <input type="text" name="new_page_name" id="new_page_name" required>

                <button type="submit" class="add-agent-btn">Save Changes</button>
            </form>
        </div>
    </div>

    <!-- Message Balance Cards -->
    <?php if (!empty($latestBalance)): ?>
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
    <?php endif; ?>

    <div class="section">
        <h2 style="text-align:center;">Messages Usage</h2>
        <canvas id="dailyMessageChart" style="max-width:1000px; margin:20px auto; display:block; width:100%; height:400px;"></canvas>
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
                        x: {
                            title: {
                                display: true,
                                text: 'Date'
                            }
                        },
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Messages'
                            }
                        }
                    }
                }
            });
        </script>
    </div>

    <!-- Pie Charts -->
    <div class="pie-charts-container">
        <?php foreach ($pages as $page):
            $stmt_platform = $pdo->prepare("
                SELECT SUM(total_messages) AS total, SUM(messages_used) AS used
                FROM message_balance
                WHERE company_name = :company_name AND platform = :platform
            ");
            $stmt_platform->execute([':company_name' => $company_name, ':platform' => $page['platform']]);
            $row = $stmt_platform->fetch(PDO::FETCH_ASSOC);

            $total = (int)($row['total'] ?? 0);
            $used  = (int)($row['used'] ?? 0);
            $remaining = max(0, $total - $used);
            $canvasId = "pieChart_" . $page['page_id'];

            if ($total > 0 || $used > 0):
        ?>
                <div class="pie-chart-card">
                    <h3><?= htmlspecialchars($page['page_name']) ?> (<?= htmlspecialchars($page['platform']) ?>)</h3>
                    <canvas id="<?= $canvasId ?>"></canvas>
                </div>
                <script>
                    new Chart(document.getElementById("<?= $canvasId ?>"), {
                        type: 'pie',
                        data: {
                            labels: ['Used', 'Remaining'],
                            datasets: [{
                                data: [<?= $used ?>, <?= $remaining ?>],
                                backgroundColor: ['#ef5350', '#66bb6a']
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
        <?php endif;
        endforeach; ?>
    </div>

    <script>
        const modal = document.getElementById("addPageModal");

        function openModal(button) {
            modal.style.display = "block";
            document.getElementById("agent_id").value = button?.dataset.agentId || "";
            document.getElementById("platform").value = "";
            document.getElementById("page_name").value = "";
        }

        function closeModal() {
            modal.style.display = "none";
        }

        const renameModal = document.getElementById("renamePageModal");

        function openRenameModal(pageId = "", currentName = "", currentPlatform = "") {
            renameModal.style.display = "block";
            document.getElementById("rename_page_id").value = pageId;
            document.getElementById("new_page_name").value = currentName;
            document.getElementById("new_platform").value = currentPlatform;
        }

        function closeRenameModal() {
            renameModal.style.display = "none";
        }

        window.onclick = function(event) {
            if (event.target == modal) closeModal();
            if (event.target == renameModal) closeRenameModal();
        };

        const platformSelect = document.getElementById('platform');
        const pageNameInput = document.getElementById('page_name');
        platformSelect?.addEventListener('change', () => {
            const platform = platformSelect.value;
            pageNameInput.value = platform ? `New ${platform} Page` : '';
        });
    </script>

    <?php include_once 'assets/component/footer.php'; ?>
</body>

</html>