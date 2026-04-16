<?php
session_start();
require_once __DIR__ . '/../../database/db.php';

if (!isset($_SESSION['user_id']) || !isset($_POST['quack_id'])) {
    echo json_encode(['status' => 'error']);
    exit;
}

$user_id = $_SESSION['user_id'];
$quack_id = $_POST['quack_id'];

// Check if user already liked it
$stmt = $dbconn->prepare("SELECT 1 FROM likes WHERE user_id = ? AND quack_id = ?");
$stmt->execute([$user_id, $quack_id]);

if ($stmt->fetch()) {
    // Unlike
    $dbconn->prepare("DELETE FROM likes WHERE user_id = ? AND quack_id = ?")->execute([$user_id, $quack_id]);
    $is_liked = false;
} else {
    // Like
    $dbconn->prepare("INSERT INTO likes (user_id, quack_id) VALUES (?, ?)")->execute([$user_id, $quack_id]);
    $is_liked = true;
}

// Get updated count
$stmt = $dbconn->prepare("SELECT COUNT(*) FROM likes WHERE quack_id = ?");
$stmt->execute([$quack_id]);
$new_count = $stmt->fetchColumn();

echo json_encode(['status' => 'success', 'new_count' => $new_count, 'is_liked' => $is_liked]);
