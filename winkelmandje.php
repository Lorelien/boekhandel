<?php
require_once __DIR__ . '/classes/Cart.php';
$db = new Database();
$cart = new Cart($db);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['book_id'])) {
    if ($_POST['action'] === 'add') {
        $cart->addItem((int)$_POST['book_id']);
    }
    header('Location: index.php');  // Terug naar homepage
    exit;
}

$items = $cart->getDetailedItems();
?>

<!-- Toon mandje-inhoud -->
<h2>Winkelmandje (<?= count($items) ?> items)</h2>
<?php foreach ($items as $item): ?>
    <div>
        <?= htmlspecialchars($item['title']) ?>
        x<?= $item['quantity'] ?> = €<?= number_format($item['subtotal'], 2) ?>
    </div>
<?php endforeach; ?>
<p><strong>Totaal: €<?= number_format($cart->getTotal(), 2) ?></strong></p>
