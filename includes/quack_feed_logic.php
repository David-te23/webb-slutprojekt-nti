<?php
// Kollar om användaren valt fliken 'Following' eller 'All Quacks'
$filter = $_GET['filter'] ?? 'all';

// HUVUDFRÅGA: Hämtar inlägg, kopplar användare och räknar interaktioner.
// COALESCE(q.parent_id, q.id) används för att räknare (likes/comments) alltid ska peka 
// på originalinlägget, även om det vi tittar på just nu är en requack.
$sql = "SELECT q.id, q.content, q.created_at, q.user_id, q.parent_id,
               u.username, u.display_name, u.profile_image,
               orig_q.content AS orig_content, 
               orig_q.created_at AS orig_created_at, -- Hämtar originalets tidsstämpel för requacks
               orig_u.username AS orig_username,
               orig_u.display_name AS orig_display_name, orig_u.profile_image AS orig_profile_image,
               orig_u.id AS orig_user_id,
               (SELECT COUNT(*) FROM likes WHERE quack_id = COALESCE(q.parent_id, q.id)) AS like_count,
               (SELECT COUNT(*) FROM comments WHERE quack_id = COALESCE(q.parent_id, q.id)) AS comment_count,
               (SELECT COUNT(*) FROM quacks WHERE parent_id = COALESCE(q.parent_id, q.id) AND content IS NULL) AS requack_count,
               -- EXISTS returnerar 1 eller 0 för att kolla om den inloggade användaren har interagerat med inlägget
               EXISTS(SELECT 1 FROM likes WHERE quack_id = COALESCE(q.parent_id, q.id) AND user_id = :my_id) AS user_liked,
               EXISTS(SELECT 1 FROM quacks WHERE parent_id = COALESCE(q.parent_id, q.id) AND user_id = :my_id AND content IS NULL) AS user_requacked
        FROM quacks q
        JOIN users u ON q.user_id = u.id
         -- LEFT JOIN används eftersom vi bara vill ha originaldata om inlägget faktiskt är en requack
        LEFT JOIN quacks orig_q ON q.parent_id = orig_q.id
        LEFT JOIN users orig_u ON orig_q.user_id = orig_u.id ";

if ($filter === 'following') {
    // INNER JOIN kopplar på följartabellen så att vi bara filtrerar ut inlägg från personer man följer
    $sql .= " INNER JOIN follows f ON q.user_id = f.following_id WHERE f.follower_id = :my_id ";
} else {
    // Ser till att vi ser allas inlägg, men rensar bort tomma requacks som man gjort på sig själv
    $sql .= " WHERE (q.content IS NOT NULL OR q.user_id != :my_id) ";
}

$sql .= " ORDER BY q.created_at DESC";

$stmt = $dbconn->prepare($sql);
$stmt->execute(['my_id' => $current_user_id]);
$quacks = $stmt->fetchAll(PDO::FETCH_ASSOC);

// NÄSTLAD FRÅGA: Hämtar bildbilagor separat för varje inlägg för att hålla huvudfrågan ren och snabb
foreach ($quacks as &$quack) {
    $targetId = $quack['parent_id'] ?? $quack['id'];
    $imgStmt = $dbconn->prepare("SELECT image_path, file_type FROM quack_images WHERE quack_id = ?");
    $imgStmt->execute([$targetId]);
    $quack['images'] = $imgStmt->fetchAll(PDO::FETCH_ASSOC);
}
unset($quack); // Bryter referensen i foreach-loopen så att inte sista elementet skrivs över av misstag senare
