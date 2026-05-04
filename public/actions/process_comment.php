<?php
session_start();
require_once __DIR__ . '/../../database/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['user_id'])) {
    $quackId = (int)$_POST['quack_id'];
    $userId = $_SESSION['user_id'];
    $content = trim($_POST['comment']);

    if (!empty($content)) {
        $stmt = $dbconn->prepare("INSERT INTO comments (quack_id, user_id, content) VALUES (?, ?, ?)");
        $stmt->execute([$quackId, $userId, $content]);
    }
}

// Skicka tillbaka användaren till quack-vyn
header("Location: ../quack.php?id=" . $quackId);
exit;
