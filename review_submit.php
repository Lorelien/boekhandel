<?php
require_once __DIR__ . '/classes/Database.php';
require_once __DIR__ . '/classes/User.php';
require_once __DIR__ . '/classes/AuthService.php';
require_once __DIR__ . '/classes/Review.php';

$db   = new Database();
$auth = new AuthService($db);
$currentUser = $auth->getCurrentUser();

if (!$currentUser) {
    header('Location: login.php');
    exit;
}

$bookId  = isset($_POST['book_id']) ? (int)$_POST['book_id'] : 0;
$rating  = isset($_POST['rating']) ? (int)$_POST['rating'] : 0;
$comment = trim($_POST['comment'] ?? '');
$comment = strip_tags($comment);

if ($bookId <= 0 || $rating < 1 || $rating > 5 || $comment === '') {
    header('Location: boek.php?id=' . $bookId);
    exit;
}

$pdo = $db->getConnection();

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
    header('Location: boek.php?id=' . $bookId . '&error=nietgekocht');
    exit;
}

Review::create($db, $bookId, $currentUser->getId(), $rating, $comment);

header('Location: boek.php?id=' . $bookId . '#reviews');
exit;
