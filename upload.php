<?php
session_start();

if (isset($_FILES["file"])) {
    $fileName = $_FILES["file"]["name"];
    $fileTmp = $_FILES["file"]["tmp_name"];
    $ext = pathinfo($fileName, PATHINFO_EXTENSION);

    $data = "";

    if ($ext === "csv") {
        $rows = array_map("str_getcsv", file($fileTmp));
        $data = json_encode($rows);
    } elseif (in_array($ext, ["doc", "docx"])) {
        // Word files require library like PHPWord
        $data = "Word file uploaded. (Need parser for actual content)";
    } else {
        $data = "Unsupported file type.";
    }

    $_SESSION["file_data"] = $data;
    header("Location: chat.php");
    exit;
}
