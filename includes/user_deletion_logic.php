<?php

function performFullUserDeletion($dbconn, $userId) {
    // Hämta alla quack-ID:n som ägs av användaren för att kunna städa upp i relaterade tabeller
    $idStmt = $dbconn->prepare("SELECT id FROM quacks WHERE user_id = ?");
    $idStmt->execute([$userId]);
    $userQuackIds = $idStmt->fetchAll(PDO::FETCH_COLUMN);

    if (!empty($userQuackIds)) {
        $placeholders = implode(',', array_fill(0, count($userQuackIds), '?'));
        // Radera tomma requacks som andra användare har gjort på den raderade användarens inlägg
        $dbconn->prepare("DELETE FROM quacks WHERE parent_id IN ($placeholders) AND content IS NULL")->execute($userQuackIds);
        // Hantera kvarvarande kopplingar: Om ett inlägg har detta parent_id, nollställer vi referensen.
        $dbconn->prepare("UPDATE quacks SET parent_id = NULL WHERE parent_id IN ($placeholders)")->execute($userQuackIds);
    }

    // MANUELL FALLBACK FÖR CASCADE: Om databasens inbyggda 'ON DELETE CASCADE' av någon anledning 
    // inte skulle vara aktiverad i tabellrelationerna, så rensar dessa rader manuellt bort all 
    // relaterad data (likes, kommentarer, bilder etc.)
    $dbconn->prepare("DELETE FROM quack_hashtags WHERE quack_id IN (SELECT id FROM quacks WHERE user_id = ?)")->execute([$userId]);
    $dbconn->prepare("DELETE FROM quack_images WHERE quack_id IN (SELECT id FROM quacks WHERE user_id = ?)")->execute([$userId]);
    $dbconn->prepare("DELETE FROM likes WHERE user_id = ? OR quack_id IN (SELECT id FROM quacks WHERE user_id = ?)")->execute([$userId, $userId]);
    $dbconn->prepare("DELETE FROM comments WHERE user_id = ? OR quack_id IN (SELECT id FROM quacks WHERE user_id = ?)")->execute([$userId, $userId]);
    $dbconn->prepare("DELETE FROM notifications WHERE user_id = ? OR source_user_id = ?")->execute([$userId, $userId]);
    $dbconn->prepare("DELETE FROM messages WHERE sender_id = ? OR receiver_id = ?")->execute([$userId, $userId]);
    $dbconn->prepare("DELETE FROM follows WHERE follower_id = ? OR following_id = ?")->execute([$userId, $userId]);

    // Nu när alla tabeller som refererar till användarens inlägg är helt tomma, kan vi säkert radera själva inläggen.
    $dbconn->prepare("DELETE FROM quacks WHERE user_id = ?")->execute([$userId]);

    // Ta bort användaren (Radera raden i tabellen 'users')
    return $dbconn->prepare("DELETE FROM users WHERE id = ?")->execute([$userId]);
}
