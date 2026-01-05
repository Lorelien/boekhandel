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

// Orders + items ophalen voor deze user
$stmt = $pdo->prepare("
    SELECT 
        o.id AS order_id,
        o.order_date,
        o.total_price,
        oi.quantity,
        oi.unit_price,
        b.title AS book_title,
        b.id    AS book_id
    FROM orders o
    JOIN order_items oi ON o.id = oi.order_id
    JOIN books b        ON oi.book_id = b.id
    WHERE o.user_id = :user_id
    ORDER BY o.order_date DESC, o.id DESC
");
$stmt->execute([':user_id' => $currentUser->getId()]);
$rows = $stmt->fetchAll();

// Groeperen per order
$orders = [];
foreach ($rows as $row) {
    $id = $row['order_id'];
    if (!isset($orders[$id])) {
        $orders[$id] = [
            'order_id'     => $id,
            'order_date'   => $row['order_date'],
            'total_price'  => $row['total_price'],
            'items'        => [],
        ];
    }

    $orders[$id]['items'][] = [
        'book_id'    => $row['book_id'],
        'book_title' => $row['book_title'],
        'quantity'   => $row['quantity'],
        'unit_price' => $row['unit_price'],
    ];
}
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <title>Mijn bestellingen - Boekhandel</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<?php include __DIR__ . '/nav.inc.php'; ?>

<main class="site-main">
    <div class="container">
        <h2>Mijn bestellingen</h2>
<p>Je huidige saldo:
    <?= number_format($currentUser->getWallet(), 2, ',', '.') ?> coins
</p>

        <?php if (empty($orders)): ?>
            <p>Je hebt nog geen bestellingen geplaatst.</p>
        <?php else: ?>
            <?php foreach ($orders as $order): ?>
                <section class="order-card">
                    <header class="order-card__header">
                        <div>
                            <strong>Bestelling #<?= htmlspecialchars($order['order_id']) ?></strong><br>
                            <span><?= htmlspecialchars($order['order_date']) ?></span>
                        </div>
                        <div>
                            <span>Status: verwerkt</span><br>
                            <span>Totaal: € <?= number_format($order['total_price'], 2, ',', '.') ?></span>
                        </div>
                    </header>

                    <div class="order-card__items">
                        <?php foreach ($order['items'] as $item): ?>
                            <div class="order-item">
                                <div class="order-item__title">
                                    <a href="boek.php?id=<?= (int)$item['book_id'] ?>">
                                        <?= htmlspecialchars($item['book_title']) ?>
                                    </a>
                                </div>
                                <div class="order-item__meta">
                                    <span>Aantal: <?= (int)$item['quantity'] ?></span>
                                    <span>Prijs: € <?= number_format($item['unit_price'], 2, ',', '.') ?></span>
                                    <span>Subtotaal: € <?= number_format($item['unit_price'] * $item['quantity'], 2, ',', '.') ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</main>
</body>
</html>
