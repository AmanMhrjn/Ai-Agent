<?php
session_start();
require_once 'config/database.php';

$user_id = $_SESSION['id'] ?? 0;
$company_name = $_SESSION['companyname'] ?? '';

if (!$user_id) {
    header("Location: assets/component/login.php");
    exit;
}

$plan = $_GET['plan'] ?? 'free';
$amount_usd = (float) ($_GET['amount'] ?? 0);
$exchange_rate = 139.72;
$amount_npr = $amount_usd * $exchange_rate;
$amount_paisa = (int) round($amount_npr * 100);

$khalti_public = "test_public_key_xxxxx";
$plan_tokens = ['free' => 5000, 'premium' => 15000, 'platinum' => 50000];
$tokens_to_add = $plan_tokens[$plan] ?? 0;

// Fetch QR payment options from database
$qr_stmt = $pdo->query("SELECT * FROM payment_qr_details ORDER BY platform ASC");
$qr_list = $qr_stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle Form Submission
$message = '';
if (isset($_POST['submit_payment'])) {
    $qr_id = $_POST['qr_id'] ?? 0;
    $platform = $_POST['platform'] ?? '';
    $screenshot = '';

    if (isset($_FILES['screenshot']) && $_FILES['screenshot']['error'] == 0) {
        $filename = time() . '_' . basename($_FILES['screenshot']['name']);
        $target = 'uploads/payment_screenshots/' . $filename;
        if (!is_dir('uploads/payment_screenshots')) {
            mkdir('uploads/payment_screenshots', 0777, true);
        }
        move_uploaded_file($_FILES['screenshot']['tmp_name'], $target);
        $screenshot = $target;
    }

    if ($qr_id && $screenshot) {
        $stmt = $pdo->prepare("
        INSERT INTO payment_requests 
        (user_id, company_name, plan, platform, amount, payment_method, screenshot, status, created_at) 
        VALUES (?, ?, ?, ?, ?, ?, ?, 'Pending', NOW())
    ");
        // set platform as NULL or empty string
        $stmt->execute([$user_id, $company_name, $plan, null, $amount_npr, 'QR', $screenshot]);

        $message = "Payment submitted successfully! Admin will review and approve your payment.";
    } else {
        $message = "Please select QR and upload screenshot.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Page</title>
    <link rel="stylesheet" href="assets/css/navbar.css">
    <link rel="stylesheet" href="assets/css/footer.css">
    <link rel="stylesheet" href="assets/css/payment.css">
    <script src="https://khalti.com/static/khalti-checkout.js"></script>
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

        .payment-flex {
            display: flex;
            gap: 20px;
            justify-content: space-between;
            flex-wrap: wrap;
        }

        .billing-summary {
            flex: 1 1 40%;
            background: #f8f9fa;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        }

        .billing-summary h2 {
            margin-bottom: 15px;
            font-size: 20px;
            color: #333;
        }

        .billing-item {
            margin: 8px 0;
            font-size: 16px;
            color: #555;
        }

        .payment-form {
            flex: 1 1 55%;
            background: #fff;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
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

        .selected-plan {
            margin: 10px 0;
            padding: 10px;
            background: #e9ecef;
            border-radius: 8px;
        }

        button {
            background: #007bff;
            color: #fff;
            border: none;
            cursor: pointer;
            font-weight: bold;
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
        }

        .error {
            background: #f8d7da;
            color: #721c24;
            padding: 10px;
            margin: 10px 0;
            border-radius: 6px;
        }

        .qr-info {
            margin-bottom: 15px;
            font-size: 14px;
        }

        .btn-secondary {
            background: #28a745;
            color: #fff;
            margin-top: 10px;
        }

        /* Responsive adjustments */
        @media (max-width: 1024px) {
            .container {
                max-width: 90%;
                margin: 40px auto;
                padding: 18px;
            }
        }

        @media (max-width: 768px) {
            .container {
                max-width: 95%;
                margin: 30px auto;
                padding: 15px;
            }

            select,
            input[type=file],
            button {
                font-size: 14px;
                padding: 8px;
            }

            .selected-plan {
                font-size: 14px;
                padding: 8px;
            }

            .qr-info {
                font-size: 13px;
            }

            .payment-flex {
                flex-direction: column;
            }

            .billing-summary,
            .payment-form {
                flex: 1 1 100%;
            }
        }

        @media (max-width: 480px) {
            h2 {
                font-size: 18px;
            }

            select,
            input[type=file],
            button {
                font-size: 13px;
                padding: 7px;
            }

            .selected-plan {
                font-size: 13px;
                padding: 7px;
            }

            .qr-info {
                font-size: 12px;
            }
        }
    </style>
</head>

<body>
    <?php include_once 'assets/Component/navbar.php'; ?>

    <div class="container">
        <div class="payment-flex">
            <!-- Left: Billing Summary -->
            <div class="billing-summary">
                <h2>Billing Summary</h2>
                <div class="billing-item"><strong>Company:</strong> <?= htmlspecialchars($company_name) ?></div>
                <div class="billing-item"><strong>Plan:</strong> <?= htmlspecialchars(ucfirst($plan)) ?></div>
                <div class="billing-item"><strong>Amount:</strong> NPR <?= number_format($amount_npr, 2) ?></div>
            </div>

            <!-- Right: Payment Form -->
            <div class="payment-form">
                <?php if ($message): ?>
                    <div id="msgDiv" class="<?= strpos($message, 'success') !== false ? 'success' : 'error' ?>">
                        <?= htmlspecialchars($message) ?>
                    </div>
                    <?php if (strpos($message, 'success') !== false): ?>
                        <script>
                            setTimeout(function() {
                                window.location.href = 'maindashboard.php';
                            }, 1000);
                        </script>
                    <?php endif; ?>
                <?php endif; ?>

                <!-- QR Payment Form -->
                <form method="post" enctype="multipart/form-data">
                    <label for="qr_id">Select QR Payment Account:</label>
                    <select name="qr_id" id="qr_id" required onchange="showQRInfo()">
                        <option value="">--Choose QR--</option>
                        <?php foreach ($qr_list as $qr): ?>
                            <option value="<?= $qr['id'] ?>"
                                data-account="<?= htmlspecialchars($qr['account_number']) ?>"
                                data-holder="<?= htmlspecialchars($qr['account_name']) ?>"
                                data-image="<?= htmlspecialchars($qr['qr_image']) ?>">
                                <?= htmlspecialchars($qr['platform']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <div class="qr-info" id="qrInfo"></div>

                    <div class="selected-plan">
                        <strong>Selected Plan:</strong> <?= htmlspecialchars(ucfirst($plan)) ?>
                    </div>

                    <!-- <label for="platform">Select Platform:</label>
                <select name="platform" required>
                    <option value="">--Choose Platform--</option>
                    <option value="Facebook">Facebook</option>
                    <option value="Instagram">Instagram</option>
                    <option value="WhatsApp">WhatsApp</option>
                </select> -->

                    <label for="screenshot">Upload Payment Screenshot:</label>
                    <input type="file" name="screenshot" required accept="image/*">

                    <button type="submit" name="submit_payment">Submit Payment</button>
                </form>
            </div>
        </div>
    </div>

    <script>
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

        // Khalti Payment
        var khaltiConfig = {
            publicKey: "<?= $khalti_public ?>",
            productIdentity: "PLAN_<?= $plan ?>_USER_<?= $user_id ?>",
            productName: "Agent Payment <?= ucfirst($plan) ?>",
            productUrl: window.location.href,
            eventHandler: {
                onSuccess(payload) {
                    alert("Khalti Payment Success!");
                },
                onError(err) {
                    console.log(err);
                    alert("Khalti Payment Failed");
                },
                onClose() {
                    console.log("Widget closed");
                }
            },
            paymentPreference: ["KHALTI"]
        };
        var checkout = new KhaltiCheckout(khaltiConfig);
        document.getElementById("khaltiPayBtn")?.addEventListener("click", function() {
            checkout.show({
                amount: <?= $amount_paisa ?>
            });
        });

        // Demo Card Payment
        document.getElementById("cardPayBtn")?.addEventListener("click", function() {
            if (confirm("Proceed with demo card payment?")) {
                alert("Demo Card Payment Success!");
            }
        });
    </script>

    <?php include_once 'assets/Component/footer.php'; ?>
</body>

</html>