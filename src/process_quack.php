<?php
session_start();
require_once __DIR__ . '/../database/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['user_id'])) {
    $content = trim($_POST['quack_content']);
    $userId = $_SESSION['user_id'];

    if (!empty($content) && strlen($content) <= 280) {
        $stmt = $dbconn->prepare("INSERT INTO quacks (user_id, content, created_at) VALUES (?, ?, NOW())");
        $stmt->execute([$userId, $content]);
    }
}

header("Location: ../public/index.php");
exit;
