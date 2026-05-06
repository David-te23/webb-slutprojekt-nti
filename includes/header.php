<?php 
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '../../database/db.php';

function getPfpPath($fileName) {
    if (!$fileName || $fileName === 'default_pfp.jpg') {
        return "../public/images/default_pfp.jpg";
    }
    return "../uploads/pfp/" . $fileName;
}

$currentPage = basename($_SERVER['PHP_SELF']);
$publicPages = ['login.php', 'register.php'];

if (!isset($_SESSION['user_id']) && !in_array($currentPage, $publicPages)) {
    header("Location: login.php");
    exit;
}

$currentUser = null;
$unreadCount = 0; // standardvärde för alla notiser
$unreadMessages = 0; //standardvärde för meddelande notiser

if (isset($_SESSION['user_id'])) {
    $userId = $_SESSION['user_id'];

    // Hämta användarinformation
    $stmt = $dbconn->prepare("SELECT * FROM users WHERE id =?");
    $stmt->execute([$_SESSION['user_id']]);
    $currentUser = $stmt->fetch(PDO::FETCH_ASSOC);

    // Hämta antal olästa notiser för nav-bubblan
    $stmtCount = $dbconn->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
    $stmtCount->execute([$_SESSION['user_id']]);
    $unreadCount = (int)$stmtCount->fetchColumn();

    $stmtMsgCount = $dbconn->prepare("SELECT COUNT(*) FROM messages WHERE receiver_id = ? AND is_read = 0");
    $stmtMsgCount->execute([$userId]);
    $unreadMessages = (int)$stmtMsgCount->fetchColumn();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? htmlspecialchars($pageTitle) : 'Quacker' ?></title>
    <link rel="icon" type="image/svg+xml" href="../public/images/QuackerLogo.svg">

    <!-- CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="css/profile.css">
    <link rel="stylesheet" href="css/messages.css">
    <link rel="stylesheet" href="css/notifications.css">
    
    <?php 
    if ($currentPage == 'login.php' || $currentPage == 'register.php'): ?>
    <link rel="stylesheet" href="css/loginregister.css">
    <?php endif; ?>

    <!-- JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
    <script type="module" src="https://cdn.jsdelivr.net/npm/emoji-picker-element@^1/index.js"></script>
    <script src="js/app.js" defer></script>
    <script src="js/index.js" defer></script>
    <script src="js/follow_ajax.js" defer></script>
    <script src="js/quacktivity.js" defer></script>
    <script src="js/messages.js" defer></script>
    
</head>
<body>
<header class="site-header" id="siteheader">
    <div class="header-container container-fluid d-flex align-items-center justify-content-between px-3">
        <!-- vänster sida av header: nav + sök-->
        <div class="nav-container d-flex ps-0 flex-grow-1 flex-b-0">
            <?php require __DIR__ . '/nav.php'; ?>
            
            <!-- sök ikon (visas bara på mobil) -->
            <button class="btn text-white d-lg-none ms-2 p-0" id="mobileSearchBtn" type="button">
            <svg xmlns="http://w3.org" width="24" height="24" fill="currentColor" class="bi bi-search" viewBox="0 0 16 16">
                    <path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001c.03.04.062.078.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1.007 1.007 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0z"/>
                </svg>
            </button>
            <input type="search" class="form-control mobile-search-input" placeholder="Search Quacker...">
            <button class="btn-close btn-close-white d-none" id="closeSearchBtn"></button>
        </div>

        <!-- mitten av header: quacker logga -->
        <div class="text-center header-logo-container">
        <a href="index.php">
            <img src="../public/images/QuackerLogo.svg" alt="Quacker Logo" class="header-img">
        </a>
        </div>

        <!-- höger sida: sökfält + profil -->
        <div class="search-profile-container flex-grow-1 d-flex justify-content-end align-items-center flex-b-0">
            <!-- döljs på mobil (d-none), visas på desktop (d-lg-block) -->
            <input type="search" name="" id="header-search" placeholder="Search Quacker" class="form-control d-none d-lg-block me-3 qSearchBar">

            <a href="profile.php?id=<?= $currentUser['id'] ?>" class="profile-button p-0 m-0">
                <img src="<?= getPfpPath($currentUser['profile_image'] ?? 'default_pfp.jpg') ?>"
                 alt="Profile" class="header-img">
            </a>
        </div>
    </div>
</header>

<div class="container py-4">
    <div class="row gx-4">

    <?php 
    $showSidebar = ($currentPage !== 'messages.php' && $currentPage !== 'login.php' && $currentPage !== 'register.php');
            
    if ($showSidebar): ?>
        <!-- Sidebar: Trending & Recommendations -->
        <aside class="col-lg-3 col-md-4 d-none d-md-block">
            <div class="trending-section mb-4 p-3 custom-sidebar-card shadow-sm">
                <h5 class="fw-bold mb-3">Trending now</h5>
                <div class="d-grid gap-2">
                    <a href="#" class="btn btn-trending shadow-sm bg-white">#trend</a>
                    <a href="#" class="btn btn-trending shadow-sm bg-white">#quackify</a>
                    <a href="#" class="btn btn-trending shadow-sm bg-white">#BTC</a>
                </div>
            </div>

            <div class="suggestions-section p-3 custom-sidebar-card shadow-sm">
                <h5 class="fw-bold mb-3">You might know</h5>
                <div class="suggestion-item d-flex align-items-center mb-3 p-2 rounded shadow-sm bg-white">
                    <div class="profile-pic-placeholder me-2"></div>
                    <div class="user-info">
                        <div class="fw-bold lh-1 text-truncate-custom">George</div>
                        <small class="text-muted text-truncate-custom">@george123</small>
                    </div>
                </div>
                <div class="suggestion-item d-flex align-items-center p-2 rounded shadow-sm bg-white">
                    <div class="profile-pic-placeholder me-2"></div>
                    <div class="user-info">
                        <div class="fw-bold lh-1 text-truncate-custom">DuckGod</div>
                        <small class="text-muted text-truncate-custom">@duckmas...</small>
                    </div>
                </div>
            </div>
        </aside>

        <!-- Main column -->
        <main class="col-lg-9 col-md-8">
    <?php else : ?>
        <main class="col-12">
    <?php endif; ?>
