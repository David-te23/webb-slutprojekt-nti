<?php
$pageTitle = "Home"; // <-- set dynamic page title
require_once __DIR__ . '/../includes/header.php';

$stmt = $dbconn->prepare("
    SELECT
         q.*,
         u.username, u.display_name, u.profile_image,
         COUNT(DISTINCT l.user_id) AS like_count,
         COUNT(DISTINCT c.id) AS comment_count,
         COUNT(DISTINCT r.id) AS requack_count
    FROM quacks q
    JOIN users u ON q.user_id = u.id
    LEFT JOIN likes l ON q.id = l.quack_id
    LEFT JOIN comments c ON q.id = c.quack_id
    LEFT JOIN quacks r ON q.id = r.parent_id
    WHERE q.parent_id IS NULL
    GROUP BY q.id
    ORDER BY q.created_at DESC
");
$stmt->execute();
$quacks = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($quacks as &$quack) {
    $imgStmt = $dbconn->prepare("SELECT image_path, file_type FROM quack_images WHERE quack_id = ?");
    $imgStmt->execute([$quack['id']]);
    $quack['images'] = $imgStmt->fetchAll(PDO::FETCH_ASSOC);
}
unset($quack);
?>

<div class="p-3 rounded shadow-sm feed-container">
    <div class="d-flex flex-row justify-content-center gap-4 sticky-top mb-3">
        <!-- All Quacks tab -->
        <button class=" feed-tab active p-3 bg-light rounded shadow w-50 d-flex justify-content-center border-0">
            <span class="m-0 fw-bold">All Quacks</span>
        </button>
        <!-- Following Quacks tab -->
        <button class="feed-tab p-3 bg-light rounded shadow w-50 d-flex justify-content-center border-0">
            <span class="m-0 fw-bold">Following</span>
        </button>
    </div>    

    <!-- New Quack container -->
    <div class="create-quack-card bg-white p-3 rounded shadow-sm mb-4">
        <div class="d-flex gap-3">
            <img src="<?= getPfpPath($currentUser['profile_image']) ?>" class="profile-pic-placeholder">
            <form action="../src/process_quack.php" method="POST" enctype="multipart/form-data" class="flex-grow-1">
                <textarea id="quack-textarea" name="quack_content" rows="1" class="form-control border-0 fs-5 mb-2" placeholder="What is quacking?" required maxlength="280"></textarea>
                <!-- selected img preview-->
                <div id="img-preview-container" class="d-flex flex-wrap gap-2 mb-2"></div>
                <div class="d-flex justify-content-between align-items-center pt-2 border-top">
                    <div class="d-flex gap-3">
                        <!-- img upload -->
                        <label for="quack-images" class="btn btn-link p-0 text-success new-quack-icon">
                            <svg viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <path fill-rule="evenodd" clip-rule="evenodd" d="M9 0H2V16H14V5L9 0ZM7 6V8H5V10H7V12H9V10H11V8H9V6H7Z" fill="#000000"></path> </g></svg>
                            <input type="file" id="quack-images" name="quack_images[]" accept="image/*" class="d-none" multiple>
                        </label>
                        <!-- emoji btn -->
                        <button type="button" id="emoji-trigger" class="btn btn-link p-0 text-success new-quack-icon">
                            <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <path d="M8.9126 15.9336C10.1709 16.249 11.5985 16.2492 13.0351 15.8642C14.4717 15.4793 15.7079 14.7653 16.64 13.863" stroke="#1C274C" stroke-width="1.5" stroke-linecap="round"></path> <ellipse cx="14.5094" cy="9.77405" rx="1" ry="1.5" transform="rotate(-15 14.5094 9.77405)" fill="#1C274C"></ellipse> <ellipse cx="8.71402" cy="11.3278" rx="1" ry="1.5" transform="rotate(-15 8.71402 11.3278)" fill="#1C274C"></ellipse> <path d="M20.7964 9.643C21.9075 13.7897 22.4631 15.863 21.5201 17.4964C20.577 19.1298 18.5037 19.6853 14.357 20.7964C10.2103 21.9075 8.13698 22.4631 6.50359 21.5201C4.87021 20.577 4.31466 18.5037 3.20356 14.357C2.09246 10.2103 1.53691 8.13698 2.47995 6.50359C3.42298 4.87021 5.49632 4.31466 9.643 3.20356C13.7897 2.09246 15.863 1.53691 17.4964 2.47995C18.5048 3.06212 19.1023 4.07505 19.6734 5.74061" stroke="#1C274C" stroke-width="1.5" stroke-linecap="round"></path> <path d="M13 16.0004L13.478 16.9742C13.8393 17.7104 14.7249 18.0198 15.4661 17.6689C16.2223 17.311 16.5394 16.4035 16.1708 15.6524L15.7115 14.7168" stroke="#1C274C" stroke-width="1.5"></path> </g></svg>
                        </button>
                        <div id="picker-container" class="emoji-picker-container">
                            <emoji-picker></emoji-picker>
                        </div>
                    </div>
                <button type="submit" class="btn btn-quack px-4 fw-bold shadow-sm">Quack!</button>
            </form>
            </div>
        </div>
    </div>

    <!-- Quack inlägg -->
    <?php foreach ($quacks as $quack) : ?>
    <div class="quack-card bg-white p-3 rounded shadow-sm mb-3">
        <div class="d-flex gap-3">
            <img src="<?= getPfpPath($quack['profile_image']) ?>" class="profile-pic-placeholder bg-secondary-subtle">
            <div class="flex-grow-1">
                <div class="d-flex align-items-center gap-2">
                    <span class="fw-bold"><?= htmlspecialchars($quack['display_name']) ?></span>
                    <span class="text-muted"><?= htmlspecialchars($quack['username']) ?> &bull; <?= date('H:i', strtotime($quack['created_at'])) ?></span>
                </div>
                <p class="mt-2 fs-5"><?= htmlspecialchars($quack['content']) ?></p>

                <!-- eventuella bilder för inlägget -->
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
                        <?= $quack['comment_count'] ?>
                    </span>
                    <span class="action-icon">
                    <svg class="quack-icon" fill="#000000" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"><path d="M5 4a2 2 0 0 0-2 2v6H0l4 4 4-4H5V6h7l2-2H5zm10 4h-3l4-4 4 4h-3v6a2 2 0 0 1-2 2H6l2-2h7V8z"></path></g></svg>
                         <?= $quack['requack_count'] ?>
                    </span>
                    <span class="action-icon">
                    <svg class="quack-icon" viewBox="0 -0.5 21 21" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" fill="#000000"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <title>like [#1385]</title> <desc>Created with Sketch.</desc> <defs> </defs> <g id="Page-1" stroke="none" stroke-width="1" fill="none" fill-rule="evenodd"> <g id="Dribbble-Light-Preview" transform="translate(-259.000000, -760.000000)" fill="#000000"> <g id="icons" transform="translate(56.000000, 160.000000)"> <path d="M203,620 L207.200006,620 L207.200006,608 L203,608 L203,620 Z M223.924431,611.355 L222.100579,617.89 C221.799228,619.131 220.638976,620 219.302324,620 L209.300009,620 L209.300009,608.021 L211.104962,601.825 C211.274012,600.775 212.223214,600 213.339366,600 C214.587817,600 215.600019,600.964 215.600019,602.153 L215.600019,608 L221.126177,608 C222.97313,608 224.340232,609.641 223.924431,611.355 L223.924431,611.355 Z" id="like-[#1385]"> </path> </g> </g> </g> </g></svg>
                         <?= $quack['like_count'] ?>
                    </span>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php
require_once __DIR__ . '/../includes/footer.php';