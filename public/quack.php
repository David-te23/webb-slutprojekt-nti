<?php
$pageTitle = "Quack";
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/quack_time_formatter.php';

$quackId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Hämta det specifika inlägget med användardata
$stmt = $dbconn->prepare("
    SELECT 
        q.id, q.content, q.created_at, q.user_id, q.parent_id,
        u.username, u.display_name, u.profile_image,
        -- Originaldata om det är en requack
        orig_q.content AS orig_content,
        orig_u.username AS orig_username,
        orig_u.display_name AS orig_display_name,
        orig_u.profile_image AS orig_profile_image,
        orig_u.id AS orig_user_id,
        -- Räknare (Peka på originalet om det är en requack)
        (SELECT COUNT(*) FROM likes WHERE quack_id = COALESCE(q.parent_id, q.id)) as like_count,
        (SELECT COUNT(*) FROM comments WHERE quack_id = COALESCE(q.parent_id, q.id)) as comment_count,
        (SELECT COUNT(*) FROM quacks WHERE parent_id = COALESCE(q.parent_id, q.id) AND content IS NULL) as requack_count,
        EXISTS(SELECT 1 FROM likes WHERE quack_id = COALESCE(q.parent_id, q.id) AND user_id = ?) as user_liked
    FROM quacks q
    JOIN users u ON q.user_id = u.id
    LEFT JOIN quacks orig_q ON q.parent_id = orig_q.id
    LEFT JOIN users orig_u ON orig_q.user_id = orig_u.id
    WHERE q.id = ?
");
$stmt->execute([$_SESSION['user_id'] ?? 0, $quackId]);
$quack = $stmt->fetch(PDO::FETCH_ASSOC);

// Bestäm vad som ska visas (precis som i index)
$isRequack = ($quack['content'] === null && $quack['parent_id'] !== null);
$display = $isRequack ? [
    'id' => $quack['parent_id'],
    'content' => $quack['orig_content'],
    'display_name' => $quack['orig_display_name'],
    'username' => $quack['orig_username'],
    'profile_image' => $quack['orig_profile_image'],
    'user_id' => $quack['orig_user_id'],
    'created_at' => $quack['created_at']
] : $quack;


if (!$display) {
    echo "<div class='container mt-5'><h2 class='text-white'>Quack not found.</h2></div>";
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

// Hämta bilder till inlägget
$imgStmt = $dbconn->prepare("SELECT image_path FROM quack_images WHERE quack_id = ?");
$imgStmt->execute([$quackId]);
$images = $imgStmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-12 custom-sidebar-card p-3">
            <!-- Tillbaka-knapp -->
            <button onclick="history.back()" class="btn btn-link text-black bg-white text-decoration-none mb-3">
                <i class="bi bi-arrow-left"></i> Back
            </button>

            <div class="quack-card bg-white p-4 rounded shadow">
                <div class="d-flex gap-3 mb-3">
                    <a href="profile.php?id=<?= $display['user_id'] ?>">
                    <img src="<?= getPfpPath($display['profile_image']) ?>" class="profile-pic-placeholder">
                    </a>
                    <div>
                        <h5 class="fw-bold mb-0"><?= htmlspecialchars($display['display_name']) ?></h5>
                        <p class="text-muted small">@<?= htmlspecialchars($display['username']) ?></p>
                    </div>
                </div>

                <p class="fs-4"><?= htmlspecialchars($display['content']) ?></p>

                
                <?php if (!empty($images)): 
                    $imgCount = count($images);
                    // Max 4 grid
                    $gridClass = ($imgCount > 4) ? 'grid-4' : 'grid-' . $imgCount;
                ?>
                    <div class="quack-image-gallery <?= $gridClass ?> mt-3 mb-3">
                        <?php foreach ($images as $index => $img): ?>
                            <div class="gallery-item cursor-pointer"
                                        data-bs-toggle="modal"
                                        data-bs-target="#imageModal"
                                        data-bs-slide-to="<?= $index ?>">
                                <!-- Kontrollera om det är video eller bild -->
                                <?php 
                                $fileExt = pathinfo($img['image_path'], PATHINFO_EXTENSION);
                                if (in_array($fileExt, ['mp4', 'webm'])): 
                                ?>
                                    <video src="../<?= htmlspecialchars($img['image_path']) ?>" controls class="rounded"></video>
                                <?php else: ?>
                                    <img src="../<?= htmlspecialchars($img['image_path']) ?>" class="img-fluid rounded">
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                <hr>
                <div class="d-flex justify-content-between text-muted">
                    <span><?= date('H:i • M j, Y', strtotime($display['created_at'])) ?></span>
                </div>
                <div class="d-flex gap-5 mt-3 text-muted">
                    <span class="action-icon d-flex align-items-center gap-1">
                    <svg class="quack-icon" viewBox="0 0 32 32" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:sketch="http://www.bohemiancoding.com/sketch/ns" fill="#000000"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <title>comment 5</title> <desc>Created with Sketch Beta.</desc> <defs> </defs> <g id="Page-1" stroke="none" stroke-width="1" fill="none" fill-rule="evenodd" sketch:type="MSPage"> <g id="Icon-Set-Filled" sketch:type="MSLayerGroup" transform="translate(-362.000000, -257.000000)" fill="#000000"> <path d="M388.667,257 L367.333,257 C364.388,257 362,259.371 362,262.297 L362,279.187 C362,282.111 364.055,284 367,284 L373.639,284 L378,289.001 L382.361,284 L389,284 C391.945,284 394,282.111 394,279.187 L394,262.297 C394,259.371 391.612,257 388.667,257" id="comment-5" sketch:type="MSShapeGroup"> </path> </g> </g> </g></svg>
                        <span class="align-middle"><?= $quack['comment_count'] ?></span>
                    </span>
                    <span class="action-icon requack-btn <?= $isRequack ? 'is-requacked' : '' ?>"
                                data-quack-id="<?= $display['id'] ?>">
                    <svg class="quack-icon" fill="#000000" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"><path d="M5 4a2 2 0 0 0-2 2v6H0l4 4 4-4H5V6h7l2-2H5zm10 4h-3l4-4 4 4h-3v6a2 2 0 0 1-2 2H6l2-2h7V8z"></path></g></svg>
                         <span class="align-middle"><?= $quack['requack_count'] ?></span>
                    </span>
                    <span class="action-icon like-btn <?= $quack['user_liked'] ? 'is-liked' : '' ?>" data-quack-id="<?= $quack['id'] ?>">
                    <svg class="quack-icon" viewBox="0 -0.5 21 21" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" fill="#000000"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <title>like [#1385]</title> <desc>Created with Sketch.</desc> <defs> </defs> <g id="Page-1" stroke="none" stroke-width="1" fill="none" fill-rule="evenodd"> <g id="Dribbble-Light-Preview" transform="translate(-259.000000, -760.000000)" fill="#000000"> <g id="icons" transform="translate(56.000000, 160.000000)"> <path d="M203,620 L207.200006,620 L207.200006,608 L203,608 L203,620 Z M223.924431,611.355 L222.100579,617.89 C221.799228,619.131 220.638976,620 219.302324,620 L209.300009,620 L209.300009,608.021 L211.104962,601.825 C211.274012,600.775 212.223214,600 213.339366,600 C214.587817,600 215.600019,600.964 215.600019,602.153 L215.600019,608 L221.126177,608 C222.97313,608 224.340232,609.641 223.924431,611.355 L223.924431,611.355 Z" id="like-[#1385]"> </path> </g> </g> </g> </g></svg>
                    <span class="like-count align-middle"><?= $quack['like_count'] ?></span>
                    </span>
                </div>
                <!-- Kommentarssektion -->
                <div class="comment-section mt-4">
                    <h5 class="text-black mb-3">Replies</h5>
                    
                    <!-- Formulär för att svara -->
                    <div class="bg-white p-3 rounded-4 shadow-sm mb-4">
                        <form action="actions/process_comment.php" method="POST">
                            <input type="hidden" name="quack_id" value="<?= $quack['id'] ?>">
                            <div class="d-flex gap-3">
                                <img src="<?= getPfpPath($currentUser['profile_image'] ?? 'default_pfp.jpg') ?>" 
                                    width="45" height="45" class="rounded-circle bg-secondary-subtle">
                                <div class="flex-grow-1">
                                    <textarea name="comment" id="reply-textarea" class="form-control border-0 fs-5" 
                                            placeholder="Quack your reply!" rows="2" required></textarea>
                                    <hr>
                                    <div class="d-flex justify-content-between align-items-center mt-2 position-relative">
                                        <button type="button" id="reply-emoji-trigger" class="btn btn-link p-0 text-success new-quack-icon">
                                            <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <path d="M8.9126 15.9336C10.1709 16.249 11.5985 16.2492 13.0351 15.8642C14.4717 15.4793 15.7079 14.7653 16.64 13.863" stroke="#1C274C" stroke-width="1.5" stroke-linecap="round"></path> <ellipse cx="14.5094" cy="9.77405" rx="1" ry="1.5" transform="rotate(-15 14.5094 9.77405)" fill="#1C274C"></ellipse> <ellipse cx="8.71402" cy="11.3278" rx="1" ry="1.5" transform="rotate(-15 8.71402 11.3278)" fill="#1C274C"></ellipse> <path d="M20.7964 9.643C21.9075 13.7897 22.4631 15.863 21.5201 17.4964C20.577 19.1298 18.5037 19.6853 14.357 20.7964C10.2103 21.9075 8.13698 22.4631 6.50359 21.5201C4.87021 20.577 4.31466 18.5037 3.20356 14.357C2.09246 10.2103 1.53691 8.13698 2.47995 6.50359C3.42298 4.87021 5.49632 4.31466 9.643 3.20356C13.7897 2.09246 15.863 1.53691 17.4964 2.47995C18.5048 3.06212 19.1023 4.07505 19.6734 5.74061" stroke="#1C274C" stroke-width="1.5" stroke-linecap="round"></path> <path d="M13 16.0004L13.478 16.9742C13.8393 17.7104 14.7249 18.0198 15.4661 17.6689C16.2223 17.311 16.5394 16.4035 16.1708 15.6524L15.7115 14.7168" stroke="#1C274C" stroke-width="1.5"></path> </g></svg>
                                        </button>
                                        <button type="submit" class="btn btn-quack rounded-pill px-4 fw-bold">Reply</button>
                                        <div id="reply-picker-container" class="emoji-picker-container">
                                        <emoji-picker id="reply-picker"></emoji-picker>
                                    </div>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>

                    <!-- Lista med befintliga kommentarer -->
                    <div class="comments-list">
                        <?php
                        // Hämta kommentarer för detta quack
                        $commentStmt = $dbconn->prepare("
                            SELECT c.*, u.username, u.display_name, u.profile_image 
                            FROM comments c 
                            JOIN users u ON c.user_id = u.id 
                            WHERE c.quack_id = ? 
                            ORDER BY c.created_at DESC
                        ");
                        $commentStmt->execute([$display['id']]);
                        $comments = $commentStmt->fetchAll(PDO::FETCH_ASSOC);

                        if (empty($comments)): ?>
                            <div class="text-center p-5 bg-dark rounded-4 border border-secondary">
                                <p class="text-white-50 mb-0">No one has quacked here yet...</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($comments as $comment): ?>
                                <div class="comment-card bg-white p-3 mb-2 rounded-4 shadow-sm border-start border-quack border-4">
                                    <div class="d-flex gap-3">
                                        <a href="profile.php?id=<?= $comment['user_id'] ?>">
                                            <img src="<?= getPfpPath($comment['profile_image']) ?>" 
                                                width="40" height="40" class="rounded-circle">
                                        </a>
                                        <div class="flex-grow-1">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div>
                                                    <span class="fw-bold text-dark"><?= htmlspecialchars($comment['display_name']) ?></span>
                                                    <span class="text-muted small">@<?= htmlspecialchars($comment['username']) ?></span>
                                                </div>
                                                <span class="text-muted small"><?= formatQuackTime($comment['created_at']) ?></span>
                                            </div>
                                            <p class="mb-0 text-dark mt-1"><?= htmlspecialchars($comment['content']) ?></p>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<!-- Modal för bildgalleri -->
<div class="modal fade" id="imageModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content bg-transparent border-0">
            <div class="modal-body p-0">
                <button type="button" class="btn-close btn-close-white position-absolute top-0 end-0 m-3 z-3" data-bs-dismiss="modal" aria-label="Close"></button>
                
                <div id="quackCarousel" class="carousel slide" data-bs-ride="false">
                    <div class="carousel-inner">
                        <?php foreach ($images as $index => $img): ?>
                            <div class="carousel-item <?= $index === 0 ? 'active' : '' ?>">
                                <img src="../<?= htmlspecialchars($img['image_path']) ?>" class="d-block w-100 rounded shadow" style="max-height: 85vh; object-fit: contain;">
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <?php if (count($images) > 1): ?>
                        <button class="carousel-control-prev" type="button" data-bs-target="#quackCarousel" data-bs-slide="prev">
                            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                        </button>
                        <button class="carousel-control-next" type="button" data-bs-target="#quackCarousel" data-bs-slide="next">
                            <span class="carousel-control-next-icon" aria-hidden="true"></span>
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>


<?php require_once __DIR__ . '/../includes/footer.php'; ?>
