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
<div class="d-flex justify-content-between align-items-center mt-3 text-muted position-relative z-2">
    <!-- Vänster sida: Interaktionsikoner -->
    <div class="d-flex gap-3 gap-md-5">
        
        <!-- kommentar -->
        <span class="action-icon d-flex align-items-center gap-1">
            <svg class="quack-icon" width="20" height="20" viewBox="0 0 32 32" version="1.1" xmlns="http://www.w3.org/2000/svg" fill="currentColor">
                <path d="M388.667,257 L367.333,257 C364.388,257 362,259.371 362,262.297 L362,279.187 C362,282.111 364.055,284 367,284 L373.639,284 L378,289.001 L382.361,284 L389,284 C391.945,284 394,282.111 394,279.187 L394,262.297 C394,259.371 391.612,257 388.667,257" transform="translate(-362, -257)"></path>
            </svg>
            <span class="align-middle"><?= $quack['comment_count'] ?></span>
        </span>

        <!-- requack -->
        <span class="action-icon requack-btn <?= $quack['user_requacked'] ? 'is-requacked' : '' ?>" data-quack-id="<?= $targetId ?>">
            <svg class="quack-icon" width="20" height="20" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                <path d="M5 4a2 2 0 0 0-2 2v6H0l4 4 4-4H5V6h7l2-2H5zm10 4h-3l4-4 4 4h-3v6a2 2 0 0 1-2 2H6l2-2h7V8z"></path>
            </svg>
            <span class="requack-count align-middle"><?= $quack['requack_count'] ?></span>
        </span>

        <!-- like -->
        <span class="action-icon like-btn <?= $quack['user_liked'] ? 'is-liked' : '' ?>" data-quack-id="<?= $targetId ?>">
            <svg class="quack-icon" width="20" height="20" viewBox="0 -0.5 21 21" version="1.1" xmlns="http://www.w3.org/2000/svg" fill="currentColor">
                <path d="M203,620 L207.200006,620 L207.200006,608 L203,608 L203,620 Z M223.924431,611.355 L222.100579,617.89 C221.799228,619.131 220.638976,620 219.302324,620 L209.300009,620 L209.300009,608.021 L211.104962,601.825 C211.274012,600.775 212.223214,600 213.339366,600 C214.587817,600 215.600019,600.964 215.600019,602.153 L215.600019,608 L221.126177,608 C222.97313,608 224.340232,609.641 223.924431,611.355 L223.924431,611.355 Z" transform="translate(-203, -600)"></path>
            </svg>
            <span class="like-count align-middle"><?= $quack['like_count'] ?></span>
        </span>
    </div>

    <!-- Höger sida: Delete -->
    <?php if ($quack['user_id'] == $_SESSION['user_id'] || $isAdmin): ?>
        <span class="action-icon delete-quack-btn text-danger" data-quack-id="<?= $quack['id'] ?>" title="Delete Quack">
            <svg width="18" height="18" fill="currentColor" class="bi bi-trash" viewBox="0 0 16 16">
                <path d="M5.5 5.5A.5.5 0 0 1 6 6v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5m2.5 0a.5.5 0 0 1 .5.5v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5m3 .5a.5.5 0 0 0-1 0v6a.5.5 0 0 0 1 0z"/>
                <path d="M14.5 3a1 1 0 0 1-1 1H13v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V4h-.5a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1H6a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1h3.5a1 1 0 0 1 1 1zM4.118 4 4 4.059V13a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V4.059L11.882 4zM2.5 3h11V2h-11z"/>
            </svg>
        </span>
    <?php endif; ?>
</div>

                </div>
            </div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>