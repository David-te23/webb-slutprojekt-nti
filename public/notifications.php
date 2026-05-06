<?php
$pageTitle = 'Notifications';

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/message_time_formatter.php';
require_once __DIR__ . '/actions/get_notification_url.php';

// Hämta notiser
$stmt = $dbconn->prepare("
    SELECT 
        n.*, 
        u.username, 
        u.display_name, 
        u.profile_image,
        q.content AS quack_preview
    FROM notifications n
    JOIN users u ON n.source_user_id = u.id
    LEFT JOIN quacks q ON n.source_id = q.id
    WHERE n.user_id = ?
    ORDER BY n.created_at DESC
    LIMIT 20
");

$stmt->execute([$_SESSION['user_id']]);
$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Markera som lästa
if (!empty($notifications)) {
    $ids = array_column($notifications, 'id');
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $updateStmt = $dbconn->prepare("UPDATE notifications SET is_read = 1 WHERE id IN ($placeholders)");
    $updateStmt->execute($ids);
}
?>

<!-- Notifications-vy börjar här -->
<div class="notifications-container shadow-sm rounded mt-2">
    <div class="p-3 border-bottom bg-light d-flex justify-content-between align-items-center">
        <h4 class="mb-0 fw-bold">Notifications</h4>
        <span class="badge bg-secondary">Latest activity</span>
    </div>

    <div class="list-group list-group-flush">
        <?php if (empty($notifications)): ?>
            <div class="list-group-item text-center py-5 text-muted">
                <p class="mb-0">No notifications yet. Quack on!</p>
            </div>
        <?php else: ?>
            <?php foreach ($notifications as $notification): 
                $targetUrl = getNotificationUrl(
                    $notification['type'], 
                    $notification['source_id'], 
                    $notification['source_user_id']
                );
                $isUnread = !$notification['is_read'];
            ?>
                <a href="<?= $targetUrl ?>" 
                   class="list-group-item list-group-item-action d-flex align-items-start py-3 <?= $isUnread ? 'bg-unread' : '' ?>">
                    
                    <div class="me-3">
                        <img src="<?= getPfpPath($notification['profile_image']) ?>" 
                             alt="Profile" 
                             class="rounded-circle shadow-sm notification-img">
                    </div>

                    <div class="flex-grow-1 text-truncate">
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="text-truncate">
                                <span class="fw-bold"><?= htmlspecialchars($notification['display_name']) ?></span>
                                <span class="text-muted small">@<?= htmlspecialchars($notification['username']) ?></span>
                                <span class="text-muted small mx-1">•</span>
                                <small class="text-muted"><?= formatMessageTime($notification['created_at']) ?></small>
                            </div>
                        </div>

                        <div class="notification-body mt-1">
                            <?php switch($notification['type']):
                                case 'message': ?>
                                    <span class="text-primary fw-medium">sent you a private message!</span>
                                <?php break; ?>

                                <?php case 'like': ?>
                                    <span>liked your quack:</span>
                                    <div class="mt-2 p-2 rounded-end small text-muted text-truncate notification-preview">
                                        "<?= htmlspecialchars($notification['quack_preview'] ?? 'Original quack not found') ?>"
                                    </div>
                                <?php break; ?>

                                <?php case 'comment': ?>
                                    <span>commented on your quack:</span>
                                    <div class="mt-2 p-2 rounded-end small text-muted text-truncate notification-preview">
                                        "<?= htmlspecialchars($notification['quack_preview'] ?? 'Original quack not found') ?>"
                                    </div>
                                <?php break; ?>

                                <?php case 'quack': ?>
                                    <span>just posted a new quack:</span>
                                    <div class="mt-2 p-2 rounded-end small text-muted text-truncate notification-preview">
                                        "<?= htmlspecialchars($notification['quack_preview'] ?? 'Quack content') ?>"
                                    </div>
                                <?php break; ?>

                                <?php case 'follow': ?>
                                    <span>started following you!</span>
                                <?php break; ?>

                                <?php case 'requack': ?>
                                    <span>requacked your quack:</span>
                                    <div class="mt-2 p-2 rounded-end small text-muted text-truncate notification-preview">
                                        "<?= htmlspecialchars($notification['quack_preview'] ?? 'Original quack not found') ?>"
                                    </div>
                                <?php break; ?>

                                <?php default: ?>
                                    <span>interacted with you.</span>
                            <?php endswitch; ?>
                        </div>
                    </div>
                </a>
            <?php endforeach; ?>
            
            <div class="p-3 text-center bg-light border-top">
                <small class="text-muted">Showing the 20 latest notifications</small>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php 

require_once __DIR__ . '/../includes/footer.php'; 
?>
