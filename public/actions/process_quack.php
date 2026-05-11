<?php
session_start();
require_once __DIR__ . '/../../database/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['user_id'])) {
    $content = trim($_POST['quack_content']);
    $userId = $_SESSION['user_id'];

    if (!empty($content) || !empty($_FILES['quack_images']['name'][0])) {
        // Spara själva quacket
        $stmt = $dbconn->prepare("INSERT INTO quacks (user_id, content, created_at) VALUES (?, ?, NOW())");
        $stmt->execute([$userId, $content]);
        $quackId = $dbconn->lastInsertId();

        // --- HASHTAG-LOGIK START ---
        // Hitta alla ord som börjar med # (stödjer även ÅÄÖ med /u)
        preg_match_all('/#(\w+)/u', $content, $matches);
        $hashtags = array_unique($matches[1]); // $matches[1] innehåller orden utan själva #

        foreach ($hashtags as $tag) {
            $tag = mb_strtolower($tag); // Gör till små bokstäver för sökbarhet

            // Spara taggen om den inte finns
            $stmtTag = $dbconn->prepare("INSERT IGNORE INTO hashtags (tag_name) VALUES (?)");
            $stmtTag->execute([$tag]);

            // Hämta ID för taggen
            $stmtGetTag = $dbconn->prepare("SELECT id FROM hashtags WHERE tag_name = ?");
            $stmtGetTag->execute([$tag]);
            $tagId = $stmtGetTag->fetchColumn();

            // Koppla taggen till quacket
            if ($tagId) {
                $stmtLink = $dbconn->prepare("INSERT IGNORE INTO quack_hashtags (quack_id, hashtag_id) VALUES (?, ?)");
                $stmtLink->execute([$quackId, $tagId]);
            }
        }
        // --- HASHTAG-LOGIK SLUT ---

        // --- NOTIS-LOGIK START ---
        $stmtFollowers = $dbconn->prepare("SELECT follower_id FROM follows WHERE following_id = ?");
        $stmtFollowers->execute([$userId]);
        $followers = $stmtFollowers->fetchAll(PDO::FETCH_COLUMN);

        if (!empty($followers)) {
            $notifStmt = $dbconn->prepare("INSERT INTO notifications (user_id, source_user_id, type, source_id, is_read) VALUES (?, ?, 'quack', ?, 0)");
            foreach ($followers as $followerId) {
                $notifStmt->execute([$followerId, $userId, $quackId]);
            }
        }
        // --- NOTIS-LOGIK SLUT ---

        // --- BILD/VIDEO-LOGIK ---
        if (!empty($_FILES['quack_images']['name'][0])) {
            $uploadDir = __DIR__ . '/../../uploads/quacks/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

            $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'video/mp4', 'video/webm'];
            $maxFileSize = 10 * 1024 * 1024; 

            foreach ($_FILES['quack_images']['tmp_name'] as $key => $tmpName) {
                if ($_FILES['quack_images']['error'][$key] === UPLOAD_ERR_OK) {
                    if ($_FILES['quack_images']['size'][$key] > $maxFileSize) continue;

                    $finfo = finfo_open(FILEINFO_MIME_TYPE);
                    $mimeType = finfo_file($finfo, $tmpName);
                    finfo_close($finfo);

                    if (!in_array($mimeType, $allowedMimeTypes)) continue;

                    $extension = pathinfo($_FILES['quack_images']['name'][$key], PATHINFO_EXTENSION);
                    $fileName = bin2hex(random_bytes(16)) . '.' . $extension;
                    $targetPath = $uploadDir . $fileName;

                    if (move_uploaded_file($tmpName, $targetPath)) {
                        $dbPath = 'uploads/quacks/' . $fileName;
                        
                        $imgStmt = $dbconn->prepare("INSERT INTO quack_images (quack_id, image_path, file_type) VALUES (?, ?, ?)");
                        $imgStmt->execute([$quackId, $dbPath, $mimeType]);
                    }
                }
            }
        }
    }
}

header("Location: ../index.php");
exit;