<?php
require_once __DIR__ . '/classes/Database.php';
require_once __DIR__ . '/classes/User.php';
require_once __DIR__ . '/classes/AuthService.php';
require_once __DIR__ . '/classes/Review.php';

header('Content-Type: application/json; charset=utf-8');

$db   = new Database();
$auth = new AuthService($db);
$currentUser = $auth->getCurrentUser();

// Helper om netjes JSON terug te sturen en te stoppen
function json_response(array $data, int $statusCode = 200): void {
    http_response_code($statusCode);
    echo json_encode($data);
    exit;
}

// 1. Moet ingelogd zijn
if (!$currentUser) {
    json_response([
        'success' => false,
        'error'   => 'Niet ingelogd'
    ], 401);
}

// 2. Enkel POST toestaan
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response([
        'success' => false,
        'error'   => 'Ongeldige methode'
    ], 405);
}

// 3. Basisvalidatie van POST-data
$bookId  = isset($_POST['book_id']) ? (int)$_POST['book_id'] : 0;
$rating  = isset($_POST['rating']) ? (int)$_POST['rating'] : 0;
$comment = trim($_POST['comment'] ?? '');

if ($bookId <= 0 || $rating < 1 || $rating > 5 || $comment === '') {
    json_response([
        'success' => false,
        'error'   => 'Ongeldige invoer'
    ], 400);
}

$pdo = $db->getConnection();

// 4. Check: heeft deze user dit boek ooit gekocht?
$stmt = $pdo->prepare("
    SELECT COUNT(*) 
    FROM orders o
    JOIN order_items oi ON o.id = oi.order_id
    WHERE o.user_id = :user_id
      AND oi.book_id = :book_id
");
$stmt->execute([
    ':user_id' => $currentUser->getId(),
    ':book_id' => $bookId
]);
$hasPurchased = (int)$stmt->fetchColumn() > 0;

if (!$hasPurchased) {
    json_response([
        'success' => false,
        'error'   => 'Je kunt alleen een review plaatsen als je dit boek hebt gekocht'
    ], 403);
}

// 5. Review wegschrijven
Review::create($db, $bookId, $currentUser->getId(), $rating, $comment);

// Optioneel: datum ophalen zoals je die in de DB opslaat (bv. NOW())
$reviewDate = date('Y-m-d H:i:s');

// 6. JSON teruggeven zodat JS de nieuwe review kan tonen
json_response([
    'success'   => true,
    'rating'    => $rating,
    'comment'   => $comment,
    'userName'  => $currentUser->getFirstname() . ' ' . $currentUser->getLastname(),
    'date'      => $reviewDate,
    'bookId'    => $bookId,
]);
