<?php
session_start();

$userMessage = $_POST["message"] ?? "";
$fileData = $_SESSION["file_data"] ?? "";

// Very simple demo logic
if (!$fileData) {
    echo "Please upload a CSV or Word file first.";
    exit;
}

// Example: just echo data if user asks "show data"
if (stripos($userMessage, "show") !== false) {
    echo "Here is your uploaded data: " . substr($fileData, 0, 200) . "...";
} else {
    echo "You said: '$userMessage'. (AI logic will go here using file data)";
}
