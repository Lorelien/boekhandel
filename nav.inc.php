<?php
// nav.inc.php
if (!isset($currentUser)) {
    $currentUser = null;
}
if (!isset($cart)) {
    $cart = null;
}

// teller berekenen als cart beschikbaar is
$cartCount = ($cart && method_exists($cart, 'getItems')) ? count($cart->getItems()) : 0;
?>
<header class="site-header">
    <div class="container">
        <h1 class="logo">📚 Boekhandel</h1>
        <nav class="main-nav">
            <a href="index.php">Home</a>
            <a href="winkelmandje.php">🛒<?= $cartCount ? ' (' . $cartCount . ')' : '' ?></a>

            <?php if ($currentUser): ?>
                <a href="bestellingen.php">Bestellingen</a>
                <a href="password_change.php">Wachtwoord</a>

                <?php if ($currentUser->isAdmin()): ?>
                    <a href="admin.php" style="color:#10b981;">Admin</a>
                <?php endif; ?>

                <span>👋 <?= htmlspecialchars($currentUser->getFirstname()) ?></span>
            <?php else: ?>
                <a href="login.php">Login</a>
            <?php endif; ?>
        </nav>
    </div>
</header>
