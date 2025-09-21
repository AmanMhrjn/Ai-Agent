<?php
require_once '../config/database.php';

if (isset($_POST['approve']) || isset($_POST['reject'])) {
    $payment_id = (int) $_POST['payment_id'];

    // Fetch payment details
    $stmt = $pdo->prepare("SELECT * FROM token_payments WHERE id = ? AND status = 'Pending'");
    $stmt->execute([$payment_id]);
    $payment = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($payment) {
        if (isset($_POST['approve'])) {
            // ✅ APPROVE: insert new row in message_balance
            $stmt = $pdo->prepare("INSERT INTO message_balance 
                (payment_id, company_name, agent_id, total_messages, messages_used, platform, plan, last_updated)
                VALUES (?, ?, ?, ?, 0, ?, ?, NOW())");

            $stmt->execute([
                $payment['id'],
                $payment['company_name'],
                $payment['agent_id'],
                $payment['tokens'],
                $payment['platform'],
                $payment['plan'] ?? 'token'
            ]);

            // Update payment status
            $pdo->prepare("UPDATE token_payments SET status='Approved' WHERE id=?")
                ->execute([$payment_id]);
        } elseif (isset($_POST['reject'])) {
            // ❌ REJECT: mark as rejected
            $pdo->prepare("UPDATE token_payments SET status='Rejected' WHERE id=?")
                ->execute([$payment_id]);
        }
    }

    $redirect = '/admin/payment.php?success=1';
    header("Location: $redirect");
    exit;
}
