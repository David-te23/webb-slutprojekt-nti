<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../../database/db.php';
require_once __DIR__ . '/../../includes/user_deletion_logic.php';

// Måste vara inloggad
if (!isset($_SESSION['user_id'])) {
    header("Location: ../public/login.php");
    exit;
}

$userId = $_SESSION['user_id'];

try {
    $dbconn->beginTransaction();
    performFullUserDeletion($dbconn, $userId);
    $dbconn->commit();
    
    session_destroy();
    header("Location: ../register.php?msg=account_deleted");
    exit;
} catch (Exception $e) {
    if ($dbconn->inTransaction()) { $dbconn->rollBack(); }
    die("Ett fel uppstod: " . $e->getMessage());
}
