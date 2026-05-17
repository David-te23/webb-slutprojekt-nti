<?php
session_start();
require_once __DIR__ . '/../../database/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['user_id'])) {
    // quack_id är originalets ID
    $quackId = (int)$_POST['quack_id'];
    $userId = $_SESSION['user_id'];
    $content = trim($_POST['comment']);

    if (!empty($content)) {
        try {
            // Spara själva kommentaren på rätt inlägg (originalet)
            $stmt = $dbconn->prepare("INSERT INTO comments (quack_id, user_id, content) VALUES (?, ?, ?)");
            $stmt->execute([$quackId, $userId, $content]);

            // --- NOTIS-LOGIK ---
            // Hämta ägaren av inlägget för att skicka en notis
            $stmtOwner = $dbconn->prepare("SELECT user_id FROM quacks WHERE id = ?");
            $stmtOwner->execute([$quackId]);
            $quackOwnerId = $stmtOwner->fetchColumn();

            // Skapa notis endast om man inte kommenterar sitt eget inlägg
            if ($quackOwnerId && $quackOwnerId != $userId) {
                $stmtNotif = $dbconn->prepare("
                    INSERT INTO notifications (user_id, source_user_id, type, source_id, is_read) 
                    VALUES (?, ?, 'comment', ?, 0)
                ");
                // source_id pekar på original-inlägget så att länken i notisen går rätt
                $stmtNotif->execute([$quackOwnerId, $userId, $quackId]);
            }
        } catch (PDOException $e) {
            // TYST FELHANTERING: Om ett databasfel inträffar sväljer vi felet här.
            // Detta förhindrar att användaren möts av en vit skärm med rå kod, 
            // och låter istället skriptet löpa vidare till omdirigeringen (redirect) nedan.
        }
        }
    }

// Om anropet kom via AJAX (fetch) kommer webbläsaren ignorera denna redirect,
// men behåller den som säkerhetsåtgärd för vanliga formulärposter.
header("Location: ../quack.php?id=" . $quackId);
exit;
