<?php
session_start();
require_once __DIR__ . '/../../database/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['user_id'])) {
    $content = trim($_POST['quack_content']);
    $userId = $_SESSION['user_id'];

    if (!empty($content) || !empty($_FILES['quack_images']['name'][0])) {
        $stmt = $dbconn->prepare("INSERT INTO quacks (user_id, content, created_at) VALUES (?, ?, NOW())");
        $stmt->execute([$userId, $content]);
        $quackId = $dbconn->lastInsertId();

        if (!empty($_FILES['quack_images']['name'][0])) {
            $uploadDir = __DIR__ . '/../uploads/quacks/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

            // max filstorlek, 10MB
            $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'video/mp4', 'video/webm'];
            $maxFileSize = 10 * 1024 * 1024; // 10MB

            foreach ($_FILES['quack_images']['tmp_name'] as $key => $tmpName) {
                if ($_FILES['quack_images']['error'][$key] === UPLOAD_ERR_OK) {
                    
                    // kontrollera filens storlek
                    if ($_FILES['quack_images']['size'][$key] > $maxFileSize) continue;

                    // kontrollera filens typ med MIME type
                    $finfo = finfo_open(FILEINFO_MIME_TYPE);
                    $mimeType = finfo_file($finfo, $tmpName);
                    finfo_close($finfo);

                    if (!in_array($mimeType, $allowedMimeTypes)) continue;

                    // skapa ett säkert filnamn
                    $extension = pathinfo($_FILES['quack_images']['name'][$key], PATHINFO_EXTENSION);
                    $fileName = bin2hex(random_bytes(16)) . '.' . $extension;
                    $targetPath = $uploadDir . $fileName;

                    if (move_uploaded_file($tmpName, $targetPath)) {
                        $dbPath = 'uploads/quacks/' . $fileName;
                        // Spara MIME type i databasen så att man vet om det är bild/video
                        $imgStmt = $dbconn->prepare("INSERT INTO quack_images (quack_id, image_path, file_type) VALUES (?, ?, ?)");
                        $imgStmt->execute([$quackId, $dbPath, $mimeType]);
                    }
                }
            }
        }
    }
}
header("Location: ../public/index.php");
exit;
