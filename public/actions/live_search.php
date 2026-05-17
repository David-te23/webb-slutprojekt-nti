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

    //Omsluter söksträngen med % (t.ex. "%sök%") så att databasen 
    // hittar matchningar oavsett var i texten eller namnet ordet dyker upp.
    $searchTerm = "%" . $query . "%";
    $response = ['users' => [], 'quacks' => []];

    // SÖK ANVÄNDARE: Hittar profiler baserat på användarnamn eller visningsnamn (max 5 resultat för snabbhet)
    $userStmt = $dbconn->prepare("SELECT id, username, display_name, profile_image FROM users WHERE username LIKE :search OR display_name LIKE :search LIMIT 5");
    $userStmt->execute(['search' => $searchTerm]);
    $response['users'] = $userStmt->fetchAll(PDO::FETCH_ASSOC);

    // SÖK INLÄGG: Hittar quacks baserat på textinnehåll och kopplar på sändarens profilinfo via en JOIN
    $quackStmt = $dbconn->prepare("SELECT q.id, q.content, u.username, u.display_name, u.profile_image FROM quacks q JOIN users u ON q.user_id = u.id WHERE q.content LIKE :search LIMIT 5");
    $quackStmt->execute(['search' => $searchTerm]);
    $response['quacks'] = $quackStmt->fetchAll(PDO::FETCH_ASSOC);

    // Skickar tillbaka en sammanslagen array med både användare och inlägg som JSON
    echo json_encode($response);

} catch (Throwable $e) {
    // Om något kraschar skickas statuskod 500 (Internal Server Error) och ett säkert, generellt felmeddelande
    http_response_code(500);
    echo json_encode([
        'error' => true,
        'message' => 'An internal error occured during search.',
        'users' => [],
        'quacks' => []
    ]);
}
