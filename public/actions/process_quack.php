<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../../database/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['user_id'])) {
    $userId = $_SESSION['user_id'];
    $displayName = trim($_POST['display_name']);
    $bio = trim($_POST['bio']);
    
    // 1. Hantera profilbild (återanvänder din logik från quacks)
    if (!empty($_FILES['profile_image']['name'])) {
        $uploadDir = __DIR__ . '/../../uploads/pfp/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

        $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $maxFileSize = 5 * 1024 * 1024; // 5MB räcker för profilbilder

        if ($_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
            $tmpName = $_FILES['profile_image']['tmp_name'];
            
            if ($_FILES['profile_image']['size'] <= $maxFileSize) {
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mimeType = finfo_file($finfo, $tmpName);
                finfo_close($finfo);

                if (in_array($mimeType, $allowedMimeTypes)) {
                    $extension = pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION);
                    // Använder din metod med random_bytes för säkra filnamn
                    $fileName = bin2hex(random_bytes(16)) . '.' . $extension;
                    $targetPath = $uploadDir . $fileName;

                    if (move_uploaded_file($tmpName, $targetPath)) {
                        // Vi sparar bara filnamnet i users-tabellen enligt din header-funktion
                        $stmt = $dbconn->prepare("UPDATE users SET profile_image = ? WHERE id = ?");
                        $stmt->execute([$fileName, $userId]);
                    }
                }
            }
        }
    }

    // 2. Uppdatera textinformationen
    $stmt = $dbconn->prepare("UPDATE users SET display_name = ?, bio = ? WHERE id = ?");
    $stmt->execute([$displayName, $bio, $userId]);

    header("Location: ../public/profile.php?id=" . $userId);
    exit;
}
