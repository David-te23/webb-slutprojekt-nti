<?php
session_start();
require_once __DIR__ . '/../../database/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['user_id'])) {
    $followerId = $_SESSION['user_id'];
    $followingId = (int)$_POST['user_id'];
    $action = $_POST['action'];

    if ($action === 'follow') {
        // Följ användaren
        $stmt = $dbconn->prepare("INSERT IGNORE INTO follows (follower_id, following_id) VALUES (?, ?)");
        $stmt->execute([$followerId, $followingId]);

        // --- NOTIS-LOGIK START ---
        // Skapa notis (men inte om man följer sig själv, även om det vore konstigt)
        if ($followerId !== $followingId) {
            // Vi använder followerId som source_id här eftersom länken ska gå till den personens profil
            $stmtNotif = $dbconn->prepare("INSERT INTO notifications (user_id, source_user_id, type, source_id, is_read) VALUES (?, ?, 'follow', ?, 0)");
            $stmtNotif->execute([$followingId, $followerId, $followerId]);
        }
        // --- NOTIS-LOGIK SLUT ---

    } else {
        // Sluta följa
        $stmt = $dbconn->prepare("DELETE FROM follows WHERE follower_id = ? AND following_id = ?");
        $stmt->execute([$followerId, $followingId]);

        // Ta bort notisen om man slutar följa (så den inte ligger kvar och skräpar)
        $stmtDelNotif = $dbconn->prepare("DELETE FROM notifications WHERE user_id = ? AND source_user_id = ? AND type = 'follow'");
        $stmtDelNotif->execute([$followingId, $followerId]);
    }

    // Hämta nya antalet följare för profilen
    $countStmt = $dbconn->prepare("SELECT COUNT(*) FROM follows WHERE following_id = ?");
    $countStmt->execute([$followingId]);
    $newFollowerCount = $countStmt->fetchColumn();

    // Skicka tillbaka data till JS
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'newCount' => $newFollowerCount,
        'action' => $action
    ]);
    exit;
}
