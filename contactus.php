<?php
include_once 'config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Get form data
        $firstName = $_POST['FirstName'] ?? '';
        $lastName = $_POST['Last_Name'] ?? '';
        $email = $_POST['Email'] ?? '';
        $phone = $_POST['PhoneNumber'] ?? '';
        $message = $_POST['Message'] ?? '';

        // Insert into DB
        $stmt = $pdo->prepare("INSERT INTO contact_messages (first_name, last_name, email, phone, message) 
                               VALUES (:first_name, :last_name, :email, :phone, :message)");
        $stmt->execute([
            ':first_name' => $firstName,
            ':last_name' => $lastName,
            ':email' => $email,
            ':phone' => $phone,
            ':message' => $message,
        ]);

        $messageStatus = "Message sent successfully!";
    } catch (PDOException $e) {
        $messageStatus = "Error: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us</title>
    <link rel="stylesheet" href="/assets/css/navbar.css">
    <link rel="stylesheet" href="/assets/css/login.css">
    <link rel="stylesheet" href="/assets/css/footer.css">
    <link rel="stylesheet" href="/assets/css/contactus.css">
</head>
<body>
<?php include_once 'assets/component/navbar.php' ?>
<div class="contact_us_green">
    <div class="responsive-container-block big-container">
        <div class="responsive-container-block container">
            <div class="responsive-cell-block wk-tab-12 wk-mobile-12 wk-desk-7 wk-ipadp-10 line" id="i69b-2">
                <?php if (!empty($messageStatus)): ?>
                    <div style="padding: 10px; color: green; font-weight: bold;"><?= htmlspecialchars($messageStatus) ?></div>
                <?php endif; ?>
                <form class="form-box" method="POST">
                    <div class="container-block form-wrapper">
                        <div class="head-text-box">
                            <p class="text-blk contactus-head">Contact us</p>
                            <p class="text-blk contactus-subhead">Lorem ipsum dolor sit amet...</p>
                        </div>
                        <div class="responsive-container-block">
                            <div class="responsive-cell-block wk-ipadp-6 wk-tab-12 wk-mobile-12 wk-desk-6">
                                <p class="text-blk input-title">FIRST NAME*</p>
                                <input class="input" name="FirstName" placeholder="FIRST NAME" required>
                            </div>
                            <div class="responsive-cell-block wk-desk-6 wk-ipadp-6 wk-tab-12 wk-mobile-12">
                                <p class="text-blk input-title">LAST NAME*</p>
                                <input class="input" name="Last_Name" placeholder="LAST NAME" required>
                            </div>
                            <div class="responsive-cell-block wk-desk-6 wk-ipadp-6 wk-tab-12 wk-mobile-12">
                                <p class="text-blk input-title">EMAIL*</p>
                                <input class="input" name="Email" type="email" placeholder="EMAIL" required>
                            </div>
                            <div class="responsive-cell-block wk-desk-6 wk-ipadp-6 wk-tab-12 wk-mobile-12">
                                <p class="text-blk input-title">PHONE NUMBER*</p>
                                <input class="input" name="PhoneNumber" placeholder="PHONE NUMBER" required>
                            </div>
                            <div class="responsive-cell-block wk-tab-12 wk-mobile-12 wk-desk-12 wk-ipadp-12">
                                <p class="text-blk input-title">WHAT DO YOU HAVE IN MIND *</p>
                                <textarea class="textinput" name="Message" placeholder="Please enter query..." required></textarea>
                            </div>
                        </div>
                        <div class="btn-wrapper">
                            <button type="submit" class="submit-btn">Submit</button>
                        </div>
                    </div>
                </form>
            </div>
            <div class="responsive-cell-block wk-tab-12 wk-mobile-12 wk-desk-5 wk-ipadp-10">
                <div class="container-box">
                    <div class="text-content">
                        <p class="text-blk contactus-head">Contact us</p>
                        <p class="text-blk contactus-subhead">Lorem ipsum dolor sit amet...</p>
                    </div>
                    <div class="workik-contact-bigbox">
                        <div class="workik-contact-box">
                            <div class="phone text-box">
                                <img class="contact-svg" src="https://workik-widget-assets.s3.amazonaws.com/widget-assets/images/ET21.jpg">
                                <p class="contact-text">+1258 3258 5679</p>
                            </div>
                            <div class="address text-box">
                                <img class="contact-svg" src="https://workik-widget-assets.s3.amazonaws.com/widget-assets/images/ET22.jpg">
                                <p class="contact-text">hello@workik.com</p>
                            </div>
                            <div class="mail text-box">
                                <img class="contact-svg" src="https://workik-widget-assets.s3.amazonaws.com/widget-assets/images/ET23.jpg">
                                <p class="contact-text">102 street, y cross 485656</p>
                            </div>
                        </div>
                        <div class="social-media-links">
                            <a href="#"><img class="social-svg" src="https://workik-widget-assets.s3.amazonaws.com/widget-assets/images/gray-mail.svg"></a>
                            <a href="#"><img class="social-svg" src="https://workik-widget-assets.s3.amazonaws.com/widget-assets/images/gray-insta.svg"></a>
                            <a href="#"><img class="social-svg" src="https://workik-widget-assets.s3.amazonaws.com/widget-assets/images/gray-fb.svg"></a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'assets/component/footer.php' ?>
</body>
</html>
