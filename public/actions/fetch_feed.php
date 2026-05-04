<?php
session_start();
require_once __DIR__ . '/../../database/db.php';
require_once __DIR__ . '/../../includes/quack_time_formatter.php';

$current_user_id = $_SESSION['user_id'] ?? 0;

// Hämta datan
require_once __DIR__ . '../../../includes/quack_feed_logic.php';

function getPfpPath($fileName) {
    if (!$fileName || $fileName === 'default_pfp.jpg') {
        return "../public/images/default_pfp.jpg";
    }
    return "../uploads/pfp/" . $fileName;
}

// Loopa ut HTML
require_once __DIR__ . '../../../includes/quack_loop.php';