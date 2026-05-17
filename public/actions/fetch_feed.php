<?php
session_start();
require_once __DIR__ . '/../../database/db.php';
require_once __DIR__ . '/../../includes/quack_time_formatter.php';

// Sätter ID för den inloggade användaren så att quack_feed_logic.php kan räkna ut likes/requacks korrekt
$current_user_id = $_SESSION['user_id'] ?? 0;

// DATAHÄMTNING: Inkluderar den centrala SQL-frågan som hämtar inlägg utifrån valt filter
require_once __DIR__ . '../../../includes/quack_feed_logic.php';

// LOKAL FALLBACK: Denna vy laddas separat via ett isolerat JavaScript-anrop (AJAX).
// Eftersom den inte passerar index.php eller header.php måste vi deklarera getPfpPath() lokalt 
// här så att inte quack_item.php kraschar på en "Undefined function"-bugg.
function getPfpPath($fileName) {
    if (!$fileName || $fileName === 'default_pfp.jpg') {
        return "../public/images/default_pfp.jpg";
    }
    return "../uploads/pfp/" . $fileName;
}

// Loopa ut HTML
require_once __DIR__ . '../../../includes/quack_loop.php';