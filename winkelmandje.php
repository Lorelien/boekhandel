<?php
require_once __DIR__ . '/classes/Database.php';
require_once __DIR__ . '/classes/Cart.php';
require_once __DIR__ . '/classes/AuthService.php';
require_once __DIR__ . '/classes/Order.php';
require_once __DIR__ . '/classes/User.php'; 

$db   = new Database();
$auth = new AuthService($db);
$cart = new Cart($db);

$currentUser = $auth->getCurrentUser();

// voorkom undefined warnings
$error   = '';
$success = '';

// POST‑acties: toevoegen/verwijderen/afrekenen
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? null;

    if ($action === 'add' && isset($_POST['book_id'])) {
        $bookId   = (int)$_POST['book_id'];
        $quantity = max(1, (int)($_POST['quantity'] ?? 1));

        $cart->addItem($bookId, $quantity);
        $success = 'Boek toegevoegd aan je winkelmandje.';
    }

    if ($action === 'remove' && isset($_POST['book_id'])) {
        $bookId = (int)$_POST['book_id'];
        $cart->removeItem($bookId);
        $success = 'Boek verwijderd uit je winkelmandje.';
    }

    if ($action === 'checkout') {
    if (!$currentUser) {
        $error = 'Je moet ingelogd zijn om te bestellen.';
    } else {
        $total = $cart->getTotal(); // totaal van mandje

        // 1. Check wallet
        if ($currentUser->getWallet() < $total) {
            $error = 'Je hebt niet genoeg credits in je wallet om deze bestelling te plaatsen.';
        } else {
            // 2. Order aanmaken
            $order = Order::createFromCart($db, $currentUser, $cart);
            if ($order) {
                // 3. Wallet updaten (credits aftrekken)
                $newBalance = $currentUser->getWallet() - $total;

                $pdo = $db->getConnection();
                $stmt = $pdo->prepare("UPDATE users SET wallet = :wallet WHERE id = :id");
                $stmt->execute([
                    ':wallet' => $newBalance,
                    ':id'     => $currentUser->getId(),
                ]);

                $currentUser->setWallet($newBalance);

                $success = 'Bestelling geplaatst! Order #' . $order->getId();
            } else {
                $error = 'Er is iets misgegaan bij het plaatsen van je bestelling.';
            }
        }
    }

    header('Location: winkelmandje.php');
    exit;
}
}

// Data voor weergave
$items = $cart->getDetailedItems();
$total = $cart->getTotal();
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <title>Winkelmandje - Boekhandel</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/cart.css">
</head>
<body>
<?php include __DIR__ . '/nav.inc.php'; ?>

<main class="site-main">
    <div class="container cart-page">
        <div class="cart-header">
            <h1>🛒 Winkelmandje</h1>
            <span><?= count($items) ?> item(s)</span>
        </div>

        <?php if (!empty($error)): ?>
            <div class="cart-message error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <div class="cart-message success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <?php if (empty($items)): ?>
            <div class="cart-empty">
                <h2>Je winkelmandje is leeg</h2>
                <p>Ga terug naar de winkel en voeg boeken toe aan je mandje.</p>
                <a href="index.php" class="btn btn-primary">⬅ Terug naar de winkel</a>
            </div>
        <?php else: ?>
            <div class="cart-items">
                <?php foreach ($items as $item): ?>
                    <div class="cart-item">
                        <img src="assets/images/<?= htmlspecialchars($item['cover_image'] ?? 'default-book.jpg') ?>"
                             alt="<?= htmlspecialchars($item['title']) ?>">

                        <div class="cart-item-info">
                            <h3><?= htmlspecialchars($item['title']) ?></h3>
                            <p>Prijs: € <?= number_format($item['price'], 2, ',', '.') ?></p>
                            <p>Aantal: <?= (int)$item['quantity'] ?></p>
                            <p><strong>Subtotaal: € <?= number_format($item['subtotal'], 2, ',', '.') ?></strong></p>

                            <div class="cart-item-actions">
                                <form method="post" style="display:inline;">
                                    <input type="hidden" name="book_id" value="<?= (int)$item['id'] ?>">
                                    <button type="submit" name="action" value="remove" class="btn btn-danger">
                                        Verwijder
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="cart-total">
                <h2>Totaal: € <?= number_format($total, 2, ',', '.') ?></h2>
                <p>Controleer je boeken en rond daarna je bestelling af.</p>

                <?php if (!$currentUser): ?>
                    <p class="cart-login-warning">Je moet ingelogd zijn om te bestellen.</p>
                    <a href="login.php" class="btn btn-primary">Login</a>
                <?php else: ?>
                    <form method="post">
                        <input type="hidden" name="action" value="checkout">
                        <button type="submit" class="btn btn-success">✅ Bestellen</button>
                    </form>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</main>
</body>
</html>
