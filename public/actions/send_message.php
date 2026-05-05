<?php
session_start();
require_once __DIR__ . '/../../database/db.php';

// Aktivera felrapportering för att se exakt vad som går fel (ta bort i produktion)
error_reporting(E_ALL);
ini_set('display_errors', 1);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['user_id'])) {
    $senderId = $_SESSION['user_id'];
    $receiverId = (int)$_POST['receiver_id'];
    $messageText = trim($_POST['message_text']);
    $imageName = null;

    if (isset($_FILES['chat_image']) && $_FILES['chat_image']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['chat_image'];
        
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($file['tmp_name']);
        $allowedTypes = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif', 'image/webp' => 'webp'];

        if (array_key_exists($mimeType, $allowedTypes)) {
            // Skapa den absoluta sökvägen ordentligt
            $uploadDir = realpath(__DIR__ . '/../../uploads/messages/') . '/';
            
            // Om realpath misslyckas existerar mappen inte
            if (!$uploadDir || !is_dir($uploadDir)) {
                // Skapa mappen om den saknas
                $uploadDir = __DIR__ . '/../../uploads/messages/';
                mkdir($uploadDir, 0777, true);
            }

            $extension = $allowedTypes[$mimeType];
            $imageName = bin2hex(random_bytes(10)) . '.' . $extension;
            $targetPath = $uploadDir . $imageName;

            // move_uploaded_file kräver tmp_name som första argument
            if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
                $imageName = null; // Sätt till null om flytten misslyckades
            }
        }
    }

    if (!empty($messageText) || $imageName !== null) {
        $stmt = $dbconn->prepare("INSERT INTO messages (sender_id, receiver_id, message_text, image_path, is_read, created_at) VALUES (?, ?, ?, ?, 0, NOW())");
        $stmt->execute([$senderId, $receiverId, $messageText, $imageName]);
        $messageId = $dbconn->lastInsertId();

        $notif = $dbconn->prepare("INSERT INTO notifications (user_id, source_user_id, type, source_id, is_read, created_at) VALUES (?, ?, 'message', ?, 0, NOW())");
        $notif->execute([$receiverId, $senderId, $messageId]);

        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            header('Content-Type: application/json');
            echo json_encode(['success' => true]);
            exit;
        }
    }
}

header("Location: ../messages.php?user_id=" . $receiverId);
exit;
