<?php
require_once __DIR__ . '/classes/Database.php';
require_once __DIR__ . '/classes/User.php';
require_once __DIR__ . '/classes/AuthService.php';

$db   = new Database();
$auth = new AuthService($db);
$currentUser = $auth->getCurrentUser();

if (!$currentUser) {
    header('Location: login.php');
    exit;
}

$pdo = $db->getConnection();

// Bestellingen van deze user ophalen
$stmt = $pdo->prepare("
    SELECT o.id, o.order_date, o.total_price
    FROM orders o
    WHERE o.user_id = :user_id
    ORDER BY o.order_date DESC
");
$stmt->execute([':user_id' => $currentUser->getId()]);
$orders = $stmt->fetchAll();

// Items per order ophalen
$orderItems = [];
if (!empty($orders)) {
    $orderIds = array_column($orders, 'id');
    $placeholders = implode(',', array_fill(0, count($orderIds), '?'));

    $stmtItems = $pdo->prepare("
        SELECT oi.order_id, oi.book_id, oi.quantity, oi.unit_price, b.title
        FROM order_items oi
        JOIN books b ON oi.book_id = b.id
        WHERE oi.order_id IN ($placeholders)
        ORDER BY oi.order_id DESC
    ");
    $stmtItems->execute($orderIds);

    while ($row = $stmtItems->fetch()) {
        $orderId = (int)$row['order_id'];
        if (!isset($orderItems[$orderId])) {
            $orderItems[$orderId] = [];
        }
        $orderItems[$orderId][] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <title>Mijn bestellingen - Boekhandel</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .orders-page { padding: 2rem 0; }
        .order-card { background:#fff; border-radius:8px; box-shadow:0 1px 3px rgba(0,0,0,0.06); padding:1rem 1.25rem; margin-bottom:1rem; }
        .order-header { display:flex; justify-content:space-between; align-items:baseline; margin-bottom:0.5rem; }
        .order-header h2 { font-size:1rem; }
        .order-meta { font-size:0.85rem; color:#6b7280; }
        .order-items { margin-top:0.5rem; font-size:0.9rem; }
        .order-items ul { list-style:none; padding-left:0; }
        .order-items li { display:flex; justify-content:space-between; padding:0.25rem 0; border-bottom:1px dashed #e5e7eb; }
        .order-items li:last-child { border-bottom:none; }
    </style>
</head>
<body>
<header class="site-header">
    <div class="container">
        <h1 class="logo">📚 Boekhandel</h1>
        <nav class="main-nav">
            <a href="index.php">Home</a>
            <a href="winkelmandje.php">Winkelmandje</a>
            <a href="bestellingen.php">Bestellingen</a>
            <a href="password_change.php">Wachtwoord</a>
            <span>👋 <?= htmlspecialchars($currentUser->getFirstname()) ?></span>
        </nav>
    </div>
</header>

<main class="site-main">
    <div class="container orders-page">
        <h1>Mijn bestellingen</h1>

        <?php if (empty($orders)): ?>
            <p style="margin-top:1rem; color:#6b7280;">Je hebt nog geen bestellingen geplaatst.</p>
        <?php else: ?>
            <?php foreach ($orders as $order): ?>
                <?php $id = (int)$order['id']; ?>
                <div class="order-card">
                    <div class="order-header">
                        <h2>Bestelling #<?= $id ?></h2>
                        <span class="order-meta">
                            <?= htmlspecialchars($order['order_date']) ?> · 
                            Totaal: € <?= number_format($order['total_price'], 2, ',', '.') ?>
                        </span>
                    </div>
                    <div class="order-items">
                        <ul>  
                            <?php foreach ($orderItems[$id] ?? [] as $item): ?>
                                <li>
                                    <span><?= htmlspecialchars($item['title']) ?> × <?= (int)$item['quantity'] ?></span>
                                    <span>
                                        € <?= number_format($item['unit_price'], 2, ',', '.') ?>
                                    </span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</main>
</body>
</html>
