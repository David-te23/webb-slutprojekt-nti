<?php
session_start();
require_once __DIR__ . '/../../database/db.php';

if (!isset($_SESSION['user_id']) || !isset($_POST['quack_id'])) {
    echo json_encode(['status' => 'error']);
    exit;
}

$user_id = $_SESSION['user_id'];
$quack_id = $_POST['quack_id'];

// Kolla om användaren redan har gillat
$stmt = $dbconn->prepare("SELECT 1 FROM likes WHERE user_id = ? AND quack_id = ?");
$stmt->execute([$user_id, $quack_id]);
$already_liked = $stmt->fetch();

if ($already_liked) {
    // Unlike: Ta bort like
    $dbconn->prepare("DELETE FROM likes WHERE user_id = ? AND quack_id = ?")->execute([$user_id, $quack_id]);
    
    // Ta bort notisen också om användaren ångrar sin like direkt
    $dbconn->prepare("DELETE FROM notifications WHERE source_user_id = ? AND source_id = ? AND type = 'like'")
           ->execute([$user_id, $quack_id]);
           
    $is_liked = false;
} else {
    // Like: Lägg till like
    $dbconn->prepare("INSERT INTO likes (user_id, quack_id) VALUES (?, ?)")->execute([$user_id, $quack_id]);
    $is_liked = true;

    // --- NOTIS-LOGIK START ---
    // Hämta ägaren av quacken (mottagaren)
    $stmtOwner = $dbconn->prepare("SELECT user_id FROM quacks WHERE id = ?");
    $stmtOwner->execute([$quack_id]);
    $quack_owner_id = $stmtOwner->fetchColumn();

    // Skapa bara notis om det inte är ens egen quack
    if ($quack_owner_id && $quack_owner_id != $user_id) {
        // Kolla om en notis redan finns (för att undvika spam om man klickar snabbt)
        $stmtCheckNotif = $dbconn->prepare("SELECT 1 FROM notifications WHERE user_id = ? AND source_user_id = ? AND type = 'like' AND source_id = ?");
        $stmtCheckNotif->execute([$quack_owner_id, $user_id, $quack_id]);
        
        if (!$stmtCheckNotif->fetch()) {
            $dbconn->prepare("INSERT INTO notifications (user_id, source_user_id, type, source_id, is_read) VALUES (?, ?, 'like', ?, 0)")
                   ->execute([$quack_owner_id, $user_id, $quack_id]);
        }
    }
    // --- NOTIS-LOGIK SLUT ---
}

// Hämta uppdaterat antal
$stmt = $dbconn->prepare("SELECT COUNT(*) FROM likes WHERE quack_id = ?");
$stmt->execute([$quack_id]);
$new_count = $stmt->fetchColumn();

echo json_encode(['status' => 'success', 'new_count' => $new_count, 'is_liked' => $is_liked]);
