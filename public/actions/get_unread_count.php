<?php
session_start();
require_once __DIR__ . '/../../database/db.php';

header('Content-Type: application/json');

$response = [
    'unread_notifications' => 0,
    'unread_messages' => 0
];

if (isset($_SESSION['user_id'])) {
    $uid = $_SESSION['user_id'];
    
    // Räkna olästa notiser
    $stmtNotif = $dbconn->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
    $stmtNotif->execute([$uid]);
    $response['unread_notifications'] = (int)$stmtNotif->fetchColumn();

    // Räkna olästa meddelanden (där man är mottagare)
    $stmtMsg = $dbconn->prepare("SELECT COUNT(*) FROM messages WHERE receiver_id = ? AND is_read = 0");
    $stmtMsg->execute([$uid]);
    $response['unread_messages'] = (int)$stmtMsg->fetchColumn();
}

// API-SVAR (JSON): Detta skript anropas asynkront (polling via JavaScript) var 5:e sekund.
// JSON-datan returneras till frontenden för att dynamiskt rita ut röda siffer-badgar i menyraden.
echo json_encode($response);
exit;
