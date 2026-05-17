<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../../database/db.php';
require_once __DIR__ . '/../../includes/quack_time_formatter.php';

// 1. ISOLERAT ANROP (AJAX-FALLBACK): Denna vy laddas via polling.
// function_exists() förhindrar krascher ifall skriptet inkluderas på en sida där funktionen redan finns.
if (!function_exists('getPfpPath')) {
    function getPfpPath($fileName) {
        if (!$fileName || $fileName === 'default_pfp.jpg') {
            return "../public/images/default_pfp.jpg";
        }
        return "../uploads/pfp/" . $fileName;
    }
}

$myId = $_SESSION['user_id'];
$contactId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : null;


// Hämtar listan över pågående konversationer i inkorgen.
// Underfrågan med MAX(id) ser till att vi BARA hämtar det absolut SENASTE meddelandet i varje chatt.
// GROUP BY IF(...) grupperar ihop meddelanden baserat på motpartens ID, oavsett om DU var sändare eller mottagare.
$convStmt = $dbconn->prepare("
    SELECT m.*, u.id as contact_id, u.username, u.display_name, u.profile_image
    FROM messages m
    JOIN users u ON (u.id = m.sender_id OR u.id = m.receiver_id) AND u.id != :myId
    WHERE m.id IN (
        SELECT MAX(id) FROM messages 
        WHERE sender_id = :myId OR receiver_id = :myId 
        GROUP BY IF(sender_id = :myId, receiver_id, sender_id)
    )
    ORDER BY m.created_at DESC
");
$convStmt->execute(['myId' => $myId]);
$conversations = $convStmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($conversations as $conv): ?>
    <a href="messages.php?user_id=<?= $conv['contact_id'] ?>" 
       class="d-flex align-items-center p-3 border-bottom text-decoration-none text-dark <?= ($contactId == $conv['contact_id']) ? 'bg-white border-start border-success border-4' : '' ?>">
        <img src="<?= getPfpPath($conv['profile_image']) ?>" class="rounded-circle me-2 msg_pfp" alt="Profile image">
        <div class="overflow-hidden text-truncate w-100">
            <div class="d-flex justify-content-between">
                <div class="fw-bold small"><?= htmlspecialchars($conv['display_name']) ?></div>
                <small class="text-muted" style="font-size: 0.7rem;"><?= formatQuackTime($conv['created_at']) ?></small>
            </div>
            <div class="small text-muted text-truncate">
                <?php
                // MEDDELANDEPREVIEW: Kontrollerar om det senaste meddelandet skickades av dig själv, 
                // och sätter i så fall prefixet 'You: '
                $prefix = ($conv['sender_id'] == $myId) ? 'You: ' : '';
                if (!empty($conv['message_text'])) {
                    echo $prefix . htmlspecialchars($conv['message_text']);
                } else if (!empty($conv['image_path'])) {
                    // Om meddelandet inte innehåller text utan en bildfil visas texten 'Sent a photo'
                    echo $prefix . 'Sent a photo';
                }
                ?>
            </div>
        </div>
    </a>
<?php endforeach; ?>
