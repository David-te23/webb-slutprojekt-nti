<?php
if (!isset($isAdmin)) {
    $isAdmin = (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1);
}
?>
 
 <?php if (empty($quacks)): ?>
    <div class="text-center p-5 bg-white rounded shadow-sm mt-3">
        <div class="fs-1 mb-3">🐥</div>
        <h4 class="fw-bold">It's quiet in here...</h4>
        <p class="text-muted">
            <?php if (isset($_GET['filter']) && $_GET['filter'] === 'following'): ?>
                You aren't following anyone yet. Follow some people to see their quacks here!
                <?php else: ?>
                    No quacks found. Be the first one to quack!
                <?php endif; ?>
        </p>
    </div>
    <?php else: ?>
        <!-- Quack inlägg -->
        <?php foreach ($quacks as $quack) :
            
            $isRequack = ($quack['content'] === null && $quack['parent_id'] !== null);

            $targetId = $isRequack ? $quack['parent_id'] : $quack['id'];

            $display = $isRequack ? [
                'id' => $quack['parent_id'],
                'user_id' => $quack['orig_user_id'],
                'content' => $quack['orig_content'],
                'display_name' => $quack['orig_display_name'],
                'username' => $quack['orig_username'],
                'profile_image' => $quack['orig_profile_image'],
                'created_at' => $quack['created_at'] //när requacked gjordes
            ] : $quack;
            ?>
        <div class="quack-card bg-white p-3 rounded shadow-sm mb-3">
            <?php if($isRequack) : ?>
                <div class="text-muted small mb-2 ms-5 fw-bold">
                    <svg class="quack-icon" fill="#000000" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"><path d="M5 4a2 2 0 0 0-2 2v6H0l4 4 4-4H5V6h7l2-2H5zm10 4h-3l4-4 4 4h-3v6a2 2 0 0 1-2 2H6l2-2h7V8z"></path></g></svg>
                    <?= htmlspecialchars($quack['display_name']) ?> requacked
                </div>
                <?php endif; ?>
            <div class="d-flex gap-3">
                <a href="profile.php?id=<?= $display['user_id'] ?>" class="position-relative z-2">
                    <img src="<?= getPfpPath($display['profile_image']) ?>" class="profile-pic-placeholder bg-secondary-subtle">
                </a>
                <div class="flex-grow-1">
                    <!-- Namn-sektionen -->
                    <div class="d-flex align-items-center gap-2 position-relative z-2">
                        <a href="profile.php?id=<?= $display['user_id'] ?>" class="text-decoration-none text-dark d-flex align-items-center gap-2">
                            <span class="fw-bold hover-underline"><?= htmlspecialchars($display['display_name']) ?></span>
                            <span class="text-muted">@<?= htmlspecialchars($display['username']) ?></span>
                        </a>
                        <span class="text-muted">&bull; <span title="<?= date('Y-m-d H:i', strtotime($display['created_at'])) ?>"><?= formatQuackTime($display['created_at']) ?></span></span>
                    </div>

                    <!-- KLICKBAR DEL: Text och Bilder -->
                    <div class="quack-content-clickable cursor-pointer" onclick="window.location.href='quack.php?id=<?= $quack['id'] ?>'">
                        <p class="mt-1 mb-0 fs-5">
                            <?php
                             // Först htmlspecialchars för säkerhet
                            $safeContent = htmlspecialchars($display['content'] ?? '');
                            
                            // letar efter hashtags och gör dem till länkar
                            // länkar till search.php med hashtagen som query
                            $formattedContent = preg_replace(
                                '/#(\w+)/u', 
                                '<a href="search.php?query=%23$1" class="hashtag-link" onclick="event.stopPropagation();">#$1</a>', 
                                $safeContent
                            );

                            // Skriv ut med nl2br för att bevara radbrytningar
                            echo nl2br($formattedContent);
                             ?>
                        </p>

                        <?php if (!empty($quack['images'])) : 
                            $imgCount = count($quack['images']);
                            $gridClass = ($imgCount > 4) ? 'grid-4' : 'grid-' . $imgCount;
                        ?>
                        <div class="quack-image-gallery <?= $gridClass ?> mt-2">
                            <?php foreach ($quack['images'] as $image): ?>
                                <div class="gallery-item">
                                    <?php if (str_contains($image['file_type'], 'video')): ?>
                                        <video src="../<?= htmlspecialchars($image['image_path']) ?>"></video>
                                    <?php else: ?>
                                        <img src="../<?= htmlspecialchars($image['image_path']) ?>">
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- Interaktionsikoner -->
                    <?php include __DIR__ . '/quack_actions.php'; ?>

                </div>
            </div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>