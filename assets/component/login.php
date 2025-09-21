<?php
session_start();
require_once '../../config/database.php'; // PDO connection $pdo

$message = '';
$success = false;

// Redirect if already logged in
if (isset($_SESSION['id']) && isset($_SESSION['companyname'])) {
    header("Location: /index.php");
    exit();
}

// Handle POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // === LOGIN ===
    if (isset($_POST['login'])) {
        $email = trim($_POST['email']);
        $password = $_POST['password'];

        if (empty($email) || empty($password)) {
            $message = "Please fill in both email and password.";
        } else {
            $stmt = $pdo->prepare("SELECT user_id, company_name, password, status FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user) {
                if (password_verify($password, $user['password'])) {
                    if ($user['status']) {
                        $_SESSION['id'] = $user['user_id'];
                        $_SESSION['companyname'] = $user['company_name'];
                        header("Location: /index.php");
                        exit();
                    } else {
                        $message = "Your account has been disabled. Please contact support.";
                    }
                } else {
                    $message = "Incorrect password.";
                }
            } else {
                $message = "No account found with that email. <a href='login.php'>Register here</a>.";
            }
        }

        // === SIGNUP ===
    } elseif (isset($_POST['signup'])) {
        $name = trim($_POST['name']);
        $company = trim($_POST['companyname']);
        $email = trim($_POST['email']);
        $password = $_POST['password'];
        $repassword = $_POST['repassword'];

        if (empty($name) || empty($company) || empty($email) || empty($password) || empty($repassword)) {
            $message = "Please fill in all signup fields.";
        } elseif ($password !== $repassword) {
            $message = "Passwords do not match.";
        } else {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $message = "Email already registered. <a href='login.php'>Login here</a>.";
            } else {
                $hash = password_hash($password, PASSWORD_BCRYPT);
                $otp = rand(100000, 999999);

                $stmt = $pdo->prepare("INSERT INTO users (username, company_name, email, password, status, verified, otp) VALUES (?, ?, ?, ?, 1, 0, ?)");
                // if ($stmt->execute([$name, $company, $email, $hash, $otp])) {
                //     $_SESSION['pending_email'] = $email;
                //     $_SESSION['otp_message'] = "Your verification code is: $otp (for testing only)";
                //     header("Location: login.php");
                //     exit();
                // }

                if ($stmt->execute([$name, $company, $email, $hash, $otp])) {
                    // Set session for OTP verification
                    $_SESSION['pending_email'] = $email;
                    $_SESSION['otp_message'] = "Your verification code is: $otp (for testing only)";

                    // --- ADD FREE PLAN PAYMENT REQUEST ---
                    $user_id = $pdo->lastInsertId(); // ID of newly inserted user

                    $insertPayment = $pdo->prepare("
                        INSERT INTO payment_requests 
                        (user_id, company_name, plan, platform, amount, payment_method, screenshot, qr_id, status)
                        VALUES (?, ?, 'free', 'free', 0, 'free', NULL, NULL, 'approved')
                    ");
                    $insertPayment->execute([$user_id, $company]);

                    // Get the payment_id for message_balance
                    $payment_id = $pdo->lastInsertId();

                    // --- ADD MESSAGE BALANCE ENTRY ---
                    $insertBalance = $pdo->prepare("
                        INSERT INTO message_balance 
                        (payment_id, company_name, total_messages, messages_used, platform, plan)
                        VALUES (?, ?, 5000, 0, 'free', 'free')
                    ");
                    $insertBalance->execute([$payment_id, $company]);

                    // Redirect to same page to show OTP modal
                    header("Location: login.php");
                    exit();
                }
            }
        }

        // === OTP VERIFICATION ===
    } elseif (isset($_POST['verify_otp'])) {
        $otp = trim($_POST['otp']);
        $email = $_SESSION['pending_email'] ?? null;

        if ($email && $otp) {
            $stmt = $pdo->prepare("SELECT otp FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && $user['otp'] == $otp) {
                $stmt = $pdo->prepare("UPDATE users SET verified = 1, otp = NULL WHERE email = ?");
                $stmt->execute([$email]);
                unset($_SESSION['pending_email']);
                $_SESSION['otp_verified'] = "Email verified successfully! You can now login.";
                header("Location: login.php");
                exit();
            } else {
                $message = "Invalid OTP. Please try again.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <title>Agent | Login | SignUp</title>
    <link rel="stylesheet" href="../css/login.css" />
    <link rel="stylesheet" href="../css/navbar.css" />
    <script src="https://kit.fontawesome.com/7d37335a3d.js" crossorigin="anonymous"></script>
    <style>
        .modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            display: none;
            justify-content: center;
            align-items: center;
            background: rgba(0, 0, 0, 0.5);
            z-index: 9999;
        }

        .modal-content {
            background: #fff;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            position: relative;
            z-index: 10000;
        }
    </style>
</head>

<body>
    <?php require_once 'navbar.php'; ?>

    <!-- Floating alerts -->
    <?php if (!empty($message)) : ?>
        <script>
            window.onload = function() {
                const alertDiv = document.createElement("div");
                alertDiv.innerHTML = <?= json_encode($message) ?>;
                alertDiv.className = "floating-alert <?= $success ? 'success' : 'error' ?>";
                document.body.appendChild(alertDiv);
                setTimeout(() => {
                    alertDiv.remove();
                }, 4000);
            };
        </script>
    <?php endif; ?>

    <div class="login-main-container">
        <div class="loginContainer" id="container">
            <!-- Signup Form -->
            <div class="login-form-container sign-up-container">
                <form action="" method="POST" id="form" onsubmit="return validateRegistration()">
                    <h1>Create Account</h1>
                    <div class="social-container">
                        <a href="#" class="social"><i class="fa-brands fa-facebook"></i></a>
                        <a href="#" class="social"><i class="fa-brands fa-google-plus-g"></i></a>
                    </div>
                    <span>or use your email for registration</span>
                    <input type="text" name="name" id="name" placeholder="Name" />
                    <div class="error" id="name_error"></div>
                    <input type="text" name="companyname" id="companyname" placeholder="Company Name" />
                    <div class="error" id="companyname_error"></div>
                    <input type="email" id="signup_email" name="email" placeholder="Email" />
                    <div class="error" id="signup_email_error"></div>
                    <input type="password" id="signup_password" name="password" placeholder="Password" />
                    <input type="password" id="signup_repassword" name="repassword" placeholder="Re-enter Password" />
                    <div class="error" id="signup_password_error"></div>
                    <button class="loginbtn" type="submit" name="signup">Sign Up</button>
                </form>
            </div>

            <!-- Login Form -->
            <div class="login-form-container sign-in-container">
                <form action="" method="POST" id="login_form" onsubmit="return validateLogin()">
                    <h1>Sign in</h1>
                    <div class="social-container">
                        <a href="#" class="social"><i class="fa-brands fa-facebook"></i></a>
                        <a href="#" class="social"><i class="fa-brands fa-google-plus-g"></i></a>
                    </div>
                    <span>or use your account</span>
                    <input type="email" id="login_email" name="email" placeholder="Email" />
                    <div class="error" id="login_email_error"></div>
                    <input type="password" id="login_password" name="password" placeholder="Password" />
                    <div class="error" id="login_password_error"></div>
                    <a href="forgetPassword.php">Forgot your password?</a>
                    <button class="loginbtn" type="submit" name="login">Sign In</button>
                </form>
            </div>

            <!-- Overlay -->
            <div class="overlay-container">
                <div class="overlay">
                    <div class="overlay-panel overlay-left">
                        <h1>Welcome Back!</h1>
                        <p>To keep connected with us please login with your personal info</p>
                        <button class="ghost" id="signIn">Sign In</button>
                    </div>
                    <div class="overlay-panel overlay-right">
                        <h1>Hello, Friend!</h1>
                        <p>Enter your personal details and start your journey with us</p>
                        <button class="ghost" id="signUp">Sign Up</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- OTP Modal -->
    <div class="modal" id="otpModal">
        <div class="modal-content">
            <h2>Email Verification</h2>
            <p>Enter the 6-digit code sent to your email.</p>
            <form method="POST">
                <input type="text" name="otp" maxlength="6" required />
                <button type="submit" name="verify_otp">Verify</button>
            </form>
        </div>
    </div>

    <!-- Show OTP modal if pending_email exists -->
    <?php if (isset($_SESSION['pending_email'])): ?>
        <script>
            window.onload = function() {
                document.getElementById("otpModal").style.display = "flex";
                <?php if (isset($_SESSION['otp_message'])): ?>
                    alert("<?= $_SESSION['otp_message'] ?>");
                <?php unset($_SESSION['otp_message']);
                endif; ?>
            };
        </script>
    <?php endif; ?>

    <!-- Show OTP verified message -->
    <?php if (isset($_SESSION['otp_verified'])): ?>
        <script>
            alert("<?= $_SESSION['otp_verified'] ?>");
        </script>
    <?php unset($_SESSION['otp_verified']);
    endif; ?>

    <script src="../js/login.js"></script>
</body>

</html>