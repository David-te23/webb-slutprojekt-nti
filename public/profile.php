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
        DATE_FORMAT(created_at, '%a') as day,
        COUNT(*) as count
    FROM quacks
    WHERE user_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    GROUP BY day, DATE(created_at)
    ORDER BY DATE(created_at) ASC
");
$chartStmt->execute([$viewUserId]);
$chartRaw = $chartStmt->fetchAll(PDO::FETCH_ASSOC);

$days = array_column($chartRaw, 'day');
$postCounts = array_column($chartRaw, 'count');

//fetch quacks for specific user
$quackStmt = $dbconn->prepare("
    SELECT q.*, u.username, u.display_name, u.profile_image,
    (SELECT COUNT(*) FROM likes WHERE quack_id = q.id) as like_count,
    (SELECT COUNT(*) FROM comments WHERE quack_id = q.id) as comment_count,
    (SELECT COUNT(*) FROM quacks WHERE parent_id = q.id) as requack_count,
    EXISTS(SELECT 1 FROM likes WHERE quack_id = q.id AND user_id = ?) as user_liked
    FROM quacks q
    JOIN users u ON q.user_id = u.id
    WHERE q.user_id = ?
    ORDER BY q.created_at DESC
");
$quackStmt->execute([$_SESSION['user_id'], $viewUserId]);
$quacks = $quackStmt->fetchAll(PDO::FETCH_ASSOC);

//fetch images for the quacks (mapping them to quack id)
foreach ($quacks as &$q) {
    $imgStmt = $dbconn->prepare("SELECT image_path, file_type FROM quack_images WHERE quack_id = ?");
    $imgStmt->execute([$q['id']]);
    $q['images'] = $imgStmt->fetchAll(PDO::FETCH_ASSOC);
}
unset($q); //clean up reference
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
                        <span class="me-3"><strong><?= $counts['following'] ?></strong>Following</span>
                        <span class="me-3"><strong><?= $counts['followers'] ?></strong>Followers</span>
                        <p class="text-white-50 small mt-2">Joined: <?= date("F Y", strtotime($user['created_at']))?></p>
                    </div>
                 </div>
            </div>
            <?php if ($viewUserId == $_SESSION['user_id']): ?>
                <div class="text-end">
                    <button class="btn btn-light btn-sm rounded-pill px-3 fw-bold mb-2">Edit Profile</button>
                </div>
                <?php endif; ?>
        </div>
    </section>
    
    <!--quacktivity section -->
    <section class="profile-chart-section mb-4 text-center">
        <h5 class="text-white mb-4"><?= htmlspecialchars($user['display_name']) ?>'s weekly quacktivity</h5>
        <div class="chart-holder">
            <canvas id="quackChart"></canvas>
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
                <?php foreach ($quacks as $quack) : ?>
                    
                    <div class="quack-card bg-white p-3 rounded shadow-sm mb-3">
                        <div class="d-flex gap-3 text-dark">
                            <img src="<?= getPfpPath($quack['profile_image']) ?>" class="profile-pic-placeholder">
                            <div class="flex-grow-1 text-start">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="fw-bold text-dark"><?= htmlspecialchars($quack['display_name']) ?></span>
                                    <span class="text-muted small">@<?= htmlspecialchars($quack['username']) ?> &bull; <?= date('H:i', strtotime($quack['created_at'])) ?></span>
                                </div>
                                <p class="mt-1 mb-0 fs-6 text-dark"><?= htmlspecialchars($quack['content']) ?></p>

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
                                    <span class="action-icon">
                                    <svg class="quack-icon" fill="#000000" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"><path d="M5 4a2 2 0 0 0-2 2v6H0l4 4 4-4H5V6h7l2-2H5zm10 4h-3l4-4 4 4h-3v6a2 2 0 0 1-2 2H6l2-2h7V8z"></path></g></svg>
                                        <span class="align-middle"><?= $quack['requack_count'] ?></span>
                                    </span>
                                    <span class="action-icon like-btn <?= $quack['user_liked'] ? 'is-liked' : '' ?>" data-quack-id="<?= $quack['id'] ?>">
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
