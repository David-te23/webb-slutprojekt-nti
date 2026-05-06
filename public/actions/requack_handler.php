<?php
session_start();
require_once __DIR__ . '/../../database/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['user_id'])) {
    $userId = $_SESSION['user_id'];
    $quackId = (int)$_POST['quack_id'];

    // Kolla om användaren redan har requackat
    $check = $dbconn->prepare("SELECT id FROM quacks WHERE user_id = ? AND parent_id = ? AND content IS NULL");
    $check->execute([$userId, $quackId]);
    $existing = $check->fetch();

    if ($existing) {
        // Ångra requack
        $delete = $dbconn->prepare("DELETE FROM quacks WHERE id = ?");
        $delete->execute([$existing['id']]);
        
        // Ta bort tillhörande notis
        $dbconn->prepare("DELETE FROM notifications WHERE source_user_id = ? AND source_id = ? AND type = 'requack'")
               ->execute([$userId, $quackId]);
               
        $status = 'removed';
    } else {
        // Skapa requack
        $insert = $dbconn->prepare("INSERT INTO quacks (user_id, parent_id, content) VALUES (?, ?, NULL)");
        $insert->execute([$userId, $quackId]);
        $status = 'added';

        // --- NOTIS-LOGIK START ---
        // Hitta ägaren av original-quacken
        $stmtOwner = $dbconn->prepare("SELECT user_id FROM quacks WHERE id = ?");
        $stmtOwner->execute([$quackId]);
        $originalOwnerId = $stmtOwner->fetchColumn();

        // Skapa notis om det inte är man själv
        if ($originalOwnerId && $originalOwnerId != $userId) {
            $dbconn->prepare("INSERT INTO notifications (user_id, source_user_id, type, source_id, is_read) VALUES (?, ?, 'requack', ?, 0)")
                   ->execute([$originalOwnerId, $userId, $quackId]);
        }
        // --- NOTIS-LOGIK SLUT ---
    }

    $countStmt = $dbconn->prepare("SELECT COUNT(*) FROM quacks WHERE parent_id = ? AND content IS NULL");
    $countStmt->execute([$quackId]);
    $newCount = $countStmt->fetchColumn();

    echo json_encode(['success' => true, 'status' => $status, 'newCount' => (int)$newCount]);
    exit;
}
