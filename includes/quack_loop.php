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
                        <p class="mt-1 mb-0 fs-5"><?= htmlspecialchars($display['content'] ?? '') ?></p>

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

                    <!-- Interaktionsikoner (Z-index ser till att de går att klicka på) -->
                    <div class="d-flex gap-5 mt-3 text-muted position-relative z-2">
                        <!-- kommentar/requack/like-ikoner -->
                        <span class="action-icon d-flex align-items-center gap-1">
                            <svg class="quack-icon" viewBox="0 0 32 32" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:sketch="http://www.bohemiancoding.com/sketch/ns" fill="#000000"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <title>comment 5</title> <desc>Created with Sketch Beta.</desc> <defs> </defs> <g id="Page-1" stroke="none" stroke-width="1" fill="none" fill-rule="evenodd" sketch:type="MSPage"> <g id="Icon-Set-Filled" sketch:type="MSLayerGroup" transform="translate(-362.000000, -257.000000)" fill="#000000"> <path d="M388.667,257 L367.333,257 C364.388,257 362,259.371 362,262.297 L362,279.187 C362,282.111 364.055,284 367,284 L373.639,284 L378,289.001 L382.361,284 L389,284 C391.945,284 394,282.111 394,279.187 L394,262.297 C394,259.371 391.612,257 388.667,257" id="comment-5" sketch:type="MSShapeGroup"> </path> </g> </g> </g></svg>
                                <span class="align-middle"><?= $quack['comment_count'] ?></span>
                            </span>
                            <span class="action-icon requack-btn <?= $quack['user_requacked'] ? 'is-requacked' : '' ?>"
                                        data-quack-id="<?= $display['id'] ?>">
                            <svg class="quack-icon" fill="#000000" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"><path d="M5 4a2 2 0 0 0-2 2v6H0l4 4 4-4H5V6h7l2-2H5zm10 4h-3l4-4 4 4h-3v6a2 2 0 0 1-2 2H6l2-2h7V8z"></path></g></svg>
                                <span class="requack-count align-middle"><?= $quack['requack_count'] ?></span>
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