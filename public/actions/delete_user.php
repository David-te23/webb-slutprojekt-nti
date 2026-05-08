<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }

require_once __DIR__ . '/../../database/db.php';
require_once __DIR__ . '/../../includes/user_deletion_logic.php';

header('Content-Type: application/json');

// Säkerhetskoll: Endast admins får köra denna fil
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    echo json_encode(['success' => false, 'error' => 'Ej behörig.']);
    exit;
}

// Hämta ID från POST
$userId = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;

if ($userId <= 0) {
    echo json_encode(['success' => false, 'error' => 'Ogiltigt användar-ID.']);
    exit;
}

try {
    $dbconn->beginTransaction();

    $stmtCheck = $dbconn->prepare("SELECT is_admin FROM users WHERE id = ?");
    $stmtCheck->execute([$userId]);
    $userToDelete = $stmtCheck->fetch();

    if ($userToDelete && $userToDelete['is_admin'] == 0) {
        performFullUserDeletion($dbconn, $userId);
        $dbconn->commit();
        echo json_encode(['success' => true]);
    } else {
        throw new Exception("Kan inte radera en administratör.");
    }

} catch (Exception $e) {
    if ($dbconn->inTransaction()) { 
        $dbconn->rollBack(); 
    }
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
