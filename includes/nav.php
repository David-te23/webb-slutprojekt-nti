<nav class="site-nav navbar navbar-expand-lg navbar-dark p-0">
    <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#quackerNav">
        <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="quackerNav">
        <ul class="navbar-nav gap-lg-4 <?= !$isAdmin ? 'nav-not-admin' : '' ?>">
            <li class="nav-item">
                <a class="nav-link text-white" href="index.php">Feed</a>
            </li>
            <li class="nav-item position-relative">
                <a class="nav-link text-white d-flex align-items-center" href="notifications.php">
                    Notifications
                    <?php if (isset($unreadCount) && $unreadCount > 0): ?>
                        <span id="notif-badge" class="badge rounded-pill bg-danger ms-2">
                            <?= $unreadCount ?>
                        </span>
                    <?php else: ?>
                        <span id="notif-badge" class="badge rounded-pill bg-danger ms-2 d-none"></span>
                    <?php endif; ?>
                </a>
            </li>
            <li class="nav-item position-relative">
                <a class="nav-link text-white d-flex align-items-center" href="messages.php">
                    Messages
                    <?php if (isset($unreadMessages) && $unreadMessages > 0): ?>
                        <span id="msg-badge" class="badge rounded-pill bg-danger ms-2">
                            <?= $unreadMessages ?>
                        </span>
                    <?php else: ?>
                        <span id="msg-badge" class="badge rounded-pill bg-danger ms-2 d-none"></span>
                    <?php endif; ?>
                </a>
            </li>
            <?php if ($isAdmin): ?>
                <li class="nav-item">
                    <a class="nav-link text-warning fw-bold" href="admin.php">Admin Panel</a>
                </li>
            <?php endif; ?>
        </ul>
    </div>
</nav>
