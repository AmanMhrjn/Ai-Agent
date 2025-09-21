<?php
// approve_payment.php
// Copy–paste ready. Fixes: session notice, zero tokens, missing platform/plan, safe re-runs.

// Start session safely
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../config/database.php';

// ---- INPUT ----
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($id <= 0) {
    die("Invalid payment id");
}

// ---- FETCH PAYMENT ----
$stmt = $pdo->prepare("SELECT * FROM payment_requests WHERE id = ?");
$stmt->execute([$id]);
$pay = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$pay) {
    die("Payment not found");
}

// Normalize plan for token mapping
$rawPlan   = is_string($pay['plan'] ?? '') ? trim($pay['plan']) : '';
$planKey   = strtolower($rawPlan);
$platform  = is_string($pay['platform'] ?? '') ? trim($pay['platform']) : '';
$company   = is_string($pay['company_name'] ?? '') ? trim($pay['company_name']) : '';

// ---- TOKEN MAP (match your purchase page numbers) ----
// If your purchase page gives: free=5000, premium=15000, platinum=50000
$PLAN_TOKENS = [
    'free'     => 5000,
    'premium'  => 15000,
    'platinum' => 50000,
];

// Fallback: try to be resilient to casing like "Premium", "PLATINUM"
$tokens = $PLAN_TOKENS[$planKey] ?? 0;

// Guardrails if platform/plan missing
if ($platform === '')  $platform = 'unknown';
if ($rawPlan === '')   $rawPlan  = $planKey ?: 'unknown';

// ---- MESSAGE ----
$message = '';

// ---- PROCESS ----
try {
    $pdo->beginTransaction();

    // 1) If pending, approve it. If already approved/rejected, don't die; we'll still try to fix missing platform/plan on balance row.
    $status = strtolower((string)($pay['status'] ?? 'pending'));
    if ($status === 'pending') {
        $upd = $pdo->prepare("UPDATE payment_requests SET status = 'approved' WHERE id = ?");
        $upd->execute([$id]);
        $status = 'approved';
    }

    // 2) Work with message_balance.
    // Strategy:
    //   - Prefer a row keyed by this payment_id (idempotent; prevents double-credit on re-click).
    //   - If it exists, ensure platform/plan filled in; credit tokens only if total_messages == 0.
    //   - If it doesn't exist, create it with correct platform/plan/tokens.

    $balSel = $pdo->prepare("SELECT * FROM message_balance WHERE payment_id = ? LIMIT 1");
    $balSel->execute([$id]);
    $bal = $balSel->fetch(PDO::FETCH_ASSOC);

    if ($bal) {
        // Legacy / existing row: ensure platform & plan are set (if left to defaults), and credit if zero.
        $currentTotal   = (int) ($bal['total_messages'] ?? 0);
        $currentPlat    = (string) ($bal['platform'] ?? '');
        $currentPlanCol = (string) ($bal['plan'] ?? '');

        // Decide whether to update platform/plan fields
        $needPlatUpdate = ($currentPlat === '' || strtolower($currentPlat) === 'default' || strtolower($currentPlat) === 'unknown');
        $needPlanUpdate = ($currentPlanCol === '' || strtolower($currentPlanCol) === 'default' || strtolower($currentPlanCol) === 'unknown');

        // If tokens not yet credited for this payment (i.e., still zero), add them now.
        if ($currentTotal === 0 && $tokens > 0) {
            $newTotal = $tokens;
            $sql = "UPDATE message_balance 
                    SET total_messages = ?, last_updated = NOW()"
                . ($needPlatUpdate ? ", platform = ?" : "")
                . ($needPlanUpdate ? ", plan = ?" : "")
                . " WHERE id = ?";

            $params = [$newTotal];
            if ($needPlatUpdate) $params[] = $platform;
            if ($needPlanUpdate) $params[] = $rawPlan;
            $params[] = $bal['id'];

            $updBal = $pdo->prepare($sql);
            $updBal->execute($params);

            $message = "✅ Payment {$status}. Tokens credited.";
        } else {
            // Not zero; don't double-credit. Still patch platform/plan if needed.
            if ($needPlatUpdate || $needPlanUpdate) {
                $sql = "UPDATE message_balance SET last_updated = NOW()"
                    . ($needPlatUpdate ? ", platform = ?" : "")
                    . ($needPlanUpdate ? ", plan = ?" : "")
                    . " WHERE id = ?";
                $params = [];
                if ($needPlatUpdate) $params[] = $platform;
                if ($needPlanUpdate) $params[] = $rawPlan;
                $params[] = $bal['id'];

                $updBal = $pdo->prepare($sql);
                $updBal->execute($params);
            }

            if ($status === 'approved') {
                $message = "ℹ️ This payment was already credited earlier.";
            } elseif ($status === 'rejected') {
                $message = "❗ This payment was rejected earlier (no credits applied).";
            } else {
                $message = "ℹ️ Nothing to change.";
            }
        }
    } else {
        // No row yet: create a fresh one with all fields set properly.
        $ins = $pdo->prepare("
            INSERT INTO message_balance 
                (payment_id, company_name, platform, plan, total_messages, messages_used, last_updated)
            VALUES (?, ?, ?, ?, ?, 0, NOW())
        ");
        $ins->execute([$id, $company, $platform, $rawPlan, $tokens]);

        $message = ($status === 'approved')
            ? "✅ Payment approved and tokens added."
            : "✅ Balance row created. (Status: {$status})";
    }

    $pdo->commit();

    // Optional: email notice
    // @mail("user@example.com", "Payment Approved", "Your payment is verified. Tokens added!");
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    $message = "❌ Error: " . $e->getMessage();
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Payment Status</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f4f4;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            padding: 10px;
            /* small padding for mobile */
            box-sizing: border-box;
        }

        /* Message Box */
        .message-box {
            background: #fff;
            padding: 30px 50px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
            text-align: center;
            font-size: 1.1rem;
            color: #333;
            max-width: 600px;
            width: 100%;
            position: relative;
            animation: fadeOut 0.5s ease 2.5s forwards;
            box-sizing: border-box;
        }

        /* Success / Error Styles */
        .success {
            border-left: 6px solid #4CAF50;
        }

        .error {
            border-left: 6px solid #f44336;
        }

        /* Small text info */
        .message-box small {
            display: block;
            margin-top: 12px;
            font-size: 0.9rem;
            color: #555;
            word-wrap: break-word;
        }

        /* Fade-out animation */
        @keyframes fadeOut {
            to {
                opacity: 0;
                transform: translateY(-20px);
            }
        }

        /* Tablet: Medium screens */
        @media (max-width: 1024px) {
            .message-box {
                padding: 25px 40px;
                font-size: 1rem;
            }

            .message-box small {
                font-size: 0.85rem;
            }
        }

        /* Mobile: Small screens */
        @media (max-width: 768px) {
            .message-box {
                padding: 20px 25px;
                font-size: 0.95rem;
            }

            .message-box small {
                font-size: 0.8rem;
            }
        }
    </style>
    <script>
        // Redirect after 3 seconds
        setTimeout(function() {
            window.location.href = 'payments.php';
        }, 3000);
    </script>
</head>

<body>
    <div class="message-box <?= (str_contains($message, 'Error') || str_contains($message, '❌')) ? 'error' : 'success' ?>">
        <?= htmlspecialchars($message) ?><br>
        <small>Payment ID: <?= htmlspecialchars((string)$id) ?> | Company: <?= htmlspecialchars($company) ?> | Platform: <?= htmlspecialchars($platform) ?> | Plan: <?= htmlspecialchars($rawPlan) ?> | Tokens: <?= htmlspecialchars((string)$tokens) ?></small>
    </div>
</body>

</html>