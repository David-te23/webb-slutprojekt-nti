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
                <?php foreach ($quacks as $quack) : 
                    $isRequack = ($quack['content'] === null && $quack['parent_id'] !== null);

                    if ($isRequack) {
                        $display = [
                            'id' => $quack['parent_id'],
                            'user_id' => $quack['orig_user_id'],
                            'content' => $quack['orig_content'],
                            'display_name' => $quack['orig_display_name'],
                            'username' => $quack['orig_username'],
                            'profile_image' => $quack['orig_profile_image'],
                            'created_at' => $quack['created_at'] //när requacked gjordes
                        ];
                    } else {
                        $display = $quack;
                    }
                    ?>
                    
                    <div class="quack-card bg-white p-3 rounded shadow-sm mb-3">
                        <?php if($isRequack) : ?>
                            <div class="text-muted small mb-2 ms-5 fw-bold">
                                <svg class="quack-icon" fill="#000000" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"><path d="M5 4a2 2 0 0 0-2 2v6H0l4 4 4-4H5V6h7l2-2H5zm10 4h-3l4-4 4 4h-3v6a2 2 0 0 1-2 2H6l2-2h7V8z"></path></g></svg>
                                <?= htmlspecialchars($quack['display_name']) ?> requacked
                            </div>
                            <?php endif; ?>
                        <div class="d-flex gap-3 text-dark">
                            <img src="<?= getPfpPath($display['profile_image']) ?>" class="profile-pic-placeholder">
                            <div class="flex-grow-1 text-start">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="fw-bold text-dark"><?= htmlspecialchars($display['display_name']) ?></span>
                                    <span class="text-muted small">
                                        @<?= htmlspecialchars($display['username']) ?> &bull;
                                        <span title="<?= date('Y-m-d H:i', strtotime($display['created_at'])) ?>">
                                         <?= formatQuackTime($display['created_at']) ?>
                                        </span>
                                        </span>
                                </div>
                                <p class="mt-1 mb-0 fs-6 text-dark"><?= htmlspecialchars($display['content']) ?></p>

                                <?php if (!empty($quack['images'])) : 
                                    $imgCount = count($quack['images']);
                                    $gridClass = ($imgCount > 4) ? 'grid-4' : 'grid-' . $imgCount;
                                ?>
                                    <div class="quack-image-gallery <?= $gridClass ?> mt-2">
                                        <?php foreach ($quack['images'] as $image): ?>
                                            <div class="gallery-item">
                                                <?php if (str_contains($image['file_type'], 'video')): ?>
                                                    <video src="../<?= htmlspecialchars($image['image_path']) ?>" controls></video>
                                                <?php else: ?>
                                                    <img src="../<?= htmlspecialchars($image['image_path']) ?>">
                                                <?php endif; ?>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>

                                
                                <div class="d-flex gap-5 mt-3 text-muted">
                                <span class="action-icon d-flex align-items-center gap-1">
                                    <svg class="quack-icon" viewBox="0 0 32 32" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:sketch="http://www.bohemiancoding.com/sketch/ns" fill="#000000"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <title>comment 5</title> <desc>Created with Sketch Beta.</desc> <defs> </defs> <g id="Page-1" stroke="none" stroke-width="1" fill="none" fill-rule="evenodd" sketch:type="MSPage"> <g id="Icon-Set-Filled" sketch:type="MSLayerGroup" transform="translate(-362.000000, -257.000000)" fill="#000000"> <path d="M388.667,257 L367.333,257 C364.388,257 362,259.371 362,262.297 L362,279.187 C362,282.111 364.055,284 367,284 L373.639,284 L378,289.001 L382.361,284 L389,284 C391.945,284 394,282.111 394,279.187 L394,262.297 C394,259.371 391.612,257 388.667,257" id="comment-5" sketch:type="MSShapeGroup"> </path> </g> </g> </g></svg>
                                        <span class="align-middle"><?= $quack['comment_count'] ?></span>
                                    </span>
                                    <span class="action-icon requack-btn <?= $quack['user_requacked'] ? 'is-requacked' : '' ?>"
                                                data-quack-id="<?= $display['id'] ?>">
                                    <svg class="quack-icon" fill="#000000" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"><path d="M5 4a2 2 0 0 0-2 2v6H0l4 4 4-4H5V6h7l2-2H5zm10 4h-3l4-4 4 4h-3v6a2 2 0 0 1-2 2H6l2-2h7V8z"></path></g></svg>
                                        <span class="align-middle"><?= $quack['requack_count'] ?></span>
                                    </span>
                                    <span class="action-icon like-btn <?= $quack['user_liked'] ? 'is-liked' : '' ?>" data-quack-id="<?= $display['id'] ?>">
                                    <svg class="quack-icon" viewBox="0 -0.5 21 21" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" fill="#000000"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <title>like [#1385]</title> <desc>Created with Sketch.</desc> <defs> </defs> <g id="Page-1" stroke="none" stroke-width="1" fill="none" fill-rule="evenodd"> <g id="Dribbble-Light-Preview" transform="translate(-259.000000, -760.000000)" fill="#000000"> <g id="icons" transform="translate(56.000000, 160.000000)"> <path d="M203,620 L207.200006,620 L207.200006,608 L203,608 L203,620 Z M223.924431,611.355 L222.100579,617.89 C221.799228,619.131 220.638976,620 219.302324,620 L209.300009,620 L209.300009,608.021 L211.104962,601.825 C211.274012,600.775 212.223214,600 213.339366,600 C214.587817,600 215.600019,600.964 215.600019,602.153 L215.600019,608 L221.126177,608 C222.97313,608 224.340232,609.641 223.924431,611.355 L223.924431,611.355 Z" id="like-[#1385]"> </path> </g> </g> </g> </g></svg>
                                    <span class="like-count align-middle"><?= $quack['like_count'] ?></span>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </section>
</div>
 
<?php
require_once __DIR__ . '/../includes/footer.php'; ?>
