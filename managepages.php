<?php
session_start();
require_once __DIR__ . '/config/database.php'; // $pdo connection

if (!isset($_SESSION['id']) || !isset($_SESSION['companyname'])) {
    header("Location: ./assets/component/login.php");
    exit;
}

$user_id = (int) $_SESSION['id'];
$session_company_name = $_SESSION['companyname'] ?? '';

function flash($type, $msg)
{
    $_SESSION['flash'][] = ['type' => $type, 'msg' => $msg];
}

$flashes = $_SESSION['flash'] ?? [];
unset($_SESSION['flash']);

// Handle Delete
if (isset($_GET['delete_id'])) {
    $delete_id = (int) $_GET['delete_id'];
    $stmt = $pdo->prepare("DELETE FROM agents WHERE id = :id AND user_id = :user_id");
    $stmt->execute([':id' => $delete_id, ':user_id' => $user_id]);
    flash('success', 'Agent deleted.');
    header("Location: managepages.php");
    exit;
}

// Handle Toggle
if (isset($_GET['toggle_id'])) {
    $toggle_id = (int) $_GET['toggle_id'];
    $stmt = $pdo->prepare("SELECT status FROM agents WHERE id = :id AND user_id = :user_id");
    $stmt->execute([':id' => $toggle_id, ':user_id' => $user_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        $new_status = ($row['status'] == 1) ? 0 : 1;
        $stmt_update = $pdo->prepare("UPDATE agents SET status = :status WHERE id = :id AND user_id = :user_id");
        $stmt_update->execute([':status' => $new_status, ':id' => $toggle_id, ':user_id' => $user_id]);
        flash('success', 'Agent status updated.');
    }
    header("Location: managepages.php");
    exit;
}

// Handle Update Page Name
if (!empty($_POST['rename_id']) && isset($_POST['new_name'])) {
    $rename_id = (int) $_POST['rename_id'];
    $new_name = trim($_POST['new_name']);
    if ($new_name !== '') {
        $stmt = $pdo->prepare("UPDATE agents SET page_name = :page_name WHERE id = :id AND user_id = :user_id");
        $stmt->execute([':page_name' => $new_name, ':id' => $rename_id, ':user_id' => $user_id]);
        flash('success', 'Page name updated.');
    } else {
        flash('error', 'Page name cannot be empty.');
    }
    header("Location: managepages.php");
    exit;
}

// Fetch Agents (also include page_name now)
$stmt = $pdo->prepare("SELECT id, company_name, platform, plan, status, created_at, page_name 
                       FROM agents 
                       WHERE user_id = :user_id 
                       ORDER BY created_at DESC, id DESC");
$stmt->execute([':user_id' => $user_id]);
$agents = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle Update Platform
if (!empty($_POST['platform_id']) && isset($_POST['new_platform'])) {
    $platform_id = (int) $_POST['platform_id'];
    $new_platform = trim($_POST['new_platform']);
    $allowed = ['Facebook', 'Instagram', 'WhatsApp'];

    if (in_array($new_platform, $allowed)) {
        $stmt = $pdo->prepare("UPDATE agents SET platform = :platform WHERE id = :id AND user_id = :user_id");
        $stmt->execute([
            ':platform' => $new_platform,
            ':id' => $platform_id,
            ':user_id' => $user_id
        ]);
        flash('success', 'Platform updated successfully.');
    } else {
        flash('error', 'Invalid platform selected.');
    }
    header("Location: managepages.php");
    exit;
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Agents</title>
    <link rel="stylesheet" href="assets/css/navbar.css">
    <link rel="stylesheet" href="assets/css/footer.css">
    <style>
        :root {
            --bg: #f5f7fb;
            --card: #fff;
            --text: #222;
            --muted: #666;
            --border: #e6e8ee;
            --primary: #2563eb;
            --primary-hover: #1e4fd4;
            --success: #16a34a;
            --danger: #ef4444;
            --shadow: 0 8px 24px rgba(16, 24, 40, 0.06);
            --radius: 12px;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Inter, sans-serif;
            background: var(--bg);
            color: var(--text);
        }

        .container {
            max-width: 1000px;
            margin: 24px auto;
            padding: 0 16px;
        }

        .panel {
            background: var(--card);
            padding: 24px;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
        }

        h2 {
            margin-top: 0;
            margin-bottom: 16px;
        }

        .flash {
            padding: 12px 16px;
            border-radius: var(--radius);
            margin-bottom: 16px;
            font-size: 0.95rem;
        }

        .flash.success {
            background: #ecfdf5;
            color: #065f46;
        }

        .flash.error {
            background: #fef2f2;
            color: #7f1d1d;
        }

        .agent-card {
            display: flex;
            flex-direction: column;
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 16px;
            margin-bottom: 16px;
            box-shadow: var(--shadow);
        }

        .agent-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 6px;
        }

        .agent-header h3 {
            margin: 0;
            font-size: 1.2rem;
        }

        .badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 8px;
            background: #f3f4f6;
            font-size: 0.85rem;
            margin-right: 6px;
            margin-top: 4px;
        }

        .badge.status {
            font-weight: 600;
        }

        .actions {
            display: block;
            margin-top: 6px;
        }

        .btn {
            padding: 6px 12px;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            font-weight: 600;
            transition: 0.2s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .btn-primary {
            background: var(--primary);
            color: #fff;
        }

        .btn-primary:hover {
            background: var(--primary-hover);
        }

        .btn-success {
            background: var(--success);
            color: #fff;
        }

        .btn-danger {
            background: var(--danger);
            color: #fff;
        }

        .inline-form-update {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            margin-top: 12px;
            margin-bottom: 12px;
        }

        /* Inline form styling */
        .inline-form {
            display: flex;
            align-items: center;
            gap: 8px;
            margin: 5px 0;
        }

        /* Dropdown select box */
        .inline-form select {
            padding: 6px 10px;
            border: 1px solid #ccc;
            border-radius: 6px;
            background: #fff;
            font-size: 14px;
            min-width: 150px;
            cursor: pointer;
            transition: all 0.2s ease-in-out;
        }

        /* Dropdown hover/focus effect */
        .inline-form select:focus {
            outline: none;
            border-color: #006bde;
            box-shadow: 0 0 5px rgba(0, 107, 222, 0.3);
        }

        /* Primary button for update */
        .inline-form .btn-primary {
            background: #006bde;
            color: #fff;
            padding: 6px 14px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            transition: background 0.2s ease-in-out;
        }

        /* Hover effect for button */
        .inline-form .btn-primary:hover {
            background: #0055b3;
        }


        input[type="text"] {
            padding: 6px 8px;
            border-radius: 6px;
            border: 1px solid var(--border);
            min-width: 160px;
        }

        .backBtn {
            padding: 12px;
            width: 150px;
            margin: auto;
            border-radius: 8px;
            margin-top: 20px;
            text-align: center;
            background: var(--primary);
        }

        .backBtn a {
            text-decoration: none;
            color: white;
        }

        .backBtn:hover {
            background: var(--primary-hover);
        }

        @media(max-width:720px) {
            .agent-header {
                flex-direction: column;
                align-items: flex-start;
            }
        }
    </style>
</head>

<body>
    <?php include_once 'assets/component/navbar.php'; ?>

    <div class="container">
        <div class="panel">
            <h2>Manage Your Pages</h2>

            <?php foreach ($flashes as $f): ?>
                <div class="flash <?php echo htmlspecialchars($f['type']); ?>"><?php echo htmlspecialchars($f['msg']); ?></div>
            <?php endforeach; ?>

            <?php if (!$agents): ?>
                <p>No agents found.</p>
            <?php else: ?>
                <?php foreach ($agents as $row):
                    $status_on = (int)$row['status'] === 1;
                    $platform = strtolower($row['platform']);
                ?>
                    <div class="agent-card">
                        <div class="agent-header">
                            <h3 style="text-transform: uppercase;"><?php echo htmlspecialchars($row['page_name'] ?: 'Untitled Agent'); ?></h3>
                            <div>
                                <span class="badge">Platform: <?php echo ucfirst($row['platform']); ?></span>
                                <span class="badge">Plan: <?php echo ucfirst($row['plan']); ?></span>
                                <span class="badge status"><?php echo $status_on ? 'Enabled' : 'Disabled'; ?></span>
                                <span class="badge">Created: <?php echo htmlspecialchars($row['created_at']); ?></span>
                            </div>
                        </div>

                        <!-- Connected Links -->
                        <div style="margin-top:8px;">
                            <?php
                            $stmt = $pdo->prepare("SELECT * FROM agent_connections WHERE agent_id=:agent_id AND platform=:platform");
                            $stmt->execute([':agent_id' => $row['id'], ':platform' => $row['platform']]);
                            $conn = $stmt->fetch(PDO::FETCH_ASSOC);

                            if ($conn):
                            ?>
                                <span class="badge">Connected:
                                    <a href="<?php echo $conn['link']; ?>" target="_blank">
                                        <?php echo ($platform === 'whatsapp') ? $conn['link'] : htmlspecialchars($conn['page_name']); ?>
                                    </a>
                                </span>
                            <?php endif; ?>
                        </div> <br>

                        <?php
                        $standardPlatforms = ['facebook', 'instagram', 'whatsapp'];
                        ?>

                        <div class="actions">
                            <?php if (!$conn): ?>
                                <?php if ($platform === 'facebook' || $platform === 'instagram'): ?>
                                    <form method="GET" action="facebook_connect.php" class="inline-form">
                                        <input type="hidden" name="agent_id" value="<?php echo $row['id']; ?>">
                                        <input type="hidden" name="platform" value="<?php echo $row['platform']; ?>">
                                        <button type="submit" class="btn btn-primary">Connect <?php echo ucfirst($platform); ?> Page</button>
                                    </form>
                                <?php elseif ($platform === 'whatsapp'): ?>
                                    <form method="POST" action="connect_whatsapp.php" class="inline-form">
                                        <input type="hidden" name="agent_id" value="<?php echo $row['id']; ?>">
                                        <input type="text" name="phone_number" placeholder="Enter WhatsApp number" required>
                                        <button type="submit" class="btn btn-success">Connect WhatsApp</button>
                                    </form>
                                <?php endif; ?>
                            <?php endif; ?>
                            <?php if (!in_array($platform, $standardPlatforms)): ?>
                                <!-- Show dropdown only if platform is not standard -->
                                <form method="POST" class="inline-form">
                                    <input type="hidden" name="platform_id" value="<?php echo $row['id']; ?>">
                                    <select name="new_platform" required>
                                        <option value="">-- Select Platform --</option>
                                        <option value="Facebook">Facebook</option>
                                        <option value="Instagram">Instagram</option>
                                        <option value="WhatsApp">WhatsApp</option>
                                    </select>
                                    <button type="submit" class="btn btn-primary">Update Platform</button>
                                </form>
                            <?php endif; ?>

                            <!-- Existing Actions -->
                            <a href="managepages.php?toggle_id=<?php echo $row['id']; ?>" class="btn <?php echo $status_on ? 'btn-success' : 'btn-primary'; ?>">
                                <?php echo $status_on ? 'Disable' : 'Enable'; ?>
                            </a>

                            <a href="managepages.php?delete_id=<?php echo $row['id']; ?>" class="btn btn-danger" onclick="return confirm('Delete this agent?')">Delete</a>

                            <!-- ✅ Update Page Name -->
                            <form method="POST" class="inline-form-update">
                                <input type="hidden" name="rename_id" value="<?php echo $row['id']; ?>">
                                <input type="text" name="new_name" placeholder="New page name" required>
                                <button type="submit" class="btn btn-primary">Update Page Name</button>
                            </form>
                        </div>

                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
            <div class="backBtn">
                <a href="dashboard.php">Dashboard</a>

            </div>
        </div>
    </div>

    <?php include_once 'assets/component/footer.php'; ?>
</body>

</html>



mysql> desc details;
+--------------------------+--------------+------+-----+---------+-------+
| Field | Type | Null | Key | Default | Extra |
+--------------------------+--------------+------+-----+---------+-------+
| Model | text | NO | | NULL | |
| Time | date | NO | | NULL | |
| Excution_id/message send | int | NO | | NULL | |
| Workflow name/Brand Name | text | NO | | NULL | |
| Total token | int | NO | | NULL | |
| Platform | text | NO | | NULL | |
| Workflow Id | varchar(300) | NO | | NULL | |
| Status | text | NO | | NULL | |
+--------------------------+--------------+------+-----+---------+-------+
8 rows in set (0.00 sec)

mysql> desc message_balance;
+----------------+--------------+------+-----+-------------------+-------------------+
| Field | Type | Null | Key | Default | Extra |
+----------------+--------------+------+-----+-------------------+-------------------+
| id | int | NO | PRI | NULL | auto_increment |
| payment_id | int | YES | | NULL | |
| company_name | varchar(255) | NO | | NULL | |
| agent_id | int | YES | | NULL | |
| total_messages | int | NO | | 0 | |
| messages_used | int | NO | | 0 | |
| last_updated | datetime | YES | | CURRENT_TIMESTAMP | DEFAULT_GENERATED |
| platform | varchar(50) | YES | | NULL | |
| plan | varchar(50) | YES | | NULL | |
+----------------+--------------+------+-----+-------------------+-------------------+
9 rows in set (0.00 sec)

mysql> show triggers;
+------------------------+--------+------------------+---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------+--------+------------------------+-----------------------------------------------------------------------------------------------------------------------+----------------+----------------------+----------------------+--------------------+
| Trigger | Event | Table | Statement | Timing | Created | sql_mode | Definer | character_set_client | collation_connection | Database Collation |
+------------------------+--------+------------------+---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------+--------+------------------------+-----------------------------------------------------------------------------------------------------------------------+----------------+----------------------+----------------------+--------------------+
| after_details_insert | INSERT | details | BEGIN
IF UPPER(NEW.Status) = 'SUCCESS' THEN
INSERT INTO message_balance (
company_name,
total_messages,
messages_used,
platform
)
VALUES (
NEW.`Workflow name/Brand Name`,
0,
1,
NEW.platform
);
END IF;
END | AFTER | 2025-08-28 13:14:55.94 | ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION | root@localhost | cp850 | cp850_general_ci | utf8mb4_0900_ai_ci |
| orders_before_insert | INSERT | orders | BEGIN
DECLARE last_id INT;


SET last_id = (SELECT IFNULL(MAX(id), 0) + 1 FROM orders);


SET NEW.order_code = CONCAT('ORD', LPAD(last_id, 3, '0'));
END | BEFORE | 2025-08-25 17:00:55.56 | ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION | root@localhost | cp850 | cp850_general_ci | utf8mb4_0900_ai_ci |
| after_payment_approved | UPDATE | payment_requests | BEGIN
IF NEW.status = 'approved' AND OLD.status <> 'approved' THEN
    INSERT INTO message_balance (
    payment_id, company_name, total_messages, messages_used, last_updated
    )
    VALUES (
    NEW.id,
    NEW.company_name,
    CASE
    WHEN NEW.plan='free' THEN 5000
    WHEN NEW.plan='basic' THEN 10000
    WHEN NEW.plan='premium' THEN 15000
    WHEN NEW.plan='platinum' THEN 50000
    ELSE 0
    END,
    0,
    NOW()
    );
    END IF;
    END | AFTER | 2025-09-14 13:49:46.07 | ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION | root@localhost | cp850 | cp850_general_ci | utf8mb4_0900_ai_ci |
    +------------------------+--------+------------------+---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------+--------+------------------------+-----------------------------------------------------------------------------------------------------------------------+----------------+----------------------+----------------------+--------------------+
    3 rows in set (0.01 sec)



    in this tables in details that will be automatically add in the table and the there will use triggers and store it in database

    but now there are some tables added
    mysql> desc agent_pages;
    +------------+--------------+------+-----+-------------------+-------------------+
    | Field | Type | Null | Key | Default | Extra |
    +------------+--------------+------+-----+-------------------+-------------------+
    | id | int | NO | PRI | NULL | auto_increment |
    | agent_id | int | NO | MUL | NULL | |
    | platform | varchar(100) | YES | | NULL | |
    | page_name | varchar(255) | NO | | NULL | |
    | created_at | timestamp | YES | | CURRENT_TIMESTAMP | DEFAULT_GENERATED |
    +------------+--------------+------+-----+-------------------+-------------------+
    5 rows in set (0.00 sec)


    in the page there is agent id and others but now if the details page has added and there willhave agent id and platforom according to that i need to show which agent id has deducted token from the platform now how do i do that ?