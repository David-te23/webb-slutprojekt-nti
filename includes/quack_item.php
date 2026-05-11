<?php
$isRequack = ($quack['content'] === null && $quack['parent_id'] !== null);
$targetId = $isRequack ? $quack['parent_id'] : $quack['id'];

// Mappa upp display-datan
$display = $isRequack ? [
    'id' => $quack['parent_id'],
    'user_id' => $quack['orig_user_id'],
    'content' => $quack['orig_content'],
    'display_name' => $quack['orig_display_name'],
    'username' => $quack['orig_username'],
    'profile_image' => $quack['orig_profile_image'],
    'created_at' => $quack['orig_created_at']
] : $quack;

// Hantera bilderna/videor
$itemImages = $quack['images'] ?? [];
if (empty($itemImages) && !isset($quack['images'])) {
    global $dbconn;
    $imgStmt = $dbconn->prepare("SELECT image_path FROM quack_images WHERE quack_id = ?");
    $imgStmt->execute([$targetId]);
    $itemImages = $imgStmt->fetchAll(PDO::FETCH_ASSOC);
}

global $images;
$images = $itemImages;

$isSinglePage = (basename($_SERVER['PHP_SELF']) === 'quack.php');

// Dynamiska klasser baserat på sida
$cardClass = $isSinglePage ? 'p-4 rounded shadow' : 'p-3 rounded shadow-sm mb-3 quack-card-clickable cursor-pointer';
$textClass = $isSinglePage ? 'fs-4' : 'fs-5';

// Kollar om klicket träffar en länk, en svg eller ligger inuti en interaktions-ikon (.action-icon)
$cardClickAction = !$isSinglePage ? "onclick=\"
    const target = event.target;
    if (
        target.closest('a') || 
        target.closest('button') || 
        target.closest('svg') || 
        target.closest('.action-icon')
    ) {
        return; 
    }
    window.location.href='quack.php?id=" . $quack['id'] . "';
\"" : "";
?>

<div class="quack-card bg-white <?= $cardClass ?>" <?= $cardClickAction ?>>
    <?php if($isRequack) : ?>
        <div class="text-muted small mb-2 ms-5 fw-bold">
            <svg class="quack-icon" fill="#000000" width="16" height="16" viewBox="0 0 20 20" xmlns="http://w3.org">
                <path d="M5 4a2 2 0 0 0-2 2v6H0l4 4 4-4H5V6h7l2-2H5zm10 4h-3l4-4 4 4h-3v6a2 2 0 0 1-2 2H6l2-2h7V8z"></path>
            </svg>
            <a href="profile.php?id=<?= $quack['user_id'] ?>" class="text-muted text-decoration-none hover-underline">
                <?= htmlspecialchars($quack['display_name']) ?>
            </a> requacked
        </div>
    <?php endif; ?>

    <div class="d-flex gap-3 <?= $isSinglePage ? 'mb-3' : '' ?>">
        <!-- Profilbild -->
        <a href="profile.php?id=<?= $display['user_id'] ?>" class="position-relative z-2">
            <img src="<?= getPfpPath($display['profile_image']) ?>" class="profile-pic-placeholder bg-secondary-subtle" alt="Profile image">
        </a>

        <div class="flex-grow-1">
            <!-- Namn- och tidssektion -->
            <div class="d-flex align-items-center gap-2 position-relative z-2">
                <a href="profile.php?id=<?= $display['user_id'] ?>" class="text-decoration-none text-dark d-flex align-items-center gap-2">
                    <?php if ($isSinglePage): ?>
                        <div>
                            <h5 class="fw-bold mb-0"><?= htmlspecialchars($display['display_name']) ?></h5>
                            <p class="text-muted small mb-0">@<?= htmlspecialchars($display['username']) ?></p>
                        </div>
                    <?php else: ?>
                        <span class="fw-bold hover-underline"><?= htmlspecialchars($display['display_name']) ?></span>
                        <span class="text-muted">@<?= htmlspecialchars($display['username']) ?></span>
                    <?php endif; ?>
                </a>
                <?php if (!$isSinglePage): ?>
                    <span class="text-muted">&bull; <span title="<?= date('Y-m-d H:i', strtotime($display['created_at'])) ?>"><?= formatQuackTime($display['created_at']) ?></span></span>
                <?php endif; ?>
            </div>

            <!-- Quack innehåll -->
            <div class="quack-body-content">
                <!-- Textinnehåll -->
                <p class="mt-2 mb-0 <?= $textClass ?>">
                    <?php
                    $safeContent = htmlspecialchars($display['content'] ?? '');
                    $formattedContent = preg_replace(
                        '/#(\w+)/u', 
                        '<a href="search.php?query=%23$1" class="hashtag-link">#$1</a>', 
                        $safeContent
                    );
                    echo nl2br($formattedContent);
                    ?>
                </p>

                <!-- Bild- och videogalleri -->
                <?php if (!empty($itemImages)) : 
                    $imgCount = count($itemImages);
                    $gridClass = ($imgCount > 4) ? 'grid-4' : 'grid-' . $imgCount;
                ?>
                <div class="quack-image-gallery <?= $gridClass ?> mt-3 mb-3"
                     <?= !$isSinglePage ? 'onclick="if(event.target.tagName === \'VIDEO\') { event.stopPropagation(); }"' : '' ?>>
                    <?php foreach ($itemImages as $index => $image): ?>
                        <div class="gallery-item <?= $isSinglePage ? 'cursor-pointer' : '' ?>"
                             <?= $isSinglePage ? "data-bs-toggle=\"modal\" data-bs-target=\"#imageModal\" data-bs-slide-to=\"$index\"" : "" ?>>
                            <?php 
                            $fileExt = pathinfo($image['image_path'], PATHINFO_EXTENSION);
                            $videoExts = ['mp4', 'webm', 'ogg', 'mov'];
                            $isVid = in_array(strtolower($fileExt), $videoExts);
                            
                            if ($isVid): ?>
                                <!-- Preload metadata och #t=0.1 fixar en preview-bild i flödet -->
                                <video src="../<?= htmlspecialchars($image['image_path']) ?>#t=0.1" 
                                       <?= $isSinglePage ? 'controls' : 'muted loop' ?> 
                                       preload="metadata"
                                       class="rounded w-100 shadow-sm quack-video-link"
                                       >
                                </video>
                            <?php else: ?>
                                <img src="../<?= htmlspecialchars($image['image_path']) ?>" class="img-fluid rounded shadow-sm" alt="Quack media">
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>

            <!-- Fullständigt datum i botten (endast på enskild sida) -->
            <?php if ($isSinglePage): ?>
                <hr>
                <div class="d-flex justify-content-between text-muted small mb-2">
                    <span><?= date('H:i • M j, Y', strtotime($display['created_at'])) ?></span>
                </div>
            <?php endif; ?>

            <!-- Interaktionsikoner (Like, Reply, Requack) -->
            <div>
                <?php include __DIR__ . '/quack_actions.php'; ?>
            </div>
        </div>
    </div>
</div>
