<?php
session_start();
require_once __DIR__ . '/../../database/db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false]); 
    exit;
}

$follower_id = $_SESSION['user_id']; // Du (den inloggade)
$following_id = (int)$_POST['user_id']; // Personen du klickar på
$action = $_POST['action'];

try {
    if ($action === 'follow') {
        // Skapa följning
        $dbconn->prepare("INSERT IGNORE INTO follows (follower_id, following_id) VALUES (?, ?)")
               ->execute([$follower_id, $following_id]);
        
        // Skapa notis, vid en följning finns inget Quack-ID att länka till. 
        // Därför sätts 'source_id' till den som följer ($follower_id) så att systemet vet vem som triggade notisen.
        $dbconn->prepare("INSERT INTO notifications (user_id, source_user_id, type, source_id, is_read) VALUES (?, ?, 'follow', ?, 0)")
               ->execute([$following_id, $follower_id, $follower_id]);
    } else {
        // Ta bort följning ur databasen
        $dbconn->prepare("DELETE FROM follows WHERE follower_id = ? AND following_id = ?")
               ->execute([$follower_id, $following_id]);
               
         // Städar bort notisen från målanvändarens flöde om man ångrar sin följning
        $dbconn->prepare("DELETE FROM notifications WHERE user_id = ? AND source_user_id = ? AND type = 'follow'")
               ->execute([$following_id, $follower_id]);
    }

    // Räkna den andras följare (Target Followers)
    $stmt1 = $dbconn->prepare("SELECT COUNT(*) FROM follows WHERE following_id = ?");
    $stmt1->execute([$following_id]);
    $targetFollowers = (int)$stmt1->fetchColumn();

    // Räkna hur många DU följer totalt (Din Following-siffra)
    $stmt2 = $dbconn->prepare("SELECT COUNT(*) FROM follows WHERE follower_id = ?");
    $stmt2->execute([$follower_id]);
    $myFollowing = (int)$stmt2->fetchColumn();
    
    echo json_encode([
        'success' => true,
        'action' => $action,
        'targetFollowers' => $targetFollowers,
        'myFollowing' => $myFollowing
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
exit;
