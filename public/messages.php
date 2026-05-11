<?php
$pageTitle = "Messages";
require_once __DIR__ . '/../includes/header.php';

$contactId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : null;
$contactUser = null;

if ($contactId) {
    // hämta namnet på personen för headern
    $uStmt = $dbconn->prepare("SELECT * FROM users WHERE id = ?");
    $uStmt->execute([$contactId]);
    $contactUser = $uStmt->fetch(PDO::FETCH_ASSOC);

    if ($contactUser) {
        // Markera som läst direkt när man landar på sidan
        $upd = $dbconn->prepare("UPDATE messages SET is_read = 1 WHERE sender_id = ? AND receiver_id = ?");
        $upd->execute([$contactId, $currentUser['id']]);
    }
}
?>

<div class="messages-layout border rounded shadow-sm bg-white overflow-hidden">
    <!-- VÄNSTER: LISTA (Döljs på mobil om en chatt är aktiv) -->
    <div class="conversation-sidebar border-end flex-column <?= $contactId ? 'd-none d-md-flex' : 'd-flex' ?>">
        <div class="p-3 border-bottom bg-white text-center">
            <button type="button" class="btn btn-success btn-sm w-100 fw-bold" data-bs-toggle="modal" data-bs-target="#newChatModal">
                Start a new conversation!
            </button>
        </div>
        <div class="flex-grow-1 overflow-auto" id="conversationList">
            <?php require __DIR__ . '/actions/get_conversations.php'?>
        </div>
    </div>

    <!-- HÖGER: CHATT (Döljs på mobil om ingen chatt är vald) -->
    <div class="chat-main flex-column <?= !$contactId ? 'd-none d-md-flex' : 'd-flex' ?>">
        <?php if ($contactUser): ?>
            <!-- Chatt-header med Tillbaka-pil för mobil -->
            <div class="p-3 border-bottom bg-white d-flex align-items-center">
                <a href="messages.php" class="d-md-none me-3 text-dark text-decoration-none">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
                </a>
                
                <a href="profile.php?id=<?= $contactUser['id'] ?>" class="text-decoration-none">
                    <img src="<?= getPfpPath($contactUser['profile_image']) ?>" class="rounded-circle me-2 msg_pfp_sm" alt="Profile image">
                </a>
                <div class="overflow-hidden">
                    <div class="fw-bold lh-1 text-truncate"><?= htmlspecialchars($contactUser['display_name']) ?></div>
                    <small class="text-muted fw-normal">@<?= htmlspecialchars($contactUser['username']) ?></small>
                </div>
            </div>

            <div id="chatHistory" class="flex-grow-1 overflow-auto p-3 chat-bg">
                <?php require __DIR__ . '/actions/get_messages.php'; ?>
            </div>

            <div class="chat-input-container position-relative p-3 border-top bg-white"> 
                <form action="actions/send_message.php" method="POST" enctype="multipart/form-data" class="d-flex flex-column gap-2">
                    <input type="hidden" name="receiver_id" value="<?= $contactId ?>">
                    <div id="chat-img-preview" class="d-flex flex-wrap gap-2"></div>
                    
                    <div class="d-flex align-items-center gap-2">
                        <button type="button" id="chat-emoji-trigger" class="btn btn-link p-0 text-success">
                            <svg class="new-quack-icon" width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M8.9126 15.9336C10.1709 16.249 11.5985 16.2492 13.0351 15.8642C14.4717 15.4793 15.7079 14.7653 16.64 13.863" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"></path><ellipse cx="14.5094" cy="9.77405" rx="1" ry="1.5" transform="rotate(-15 14.5094 9.77405)" fill="currentColor"></ellipse><ellipse cx="8.71402" cy="11.3278" rx="1" ry="1.5" transform="rotate(-15 8.71402 11.3278)" fill="currentColor"></ellipse><path d="M20.7964 9.643C21.9075 13.7897 22.4631 15.863 21.5201 17.4964C20.577 19.1298 18.5037 19.6853 14.357 20.7964C10.2103 21.9075 8.13698 22.4631 6.50359 21.5201C4.87021 20.577 4.31466 18.5037 3.20356 14.357C2.09246 10.2103 1.53691 8.13698 2.47995 6.50359C3.42298 4.87021 5.49632 4.31466 9.643 3.20356C13.7897 2.09246 15.863 1.53691 17.4964 2.47995C18.5048 3.06212 19.1023 4.07505 19.6734 5.74061" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"></path></svg>
                        </button>
                        
                        <label for="chat-image-input" class="btn btn-link p-0 text-success mb-0 cursor-pointer">
                            <svg class="new-quack-icon" width="24" height="24" viewBox="0 0 16 16" fill="currentColor" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M9 0H2V16H14V5L9 0ZM7 6V8H5V10H7V12H9V10H11V8H9V6H7Z"></path></svg>
                            <input type="file" id="chat-image-input" name="chat_image" accept="image/*,video/*" class="d-none">
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
            <!-- Tom vy på desktop -->
            <div class="h-100 d-none d-md-flex align-items-center justify-content-center text-muted bg-light">
                <div class="text-center">
                    <div class="fs-1 mb-2">✉️</div>
                    <p class="fw-bold">Välj en vän att quacka med!</p>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- MODAL: SÖK ANVÄNDARE (Oförändrad) -->
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
                            <img src="<?= getPfpPath($user['profile_image']) ?>" class="rounded-circle me-3 msg_pfp" alt="Profile image">
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

<!-- Modal för att visa bilder i stort format -->
<div class="modal fade" id="imageModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content bg-transparent border-0">
            <div class="modal-body p-0 text-center position-relative">
                <img src="" id="modalImage" class="img-fluid rounded shadow-lg modal-img" alt="Fullscreen image">
                <button type="button" class="btn-close btn-close-white position-absolute top-0 end-0 m-3" data-bs-dismiss="modal"></button>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
