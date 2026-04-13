<?php
session_start();
require_once __DIR__ . '/../database/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['user_id'])) {
    $content = trim($_POST['quack_content']);
    $userId = $_SESSION['user_id'];

    if (!empty($content) || !empty($_FILES['quack_images']['name'][0])) {
        // insert quack
        $stmt = $dbconn->prepare("INSERT INTO quacks (user_id, content, created_at) VALUES (?, ?, NOW())");
        $stmt->execute([$userId, $content]);
        $quackId = $dbconn->lastInsertId();

        // handle multi img upload
        if (!empty($_FILES['quack_images']['name'][0])) {
            $uploadDir = __DIR__ . '/../uploads/quacks/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

            foreach ($_FILES['quack_images']['tmp_name'] as $key => $tmpName) {
                if ($_FILES['quack_images']['error'][$key] === UPLOAD_ERR_OK) {
                    // unique filename
                    $fileName = time() . '_' . bin2hex(random_bytes(4)) . '_' . basename($_FILES['quack_images']['name'][$key]);
                    $targetPath = $uploadDir . $fileName;

                    if (move_uploaded_file($tmpName, $targetPath)) {
                        $dbPath = 'uploads/quacks/' . $fileName;
                        $imgStmt = $dbconn->prepare("INSERT INTO quack_images (quack_id, image_path) VALUES (?, ?)");
                        $imgStmt->execute([$quackId, $dbPath]);
                    }
                }
            }
        }
    }
}

header("Location: ../public/index.php");
exit;