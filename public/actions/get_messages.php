<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../../database/db.php';

$myId = $_SESSION['user_id'];
$contactId = (int)$_GET['user_id'];

$mStmt = $dbconn->prepare("SELECT * FROM messages WHERE (sender_id = :m AND receiver_id = :c) OR (sender_id = :c AND receiver_id = :m) ORDER BY created_at ASC");
$mStmt->execute(['m' => $myId, 'c' => $contactId]);
$messages = $mStmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($messages as $msg): 
    $sentByMe = ($msg['sender_id'] == $myId); ?>
    <div class="d-flex mb-3 <?= $sentByMe ? 'justify-content-end' : 'justify-content-start' ?>">
        <div class="message-bubble <?= $sentByMe ? 'sent' : 'received' ?>">
            <?php if ($msg['image_path']): ?>
                <img src="../uploads/messages/<?= htmlspecialchars($msg['image_path']) ?>" class="chat-img-msg img-fluid rounded mb-1">
            <?php endif; ?>
            <?php if ($msg['message_text']): ?>
                <div class="message-text"><?= htmlspecialchars($msg['message_text']) ?></div>
            <?php endif; ?>
            <span class="message-time"><?= date('H:i', strtotime($msg['created_at'])) ?></span>
        </div>
    </div>
<?php endforeach; ?>
