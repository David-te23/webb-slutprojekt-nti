<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }

require_once __DIR__ . '/../../database/db.php';
require_once __DIR__ . '/../../includes/user_deletion_logic.php';

header('Content-Type: application/json');

// Säkerhetskoll: Endast admins får köra denna fil
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    echo json_encode(['success' => false, 'error' => 'Not authorized.']);
    exit;
}

// Hämta ID från POST och säkerställ att det är ett giltigt heltal
$userId = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;

if ($userId <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid user-ID.']);
    exit;
}

try {
    // Startar en transaktion. alla kommande raderingar (likes, kommentarer, quacks, etc.)
    // körs som ett enda block. Om något fel inträffar halvvägs sparas ingenting
    $dbconn->beginTransaction();

    // SKYDD MOT ADMIN-RADERING: Kontrollerar målets status i databasen först.
    $stmtCheck = $dbconn->prepare("SELECT is_admin FROM users WHERE id = ?");
    $stmtCheck->execute([$userId]);
    $userToDelete = $stmtCheck->fetch();

    // Tillåt endast radering om målet existerar och INTE är en administratör (is_admin == 0)
    if ($userToDelete && $userToDelete['is_admin'] == 0) {
        performFullUserDeletion($dbconn, $userId);

          // Sparar alla ändringar permanent i databasen i ett enda svep
        $dbconn->commit();
        echo json_encode(['success' => true]);
    } else {
        throw new Exception("Kan inte radera en administratör.");
    }

} catch (Exception $e) {
    // ÅTERSTÄLLNING (ROLLBACK): Om något kraschade i raderingsprocessen återställs 
    // databasen till exakt det skick den var i innan transaktionen startade.
    if ($dbconn->inTransaction()) { 
        $dbconn->rollBack(); 
    }
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
