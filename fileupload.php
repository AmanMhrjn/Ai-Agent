<?php
session_start();
if (!isset($_SESSION['id'])) {
    echo '<!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8" />
        <title>Access Denied</title>
        <style>
            body { display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; font-family: Arial, sans-serif; background-color: #fff3f3; }
            .alert-box { background: #ffe2e2; border: 1px solid #e53e3e; padding: 30px 50px; border-radius: 10px; color: #c53030; text-align: center; box-shadow: 0 0 10px rgba(229, 62, 62, 0.3); font-size: 1.3rem; }
            .alert-box small { display: block; margin-top: 10px; color: #666; font-size: 1rem; }
        </style>
        <script>
            setTimeout(() => { window.location.href = "assets/component/login.php"; }, 3000);
        </script>
    </head>
    <body>
        <div class="alert-box">
            ⚠️ Please <strong>LOGIN</strong> to access the <strong>DASHBOARD</strong>.
            <small>Redirecting to login page...</small>
        </div>
    </body>
    </html>';
    exit;
}

include_once 'config/database.php';

// Fetch all pages for the logged-in user
$user_id = $_SESSION['id'];
$stmt = $pdo->prepare("SELECT ap.id AS page_id, ap.page_name, ap.agent_id 
                       FROM agent_pages ap
                       JOIN agents a ON a.id = ap.agent_id
                       WHERE a.user_id = :user_id");
$stmt->execute(['user_id' => $user_id]);
$pages = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Upload CSV - AI Agent</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/navbar.css">
    <link rel="stylesheet" href="assets/css/login.css">
    <link rel="stylesheet" href="assets/css/footer.css">
    <link rel="stylesheet" href="assets/css/fileupload.css">
</head>

<body>

    <?php include_once 'assets/component/navbar.php'; ?>

    <div class="file-container">
        <div class="file-left">
            <h2 class="file-h2">CSV File Upload</h2>
            <p>Upload CSV file to train your AI with product details.</p>

            <label for="pageSelect">Select Page:</label>
            <select id="pageSelect" style="margin-bottom: 15px; padding: 5px 10px;">
                <option value="">-- Select Page --</option>
                <?php foreach ($pages as $page): ?>
                    <option value="<?= $page['page_id'] ?>"><?= htmlspecialchars($page['page_name']) ?></option>
                <?php endforeach; ?>
            </select>

            <div class="file-note">⚠️ Only CSV files allowed. Max size: 400 KB</div>
            <div id="dropArea" class="drop-area-file">
                Drag & drop CSV here, or click to select<br>
                <small>Supported: CSV only (Max 400 KB)</small>
                <input type="file" id="fileInput" style="display: none;" />
            </div>
        </div>

        <div class="file-right">
            <h3>Sources</h3>
            <div class="meta">Total size: <span id="fileSize">0 B / 400 KB</span></div>
            <div class="action-buttons">
                <button class="create-agent-btn-file" id="trainAgentBtn">Train Agent</button>
            </div>
        </div>
    </div>

    <div class="uploaded-section">
        <h3>Selected File</h3>
        <div id="uploadedFiles" class="uploaded-list"></div>
    </div>

    <script>
        const dropArea = document.getElementById('dropArea');
        const fileInput = document.getElementById('fileInput');
        const fileSizeText = document.getElementById('fileSize');
        const uploadedFilesDiv = document.getElementById('uploadedFiles');
        const MAX_SIZE = 400 * 1024; // 400 KB
        let selectedFile = null;

        function formatBytes(bytes) {
            const sizes = ['B', 'KB', 'MB'];
            if (bytes === 0) return '0 B';
            const i = parseInt(Math.floor(Math.log(bytes) / Math.log(1024)));
            return (bytes / Math.pow(1024, i)).toFixed(2) + ' ' + sizes[i];
        }

        dropArea.addEventListener('click', () => fileInput.click());
        dropArea.addEventListener('dragover', (e) => {
            e.preventDefault();
            dropArea.style.borderColor = '#000';
        });
        dropArea.addEventListener('dragleave', () => {
            dropArea.style.borderColor = '#ccc';
        });
        dropArea.addEventListener('drop', (e) => {
            e.preventDefault();
            dropArea.style.borderColor = '#ccc';
            handleFile(e.dataTransfer.files[0]);
        });
        fileInput.addEventListener('change', (e) => handleFile(e.target.files[0]));

        function handleFile(file) {
            if (!file) return;
            const ext = file.name.split('.').pop().toLowerCase();
            if (ext !== 'csv') {
                alert("❌ Only CSV files are allowed!");
                fileInput.value = "";
                return;
            }
            if (file.size > MAX_SIZE) {
                alert("❌ File exceeds 400 KB limit!");
                fileInput.value = "";
                return;
            }
            selectedFile = file;
            fileSizeText.textContent = formatBytes(file.size) + ' / 400 KB';
            uploadedFilesDiv.innerHTML = `<div class="uploaded-file"><strong>${file.name}</strong> (${formatBytes(file.size)})</div>`;
        }

        document.getElementById("trainAgentBtn").addEventListener("click", function(e) {
            e.preventDefault();
            const pageId = document.getElementById("pageSelect").value;
            if (!pageId) {
                alert("Please select a Page!");
                return;
            }
            if (!selectedFile) {
                alert("Please select a CSV file!");
                return;
            }

            const formData = new FormData();
            formData.append("csv_file", selectedFile);
            formData.append("page_id", pageId);

            fetch("assets/component/importCSV.php", {
                    method: "POST",
                    body: formData
                })
                .then(res => res.text())
                .then(response => {
                    const [status, message] = response.split("|");
                    if (status.trim() === "success") {
                        alert("✅ " + message);
                        window.location.href = "dashboard.php";
                    } else {
                        alert("❌ " + message);
                    }
                })
                .catch(err => alert("❌ An error occurred: " + err));
        });
    </script>

    <?php include_once 'assets/component/footer.php'; ?>
</body>

</html>