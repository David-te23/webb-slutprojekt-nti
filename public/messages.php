<?php
$pageTitle = "Messages";
require_once __DIR__ . '/../includes/header.php';

$contactId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : null;

// Hämta konversationer för vänsterlistan
$convStmt = $dbconn->prepare("
    SELECT m.*, u.id as contact_id, u.username, u.display_name, u.profile_image
    FROM messages m
    JOIN users u ON (u.id = m.sender_id OR u.id = m.receiver_id) AND u.id != :myId
    WHERE m.id IN (
        SELECT MAX(id) FROM messages 
        WHERE sender_id = :myId OR receiver_id = :myId 
        GROUP BY IF(sender_id = :myId, receiver_id, sender_id)
    )
    ORDER BY m.created_at DESC
");
$convStmt->execute(['myId' => $currentUser['id']]);
$conversations = $convStmt->fetchAll(PDO::FETCH_ASSOC);

$messages = [];
$contactUser = null;

if ($contactId) {
    $uStmt = $dbconn->prepare("SELECT * FROM users WHERE id = ?");
    $uStmt->execute([$contactId]);
    $contactUser = $uStmt->fetch(PDO::FETCH_ASSOC);

    if ($contactUser) {
        $upd = $dbconn->prepare("UPDATE messages SET is_read = 1 WHERE sender_id = ? AND receiver_id = ?");
        $upd->execute([$contactId, $currentUser['id']]);

        $mStmt = $dbconn->prepare("
            SELECT * FROM messages 
            WHERE (sender_id = :myId AND receiver_id = :cId) 
               OR (sender_id = :cId AND receiver_id = :myId)
            ORDER BY created_at ASC
        ");
        $mStmt->execute(['myId' => $currentUser['id'], 'cId' => $contactId]);
        $messages = $mStmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>

<div class="messages-layout border rounded shadow-sm bg-white overflow-hidden">
    <!-- VÄNSTER: LISTA -->
    <div class="conversation-sidebar border-end">
        <div class="p-3 border-bottom bg-white">
            <button type="button" class="btn btn-success btn-sm w-100 fw-bold" data-bs-toggle="modal" data-bs-target="#newChatModal">
                Start a new conversation!
            </button>
        </div>
        <div class="flex-grow-1 overflow-auto">
            <?php foreach ($conversations as $conv): ?>
                <a href="messages.php?user_id=<?= $conv['contact_id'] ?>" 
                   class="d-flex align-items-center p-3 border-bottom text-decoration-none text-dark <?= ($contactId == $conv['contact_id']) ? 'bg-white border-start border-success border-4' : '' ?>">
                    <img src="<?= getPfpPath($conv['profile_image']) ?>" class="rounded-circle me-2" width="45" height="45" style="object-fit: cover;">
                    <div class="overflow-hidden text-truncate">
                        <div class="fw-bold small"><?= htmlspecialchars($conv['display_name']) ?></div>
                        <div class="small text-muted"><?= htmlspecialchars($conv['message_text']) ?></div>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- HÖGER: CHATT -->
    <div class="chat-main">
        <?php if ($contactUser): ?>
            <div class="p-3 border-bottom bg-white d-flex align-items-center">
                <img src="<?= getPfpPath($contactUser['profile_image']) ?>" class="rounded-circle me-2" width="35" height="35" style="object-fit: cover;">
                <span class="fw-bold"><?= htmlspecialchars($contactUser['display_name']) ?></span>
            </div>

            <div id="chatHistory">
                <?php require __DIR__ . '/actions/get_messages.php'; ?>
            </div>

            <div class="chat-input-container position-relative"> 
                <form action="actions/send_message.php" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="receiver_id" value="<?= $contactId ?>">
                    <div id="chat-img-preview"></div>
                    
                    <div class="d-flex align-items-center gap-2">
                        <button type="button" id="chat-emoji-trigger" class="btn btn-link p-0 text-success">
                        <svg class="new-quack-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <path d="M8.9126 15.9336C10.1709 16.249 11.5985 16.2492 13.0351 15.8642C14.4717 15.4793 15.7079 14.7653 16.64 13.863" stroke="#1C274C" stroke-width="1.5" stroke-linecap="round"></path> <ellipse cx="14.5094" cy="9.77405" rx="1" ry="1.5" transform="rotate(-15 14.5094 9.77405)" fill="#1C274C"></ellipse> <ellipse cx="8.71402" cy="11.3278" rx="1" ry="1.5" transform="rotate(-15 8.71402 11.3278)" fill="#1C274C"></ellipse> <path d="M20.7964 9.643C21.9075 13.7897 22.4631 15.863 21.5201 17.4964C20.577 19.1298 18.5037 19.6853 14.357 20.7964C10.2103 21.9075 8.13698 22.4631 6.50359 21.5201C4.87021 20.577 4.31466 18.5037 3.20356 14.357C2.09246 10.2103 1.53691 8.13698 2.47995 6.50359C3.42298 4.87021 5.49632 4.31466 9.643 3.20356C13.7897 2.09246 15.863 1.53691 17.4964 2.47995C18.5048 3.06212 19.1023 4.07505 19.6734 5.74061" stroke="#1C274C" stroke-width="1.5" stroke-linecap="round"></path> <path d="M13 16.0004L13.478 16.9742C13.8393 17.7104 14.7249 18.0198 15.4661 17.6689C16.2223 17.311 16.5394 16.4035 16.1708 15.6524L15.7115 14.7168" stroke="#1C274C" stroke-width="1.5"></path> </g></svg>
                        </button>
                        
                        <label for="chat-image-input" class="btn btn-link p-0 text-success mb-0 cursor-pointer">
                            <svg class="new-quack-icon" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <path fill-rule="evenodd" clip-rule="evenodd" d="M9 0H2V16H14V5L9 0ZM7 6V8H5V10H7V12H9V10H11V8H9V6H7Z" fill="#000000"></path> </g></svg>
                            <input type="file" id="chat-image-input" name="chat_image" accept="image/*" class="d-none">
                        </label>
                        
                        <input type="text" id="chat-input-field" name="message_text" class="form-control rounded-pill" placeholder="Message" autocomplete="off">
                        <button type="submit" class="btn btn-success rounded-pill px-4">Send</button>
                    </div>

                    
                    <div id="chat-picker-container" class="emoji-picker-container msg-emoji-picker">
                        <emoji-picker id="chat-emoji-picker"></emoji-picker>
                    </div>
                </form>
            </div>
        <?php else: ?>
            <div class="h-100 d-flex align-items-center justify-content-center text-muted bg-light">Välj en vän att quacka med!</div>
        <?php endif; ?>
    </div>
</div>

<!-- MODAL: SÖK ANVÄNDARE -->
<div class="modal fade" id="newChatModal" tabindex="-1" aria-labelledby="newChatModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold" id="newChatModalLabel">New Message</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <input type="text" id="userSearchInput" class="form-control rounded-pill" placeholder="Search people...">
                </div>
                <div class="list-group list-group-flush" id="userList">
                    <?php
                    $allUsersStmt = $dbconn->prepare("SELECT id, username, display_name, profile_image FROM users WHERE id != ? ORDER BY display_name ASC");
                    $allUsersStmt->execute([$currentUser['id']]);
                    $allUsers = $allUsersStmt->fetchAll(PDO::FETCH_ASSOC);

                    foreach ($allUsers as $user): ?>
                        <a href="messages.php?user_id=<?= $user['id'] ?>" class="list-group-item list-group-item-action d-flex align-items-center p-3 border-0 rounded user-search-item">
                            <img src="<?= getPfpPath($user['profile_image']) ?>" class="rounded-circle me-3" width="40" height="40" style="object-fit: cover;">
                            <div>
                                <div class="fw-bold small"><?= htmlspecialchars($user['display_name']) ?></div>
                                <div class="text-muted small">@<?= htmlspecialchars($user['username']) ?></div>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
