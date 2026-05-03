<?php
session_start();
require_once __DIR__ . '/../../database/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['user_id'])) {
    $followerId = $_SESSION['user_id'];
    $followingId = (int)$_POST['user_id'];
    $action = $_POST['action'];

    if ($action === 'follow') {
        $stmt = $dbconn->prepare("INSERT IGNORE INTO follows (follower_id, following_id) VALUES (?, ?)");
        $stmt->execute([$followerId, $followingId]);
    } else {
        $stmt = $dbconn->prepare("DELETE FROM follows WHERE follower_id = ? AND following_id = ?");
        $stmt->execute([$followerId, $followingId]);
    }

    //hämta nya antalet följare för profilen
    $countStmt = $dbconn->prepare("SELECT COUNT(*) FROM follows WHERE following_id = ?");
    $countStmt->execute([$followingId]);
    $newFollowerCount = $countStmt->fetchColumn();

    //skicka tillbaka data till JS
    header('Content-Type: Application/json');
    echo json_encode([
        'success' => true,
        'newCount' => $newFollowerCount,
        'action' => $action
    ]);
    exit;
}