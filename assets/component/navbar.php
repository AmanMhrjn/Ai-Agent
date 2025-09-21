<?php
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>AI Agent Navbar</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      /* margin-top: 100px; */
      font-family: Arial, sans-serif;
    }

    .topnav {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 15px 30px;
      background-color: #fff;
      box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      z-index: 1000;
    }

    .nav_logo {
      width: 120px;
      height: auto;
    }

    /* Center nav links (desktop only) */
    .nav-center {
      position: absolute;
      left: 50%;
      top: 50%;
      transform: translate(-50%, -50%);
      display: flex;
      gap: 30px;
      z-index: 1;
    }

    .nav-center a {
      text-decoration: none;
      color: #333;
      font-weight: 500;
      transition: color 0.3s;
    }

    .nav-center a:hover {
      color: #007BFF;
    }

    #nav_icon {
      display: none;
      cursor: pointer;
      z-index: 1001;
    }

    .nav_menu {
      list-style: none;
      display: flex;
      align-items: center;
      gap: 20px;
    }

    .nav_menu a {
      text-decoration: none;
      color: #333;
      font-weight: 500;
    }

    .login-signup .nav_link {
      padding: 8px 16px;
      background-color: #007BFF;
      color: #fff !important;
      border-radius: 5px;
    }

    .login-signup .nav_link:hover {
      background-color: #0056b3;
    }

    .nav_profile {
      cursor: pointer;
      position: relative;
    }

    .nav_profile .sub_menu {
      display: none;
      position: absolute;
      font-size: 15px;
      width: 140px;
      top: 100%;
      right: 0;
      background: #fff;
      box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
      list-style: none;
      padding: 10px 0;
      z-index: 999;
      border-radius: 6px;
    }

    .nav_profile:hover .sub_menu {
      display: block;
    }

    .sub_nav_item a {
      padding: 10px 20px;
      display: block;
      color: #333;
      text-decoration: none;
    }

    .sub_nav_item a:hover {
      background-color: #f0f0f0;
    }

    /* Hide mobile duplicates on desktop */
    .mobile-only {
      display: none;
    }

    /* Mobile styles */
    @media (max-width: 768px) {
      .nav-center {
        display: none;
      }

      #nav_icon {
        display: block;
      }

      .mobile-only {
        display: block;
      }

      .nav_menu {
        flex-direction: column;
        position: absolute;
        top: 100%;
        right: 0;
        width: 100%;
        background: #fff;
        border-top: 1px solid #ddd;
        display: none;
        padding: 15px 0;
        z-index: 1000;
      }

      .nav_menu.active {
        display: flex;
      }

      .nav_menu li {
        text-align: center;
        margin: 10px 0;
      }

      .nav_menu .sub_menu {
        position: static;
        box-shadow: none;
      }

      .nav_menu .nav_profile:hover .sub_menu {
        display: block;
      }
    }
  </style>
</head>

<body>

  <div class="topnav">
    <a href="/"><img class="nav_logo" src="#" alt="logo" /></a>

    <i id="nav_icon" class="fa-solid fa-bars fa-lg"></i>

    <div class="nav-center">
      <a href="../../pricing.php">Pricing</a>
      <a href="../../enterprises.php">Enterprises</a>
      <a href="../../contactus.php">Contact Us</a>
    </div>

    <ul class="nav_menu" id="nav_menu">
      <li class="mobile-only"><a href="../../pricing.php">Pricing</a></li>
      <li class="mobile-only"><a href="../../enterprises.php">Enterprises</a></li>
      <li class="mobile-only"><a href="../../contactus.php">Contact Us</a></li>

      <?php if (isset($_SESSION['id'])): ?>
        <li class="nav_item nav_profile">
          <span class="nav_link">
            <?php
            if (isset($_SESSION['companyname'])) {
              echo htmlspecialchars($_SESSION['companyname']);
            } else {
              echo 'Companyname';
            }
            ?>
          </span>
          <ul class="sub_menu">
            <li class="sub_nav_item"><a class="nav_link" href="/maindashboard.php">Dashboard</a></li>
            <li class="sub_nav_item"><a class="nav_link" href="/chat.php">Chat</a></li>
            <li class="sub_nav_item"><a class="nav_link" href="/profile.php">Profile</a></li>
            <li class="sub_nav_item"><a class="nav_link" href="/products.php">Products</a></li>
            <li class="sub_nav_item"><a class="nav_link" href="/orders.php">Orders</a></li>
            <li class="sub_nav_item"><a class="nav_link" href="/fileupload.php">File Upload</a></li>
            <li class="sub_nav_item"><a class="nav_link" href="/assets/component/logout.php">Logout</a></li>
          </ul>
        </li>
      <?php else: ?>
        <li class="nav_item login-signup">
          <a class="nav_link" href="/assets/component/login.php">Login</a>
        </li>
      <?php endif; ?>
    </ul>
  </div>

  <script>
    const navIcon = document.getElementById("nav_icon");
    const navMenu = document.getElementById("nav_menu");

    navIcon.addEventListener("click", () => {
      navMenu.classList.toggle("active");
    });
  </script>

</body>
</html>
