<?php
$pageTitle = "Quack";
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/quack_time_formatter.php';

$quackId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Hämta det specifika inlägget med namngivna parametrar (:my_id och :quack_id)
$stmt = $dbconn->prepare("
    SELECT 
        q.id, q.content, q.created_at, q.user_id, q.parent_id,
        u.username, u.display_name, u.profile_image,
        -- Originaldata om det är en requack
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
        -- Check för status (Använder nu :my_id istället för ?)
        EXISTS(SELECT 1 FROM likes WHERE quack_id = COALESCE(q.parent_id, q.id) AND user_id = :my_id) as user_liked,
        EXISTS(SELECT 1 FROM quacks WHERE parent_id = COALESCE(q.parent_id, q.id) AND user_id = :my_id AND content IS NULL) as user_requacked
    FROM quacks q
    JOIN users u ON q.user_id = u.id
    LEFT JOIN quacks orig_q ON q.parent_id = orig_q.id
    LEFT JOIN users orig_u ON orig_q.user_id = orig_u.id
    WHERE q.id = :quack_id -- Använder nu :quack_id istället för ?
");

$stmt->execute([
    'my_id' => $_SESSION['user_id'] ?? 0,
    'quack_id' => $quackId
]);
$quack = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$quack) {
    echo "<div class='container mt-5'><h2 class='text-white'>Quack not found.</h2></div>";
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

// Om inlägget är en requack (har parent_id), sätt original-ID som mål för kommentarer
$commentTargetId = !empty($quack['parent_id']) ? $quack['parent_id'] : $quack['id'];



// Hämta bilder till inlägget (Om det är en requack hämtas bilderna från originalinlägget)
$targetId = $quack['parent_id'] ?? $quack['id'];
$imgStmt = $dbconn->prepare("SELECT image_path FROM quack_images WHERE quack_id = ?");
$imgStmt->execute([$targetId]);
$images = $imgStmt->fetchAll(PDO::FETCH_ASSOC);
?>


<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-12 custom-sidebar-card p-3">
           <!-- Tillbaka-knapp -->
            <button onclick="history.back()" class="btn btn-link text-black bg-white text-decoration-none mb-3 d-flex align-items-center gap-2 fw-bold shadow-sm rounded-pill px-3">
                <svg xmlns="http://w3.org" width="20" height="20" fill="currentColor" viewBox="0 0 16 16">
                    <path fill-rule="evenodd" d="M15 8a.5.5 0 0 0-.5-.5H2.707l3.147-3.146a.5.5 0 1 0-.708-.708l-4 4a.5.5 0 0 0 0 .708l4 4a.5.5 0 0 0 .708-.708L2.707 8.5H14.5A.5.5 0 0 0 15 8z"/>
                </svg>
                Back
            </button>


            <?php include __DIR__ . '/../includes/quack_item.php' ?>

                <!-- Kommentarssektion -->
                <div class="comment-section mt-4">
                    <h5 class="text-black mb-3">Replies</h5>
                    
                    <!-- Formulär för att svara -->
                    <div class="bg-white p-3 rounded-4 shadow-sm mb-4">
                        <form id="commentForm" action="actions/process_comment.php" method="POST">
                            <input type="hidden" name="quack_id" value="<?= $commentTargetId ?>">
                            <div class="d-flex gap-3">
                                <img src="<?= getPfpPath($currentUser['profile_image'] ?? 'default_pfp.jpg') ?>" alt="Profile image"
                                    width="45" height="45" class="rounded-circle bg-secondary-subtle">
                                <div class="flex-grow-1">
                                    <textarea name="comment" id="reply-textarea" class="form-control border-0 fs-5" 
                                            placeholder="Quack your reply!" rows="1" required></textarea>
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

                        $commentStmt->execute([$commentTargetId]);
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
                                            <img src="<?= getPfpPath($comment['profile_image']) ?>" alt="Profilbild"
                                                width="40" height="40" class="rounded-circle">
                                        </a>
                                        <div class="flex-grow-1">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div>
                                                    <span class="fw-bold text-dark"><?= htmlspecialchars($comment['display_name']) ?></span>
                                                    <span class="text-muted small">@<?= htmlspecialchars($comment['username']) ?></span>
                                                </div>
                                                
                                                <div class="d-flex align-items-center gap-2">
                                                    <span class="text-muted small"><?= formatQuackTime($comment['created_at']) ?></span>
                                                    
                                                    <!-- RADERA-KNAPP FÖR KOMMENTAR -->
                                                    <?php if ($comment['user_id'] == $_SESSION['user_id'] || $isAdmin): ?>
                                                        <button class="btn btn-link text-danger p-0 delete-comment-btn" 
                                                                data-comment-id="<?= $comment['id'] ?>" 
                                                                title="Delete comment"
                                                                style="line-height: 0;">
                                                            <svg xmlns="http://w3.org" width="14" height="14" fill="currentColor" class="bi bi-trash" viewBox="0 0 16 16">
                                                                <path d="M5.5 5.5A.5.5 0 0 1 6 6v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm2.5 0a.5.5 0 0 1 .5.5v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm3 .5a.5.5 0 0 0-1 0v6a.5.5 0 0 0 1 0V6z"/>
                                                                <path fill-rule="evenodd" d="M14.5 3a1 1 0 0 1-1 1H13v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V4h-.5a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1H6a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1h3.5a1 1 0 0 1 1 1v1zM4.118 4 4 4.059V13a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V4.059L11.882 4H4.118zM2.5 3V2h11v1h-11z"/>
                                                            </svg>
                                                        </button>
                                                    <?php endif; ?>
                                                </div>
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
                                <img src="../<?= htmlspecialchars($img['image_path']) ?>" class="d-block w-100 rounded shadow fullscreen-img" alt="Fullscreen image">
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

<!-- Modal för att bekräfta borttagning av kommentar -->
<div class="modal fade" id="deleteCommentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content border-0 shadow-lg rounded-4 bg-dark text-white">
            <div class="modal-body text-center p-4">
                <h5 class="fw-bold mb-3">Delete Comment?</h5>
                <p class="text-white-50 small mb-4">This action cannot be undone.</p>
                <div class="d-grid gap-2">
                    <button type="button" id="confirmDeleteCommentBtn" class="btn btn-danger rounded-pill fw-bold">Delete</button>
                    <button type="button" class="btn btn-outline-light rounded-pill fw-bold" data-bs-dismiss="modal">Cancel</button>
                </div>
            </div>
        </div>
    </div>
</div>


<?php require_once __DIR__ . '/../includes/footer.php'; ?>
