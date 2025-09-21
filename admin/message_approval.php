<?php
// approve_payment_agent.php
// Approve payment, credit the exact selected tokens & extras to the selected agent
if (session_status() === PHP_SESSION_NONE) session_start();
require_once '../config/database.php';

// ---- INPUT ----
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) die("Invalid payment id");

// ---- FETCH PAYMENT ----
$stmt = $pdo->prepare("SELECT * FROM payment_requests WHERE id = ?");
$stmt->execute([$id]);
$pay = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$pay) die("Payment not found");

// ---- NORMALIZE FIELDS ----
$platform = trim($pay['platform'] ?? '');
$company  = trim($pay['company_name'] ?? '');
$agent_id = $pay['agent_id'] ?? null;
$selectedTokens = (int)($pay['plan'] ?? 0); // <-- plan column stores exact selected tokens
$extras = [];
if (!empty($pay['extras'])) {
    $extras = json_decode($pay['extras'], true);
    if (!is_array($extras)) $extras = [];
}

// Guardrails
if ($platform === '') $platform = 'unknown';

// ---- PROCESS ----
$message = '';
try {
    $pdo->beginTransaction();

    // 1) Approve payment if pending
    $status = strtolower($pay['status'] ?? 'pending');
    if ($status === 'pending') {
        $upd = $pdo->prepare("UPDATE payment_requests SET status = 'approved' WHERE id = ?");
        $upd->execute([$id]);
        $status = 'approved';
    }

    // 2) Compute extras tokens
    $extraTokens = 0;
    if (!empty($extras['Extra agents'])) $extraTokens += (int)$extras['Extra agents'] * 1000;
    if (!empty($extras['Extra message credits'])) $extraTokens += (int)$extras['Extra message credits'];
    // You can add custom domain token logic if needed

    $totalTokens = $selectedTokens + $extraTokens;

    // 3) Insert new row into message_balance for the agent
    $ins = $pdo->prepare("
        INSERT INTO message_balance
            (payment_id, company_name, platform, plan, total_messages, messages_used, last_updated, agent_id)
        VALUES (?, ?, ?, ?, ?, 0, NOW(), ?)
    ");
    $ins->execute([$id, $company, $platform, $selectedTokens, $totalTokens, $agent_id]);

    $pdo->commit();

    $message = "✅ Payment approved. {$totalTokens} tokens credited to the selected agent.";

} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    $message = "❌ Error: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Payment Approval</title>
<style>
body { font-family: Arial,sans-serif; background:#f4f4f4; display:flex; justify-content:center; align-items:center; height:100vh; margin:0; }
.message-box { background:#fff; padding:30px 50px; border-radius:10px; box-shadow:0 5px 15px rgba(0,0,0,0.2); text-align:center; max-width:600px; width:100%; }
.success { border-left:6px solid #4CAF50; }
.error { border-left:6px solid #f44336; }
.message-box small { display:block; margin-top:12px; font-size:0.9rem; color:#555; word-wrap:break-word; }
</style>
<script>
setTimeout(function(){ window.location.href='payments.php'; }, 3000);
</script>
</head>
<body>
<div class="message-box <?= str_contains($message,'Error')||str_contains($message,'❌')?'error':'success' ?>">
    <?= htmlspecialchars($message) ?><br>
    <small>
        Payment ID: <?= htmlspecialchars($id) ?> |
        Company: <?= htmlspecialchars($company) ?> |
        Platform: <?= htmlspecialchars($platform) ?> |
        Selected Tokens: <?= htmlspecialchars($selectedTokens) ?> |
        Total Tokens Credited: <?= htmlspecialchars($totalTokens) ?> |
        Agent ID: <?= htmlspecialchars($agent_id ?? 'N/A') ?>
    </small>
</div>
</body>
</html>
