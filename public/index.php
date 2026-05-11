<?php
$pageTitle = "Home"; // <-- set dynamic page title
require_once __DIR__ . '/../includes/header.php';

$current_user_id = $currentUser['id'] ?? 0;
require_once __DIR__ . '/../includes/quack_feed_logic.php';

// Hämta bilder (för både vanliga och originalet i requacks)
foreach ($quacks as &$quack) {
    // Om det är en requack, hämta bilderna för originalet (parent_id)
    $targetId = $quack['parent_id'] ?? $quack['id'];
    $imgStmt = $dbconn->prepare("SELECT image_path, file_type FROM quack_images WHERE quack_id = ?");
    $imgStmt->execute([$targetId]);
    $quack['images'] = $imgStmt->fetchAll(PDO::FETCH_ASSOC);
}
unset($quack);

require_once __DIR__ . '/../includes/quack_time_formatter.php';
?>

<div class="p-3 rounded shadow-sm feed-container">
    <div class="d-flex flex-row justify-content-center gap-4 sticky-top mb-3">
        <!-- All Quacks tab -->
        <button class=" feed-tab active p-3 bg-light rounded shadow w-50 d-flex justify-content-center border-0"
                        data-filter="all">
            <span class="m-0 fw-bold">All Quacks</span>
        </button>
        <!-- Following Quacks tab -->
        <button class="feed-tab p-3 bg-light rounded shadow w-50 d-flex justify-content-center border-0"
                        data-filter="following">
            <span class="m-0 fw-bold">Following</span>
        </button>
    </div>    

    <!-- New Quack container -->
    <div class="create-quack-card bg-white p-3 rounded shadow-sm mb-4">
        <div class="d-flex gap-3">
            <img src="<?= getPfpPath($currentUser['profile_image']) ?>" class="profile-pic-placeholder" alt="Profile image">
            <form action="actions/process_quack.php" method="POST" enctype="multipart/form-data" class="flex-grow-1">
                <textarea id="quack-textarea" name="quack_content" rows="1" class="form-control border-0 fs-5 mb-2" placeholder="What is quacking?" required maxlength="280"></textarea>
                <!-- selected img preview-->
                <div id="img-preview-container" class="d-flex flex-wrap gap-2 mb-2"></div>
                <div class="d-flex justify-content-between align-items-center pt-2 border-top">
                    <div class="d-flex gap-3">
                        <!-- img upload -->
                        <label for="quack-images" class="btn btn-link p-0 text-success new-quack-icon">
                            <svg viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <path fill-rule="evenodd" clip-rule="evenodd" d="M9 0H2V16H14V5L9 0ZM7 6V8H5V10H7V12H9V10H11V8H9V6H7Z" fill="#000000"></path> </g></svg>
                            <input type="file" id="quack-images" name="quack_images[]" accept="image/*,video/*" class="d-none" multiple>
                        </label>
                        <!-- emoji btn -->
                        <button type="button" id="emoji-trigger" class="btn btn-link p-0 text-success new-quack-icon">
                            <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <path d="M8.9126 15.9336C10.1709 16.249 11.5985 16.2492 13.0351 15.8642C14.4717 15.4793 15.7079 14.7653 16.64 13.863" stroke="#1C274C" stroke-width="1.5" stroke-linecap="round"></path> <ellipse cx="14.5094" cy="9.77405" rx="1" ry="1.5" transform="rotate(-15 14.5094 9.77405)" fill="#1C274C"></ellipse> <ellipse cx="8.71402" cy="11.3278" rx="1" ry="1.5" transform="rotate(-15 8.71402 11.3278)" fill="#1C274C"></ellipse> <path d="M20.7964 9.643C21.9075 13.7897 22.4631 15.863 21.5201 17.4964C20.577 19.1298 18.5037 19.6853 14.357 20.7964C10.2103 21.9075 8.13698 22.4631 6.50359 21.5201C4.87021 20.577 4.31466 18.5037 3.20356 14.357C2.09246 10.2103 1.53691 8.13698 2.47995 6.50359C3.42298 4.87021 5.49632 4.31466 9.643 3.20356C13.7897 2.09246 15.863 1.53691 17.4964 2.47995C18.5048 3.06212 19.1023 4.07505 19.6734 5.74061" stroke="#1C274C" stroke-width="1.5" stroke-linecap="round"></path> <path d="M13 16.0004L13.478 16.9742C13.8393 17.7104 14.7249 18.0198 15.4661 17.6689C16.2223 17.311 16.5394 16.4035 16.1708 15.6524L15.7115 14.7168" stroke="#1C274C" stroke-width="1.5"></path> </g></svg>
                        </button>
                        <div id="picker-container" class="emoji-picker-container">
                            <emoji-picker id="quack-picker"></emoji-picker>
                        </div>
                    </div>
                <button type="submit" class="btn btn-quack px-4 fw-bold shadow-sm">Quack!</button>
            </form>
            </div>
        </div>
    </div>

    <!-- Quack inlägg -->
    <div id="feed-container">
        <?php 
        require_once __DIR__ . '/../includes/quack_loop.php';
        ?>
    </div>
</div>
<?php
require_once __DIR__ . '/../includes/footer.php';