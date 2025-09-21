<?php
if (session_status() == PHP_SESSION_NONE) {
  session_start();
  if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit;
  }
}

require_once __DIR__ . '/../config/database.php';

// Fetch all users with unread message count
$userSql = "
    SELECT u.user_id, u.username, u.company_name, 
           SUM(CASE WHEN cm.sender='user' AND cm.is_read=0 THEN 1 ELSE 0 END) AS unread_count
    FROM users u
    LEFT JOIN chat_messages cm ON u.user_id = cm.user_id
    GROUP BY u.user_id
";
$userStmt = $pdo->query($userSql);
$users = $userStmt->fetchAll(PDO::FETCH_ASSOC);

// Selected user
$selectedUserId = $_GET['user_id'] ?? null;

// Mark messages as read when a user is selected
if ($selectedUserId) {
  $pdo->prepare("UPDATE chat_messages SET is_read=1 WHERE user_id=? AND sender='user'")
    ->execute([$selectedUserId]);
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>Admin Chat Panel</title>
  <link rel="stylesheet" href="../assets/css/main.css" />
  <style>
    body {
      font-family: Arial, sans-serif;
      background: #ece5dd;
      margin: 0;
    }

    .container {
      display: flex;
      max-width: 1000px;
      height: 80vh;
      margin: 20px auto;
      border-radius: 8px;
      overflow: hidden;
      background: #fff;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }

    /* Sidebar */
    .user-list {
      width: 280px;
      border-right: 1px solid #ccc;
      overflow-y: auto;
      background: #f0f0f0;
    }

    .user-item {
      padding: 15px;
      cursor: pointer;
      border-bottom: 1px solid #e0e0e0;
      display: flex;
      justify-content: space-between;
      align-items: center;
      text-decoration: none;
      color: #000;
    }

    .user-item.active,
    .user-item:hover {
      background: #e0e0e0;
    }

    .unread {
      background: #25d366;
      color: #fff;
      font-size: 12px;
      padding: 2px 8px;
      border-radius: 12px;
      font-weight: bold;
    }

    /* Chat container */
    .chat-container {
      flex: 1;
      display: flex;
      flex-direction: column;
    }

    .chat-header {
      padding: 15px;
      background: #075e54;
      color: #fff;
      border-bottom: 1px solid #ccc;
    }

    .chat-header h3 {
      margin: 0;
      font-size: 18px;
    }

    .chat-header small {
      display: block;
      font-size: 12px;
      color: #d1f0e0;
      margin-top: 2px;
    }

    .chat-boxs {
      flex: 1;
      padding: 15px;
      overflow-y: auto;
      background: #e5ddd5;
    }

    .message {
      padding: 10px 15px;
      margin-bottom: 10px;
      border-radius: 20px;
      max-width: 70%;
      word-wrap: break-word;
      clear: both;
      display: inline-block;
    }

    .user-message {
      background: #fff;
      float: left;
      border-radius: 0 15px 15px 15px;
    }

    .admin-message {
      background: #dcf8c6;
      float: right;
      border-radius: 15px 0 15px 15px;
    }

    .message small {
      display: block;
      font-size: 10px;
      color: #555;
      margin-top: 5px;
      text-align: right;
    }

    form {
      display: flex;
      padding: 10px;
      border-top: 1px solid #ccc;
      background: #f0f0f0;
    }

    form textarea {
      flex: 1;
      padding: 10px 15px;
      border-radius: 20px;
      border: 1px solid #ccc;
      resize: none;
      font-size: 14px;
    }

    form button {
      margin-left: 10px;
      padding: 10px 20px;
      border-radius: 20px;
      border: none;
      background: #075e54;
      color: #fff;
      cursor: pointer;
      font-weight: bold;
    }

    form button:hover {
      background: #128c7e;
    }

    /* Scrollbar styling */
    .user-list::-webkit-scrollbar,
    .chat-box::-webkit-scrollbar {
      width: 6px;
    }

    .user-list::-webkit-scrollbar-thumb,
    .chat-box::-webkit-scrollbar-thumb {
      background-color: rgba(0, 0, 0, 0.2);
      border-radius: 3px;
    }
  </style>
</head>

<body>
  <?php include_once 'component/sidebar.php' ?>
  <div class="main-content">
    <div class="container">
      <!-- User List -->
      <div class="user-list">
        <?php foreach ($users as $user): ?>
          <a href="?user_id=<?= $user['user_id'] ?>" class="user-item <?= $selectedUserId == $user['user_id'] ? 'active' : '' ?>">
            <span><?= htmlspecialchars($user['username']) ?> (<?= htmlspecialchars($user['company_name']) ?>)</span>
            <?php if ($user['unread_count'] > 0): ?>
              <span class="unread"><?= $user['unread_count'] ?></span>
            <?php endif; ?>
          </a>
        <?php endforeach; ?>
      </div>

      <!-- Chat Container -->
      <div class="chat-container">
        <!-- Header -->
        <div class="chat-header">
          <h3>Admin Chat Panel</h3>
          <?php if ($selectedUserId): ?>
            <small>Chatting with: <?= htmlspecialchars($users[array_search($selectedUserId, array_column($users, 'user_id'))]['username'] ?? '') ?></small>
          <?php endif; ?>
        </div>

        <!-- Chat messages -->
        <div class="chat-boxs" id="chat-box"></div>

        <!-- Reply Form -->
        <?php if ($selectedUserId): ?>
          <form method="post" action="send_reply.php">
            <input type="hidden" name="user_id" value="<?= $selectedUserId ?>">
            <textarea name="reply" rows="2" placeholder="Type your reply"></textarea>
            <button type="submit">Send</button>
          </form>
        <?php endif; ?>
      </div>
    </div>
  </div>
  <script>
    const chatBox = document.getElementById('chat-box');
    const userId = <?= $selectedUserId ? $selectedUserId : 'null' ?>;

    function loadMessages() {
      if (!userId) return;
      fetch('fetch_messages.php?user_id=' + userId)
        .then(response => response.text())
        .then(data => {
          chatBox.innerHTML = data;
          chatBox.scrollTop = chatBox.scrollHeight; // auto scroll
        });
    }

    loadMessages();
    setInterval(loadMessages, 3000);
  </script>
</body>

</html>