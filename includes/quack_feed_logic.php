<?php
$filter = $_GET['filter'] ?? 'all';

$sql = "SELECT q.id, q.content, q.created_at, q.user_id, q.parent_id,
               u.username, u.display_name, u.profile_image,
               orig_q.content AS orig_content, orig_u.username AS orig_username,
               orig_u.display_name AS orig_display_name, orig_u.profile_image AS orig_profile_image,
               orig_u.id AS orig_user_id,
               (SELECT COUNT(*) FROM likes WHERE quack_id = COALESCE(q.parent_id, q.id)) AS like_count,
               (SELECT COUNT(*) FROM comments WHERE quack_id = COALESCE(q.parent_id, q.id)) AS comment_count,
               (SELECT COUNT(*) FROM quacks WHERE parent_id = COALESCE(q.parent_id, q.id) AND content IS NULL) AS requack_count,
               EXISTS(SELECT 1 FROM likes WHERE quack_id = COALESCE(q.parent_id, q.id) AND user_id = :my_id) AS user_liked,
               EXISTS(SELECT 1 FROM quacks WHERE parent_id = COALESCE(q.parent_id, q.id) AND user_id = :my_id AND content IS NULL) AS user_requacked
        FROM quacks q
        JOIN users u ON q.user_id = u.id
        LEFT JOIN quacks orig_q ON q.parent_id = orig_q.id
        LEFT JOIN users orig_u ON orig_q.user_id = orig_u.id ";

if ($filter === 'following') {
    $sql .= " INNER JOIN follows f ON q.user_id = f.following_id WHERE f.follower_id = :my_id ";
} else {
    $sql .= " WHERE (q.content IS NOT NULL OR q.user_id != :my_id) ";
}

$sql .= " ORDER BY q.created_at DESC";

$stmt = $dbconn->prepare($sql);
$stmt->execute(['my_id' => $current_user_id]);
$quacks = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($quacks as &$quack) {
    $targetId = $quack['parent_id'] ?? $quack['id'];
    $imgStmt = $dbconn->prepare("SELECT image_path, file_type FROM quack_images WHERE quack_id = ?");
    $imgStmt->execute([$targetId]);
    $quack['images'] = $imgStmt->fetchAll(PDO::FETCH_ASSOC);
}
unset($quack);
