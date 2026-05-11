<?php
session_start();
require_once __DIR__ . '/../../database/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['user_id'])) {
    $senderId = $_SESSION['user_id'];
    $receiverId = (int)$_POST['receiver_id'];
    $messageText = trim($_POST['message_text']);
    $imageName = null;

    if (isset($_FILES['chat_image']) && $_FILES['chat_image']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['chat_image'];
        
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($file['tmp_name']);

        $allowedTypes = [
            'image/jpeg' => 'jpg', 
            'image/png' => 'png', 
            'image/gif' => 'gif', 
            'image/webp' => 'webp',
            'video/mp4' => 'mp4', 
            'video/webm' => 'webm',
            'video/quicktime' => 'mov'
        ];

        if (array_key_exists($mimeType, $allowedTypes)) {
            $uploadDir = __DIR__ . '/../../uploads/messages/';
            
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            $extension = $allowedTypes[$mimeType];
            $imageName = bin2hex(random_bytes(10)) . '.' . $extension;
            $targetPath = $uploadDir . $imageName;

            if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
                $imageName = null; 
            }
        }
    }

    if (!empty($messageText) || $imageName !== null) {
        // INSERT och NOTIFIKATIONER
        $stmt = $dbconn->prepare("INSERT INTO messages (sender_id, receiver_id, message_text, image_path, is_read, created_at) VALUES (?, ?, ?, ?, 0, NOW())");
        $stmt->execute([$senderId, $receiverId, $messageText, $imageName]);
        
        // AJAX respons logik
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            header('Content-Type: application/json');
            echo json_encode(['success' => true]);
            exit;
        }
    }
}
header("Location: ../messages.php?user_id=" . $receiverId);
exit;
