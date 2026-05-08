<?php
$pageTitle = "Profile";
require_once __DIR__ . '/../includes/header.php';

// Bestäm vilken användare vi tittar på
$viewUserId = isset($_GET['id']) ? (int)$_GET['id'] : $_SESSION['user_id'];

// Hämta profildata
$stmt = $dbconn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$viewUserId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo "<h2>User not found.</h2>";
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

// Hämta statistik (följare/följer)
$countStmt = $dbconn->prepare("
    SELECT
        (SELECT COUNT(*) FROM follows WHERE following_id = ?) as followers,
        (SELECT COUNT(*) FROM follows WHERE follower_id = ?) as following
");
$countStmt->execute([$viewUserId, $viewUserId]);
$counts = $countStmt->fetch(PDO::FETCH_ASSOC);

// Hämta quacktivity-data
$chartStmt = $dbconn->prepare("
    SELECT
        DATE(created_at) as day,
        COUNT(*) as count
    FROM quacks
    WHERE user_id = ? AND created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
    GROUP BY day
    ORDER BY day ASC
");
$chartStmt->execute([$viewUserId]);
$chartRaw = $chartStmt->fetchAll(PDO::FETCH_ASSOC);

$days = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
$daysLabels = [];
$postCounts = [];

for ($i = 6; $i >= 0; $i--) {
    $ts = strtotime("-$i days");
    $dateKey = date('Y-m-d', $ts);
    $dayIndex = (int)date('N', $ts) - 1;
    $dayName = ($i === 0) ? "Today" : $days[$dayIndex];

    $count = 0;
    foreach ($chartRaw as $row) {
        if ($row['day'] == $dateKey) { $count = (int)$row['count']; break; }
    }
    $daysLabels[] = $dayName;
    $postCounts[] = $count;
}

// Hämta quacks för feeden
$quackStmt = $dbconn->prepare("
    SELECT 
        q.id, q.content, q.created_at, q.user_id, q.parent_id,
        u.username, u.display_name, u.profile_image,
        orig_q.content AS orig_content,
        orig_q.created_at AS orig_created_at,
        orig_u.username AS orig_username,
        orig_u.display_name AS orig_display_name,
        orig_u.profile_image AS orig_profile_image,
        orig_u.id AS orig_user_id,
        (SELECT COUNT(*) FROM likes WHERE quack_id = COALESCE(q.parent_id, q.id)) as like_count,
        (SELECT COUNT(*) FROM comments WHERE quack_id = COALESCE(q.parent_id, q.id)) as comment_count,
        (SELECT COUNT(*) FROM quacks WHERE parent_id = COALESCE(q.parent_id, q.id) AND content IS NULL) as requack_count,
        EXISTS(SELECT 1 FROM likes WHERE quack_id = COALESCE(q.parent_id, q.id) AND user_id = ?) as user_liked,
        EXISTS(SELECT 1 FROM quacks WHERE parent_id = COALESCE(q.parent_id, q.id) AND user_id = ? AND content IS NULL) as user_requacked
    FROM quacks q
    JOIN users u ON q.user_id = u.id
    LEFT JOIN quacks orig_q ON q.parent_id = orig_q.id
    LEFT JOIN users orig_u ON orig_q.user_id = orig_u.id
    WHERE q.user_id = ?
    ORDER BY q.created_at DESC
");

$quackStmt->execute([$_SESSION['user_id'], $_SESSION['user_id'], $viewUserId]);
$quacks = $quackStmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($quacks as &$quack) {
    $targetId = $quack['parent_id'] ?? $quack['id'];
    $imgStmt = $dbconn->prepare("SELECT image_path, file_type FROM quack_images WHERE quack_id = ?");
    $imgStmt->execute([$targetId]);
    $quack['images'] = $imgStmt->fetchAll(PDO::FETCH_ASSOC);
}
unset($quack);

$isFollowing = false;
if ($viewUserId != $_SESSION['user_id']) {
    $followCheck = $dbconn->prepare("SELECT 1 FROM follows WHERE follower_id = ? AND following_id = ?");
    $followCheck->execute([$_SESSION['user_id'], $viewUserId]);
    $isFollowing = (bool)$followCheck->fetch();
}

require_once __DIR__ . '/../includes/quack_time_formatter.php';
?>

<div class="profile-main-wrapper">

    <!-- Felmeddelanden vid profiluppdatering -->
    <?php if (isset($_GET['status'])): ?>
        <?php if ($_GET['status'] === 'invalid_type'): ?>
            <div class="alert alert-warning rounded-pill py-2 px-4 small border-0 shadow-sm mb-4">
                Filtypen stöds inte. Använd JPG, PNG eller WebP.
            </div>
        <?php elseif ($_GET['status'] === 'too_large'): ?>
            <div class="alert alert-warning rounded-pill py-2 px-4 small border-0 shadow-sm mb-4">
                Bilden är för stor (max 5MB).
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <!-- Profil Header -->
    <section class="profile-header-card mb-4">
        <div class="d-flex justify-content-between align-items-start">
            <div class="d-flex align-items-center gap-4">
                <img src="<?= getPfpPath($user['profile_image'] ?? 'default_pfp.jpg') ?>" alt="Profile" class="profile-main-img">
                <div class="text-white text-content">
                    <h2 class="fw-bold mb-0"><?= htmlspecialchars($user['display_name']) ?></h2>
                    <p class="text-white-50">@<?= htmlspecialchars($user['username']) ?></p>
                    <?php if (!empty($user['bio'])): ?>
                        <p class="mt-2 bio-text"><?= htmlspecialchars($user['bio']) ?></p>
                    <?php endif; ?>
                    <div class="profile-stats text-white">
                        <span><strong><?= $counts['following'] ?></strong> Following</span>
                        <span class="ms-3"><strong><?= $counts['followers'] ?></strong> Followers</span>
                        <p class="text-white-50 small mt-2 mb-0">Joined: <?= date("F Y", strtotime($user['created_at']))?></p>
                    </div>
                </div>
            </div>
            <div class="text-end d-flex flex-column gap-2 profile-actions">
                <?php if ($viewUserId == $_SESSION['user_id']): ?>
                    <button class="btn btn-light btn-sm rounded-pill px-3 fw-bold" data-bs-toggle="modal" data-bs-target="#editProfileModal">Edit Profile</button>
                    <a href="actions/logout.php" class="btn btn-outline-light btn-sm rounded-pill px-3 fw-bold"><i class="bi bi-box-arrow-right"></i> Log out</a>
                <?php else: ?>
                    <div class="d-flex gap-2">
                        <a href="messages.php?user_id=<?= $viewUserId ?>" class="btn btn-outline-light btn-sm rounded-pill px-3 fw-bold"><i class="bi bi-envelope"></i></a>
                        <button id="follow-btn" data-user-id="<?= $viewUserId ?>" data-action="<?= $isFollowing ? 'unfollow' : 'follow' ?>" class="btn <?= $isFollowing ? 'btn-outline-danger' : 'btn-light' ?> btn-sm rounded-pill px-4 fw-bold"><?= $isFollowing ? 'Unfollow' : 'Follow' ?></button>
                    </div>
                <?php endif; ?> 
            </div>
        </div>
    </section>
    
    <!-- Quacktivity -->
    <section class="profile-chart-section mb-4 text-center">
        <h5 class="text-white mb-4"><?= htmlspecialchars($user['display_name']) ?>'s weekly quacktivity</h5>
        <div class="chart-holder">
            <canvas id="quackChart" data-days='<?= json_encode($daysLabels) ?>' data-counts='<?= json_encode($postCounts) ?>'></canvas>
        </div>
    </section>

    <!-- Feed -->
    <section class="profile-feed-section">
        <?php if (empty($quacks)): ?>
            <div class="py-5 text-center text-white-50"><p>This user hasn't quacked yet!</p></div>
        <?php else: ?>
            <?php require __DIR__ . '/../includes/quack_loop.php'; ?>
        <?php endif; ?>
    </section>
</div>

<!-- MODAL 1: EDIT PROFILE -->
<?php if ($viewUserId == $_SESSION['user_id']): ?>
<div class="modal fade" id="editProfileModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content profile-modal-dark shadow-lg rounded-4">
            <div class="modal-header border-secondary">
                <h5 class="modal-title fw-bold text-white">Edit Profile</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="actions/update_profile.php" method="POST" enctype="multipart/form-data">
                <div class="modal-body">
                    <div class="profile-edit-image-container text-center mb-4">
                        <div class="position-relative d-inline-block image-hover-container">
                            <img src="<?= getPfpPath($user['profile_image'] ?? 'default_pfp.jpg') ?>" class="profile-main-img border-secondary shadow" id="previewImg">
                            <label for="pfpInput" class="camera-overlay">
                                <svg xmlns="http://w3.org" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"></path>
                                    <circle cx="12" cy="13" r="4"></circle>
                                </svg>
                            </label>
                        </div>
                        <input type="file" name="profile_image" id="pfpInput" class="d-none" accept=".jpg, .jpeg, .png, .webp">
                    </div>

                    <div class="form-floating mb-3">
                        <input type="text" name="display_name" class="form-control" id="editName" placeholder="Name" value="<?= htmlspecialchars($user['display_name']) ?>" required>
                        <label for="editName">Display Name</label>
                    </div>

                    <div class="form-floating mb-4">
                        <textarea name="bio" class="form-control" id="editBio" placeholder="Bio" style="height: 100px"><?= htmlspecialchars($user['bio'] ?? '') ?></textarea>
                        <label for="editBio">Bio</label>
                    </div>

                    <div class="danger-zone p-3 border border-danger rounded-3 text-center mt-3">
                        <h6 class="text-danger fw-bold small uppercase">Danger Zone</h6>
                        <p class="sx-small mb-3 text-white-50">Deleting your account is permanent.</p>
                        <button type="button" class="btn btn-outline-danger btn-sm rounded-pill w-100 fw-bold" data-bs-toggle="modal" data-bs-target="#confirmDeleteModal">Delete Account</button>
                    </div>
                </div>
                <div class="modal-footer border-secondary">
                    <button type="submit" class="btn btn-light rounded-pill px-4 fw-bold w-100">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL 2: CONFIRM DELETE -->
<div class="modal fade" id="confirmDeleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content border-danger bg-dark text-white rounded-4">
            <div class="modal-body text-center p-4">
                <svg xmlns="http://w3.org" width="48" height="48" fill="#dc3545" class="bi bi-exclamation-triangle mb-3" viewBox="0 0 16 16">
                    <path d="M7.938 2.016A.13.13 0 0 1 8.002 2a.13.13 0 0 1 .063.016.146.146 0 0 1 .054.057l6.857 11.667c.036.06.035.124.002.183a.163.163 0 0 1-.054.06.116.116 0 0 1-.066.017H1.146a.115.115 0 0 1-.066-.017.163.163 0 0 1-.054-.06.176.176 0 0 1 .002-.183L7.884 2.073a.147.147 0 0 1 .054-.057zm1.044-.45a1.13 1.13 0 0 0-1.96 0L.165 13.233c-.457.778.091 1.767.98 1.767h13.713c.889 0 1.438-.99.98-1.767L8.982 1.566z"/>
                    <path d="M7.002 12a1 1 0 1 1 2 0 1 1 0 0 1-2 0zM7.1 5.995a.905.905 0 1 1 1.8 0l-.35 3.507a.552.552 0 0 1-1.1 0L7.1 5.995z"/>
                </svg>
                <h5 class="fw-bold">Are you sure?</h5>
                <p class="small text-white-50">All your data will be permanently removed.</p>
                <div class="d-grid gap-2 mt-4">
                    <a href="actions/delete_my_account.php" class="btn btn-danger rounded-pill fw-bold">Yes, Delete Account</a>
                    <button type="button" class="btn btn-outline-light rounded-pill fw-bold" data-bs-dismiss="modal">Cancel</button>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
