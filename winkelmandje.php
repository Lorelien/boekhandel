<?php
require_once __DIR__ . '/classes/Database.php';
require_once __DIR__ . '/classes/Cart.php';
require_once __DIR__ . '/classes/AuthService.php';
require_once __DIR__ . '/classes/Order.php';

$db = new Database();
$auth = new AuthService($db);
$cart = new Cart($db);

$currentUser = $auth->getCurrentUser();

// Voeg toe aan mandje of verwijder
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'], $_POST['book_id'])) {
        if ($_POST['action'] === 'add') {
            $cart->addItem((int)$_POST['book_id'], (int)($_POST['quantity'] ?? 1));
        } elseif ($_POST['action'] === 'remove') {
            $cart->removeItem((int)$_POST['book_id']);
        } elseif ($_POST['action'] === 'checkout' && $currentUser) {
            // Bestelling plaatsen
            $order = Order::createFromCart($db, $currentUser, $cart);
            if ($order) {
                $success = 'Bestelling geplaatst! Order #' . $order->getId();
            } else {
                $error = 'Fout bij plaatsen bestelling';
            }
        }
    }
    header('Location: winkelmandje.php');
    exit;
}

$items = $cart->getDetailedItems();
$total = $cart->getTotal();
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <title>Winkelmandje - Boekhandel</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <!-- Header zoals index.php -->
    <header class="site-header">...</header>

    <main class="site-main">
        <div class="container">
            <h1>🛒 Winkelmandje (<?= count($items) ?> items)</h1>

            <?php if ($error): ?>
                <div class="error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            <?php if (isset($success)): ?>
                <div class="success"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>

            <?php if (empty($items)): ?>
                <div style="text-align: center; padding: 3rem;">
                    <h2>Je winkelmandje is leeg</h2>
                    <a href="index.php" class="btn">🛍️ Naar de winkel</a>
                </div>
            <?php else: ?>
                <div class="cart-items">
                    <?php foreach ($items as $item): ?>
                        <div class="cart-item">
                            <img src="assets/images/<?= htmlspecialchars($item['cover_image'] ?? 'default.jpg') ?>" 
                                 alt="<?= htmlspecialchars($item['title']) ?>" width="80" height="120">
                            <div>
                                <h3><?= htmlspecialchars($item['title']) ?></h3>
                                <p>€ <?= number_format($item['price'], 2) ?> x <?= $item['quantity'] ?></p>
                                <p><strong>Subtotaal: € <?= number_format($item['subtotal'], 2) ?></strong></p>
                                <form method="post" style="display: inline;">
                                    <input type="hidden" name="book_id" value="<?= $item['id'] ?>">
                                    <button type="submit" name="action" value="remove" class="btn btn-danger">Verwijder</button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="cart-total">
                    <h2>Totaal: € <?= number_format($total, 2, ',', '.') ?></h2>
                    
                    <?php if (!$currentUser): ?>
                        <p style="color: #dc2626;">💳 Log in om te bestellen</p>
                        <a href="login.php" class="btn">Login</a>
                    <?php else: ?>
                        <form method="post" style="display: inline;">
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
