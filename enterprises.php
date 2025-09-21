<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI Agent | Enterprise</title>
    <link rel="stylesheet" href="assets/css/navbar.css">
    <link rel="stylesheet" href="assets/css/login.css">
    <link rel="stylesheet" href="assets/css/footer.css">
    <link rel="stylesheet" href="assets/css/enterprises.css">
    <link rel="stylesheet" href="assets/css/main.css">
</head>

<body>
    <?php include_once 'assets/component/navbar.php'; ?>
    <div class="entp-container">
        <div class="entp-herosection">
            <div class="entp-image">
                <img src="images/aiagent.jpg" alt="">
            </div>
            <h1>Enterprise-Ready AI for customer service</h1>
            <p class="entp-text">Empower your customer support with enterprise-level AI agents. Effortlessly manage
                high volumes using robust infrastructure, <br>smart automation, and dedicated team collaboration. Ensure
                seamless customer experiences backed <br>by proven reliability.</p>
            <!-- <button class="entp-btn"><a href="contactus.php">Contact Us</a></button> -->
            <div>
                <button class="entp-btn">
                    <i class="animation"></i>
                    <a href="contactus.php">Contact Us</a>
                    <i class="animation"></i>
                </button>
            </div>
        </div>

        <div class="features-container">
            <div class="feature-trusted">
                Trusted by <span>9000+ businesses</span> worldwide
            </div>

            <div class="feature-logos">
                <img src="images/favicon.jpg" alt="Siemens" width="50px" height="100px" />
                <img src="images/favicon.jpg" alt="Siemens" width="50px" height="100px" />
                <img src="images/favicon.jpg" alt="Siemens" width="50px" height="100px" />
                <img src="images/favicon.jpg" alt="Siemens" width="50px" height="100px" />
                <img src="images/favicon.jpg" alt="Siemens" width="50px" height="100px" />
                <img src="images/favicon.jpg" alt="Siemens" width="50px" height="100px" />

            </div>
        </div>
        <div class="entp-cards">
            <h1>What's included in Enterprise </h1>
            <div class="entp-card-container">
                <div class="entp-feature-card">
                    <div class="entp-feature-image">
                        <img src="images/account.jpg" alt="security" />
                    </div>
                    <h3>Dedicated Account Manager</h3>
                    <p>Enjoy 24/7 support, monitoring, and guidance from your expert.</p>
                </div>
                <div class="entp-feature-card">
                <div class="entp-feature-image">
                    <img src="images/integration.jpg" alt="security" />
                </div>
                <h3>Custom Integration</h3>
                <p>We build tailored integrations to seamlessly fit your internal tools and workflows.</p>
            </div>
            <div class="entp-feature-card">
                <div class="entp-feature-image">
                    <img src="images/sla.jpg" alt="security" />
                </div>
                <h3>SLA Guarantees</h3>
                <p>Enjoy enterprise-grade uptime, reliability, and support with SLAs.</p>
            </div>
        </div>
        <div class="entp-calltoaction">
            <div class="entp-left-container">
                <div class="entp-left">
                    <h1>Ready to elevate your business?</h1>
                    <p>Message us to learn more about our Enterprise plan and how it can help you grow.</p>
                </div>
            </div>
            <div class="entp-right-container">
                <div class="entp-right">
                    <h2>Get a Quote</h2>
                    <p>We will get back to you within 24 hours.</p>
                    <form class="entp-form" action="">
                        <input type="text" name="email" placeholder="Your Email" /> <br>
                        <input type="number" name="number" placeholder="Phone number"><br>
                        <textarea name="message" id="message" placeholder="Type message here" rows="4" cols="50"></textarea><br>
                        <button type="submit">SUBMIT</button>
                    </form>
                </div>
            </div>
        </div>

        <h2>Add More sections Here</h2>
    </div>
    <?php include_once 'assets/component/footer.php'; ?>
</body>

</html>