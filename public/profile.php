<?php
$pageTitle = "Profile";
require_once __DIR__ . '/../includes/header.php';

//determine which user we are looking at, if an id in url exists go there otherwise default to logged in user
$viewUserId = isset($_GET['id']) ? (int)$_GET['id'] : $_SESSION['user_id'];

//fetch user profile data
$stmt = $dbconn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$viewUserId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo "<h2>User not found.</h2>";
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

//fetch follower/following counts
$countStmt = $dbconn->prepare("
    SELECT
        (SELECT COUNT(*) FROM follows WHERE following_id = ?) as followers,
        (SELECT COUNT(*) FROM follows WHERE follower_id = ?) as following
");
$countStmt->execute([$viewUserId, $viewUserId]);
$counts = $countStmt->fetch(PDO::FETCH_ASSOC);

//fetch last 7 days of quacks (chart)
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

    $dayIndex = (int)date('N', $ts) -1;
    $dayName = ($i === 0) ? "Today" : $days[$dayIndex];

    $count = 0;
    foreach ($chartRaw as $row) {
        if ($row['day'] == $dateKey) {
            $count = (int)$row['count'];
            break;
        }
    }

    $daysLabels[] = $dayName;
    $postCounts[] = $count;
}

//fetch quacks for specific user
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
        -- Check för både Like och Requack status
        EXISTS(SELECT 1 FROM likes WHERE quack_id = COALESCE(q.parent_id, q.id) AND user_id = ?) as user_liked,
        EXISTS(SELECT 1 FROM quacks WHERE parent_id = COALESCE(q.parent_id, q.id) AND user_id = ? AND content IS NULL) as user_requacked
    FROM quacks q
    JOIN users u ON q.user_id = u.id
    LEFT JOIN quacks orig_q ON q.parent_id = orig_q.id
    LEFT JOIN users orig_u ON orig_q.user_id = orig_u.id
    WHERE q.user_id = ?
    ORDER BY q.created_at DESC
");

// Skicka in inloggad användares ID två gånger (för de två EXISTS-checkarna) och sen profilens ID
$quackStmt->execute([$_SESSION['user_id'], $_SESSION['user_id'], $viewUserId]);
$quacks = $quackStmt->fetchAll(PDO::FETCH_ASSOC);
// Hämta bilder (för både vanliga och originalet i requacks)
foreach ($quacks as &$quack) {
    $targetId = $quack['parent_id'] ?? $quack['id'];
    $imgStmt = $dbconn->prepare("SELECT image_path, file_type FROM quack_images WHERE quack_id = ?");
    $imgStmt->execute([$targetId]);
    $quack['images'] = $imgStmt->fetchAll(PDO::FETCH_ASSOC);
}
unset($quack); //städa upp referenser

$isFollowing = false;
if ($viewUserId != $_SESSION['user_id']) {
    $followCheck = $dbconn->prepare("SELECT 1 FROM follows WHERE follower_id = ? AND following_id = ?");
    $followCheck->execute([$_SESSION['user_id'], $viewUserId]);
    $isFollowing = (bool)$followCheck->fetch();
}

require_once __DIR__ . '/../includes/quack_time_formatter.php';
?>

<div class="profile-main-wrapper">

    <!-- profile info section -->
    <section class="profile-header-card mb-4">
        <div class="d-flex justify-content-between align-items-start">
            <div class="d-flex align-items-center gap-4">
                <img src="<?= getPfpPath($user['profile_image'] ?? 'default_pfp.jpg') ?>"
                 alt="Profile" class="profile-main-img">

                 <div class="text-white">
                    <h2 class="fw-bold mb-0"><?= htmlspecialchars($user['display_name']) ?></h2>
                    <p class="text-white-50">@<?= htmlspecialchars($user['username']) ?></p>
                    <p class="mt-2"><?= htmlspecialchars($user['bio'] ?? 'No bio yet...') ?></p>

                    <div class="profile-stats text-white">
                        <span class="me-3"><strong id="following-count"><?= $counts['following'] ?></strong> Following</span>
                        <span class="me-3"><strong id="follower-count"><?= $counts['followers'] ?></strong> Followers</span>
                        <p class="text-white-50 small mt-2">Joined: <?= date("F Y", strtotime($user['created_at']))?></p>
                    </div>
                 </div>
            </div>
            <div class="text-end d-flex flex-column gap-2">
                <?php if ($viewUserId == $_SESSION['user_id']): ?>
                    <!-- om det är ens egna profil -->
                    <button class="btn btn-light btn-sm rounded-pill px-3 fw-bold mb-2">Edit Profile</button>
                <?php else: ?>
                    <!-- om det inte är ens egna profil -->
                    <div class="d-flex gap-2">
                        <!-- message knapp -->
                        <a href="messages.php?user_id=<?= $viewUserId ?>" class="btn btn-outline-light btn-sm rounded-pill px-3 fw-bold">
                            <i class="bi bi-envelope"></i> Message
                        </a>

                        <!-- follow/unfollow knapp -->
                        <button id="follow-btn" 
                                data-user-id="<?= $viewUserId ?>" 
                                data-action="<?= $isFollowing ? 'unfollow' : 'follow' ?>"
                                class="btn <?= $isFollowing ? 'btn-outline-danger' : 'btn-light' ?> btn-sm rounded-pill px-4 fw-bold">
                            <?= $isFollowing ? 'Unfollow' : 'Follow' ?>
                        </button>
                    </div>
                <?php endif; ?> 
            </div>
        </div>
    </section>
    
    <!--quacktivity section -->
    <section class="profile-chart-section mb-4 text-center">
        <h5 class="text-white mb-4"><?= htmlspecialchars($user['display_name']) ?>'s weekly quacktivity</h5>
        <div class="chart-holder">
            <canvas id="quackChart"
                    data-days='<?= json_encode($daysLabels) ?>'
                    data-counts='<?= json_encode($postCounts) ?>'></canvas>
        </div>
    </section>

    <!-- Feed Section -->
<section class="profile-feed-section">
    <div>
        <?php if (empty($quacks)): ?>
            <div class="py-5 text-center text-white-50">
                <p>This user hasn't quacked yet!</p>
            </div>
        <?php else: ?>
            <?php 
            // Förbered bilderna för varje quack
            foreach ($quacks as &$quack) {
                $targetIdForImgs = $quack['parent_id'] ?? $quack['id'];
                $imgStmt = $dbconn->prepare("SELECT image_path, file_type FROM quack_images WHERE quack_id = ?");
                $imgStmt->execute([$targetIdForImgs]);
                $quack['images'] = $imgStmt->fetchAll(PDO::FETCH_ASSOC);
            }
            unset($quack); // Bryt referensen

            // Inkludera quack loopen
            require __DIR__ . '/../includes/quack_loop.php'; 
            ?>
        <?php endif; ?>
    </div>
</section>

</div>
 
<?php
require_once __DIR__ . '/../includes/footer.php'; ?>
