<?php
session_start();
require_once __DIR__ . '/../../database/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['user_id'])) {
    $quackId = (int)$_POST['quack_id'];
    $userId = $_SESSION['user_id'];
    $content = trim($_POST['comment']);

    if (!empty($content)) {
        // Spara själva kommentaren
        $stmt = $dbconn->prepare("INSERT INTO comments (quack_id, user_id, content) VALUES (?, ?, ?)");
        $stmt->execute([$quackId, $userId, $content]);

        // --- NOTIS-LOGIK START ---
        // Ta reda på vem som äger quacken som kommenterades
        $stmtOwner = $dbconn->prepare("SELECT user_id FROM quacks WHERE id = ?");
        $stmtOwner->execute([$quackId]);
        $quackOwnerId = $stmtOwner->fetchColumn();

        //  Skapa notis om det inte är man själv som kommenterar
        if ($quackOwnerId && $quackOwnerId != $userId) {
            $stmtNotif = $dbconn->prepare("
                INSERT INTO notifications (user_id, source_user_id, type, source_id, is_read) 
                VALUES (?, ?, 'comment', ?, 0)
            ");
            // Vi sätter quackId som source_id så att notisen länkar till inlägget där kommentaren finns
            $stmtNotif->execute([$quackOwnerId, $userId, $quackId]);
        }
        // --- NOTIS-LOGIK SLUT ---
    }
}

// Skicka tillbaka användaren till quack-vyn
header("Location: ../quack.php?id=" . $quackId);
exit;
