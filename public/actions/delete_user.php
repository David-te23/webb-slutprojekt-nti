<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }

require_once __DIR__ . '/../../database/db.php';

// Säkerhetskoll
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Ej behörig.']);
    exit;
}

$userId = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;

if ($userId <= 0) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Ogiltigt användar-ID.']);
    exit;
}

try {
    $dbconn->beginTransaction();

    // Hantera REQUACKS (Rensa bort tomma requacks av användarens inlägg)
    // Hämta ID:n för alla quacks som tillhör användaren som ska raderas
    $idStmt = $dbconn->prepare("SELECT id FROM quacks WHERE user_id = ?");
    $idStmt->execute([$userId]);
    $userQuackIds = $idStmt->fetchAll(PDO::FETCH_COLUMN);

    if (!empty($userQuackIds)) {
        $placeholders = implode(',', array_fill(0, count($userQuackIds), '?'));
        
        // Radera alla rena requacks (inlägg utan eget innehåll) som pekar på dessa inlägg
        $delReqStmt = $dbconn->prepare("DELETE FROM quacks WHERE parent_id IN ($placeholders) AND content IS NULL");
        $delReqStmt->execute($userQuackIds);

        // För "Quote Quacks" (inlägg MED eget innehåll), nollställ bara parent_id
        $updQuoteStmt = $dbconn->prepare("UPDATE quacks SET parent_id = NULL WHERE parent_id IN ($placeholders)");
        $updQuoteStmt->execute($userQuackIds);
    }

    // Ta bort kopplingar i kopplingstabeller (Hashtags och Bilder)
    $dbconn->prepare("DELETE FROM quack_hashtags WHERE quack_id IN (SELECT id FROM quacks WHERE user_id = ?)")->execute([$userId]);
    $dbconn->prepare("DELETE FROM quack_images WHERE quack_id IN (SELECT id FROM quacks WHERE user_id = ?)")->execute([$userId]);

    // Ta bort interaktioner (Likes, Comments, Notifications)
    $dbconn->prepare("DELETE FROM likes WHERE user_id = ? OR quack_id IN (SELECT id FROM quacks WHERE user_id = ?)")->execute([$userId, $userId]);
    $dbconn->prepare("DELETE FROM comments WHERE user_id = ? OR quack_id IN (SELECT id FROM quacks WHERE user_id = ?)")->execute([$userId, $userId]);
    
    // Notifications: Ta bort där användaren är mottagare ELLER orsak
    $dbconn->prepare("DELETE FROM notifications WHERE user_id = ? OR source_user_id = ?")->execute([$userId, $userId]);

    // Meddelanden och Följare
    $dbconn->prepare("DELETE FROM messages WHERE sender_id = ? OR receiver_id = ?")->execute([$userId, $userId]);
    $dbconn->prepare("DELETE FROM follows WHERE follower_id = ? OR following_id = ?")->execute([$userId, $userId]);

    // Ta bort användarens egna Quacks
    $dbconn->prepare("DELETE FROM quacks WHERE user_id = ?")->execute([$userId]);

    // Ta slutligen bort användaren
    $stmt = $dbconn->prepare("DELETE FROM users WHERE id = ? AND is_admin = 0");
    $stmt->execute([$userId]);

    $dbconn->commit();
    
    header('Content-Type: application/json');
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    if ($dbconn->inTransaction()) { 
        $dbconn->rollBack(); 
    }
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
