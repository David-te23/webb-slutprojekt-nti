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

    if (isset($_SESSION['user_id'])) {
        $userId = $_SESSION['user_id'];
    
        $trendingStmt = $dbconn->query("
            SELECT h.tag_name, COUNT(qh.quack_id) as usage_count 
            FROM hashtags h
            JOIN quack_hashtags qh ON h.id = qh.hashtag_id
            GROUP BY h.id
            ORDER BY usage_count DESC
            LIMIT 3
        ");
        $trendingTags = $trendingStmt->fetchAll(PDO::FETCH_ASSOC);

        // Hämta rekommenderade användare (You might know)
        $suggestionsStmt = $dbconn->prepare("
        SELECT u.id, u.username, u.display_name, u.profile_image, 
            COUNT(f2.follower_id) as mutual_followers
        FROM users u
        -- Hitta kopplingar via folk du följer
        LEFT JOIN follows f2 ON u.id = f2.following_id 
        AND f2.follower_id IN (SELECT following_id FROM follows WHERE follower_id = ?)
        WHERE u.id != ? -- Inte du själv
        AND u.id NOT IN (SELECT following_id FROM follows WHERE follower_id = ?) -- Inte folk du redan följer
        GROUP BY u.id
        ORDER BY mutual_followers DESC, RAND() -- Prioritera gemensamma vänner, sen slumpmässigt
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
            </form>
            <button class="btn-close btn-close-white d-none" id="closeSearchBtn"></button>
        </div>

        <!-- Mitten: Quacker logga (Hålls centrerad) -->
        <div class="text-center header-logo-container">
            <a href="index.php">
                <img src="../public/images/QuackerLogo.svg" alt="Quacker Logo" class="header-img">
            </a>
        </div>

       <!-- höger sida: sökfält + profil -->
        <div class="search-profile-container flex-grow-1 d-flex justify-content-end align-items-center flex-basis-0">
            <form action="search.php" method="GET" class="d-none d-lg-flex align-items-center m-0">
                <input type="search" name="query" id="header-search" placeholder="Search Quacker" class="form-control me-3 qSearchBar">
            </form>

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
                            <img src="<?= getPfpPath($suggestedUser['profile_image']) ?>" class="suggestion-pfp me-2">
                            
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
