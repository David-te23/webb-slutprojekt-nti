<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../../database/db.php';
require_once __DIR__ . '/../../includes/message_time_formatter.php';

$myId = $_SESSION['user_id'];
$contactId = (int)$_GET['user_id'];

// Hämtar hela konversationen mellan dig (:m) och din kontakt (:c).
// OR-villkoret är livsviktigt eftersom det hämtar meddelanden i båda riktningarna: 
// de du har skickat till kontakten, samt de kontakten har skickat till dig.
$mStmt = $dbconn->prepare("SELECT * FROM messages WHERE (sender_id = :m AND receiver_id = :c) OR (sender_id = :c AND receiver_id = :m) ORDER BY created_at ASC");
$mStmt->execute(['m' => $myId, 'c' => $contactId]);
$messages = $mStmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($messages as $msg): 
    $sentByMe = ($msg['sender_id'] == $myId); ?>
    <div class="d-flex mb-3 <?= $sentByMe ? 'justify-content-end' : 'justify-content-start' ?>">
        <div class="message-bubble <?= $sentByMe ? 'sent' : 'received' ?>">
            
            <?php if ($msg['image_path']): 
                $filePath = $msg['image_path'];
                // Hämtar filändelsen (t.ex. mp4 eller png) för att avgöra mediatyp
                $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
                $videoExtensions = ['mp4', 'webm', 'ogg', 'mov'];
                
                if (in_array($ext, $videoExtensions)): ?>
                    <!-- Visa som VIDEO -->
                    <video controls class="chat-img-msg img-fluid rounded mb-1" style="max-width: 100%; max-height: 300px;">
                        <source src="../uploads/messages/<?= htmlspecialchars($filePath) ?>" type="video/<?= $ext ?>">
                        Your browser does not support the video tag.
                    </video>
                <?php else: ?>
                    <!-- Visa som BILD -->
                    <img src="../uploads/messages/<?= htmlspecialchars($filePath) ?>" class="chat-img-msg img-fluid rounded mb-1" alt="Chat image">
                <?php endif; ?>
            <?php endif; ?>

            <?php if ($msg['message_text']): ?>
                <div class="message-text"><?= htmlspecialchars($msg['message_text']) ?></div>
            <?php endif; ?>
            <span class="message-time"><?= formatMessageTime($msg['created_at']) ?></span>
        </div>
    </div>
<?php endforeach; ?>
