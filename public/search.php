<?php
$pageTitle = 'Search Results';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/quack_time_formatter.php';

$query = isset($_GET['query']) ? trim($_GET['query']) : '';
$quacks = [];
$users = [];

if ($query !== '') {
    $userId = $_SESSION['user_id'];
    $searchTerm = "%$query%";

    // --- SÖK EFTER ANVÄNDARE ---
    $userStmt = $dbconn->prepare("
        SELECT id, username, display_name, profile_image 
        FROM users 
        WHERE (username LIKE ? OR display_name LIKE ?) 
        LIMIT 5
    ");
    $userStmt->execute([$searchTerm, $searchTerm]);
    $users = $userStmt->fetchAll(PDO::FETCH_ASSOC);

    // --- SÖK EFTER QUACKS ---
    // Bas-SQL som innehåller allt quack_loop.php behöver (likes, counts, etc)
    $sql = "SELECT q.*, u.username, u.display_name, u.profile_image,
            (SELECT COUNT(*) FROM likes WHERE quack_id = q.id) as like_count,
            (SELECT COUNT(*) FROM comments WHERE quack_id = q.id) as comment_count,
            (SELECT COUNT(*) FROM quacks WHERE parent_id = q.id AND content IS NULL) as requack_count,
            EXISTS(SELECT 1 FROM likes WHERE quack_id = q.id AND user_id = ?) as user_liked,
            EXISTS(SELECT 1 FROM quacks WHERE parent_id = q.id AND user_id = ? AND content IS NULL) as user_requacked
            FROM quacks q
            JOIN users u ON q.user_id = u.id";

    if (str_starts_with($query, '#')) {
        // Sökning via Hashtag-kopplingstabellen
        $tagName = ltrim($query, '#');
        $stmt = $dbconn->prepare($sql . " 
            JOIN quack_hashtags qh ON q.id = qh.quack_id
            JOIN hashtags h ON qh.hashtag_id = h.id
            WHERE h.tag_name = ?
            ORDER BY q.created_at DESC");
        $stmt->execute([$userId, $userId, $tagName]);
    } else {
        // Vanlig textsökning
        $stmt = $dbconn->prepare($sql . " 
            WHERE q.content LIKE ?
            ORDER BY q.created_at DESC");
        $stmt->execute([$userId, $userId, $searchTerm]);
    }
    $quacks = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Hämta bilder för resultaten
    foreach ($quacks as &$quack) {
        $targetId = $quack['parent_id'] ?? $quack['id'];
        $imgStmt = $dbconn->prepare("SELECT image_path, file_type FROM quack_images WHERE quack_id = ?");
        $imgStmt->execute([$targetId]);
        $quack['images'] = $imgStmt->fetchAll(PDO::FETCH_ASSOC);
    }
    unset($quack);
}
?>

<!-- Återanvänder feed-container för den grå bakgrunden -->
<div class="p-3 rounded shadow-sm feed-container">
<h1 class="visually-hidden">Quacker - Search results</h1>

    
    <!-- Sök-header (Vitt kort) -->
    <div class="bg-white p-3 rounded shadow-sm mb-4">
        <h2 class="h4 m-0 fw-bold">
            Results for <span class="text-success"><?= htmlspecialchars($query) ?></span>
        </h2>
        <small class="text-muted"><?= count($users) ?> users and <?= count($quacks) ?> quacks found</small>
    </div>

    <!-- ANVÄNDARLISTA -->
    <?php if (!empty($users)): ?>
        <div class="users-section mb-4">
            <h3 class="h6 text-muted fw-bold ps-2 mb-2">Users</h3>
            <div class="bg-white rounded shadow-sm overflow-hidden">
                <?php foreach ($users as $user): ?>
                    <a href="profile.php?id=<?= $user['id'] ?>" class="d-flex align-items-center p-3 text-decoration-none text-dark border-bottom user-search-item">
                        <img src="<?= getPfpPath($user['profile_image']) ?>" class="profile-pic-placeholder me-3" alt="Profile image">
                        <div>
                            <div class="fw-bold"><?= htmlspecialchars($user['display_name']) ?></div>
                            <div class="text-muted small">@<?= htmlspecialchars($user['username']) ?></div>
                        </div>
                        <div class="ms-auto">
                            <span class="btn btn-sm btn-outline-success rounded-pill px-3">View Profile</span>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- QUACKS LISTA -->
    <div id="feed-container">
        <h4 class="h6 text-muted fw-bold ps-2 mb-2">Quacks</h4>
        <?php 
        require_once __DIR__ . '/../includes/quack_loop.php'; 
        ?>
    </div>
</div>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>
