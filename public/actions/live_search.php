<?php
error_reporting(E_ALL);
ini_set('display_errors', 0); 

header('Content-Type: application/json');

try {
    require_once __DIR__ . '/../../database/db.php';

    $query = isset($_GET['query']) ? trim($_GET['query']) : '';

    if (strlen($query) < 2) {
        echo json_encode(['users' => [], 'quacks' => []]);
        exit;
    }

    $searchTerm = "%" . $query . "%";
    $response = ['users' => [], 'quacks' => []];

    // Sök efter användare
    $userStmt = $dbconn->prepare("SELECT id, username, display_name, profile_image FROM users WHERE username LIKE :search OR display_name LIKE :search LIMIT 5");
    $userStmt->execute(['search' => $searchTerm]);
    $response['users'] = $userStmt->fetchAll(PDO::FETCH_ASSOC);

    // Sök efter Quacks
    $quackStmt = $dbconn->prepare("SELECT q.id, q.content, u.username, u.display_name, u.profile_image FROM quacks q JOIN users u ON q.user_id = u.id WHERE q.content LIKE :search LIMIT 5");
    $quackStmt->execute(['search' => $searchTerm]);
    $response['quacks'] = $quackStmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($response);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'error' => true,
        'message' => 'Ett internt fel uppstod vid sökningen.',
        'users' => [],
        'quacks' => []
    ]);
}
