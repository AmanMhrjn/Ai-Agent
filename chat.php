<?php
session_start();
require_once "config/database.php"; // make sure $pdo is defined

// --- Check login ---
if (!isset($_SESSION["id"])) {
    header("Location: assets/component/login.php");
    exit;
}

$user_id = $_SESSION["id"];
$username = $_SESSION["username"] ?? "Guest";
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>AI File Chat</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            background: #ece5dd;
            font-family: Arial, sans-serif;
            /* display: flex; */
            justify-content: center;
            align-items: flex-start;
            /* min-height: 100vh; */
        }

        .chat-container {
            width: 420px;
            height: 80vh;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            display: flex;
            flex-direction: column;
            overflow: hidden;
            margin: 70px auto 0px auto;
        }

        .chat-header {
            background: #075e54;
            color: white;
            padding: 14px;
            font-size: 18px;
            font-weight: bold;
            text-align: center;
        }

        .chat-messages {
            flex: 1;
            padding: 12px;
            overflow-y: auto;
            background: #ece5dd;
            scroll-behavior: smooth;
        }

        /* Custom scrollbar */
        .chat-messages::-webkit-scrollbar {
            width: 6px;
        }

        .chat-messages::-webkit-scrollbar-thumb {
            background: #888;
            border-radius: 6px;
        }

        .message {
            margin: 8px 0;
            padding: 10px 14px;
            border-radius: 18px;
            max-width: 75%;
            word-wrap: break-word;
            font-size: 14px;
            line-height: 1.4;
            clear: both;
        }

        .user-message {
            background: #dcf8c6;
            float: right;
            border-bottom-right-radius: 4px;
        }

        .admin-message {
            background: #fff;
            float: left;
            border-bottom-left-radius: 4px;
        }

        .chat-input {
            display: flex;
            border-top: 1px solid #ddd;
            background: #f7f7f7;
            padding: 8px;
        }

        .chat-input input[type="text"] {
            flex: 1;
            border: 1px solid #ccc;
            padding: 10px;
            border-radius: 20px;
            font-size: 14px;
            outline: none;
        }

        .chat-input button {
            background: #075e54;
            color: white;
            border: none;
            border-radius: 20px;
            padding: 10px 16px;
            margin-left: 8px;
            cursor: pointer;
            font-size: 14px;
            transition: background 0.2s;
        }

        .chat-input button:hover {
            background: #0a7c6d;
        }
    </style>
    <link rel="stylesheet" href="assets/css/footer.css">

</head>

<body>
    <?php include_once 'assets/component/navbar.php'; ?>
    <div class="chat-container">
        <div class="chat-header">Chat</div>
        <div class="chat-messages" id="chat-box"></div>

        <div class="chat-input">
            <input type="text" id="user-input" placeholder="Type your message...">
            <button onclick="sendMessage()">Send</button>
        </div>
    </div>

    <script>
        const chatBox = document.getElementById("chat-box");

        // Load chat messages from server
        function loadMessages() {
            fetch("fetch_user_messages.php")
                .then(response => response.json())
                .then(data => {
                    chatBox.innerHTML = "";
                    data.forEach(msg => {
                        const div = document.createElement("div");
                        div.classList.add("message", msg.sender === "user" ? "user-message" : "admin-message");
                        div.textContent = msg.message;
                        chatBox.appendChild(div);
                    });
                    chatBox.scrollTop = chatBox.scrollHeight; // auto scroll
                });
        }

        // Send user message
        function sendMessage() {
            const input = document.getElementById("user-input");
            const text = input.value.trim();
            if (!text) return;

            fetch("save_message.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/x-www-form-urlencoded"
                },
                body: "message=" + encodeURIComponent(text)
            }).then(() => loadMessages());

            input.value = "";
        }

        // Initial load
        loadMessages();

        // Auto-refresh every 3 seconds
        setInterval(loadMessages, 3000);
    </script>
    <?php include_once 'assets/component/footer.php'; ?>
</body>

</html>