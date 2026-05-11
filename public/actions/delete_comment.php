<?php
session_start();
require_once __DIR__ . '/../../database/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['user_id'])) {
    $commentId = (int)$_POST['comment_id'];
    $userId = $_SESSION['user_id'];

    // Säkerställ att det är ägaren eller en admin som raderar
    $stmt = $dbconn->prepare("DELETE FROM comments WHERE id = ? AND (user_id = ? OR ? = 1)");
    $success = $stmt->execute([$commentId, $userId, $_SESSION['is_admin']]);

    echo json_encode(['success' => $success]);
    exit;
}
