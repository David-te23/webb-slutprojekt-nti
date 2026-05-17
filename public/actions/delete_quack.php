<?php
session_start();
require_once __DIR__ . '/../../database/db.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['user_id'])) {
    $quackId = (int)$_POST['quack_id'];
    $userId = $_SESSION['user_id'];
    $isAdmin = (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1);

    // BEHÖRIGHETSKONTROLL: Hämtar inläggets ägare från databasen först 
    // för att säkerställa att en extern användare inte kan manipulera POST-datan.
    $stmt = $dbconn->prepare("SELECT user_id FROM quacks WHERE id = ?");
    $stmt->execute([$quackId]);
    $ownerId = $stmt->fetchColumn();

     // Endast ägaren av inlägget eller en administratör tillåts gå vidare 
    if ($ownerId == $userId || $isAdmin) {
        try {
            // Radera allt som kan tänkas blockera raderingen av huvudquacket
            $dbconn->prepare("DELETE FROM comments WHERE quack_id = ?")->execute([$quackId]);
            $dbconn->prepare("DELETE FROM quack_hashtags WHERE quack_id = ?")->execute([$quackId]);
            $dbconn->prepare("DELETE FROM likes WHERE quack_id = ?")->execute([$quackId]);
            $dbconn->prepare("DELETE FROM quack_images WHERE quack_id = ?")->execute([$quackId]);
            $dbconn->prepare("DELETE FROM notifications WHERE source_id = ? AND type != 'follow'")->execute([$quackId]);
            
            // Hantera requacks (om andra har delat detta quack)
            $dbconn->prepare("DELETE FROM quacks WHERE parent_id = ?")->execute([$quackId]);

            // HUVUDRADERING: Nu när alla beroenden och länkade rader är borta 
            // kan originalinlägget raderas säkert
            $delete = $dbconn->prepare("DELETE FROM quacks WHERE id = ?");
            if ($delete->execute([$quackId])) {
                echo json_encode(['success' => true]);
                exit;
            }
        } catch (PDOException $e) {
            // Fångar upp eventuella databasfel så att skriptet inte kraschar helt
            echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
            exit;
        }
    }
}
echo json_encode(['success' => false, 'error' => 'Unauthorized']);
