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
$publicPages = ['login.php', 'register.php', 'forgot_password.php', 'reset_password.php', 'info.php'];

if (!isset($_SESSION['user_id']) && !in_array($currentPage, $publicPages)) {
    header("Location: login.php");
    exit;
}

// Sidor som inloggade aldrig ska se
$authOnlyPages = ['login.php', 'register.php'];

if (isset($_SESSION['user_id']) && in_array($currentPage, $authOnlyPages)) {
    header("Location: index.php");
    exit;
}

$currentUser = null;
$unreadCount = 0; 
$unreadMessages = 0; 
$isAdmin = false; 

if (isset($_SESSION['user_id'])) {
    $userId = $_SESSION['user_id'];

    // Användarinformation
    $stmt = $dbconn->prepare("SELECT * FROM users WHERE id =?");
    $stmt->execute([$userId]);
    $currentUser = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$currentUser) {
        // Om sessionen finns men användaren inte hittas i databasen (om användaren tagits bort)
        session_unset();
        session_destroy();
        header("Location: login.php");
        exit;
    }

    // Notis-counts
    $stmtCount = $dbconn->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
    $stmtCount->execute([$userId]);
    $unreadCount = (int)$stmtCount->fetchColumn();

    $stmtMsgCount = $dbconn->prepare("SELECT COUNT(*) FROM messages WHERE receiver_id = ? AND is_read = 0");
    $stmtMsgCount->execute([$userId]);
    $unreadMessages = (int)$stmtMsgCount->fetchColumn();

    // Admin-status
    $isAdmin = isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1;

    // Trending & Suggestions (Hämtas bara om vi inte är på login/register)
    if (!in_array($currentPage, $publicPages)) {
        $trendingStmt = $dbconn->query("
            SELECT h.tag_name, COUNT(qh.quack_id) as usage_count 
            FROM hashtags h
            JOIN quack_hashtags qh ON h.id = qh.hashtag_id
            GROUP BY h.id
            ORDER BY usage_count DESC
            LIMIT 3
        ");
        $trendingTags = $trendingStmt->fetchAll(PDO::FETCH_ASSOC);

        $suggestionsStmt = $dbconn->prepare("
            SELECT u.id, u.username, u.display_name, u.profile_image, 
            COUNT(f2.follower_id) as mutual_followers
            FROM users u
            LEFT JOIN follows f2 ON u.id = f2.following_id 
            AND f2.follower_id IN (SELECT following_id FROM follows WHERE follower_id = ?)
            WHERE u.id != ? 
            AND u.id NOT IN (SELECT following_id FROM follows WHERE follower_id = ?) 
            GROUP BY u.id
            ORDER BY mutual_followers DESC, RAND() 
            LIMIT 3
        ");
        $suggestionsStmt->execute([$userId, $userId, $userId]);
        $suggestions = $suggestionsStmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? htmlspecialchars($pageTitle) : 'Quacker' ?></title>
    <link rel="icon" type="image/svg+xml" href="../public/images/QuackerLogo.svg">

    <!-- Bas-CSS (alltid med) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/styles.css">

    <!-- Sid-specifik CSS -->
    <?php if ($currentPage == 'profile.php'): ?>
        <link rel="stylesheet" href="css/profile.css">
    <?php elseif ($currentPage == 'messages.php'): ?>
        <link rel="stylesheet" href="css/messages.css">
    <?php elseif ($currentPage == 'notifications.php'): ?>
        <link rel="stylesheet" href="css/notifications.css">
    <?php elseif ($currentPage == 'admin.php'): ?>
        <link rel="stylesheet" href="css/admin.css">
    <?php endif; ?>

    <!-- CSS för Auth-sidor -->
    <?php 
    $authPages = ['login.php', 'register.php', 'forgot_password.php', 'reset_password.php'];
    if (in_array($currentPage, $authPages)): ?>
        <link rel="stylesheet" href="css/loginregister.css">
    <?php endif; ?>

    <!-- Bas-JS (Bootstrap och App-logik) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" defer></script>
    <script src="js/app.js" defer></script>

    <!-- Sid-specifik JS -->
    <?php if (!in_array($currentPage, $authPages)): ?>
        <!-- Saker som bara behövs när man är inloggad -->
        <script src="js/follow_ajax.js" defer></script>
        <script src="js/live_search.js" defer></script>
        <script src="js/quacktivity.js" defer></script>
        
        <?php if ($currentPage == 'index.php'): ?>
            <script src="js/index.js" defer></script>
        <?php elseif ($currentPage == 'messages.php'): ?>
            <script src="js/messages.js" defer></script>
        <?php elseif ($currentPage == 'admin.php'): ?>
            <script src="js/admin.js" defer></script>
        <?php elseif ($currentPage == 'profile.php'): ?>
            <script src="js/profile_edit.js" defer></script>
        <?php endif; ?>
        
        <script type="module" src="https://cdn.jsdelivr.net/npm/emoji-picker-element@^1/index.js"></script>
    <?php endif; ?>
</head>

<body>
<header class="site-header" id="siteheader">
    <div class="header-container container-fluid d-flex align-items-center justify-content-between px-3">
        
        <!-- Vänster sida: nav + mobilsök -->
        <div class="nav-container d-flex ps-0 flex-grow-1 flex-basis-0">
            <?php require __DIR__ . '/nav.php'; ?>
            
            <button class="btn text-white d-lg-none ms-2 p-0" id="mobileSearchBtn" type="button">
                <svg xmlns="http://w3.org" width="24" height="24" fill="currentColor" class="bi bi-search" viewBox="0 0 16 16">
                    <path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001c.03.04.062.078.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1.007 1.007 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0z"/>
                </svg>
            </button>

            <form action="search.php" method="GET" class="mobile-search-form flex-grow-1">
                <input type="search" name="query" id="mobileInput" class="form-control mobile-search-input" placeholder="Search Quacker...">
                <div id="mobile-search-results" class="search-results-dropdown"></div>
            </form>
            <button class="btn-close btn-close-white d-none" id="closeSearchBtn"></button>
        </div>

        <!-- Mitten: Quacker logga -->
        <div class="text-center header-logo-container">
            <a href="index.php">
                <img src="../public/images/QuackerLogo.svg" alt="Quacker Logo" class="site-logo">
            </a>
        </div>

       <!-- Höger sida: sökfält + profil -->
        <div class="search-profile-container flex-grow-1 d-flex justify-content-end align-items-center flex-basis-0">
            <form action="search.php" method="GET" class="d-none d-lg-flex align-items-center m-0 search-container">
                <div class="position-relative search-input-wrapper">
                    <!-- Sök-ikon -->
                    <span class="search-icon-inside">
                        <svg xmlns="http://w3.org" width="16" height="16" fill="currentColor" class="bi bi-search" viewBox="0 0 16 16">
                            <path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001c.03.04.062.078.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1.007 1.007 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0z"/>
                        </svg>
                    </span>
                    <input type="search" name="query" id="header-search" placeholder="Search Quacker" class="form-control qSearchBar" autocomplete="off">
                    <div id="desktop-search-results" class="search-results-dropdown"></div>
                </div>
            </form>

            <a href="profile.php?id=<?= $currentUser['id'] ?>" class="profile-button p-0 m-0">
                <img src="<?= getPfpPath($currentUser['profile_image'] ?? 'default_pfp.jpg') ?>" alt="Profile" class="header-img">
            </a>
        </div>
    </div>
</header>



<div class="container py-4">
    <div class="row gx-4">

    <?php 
    $showSidebar = !in_array($currentPage, [
        'messages.php',
        'login.php',
        'register.php',
        'admin.php',
        'forgot_password.php',
        'reset_password.php',
        'info.php'
    ]);
            
    if ($showSidebar): ?>
        <!-- Sidebar: Trending & Recommendations -->
        <aside class="col-lg-3 col-md-4 d-none d-md-block">
            <div class="trending-section mb-4 p-3 custom-sidebar-card shadow-sm">
                <h5 class="fw-bold mb-3">Trending now</h5>
                <div class="d-grid gap-2">
                <?php if (!empty($trendingTags)): ?>
                <?php foreach ($trendingTags as $tag): ?>
                    <a href="search.php?query=%23<?= urlencode($tag['tag_name']) ?>" class="btn btn-trending shadow-sm bg-white">
                        #<?= htmlspecialchars($tag['tag_name']) ?>
                    </a>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="text-muted small ps-2">No trends yet...</p>
            <?php endif; ?>
                </div>
            </div>

            <div class="suggestions-section p-3 custom-sidebar-card shadow-sm">
            <h5 class="fw-bold mb-3">You might know</h5>
    
            <?php if (!empty($suggestions)): ?>
                <?php foreach ($suggestions as $suggestedUser): ?>
                    <div class="suggestion-wrapper d-flex align-items-center justify-content-between mb-3 p-2 rounded shadow-sm bg-white">
                        <!-- Vänster sida: Klickbar profilinfo -->
                        <a href="profile.php?id=<?= $suggestedUser['id'] ?>" class="suggestion-item d-flex align-items-center text-decoration-none text-dark flex-grow-1 overflow-hidden">
                            <img src="<?= getPfpPath($suggestedUser['profile_image']) ?>" class="suggestion-pfp me-2" alt="Profile">
                            
                            <div class="user-info text-truncate">
                                <div class="fw-bold lh-1 text-truncate-custom"><?= htmlspecialchars($suggestedUser['display_name']) ?></div>
                                <small class="text-muted text-truncate-custom">@<?= htmlspecialchars($suggestedUser['username']) ?></small>
                            </div>
                        </a>

                        <!-- Höger sida: Follow-knapp -->
                        <button class="btn btn-sm btn-outline-success rounded-pill follow-btn-sm ms-2" 
                                data-user-id="<?= $suggestedUser['id'] ?>" 
                                data-action="follow">
                            +
                        </button>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="p-2 text-muted small">No new suggestions...</div>
            <?php endif; ?>
        </div>
        </aside>

        <!-- Main column -->
        <main class="col-lg-9 col-md-8">
    <?php else : ?>
        <main class="col-12">
    <?php endif; ?>
