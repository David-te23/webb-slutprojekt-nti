<?php

function performFullUserDeletion($dbconn, $userId) {
    // Hämta quack-ID:n för att städa requacks/quotes
    $idStmt = $dbconn->prepare("SELECT id FROM quacks WHERE user_id = ?");
    $idStmt->execute([$userId]);
    $userQuackIds = $idStmt->fetchAll(PDO::FETCH_COLUMN);

    if (!empty($userQuackIds)) {
        $placeholders = implode(',', array_fill(0, count($userQuackIds), '?'));
        // Radera requacks
        $dbconn->prepare("DELETE FROM quacks WHERE parent_id IN ($placeholders) AND content IS NULL")->execute($userQuackIds);
        // Nollställ quotes
        $dbconn->prepare("UPDATE quacks SET parent_id = NULL WHERE parent_id IN ($placeholders)")->execute($userQuackIds);
    }

    // Ta bort allt kopplat till användaren (Bilder, Hashtags, Likes, etc.)
    $dbconn->prepare("DELETE FROM quack_hashtags WHERE quack_id IN (SELECT id FROM quacks WHERE user_id = ?)")->execute([$userId]);
    $dbconn->prepare("DELETE FROM quack_images WHERE quack_id IN (SELECT id FROM quacks WHERE user_id = ?)")->execute([$userId]);
    $dbconn->prepare("DELETE FROM likes WHERE user_id = ? OR quack_id IN (SELECT id FROM quacks WHERE user_id = ?)")->execute([$userId, $userId]);
    $dbconn->prepare("DELETE FROM comments WHERE user_id = ? OR quack_id IN (SELECT id FROM quacks WHERE user_id = ?)")->execute([$userId, $userId]);
    $dbconn->prepare("DELETE FROM notifications WHERE user_id = ? OR source_user_id = ?")->execute([$userId, $userId]);
    $dbconn->prepare("DELETE FROM messages WHERE sender_id = ? OR receiver_id = ?")->execute([$userId, $userId]);
    $dbconn->prepare("DELETE FROM follows WHERE follower_id = ? OR following_id = ?")->execute([$userId, $userId]);
    $dbconn->prepare("DELETE FROM quacks WHERE user_id = ?")->execute([$userId]);

    // Ta bort användaren
    return $dbconn->prepare("DELETE FROM users WHERE id = ?")->execute([$userId]);
}
