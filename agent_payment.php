<?php
session_start();
require_once 'config/database.php';

// User and agent info
$user_id = $_SESSION['id'] ?? 0;
$company_name = $_SESSION['companyname'] ?? '';
$agent_id = $_SESSION['agent_id'] ?? ($_GET['agent_id'] ?? 0);

if (!$user_id) {
    header("Location: assets/component/login.php");
    exit;
}

// Exchange rate
$exchange_rate = 139.72;

// Default values
$plan = $_GET['plan'] ?? 'free';
$amount_usd = (float) ($_GET['amount'] ?? 0);

// Token packages
$tokenPackages = [
    ['tokens' => 1000, 'price' => 10, 'label' => '1,000 Tokens'],
    ['tokens' => 5000, 'price' => 45, 'label' => '5,000 Tokens (10% off)'],
    ['tokens' => 10000, 'price' => 80, 'label' => '10,000 Tokens (20% off)'],
    ['tokens' => 20000, 'price' => 150, 'label' => '20,000 Tokens (25% off)'],
];

// Fetch QR payment options
$qr_stmt = $pdo->query("SELECT * FROM payment_qr_details ORDER BY platform ASC");
$qr_list = $qr_stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch agent info
$agent = null;
if ($agent_id) {
    $stmt = $pdo->prepare("SELECT platform FROM agents WHERE id = :id AND user_id = :user_id LIMIT 1");
    $stmt->execute([
        'id' => $agent_id,
        'user_id' => $user_id
    ]);
    $agent = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Handle Form Submission
$message = '';
$message_type = '';
if (isset($_POST['submit_payment'])) {
    $selected_tokens = (int) ($_POST['selected_tokens'] ?? 0);
    $extras = $_POST['extras'] ?? [];
    $total_usd = (float) ($_POST['total_usd'] ?? 0);
    $qr_id = (int) ($_POST['qr_id'] ?? 0);
    $platform = '';
    $screenshot = '';

    // Fetch platform name
    if ($qr_id > 0) {
        $qr_check = $pdo->prepare("SELECT platform FROM payment_qr_details WHERE id = ?");
        $qr_check->execute([$qr_id]);
        $qr_row = $qr_check->fetch(PDO::FETCH_ASSOC);
        if ($qr_row) $platform = $qr_row['platform'];
    }

    // Upload screenshot
    if (isset($_FILES['screenshot']) && $_FILES['screenshot']['error'] === 0) {
        $filename = time() . '_' . basename($_FILES['screenshot']['name']);
        $target = 'uploads/payment_screenshots/' . $filename;
        if (!is_dir('uploads/payment_screenshots')) mkdir('uploads/payment_screenshots', 0777, true);
        move_uploaded_file($_FILES['screenshot']['tmp_name'], $target);
        $screenshot = $target;
    }

    // Validate all required fields
    if ($agent_id && $qr_id && $platform && $screenshot && $selected_tokens > 0) {
        $stmt = $pdo->prepare("INSERT INTO token_payments
            (user_id, company_name, agent_id, tokens, extras, amount_usd, amount_npr, platform, payment_method, screenshot, status, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending', NOW())");
        $stmt->execute([
            $user_id,
            $company_name,
            $agent_id,
            $selected_tokens,
            json_encode($extras),
            $total_usd,
            $total_usd * $exchange_rate,
            $platform,
            'QR',
            $screenshot
        ]);
        $message = "Payment submitted successfully! Redirecting to dashboard...";
        $message_type = "success";
    } else {
        $message = "Please select an agent, tokens, QR account, and upload screenshot.";
        $message_type = "error";
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Token Payment</title>
    <link rel="stylesheet" href="assets/css/navbar.css">
    <link rel="stylesheet" href="assets/css/footer.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f6f9;
            margin: 0;
            padding: 0;
        }

        .container {
            max-width: 1000px;
            margin: 50px auto;
            background: #fff;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        h2 {
            text-align: center;
            margin-bottom: 20px;
        }

        select,
        input[type=file],
        button {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
            border-radius: 8px;
            box-sizing: border-box;
        }

        button {
            background: #007bff;
            color: #fff;
            border: none;
            cursor: pointer;
            font-weight: bold;
            transition: 0.3s;
        }

        button:hover {
            background: #0056b3;
        }

        .success {
            background: #d4edda;
            color: #155724;
            padding: 10px;
            margin: 10px 0;
            border-radius: 6px;
            text-align: center;
            font-weight: bold;
        }

        .error {
            background: #f8d7da;
            color: #721c24;
            padding: 10px;
            margin: 10px 0;
            border-radius: 6px;
            text-align: center;
            font-weight: bold;
        }

        .payment-flex {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
            justify-content: space-between;
        }

        .billing-summary,
        .payment-form {
            flex: 1 1 45%;
            background: #f8f9fa;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        }

        .billing-summary h2,
        .payment-form h2 {
            text-align: left;
        }

        .billing-item {
            margin: 8px 0;
            font-size: 16px;
            color: #555;
        }

        .token-options {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .token-option {
            border: 1px solid #ccc;
            padding: 10px;
            border-radius: 8px;
            cursor: pointer;
            text-align: center;
            flex: 1 1 45%;
            transition: 0.3s;
        }

        .token-option.selected {
            border-color: #007bff;
            background: #e6f0ff;
        }

        .extra-option {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
        }

        .switch {
            position: relative;
            display: inline-block;
            width: 50px;
            height: 24px;
        }

        .switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: #ccc;
            transition: .4s;
            border-radius: 24px;
        }

        .slider:before {
            position: absolute;
            content: "";
            height: 18px;
            width: 18px;
            left: 3px;
            bottom: 3px;
            background: white;
            transition: .4s;
            border-radius: 50%;
        }

        input:checked+.slider {
            background: #007bff;
        }

        input:checked+.slider:before {
            transform: translateX(26px);
        }

        @media(max-width:768px) {
            .payment-flex {
                flex-direction: column;
            }

            .billing-summary,
            .payment-form {
                flex: 1 1 100%;
            }
        }
    </style>
</head>

<body>
    <?php include_once 'assets/Component/navbar.php'; ?>

    <div class="container">
        <div class="payment-flex">
            <!-- Billing Summary -->
            <div class="billing-summary">
                <h2>Billing Summary</h2>
                <div class="billing-item"><strong>Company: </strong> <?= htmlspecialchars($company_name) ?></div>
                <div class="billing-item"><strong>Agent ID: </strong> <?= $agent_id ? htmlspecialchars($agent_id) : 'No agent selected' ?></div>
                <?php if ($agent): ?>
                    <div class="billing-item"><strong>Platform: </strong><?= htmlspecialchars($agent['platform']) ?></div>
                <?php endif; ?>
                <div class="billing-item"><strong>Selected Tokens: </strong> <span id="summaryTokens">0</span></div>
                <div class="billing-item"><strong>Extras: </strong> <span id="summaryExtras">None</span></div>
                <div class="billing-item"><strong>Total Amount (USD): </strong> $<span id="summaryTotal">0.00</span></div>
                <div class="billing-item"><strong>Total Amount (NPR): </strong> NPR <span id="summaryTotalNPR">0.00</span></div>
            </div>

            <!-- Payment Form -->
            <div class="payment-form">
                <h2>Select Tokens & Extras</h2>

                <?php if ($message): ?>
                    <div class="<?= $message_type ?>"><?= htmlspecialchars($message) ?></div>
                    <?php if ($message_type === "success"): ?>
                        <script>
                            setTimeout(() => {
                                window.location.href = "dashboard.php";
                            }, 1000);
                        </script>
                    <?php endif; ?>
                <?php endif; ?>

                <form method="post" enctype="multipart/form-data">
                    <!-- Token Packages -->
                    <div class="token-options">
                        <?php foreach ($tokenPackages as $pkg): ?>
                            <div class="token-option" data-tokens="<?= $pkg['tokens'] ?>" data-price="<?= $pkg['price'] ?>">
                                <h3><?= $pkg['label'] ?></h3>
                                <p>$<?= number_format($pkg['price'], 2) ?></p>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Extras -->
                    <h3 style="margin-top:20px;">Extras</h3>
                    <?php
                    $extras_list = [
                        ['name' => 'Extra agents', 'price' => 7],
                        ['name' => 'Custom domains', 'price' => 59],
                        ['name' => 'Extra message credits', 'price' => 12]
                    ];
                    foreach ($extras_list as $ex): ?>
                        <div class="extra-option">
                            <span><?= $ex['name'] ?> ($<?= $ex['price'] ?>)</span>
                            <label class="switch">
                                <input type="checkbox" class="extra-toggle" data-price="<?= $ex['price'] ?>" data-name="<?= $ex['name'] ?>">
                                <span class="slider"></span>
                            </label>
                        </div>
                    <?php endforeach; ?>

                    <!-- Hidden inputs -->
                    <input type="hidden" name="selected_tokens" id="selected_tokens">
                    <input type="hidden" name="total_usd" id="total_usd">
                    <input type="hidden" name="extras" id="extras_input">

                    <!-- QR Payment -->
                    <label for="qr_id">Select QR Payment Account:</label>
                    <select name="qr_id" id="qr_id" required onchange="showQRInfo()">
                        <option value="">--Choose QR--</option>
                        <?php foreach ($qr_list as $qr): ?>
                            <option value="<?= $qr['id'] ?>" data-account="<?= htmlspecialchars($qr['account_number']) ?>"
                                data-holder="<?= htmlspecialchars($qr['account_name']) ?>"
                                data-image="<?= htmlspecialchars($qr['qr_image']) ?>"
                                data-platform="<?= htmlspecialchars($qr['platform']) ?>">
                                <?= htmlspecialchars($qr['platform']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="qr-info" id="qrInfo"></div>

                    <label for="screenshot">Upload Payment Screenshot:</label>
                    <input type="file" name="screenshot" required accept="image/*">

                    <button type="submit" name="submit_payment">Submit Payment</button>
                </form>
            </div>
        </div>
    </div>

    <script>
        const tokenOptions = document.querySelectorAll('.token-option');
        const extraToggles = document.querySelectorAll('.extra-toggle');
        const selectedTokensInput = document.getElementById('selected_tokens');
        const totalUsdInput = document.getElementById('total_usd');
        const extrasInput = document.getElementById('extras_input');
        const summaryTokens = document.getElementById('summaryTokens');
        const summaryExtras = document.getElementById('summaryExtras');
        const summaryTotal = document.getElementById('summaryTotal');
        const summaryTotalNPR = document.getElementById('summaryTotalNPR');
        const exchangeRate = <?= $exchange_rate ?>;

        let selectedTokenPrice = 0;
        let selectedTokenCount = 0;
        let selectedExtras = [];

        function updateSummary() {
            let extrasTotal = 0;
            selectedExtras = [];
            extraToggles.forEach(t => {
                if (t.checked) {
                    extrasTotal += parseFloat(t.dataset.price);
                    selectedExtras.push(t.dataset.name);
                }
            });
            let total = selectedTokenPrice + extrasTotal;
            summaryTokens.textContent = selectedTokenCount;
            summaryExtras.textContent = selectedExtras.length > 0 ? selectedExtras.join(', ') : 'None';
            summaryTotal.textContent = total.toFixed(2);
            summaryTotalNPR.textContent = (total * exchangeRate).toFixed(2);

            selectedTokensInput.value = selectedTokenCount;
            totalUsdInput.value = total.toFixed(2);
            extrasInput.value = JSON.stringify(selectedExtras);
        }

        tokenOptions.forEach(option => {
            option.addEventListener('click', () => {
                tokenOptions.forEach(o => o.classList.remove('selected'));
                option.classList.add('selected');
                selectedTokenCount = parseInt(option.dataset.tokens);
                selectedTokenPrice = parseFloat(option.dataset.price);
                updateSummary();
            });
        });

        extraToggles.forEach(toggle => toggle.addEventListener('change', updateSummary));

        function showQRInfo() {
            const sel = document.getElementById('qr_id');
            const infoDiv = document.getElementById('qrInfo');
            const selected = sel.options[sel.selectedIndex];
            if (selected && selected.dataset.account) {
                const holder = selected.dataset.holder;
                const account = selected.dataset.account;
                const image = selected.dataset.image;
                let html = `Account Holder: ${holder}<br>Account Number: ${account}<br>`;
                if (image) {
                    html += `<img src="${image}" width="150" style="margin-top:10px; border-radius:8px;">`;
                }
                infoDiv.innerHTML = html;
            } else {
                infoDiv.innerHTML = "";
            }
        }
    </script>

    <?php include_once 'assets/Component/footer.php'; ?>
</body>

</html>