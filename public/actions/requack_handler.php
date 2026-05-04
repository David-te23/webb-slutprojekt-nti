<?php
session_start();
require_once __DIR__ . '/../../database/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['user_id'])) {
    $userId = $_SESSION['user_id'];
    $quackId = (int)$_POST['quack_id'];

    // Kolla om användaren redan har requackat inlägget
    $check = $dbconn->prepare("SELECT id FROM quacks WHERE user_id = ? AND parent_id = ? AND content IS NULL");
    $check->execute([$userId, $quackId]);
    $existing = $check->fetch();

    if ($existing) {
        // Om det redan finns så ångra requack (ta bort)
        $delete = $dbconn->prepare("DELETE FROM quacks WHERE id = ?");
        $delete->execute([$existing['id']]);
        $status = 'removed';
    } else {
        // Om det inte finns så skapa nytt requack-inlägg
        $insert = $dbconn->prepare("INSERT INTO quacks (user_id, parent_id, content) VALUES (?, ?, NULL)");
        $insert->execute([$userId, $quackId]);
        $status = 'added';
    }

    // Hämta det nya totala antalet requacks för originalet
    $countStmt = $dbconn->prepare("SELECT COUNT(*) FROM quacks WHERE parent_id = ? AND content IS NULL");
    $countStmt->execute([$quackId]);
    $newCount = $countStmt->fetchColumn();

    echo json_encode([
        'success' => true,
        'status' => $status,
        'newCount' => (int)$newCount
    ]);
    exit;
}
