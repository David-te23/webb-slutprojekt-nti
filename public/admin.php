<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }

// Säkerhetskoll
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header("Location: index.php"); exit;
}

$pageTitle = 'Admin Panel';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/quack_time_formatter.php'; 

// Kolla om vi ska titta på en specifik användares quacks
$viewUserId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : null;

if ($viewUserId) {
    // --- VISA EN ANVÄNDARES QUACKS ---
$userStmt = $dbconn->prepare("SELECT * FROM users WHERE id = ?");
$userStmt->execute([$viewUserId]);
$managedUser = $userStmt->fetch(PDO::FETCH_ASSOC);

// Hämta quacks med ALL data som quack_loop.php kräver
$quackStmt = $dbconn->prepare("
    SELECT 
        q.id, q.content, q.created_at, q.user_id, q.parent_id,
        u.username, u.display_name, u.profile_image,
        -- Originaldata för requacks
        orig_q.content AS orig_content,
        orig_q.created_at AS orig_created_at,
        orig_u.username AS orig_username,
        orig_u.display_name AS orig_display_name,
        orig_u.profile_image AS orig_profile_image,
        orig_u.id AS orig_user_id,
        -- Räknare (Peka på originalet om det är en requack)
        (SELECT COUNT(*) FROM likes WHERE quack_id = COALESCE(q.parent_id, q.id)) as like_count,
        (SELECT COUNT(*) FROM comments WHERE quack_id = COALESCE(q.parent_id, q.id)) as comment_count,
        (SELECT COUNT(*) FROM quacks WHERE parent_id = COALESCE(q.parent_id, q.id) AND content IS NULL) as requack_count,
        -- Check för status
        EXISTS(SELECT 1 FROM likes WHERE quack_id = COALESCE(q.parent_id, q.id) AND user_id = ?) as user_liked,
        EXISTS(SELECT 1 FROM quacks WHERE parent_id = COALESCE(q.parent_id, q.id) AND user_id = ? AND content IS NULL) as user_requacked
    FROM quacks q
    JOIN users u ON q.user_id = u.id
    LEFT JOIN quacks orig_q ON q.parent_id = orig_q.id
    LEFT JOIN users orig_u ON orig_q.user_id = orig_u.id
    WHERE q.user_id = ?
    ORDER BY q.created_at DESC
");

// skicka med adminens ID två gånger (för status-checken) och sen den valda användarens ID
$quackStmt->execute([$_SESSION['user_id'], $_SESSION['user_id'], $viewUserId]);
$quacks = $quackStmt->fetchAll(PDO::FETCH_ASSOC);


    // Förbered bilder för loopen
    foreach ($quacks as &$quack) {
        $imgStmt = $dbconn->prepare("SELECT image_path, file_type FROM quack_images WHERE quack_id = ?");
        $imgStmt->execute([$quack['id']]);
        $quack['images'] = $imgStmt->fetchAll(PDO::FETCH_ASSOC);
    }
    unset($quack);
} else {
    // --- LISTA ALLA ANVÄNDARE ---
    $stmt = $dbconn->query("SELECT id, username, display_name, email, profile_image FROM users WHERE is_admin = 0 ORDER BY display_name ASC");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<div class="admin-container shadow-sm">
    <div class="admin-header d-flex justify-content-between align-items-center">
        <h4 class="fw-bold m-0">
            <?= $viewUserId ? 'Managing Quacks: @' . htmlspecialchars($managedUser['username']) : 'Admin Panel' ?>
        </h4>
        <?php if ($viewUserId): ?>
            <a href="admin.php" class="btn btn-sm btn-light rounded-pill px-3 fw-bold">Back to Users</a>
        <?php else: ?>
            <span class="badge-admin">Admin Mode</span>
        <?php endif; ?>
    </div>

    <div class="admin-content">
        <?php if ($viewUserId): ?>
            <!-- VISA QUACKS FÖR VALD ANVÄNDARE -->
            <div class="p-4 bg-light-subtle">
                <?php if (empty($quacks)): ?>
                    <p class="text-center text-muted py-5">This user has no quacks yet.</p>
                <?php else: ?>
                    <div class="feed-container p-0">
                        <?php require __DIR__ . '/../includes/quack_loop.php'; ?>
                    </div>
                <?php endif; ?>
            </div>

        <?php else: ?>
            <!-- LISTA ALLA ANVÄNDARE -->
            <div class="px-4 py-3 border-bottom bg-light-subtle">
                <input type="text" id="adminUserSearch" class="form-control admin-search-input" placeholder="Search for users...">
            </div>

            <div id="adminUserList">
                <?php foreach ($users as $user): ?>
                    <div class="user-item-row p-3 p-md-4 border-bottom user-card-item" 
                        data-username="<?= strtolower(htmlspecialchars($user['username'])) ?>" 
                        data-displayname="<?= strtolower(htmlspecialchars($user['display_name'])) ?>">
                        
                        <div class="row align-items-center">
                            <!-- Vänster sida: Profilbild, Namn och Information -->
                            <div class="col-12 col-md-7 col-lg-8 mb-3 mb-md-0">
                                <a href="admin.php?user_id=<?= $user['id'] ?>" class="d-flex gap-3 align-items-center text-decoration-none">
                                    <img src="<?= getPfpPath($user['profile_image']) ?>" class="admin-avatar flex-shrink-0" alt="Profile image">
                                    <div class="text-truncate">
                                        <!-- Denna container håller både namngruppen och mailen på samma rad -->
                                        <div class="d-flex flex-wrap align-items-center gap-3">
                                            
                                            <!-- Grupp för Namn och @Användarnamn (staplade vertikalt) -->
                                            <div class="d-flex flex-column">
                                                <span class="fw-bold text-dark text-truncate" style="line-height: 1.2;">
                                                    <?= htmlspecialchars($user['display_name']) ?>
                                                </span>
                                                <span class="text-muted small">@<?= htmlspecialchars($user['username']) ?></span>
                                            </div>

                                            <!-- Mailen i en rundad ruta, centrerad vertikalt bredvid namnen -->
                                            <span class="badge bg-light text-primary-emphasis border rounded-pill fw-normal px-3 py-2 small d-inline-flex align-items-center">
                                                <i class="bi bi-envelope-at me-2"></i>
                                                <?= htmlspecialchars($user['email']) ?>
                                            </span>
                                            
                                        </div>
                                    </div>
                                </a>
                            </div>
                            
                            <!-- Höger sida: Knappar -->
                            <div class="col-12 col-md-5 col-lg-4 text-md-end">
                                <div class="d-flex gap-2 justify-content-md-end">
                                    <a href="messages.php?user_id=<?= $user['id'] ?>" class="btn btn-outline-primary btn-sm rounded-pill px-3 flex-fill flex-md-grow-0">
                                        Message
                                    </a>
                                    <button class="btn btn-outline-danger btn-sm rounded-pill px-3 delete-user-btn flex-fill flex-md-grow-0" 
                                            data-user-id="<?= $user['id'] ?>" 
                                            data-username="<?= htmlspecialchars($user['username']) ?>">
                                        Delete User
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- MODAL FÖR RADERING AV ANVÄNDARE -->
<div class="modal fade" id="deleteUserModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 15px;">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold">Delete User?</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-muted">
                Are you sure you want to delete <span id="delete-username-display" class="fw-bold text-dark"></span>? 
                <br><br>
                <div class="alert alert-danger p-2 small">
                    <i class="bi bi-exclamation-triangle-fill"></i> 
                    This will permanently remove all their quacks, messages, and followers.
                </div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-light rounded-pill px-4 fw-bold" data-bs-dismiss="modal">Cancel</button>
                <button type="button" id="confirmDeleteUserBtn" class="btn btn-danger rounded-pill px-4 fw-bold">Delete User</button>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
