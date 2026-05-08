<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../database/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['user_id'])) {
    $userId = $_SESSION['user_id'];
    $displayName = trim($_POST['display_name']);
    $bio = trim($_POST['bio']);
    $status = ""; // För att hålla koll på om något gick fel

    // Hantera profilbild
    if (!empty($_FILES['profile_image']['name'])) {
        $uploadDir = __DIR__ . '/../../uploads/pfp/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

        $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $maxFileSize = 5 * 1024 * 1024; 

        if ($_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
            $tmpName = $_FILES['profile_image']['tmp_name'];
            
            if ($_FILES['profile_image']['size'] <= $maxFileSize) {
                // Säker MIME-kontroll
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mimeType = finfo_file($finfo, $tmpName);
                finfo_close($finfo);

                if (in_array($mimeType, $allowedMimeTypes)) {
                    $extension = pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION);
                    $fileName = bin2hex(random_bytes(16)) . '.' . $extension;
                    $targetPath = $uploadDir . $fileName;

                    if (move_uploaded_file($tmpName, $targetPath)) {
                        $stmt = $dbconn->prepare("UPDATE users SET profile_image = ? WHERE id = ?");
                        $stmt->execute([$fileName, $userId]);
                        $status = "success";
                    }
                } else {
                    $status = "invalid_type";
                }
            } else {
                $status = "too_large";
            }
        } else {
            $status = "upload_error";
        }
    }

    // Uppdatera textinformationen (görs alltid)
    $stmt = $dbconn->prepare("UPDATE users SET display_name = ?, bio = ? WHERE id = ?");
    $stmt->execute([$displayName, $bio, $userId]);

    // Om status är tom (ingen bild laddades upp) men texten uppdaterades
    if ($status === "") $status = "success";

    header("Location: ../profile.php?id=" . $userId . "&status=" . $status);
    exit;
}
