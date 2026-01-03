<?php
require_once __DIR__ . '/classes/Database.php';
require_once __DIR__ . '/classes/Book.php';
require_once __DIR__ . '/classes/Category.php';
require_once __DIR__ . '/classes/Cart.php';
require_once __DIR__ . '/classes/AuthService.php';
require_once __DIR__ . '/classes/User.php'; 

$db   = new Database();
$cart = new Cart($db);
$auth = new AuthService($db);
$currentUser = $auth->getCurrentUser();

$categoryId = isset($_GET['category_id']) ? (int)$_GET['category_id'] : null;
$search     = $_GET['q'] ?? null;

$categories = Category::findAll($db);
$books      = Book::findAll($db, $categoryId, $search);
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Boekhandel</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<header class="site-header">
    <div class="container">
        <h1 class="logo">📚 Boekhandel</h1>
        <nav class="main-nav">
            <a href="index.php">Home</a>
            <a href="winkelmandje.php">🛒 (<?= count($cart->getItems()) ?>)</a>
            <?php if ($currentUser): ?>
                <a href="bestellingen.php">Bestellingen</a>
                <a href="password_change.php">Wachtwoord</a>
                <span>👋 <?= htmlspecialchars($currentUser->getFirstname()) ?></span>
                <?php if ($currentUser->isAdmin()): ?>
                    <a href="admin.php" style="color:#10b981;">Admin</a>
                <?php endif; ?>
                <a href="login.php">Account</a>
            <?php else: ?>
                <a href="login.php">Login</a>
            <?php endif; ?>
        </nav>
    </div>
</header>

<main class="site-main">
    <div class="container">
        <!-- Sidebar: hoofdcategorieën + subcategorieën -->
        <aside class="sidebar">
            <h2>Navigeer</h2>
            <ul class="category-list">
                <?php foreach ($categories as $cat): ?>
                    <?php if ($cat->isMainCategory()): ?>
                        <li>
                            <strong><?= htmlspecialchars($cat->getName()) ?></strong>

                            <?php $subcats = Category::findSubcategories($db, $cat->getId()); ?>
                            <?php if (!empty($subcats)): ?>
                                <ul class="subcategory-list">
                                    <?php foreach ($subcats as $subcat): ?>
                                        <li>
                                            <a href="?category_id=<?= $subcat->getId() ?>">
                                                📖 <?= htmlspecialchars($subcat->getName()) ?>
                                            </a>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>
                        </li>
                    <?php endif; ?>
                <?php endforeach; ?>
            </ul>

            <h2>🔍 Zoeken</h2>
            <form method="get" class="search-form">
                <input type="text"
                       name="q"
                       value="<?= htmlspecialchars($search ?? '') ?>"
                       placeholder="Titel of auteur...">
                <button type="submit">Zoek</button>
            </form>
        </aside>

        <!-- Productlijst -->
        <section class="product-list">
            <h2>
                <?php
                if ($categoryId) {
                    $selectedCat = null;
                    foreach ($categories as $c) {
                        if ($c->getId() === $categoryId) {
                            $selectedCat = $c;
                            break;
                        }
                    }
                    echo '📚 Boeken in: ' . ($selectedCat ? htmlspecialchars($selectedCat->getName()) : 'Onbekende categorie');
                } else {
                    echo '📚 Alle boeken (' . count($books) . ')';
                }
                ?>
            </h2>

            <?php if (empty($books)): ?>
                <p style="padding:2rem; text-align:center; color:#6b7280;">
                    Geen boeken gevonden. Probeer een andere categorie of zoekterm.
                </p>
            <?php else: ?>
                <?php foreach ($books as $book): ?>
                    <article class="book-card">
                        <img src="assets/images/<?= htmlspecialchars($book->getCoverImage() ?? 'default-book.jpg') ?>"
                             alt="<?= htmlspecialchars($book->getTitle()) ?>"
                             class="book-cover">

                        <div class="book-info">
                            <h3 class="book-title"><?= htmlspecialchars($book->getTitle()) ?></h3>
                            <p class="book-author">
                                Auteur: <?= htmlspecialchars($book->getAuthorName() ?? 'Onbekend') ?>
                            </p>
                            <p class="book-price">
                                € <?= number_format($book->getPrice(), 2, ',', '.') ?>
                            </p>

                            <div class="cart-actions">
                                <a href="boek.php?id=<?= $book->getId() ?>" class="btn">👁️ Details</a>

                                <form method="post" action="winkelmandje.php" style="display:inline;">
                                    <input type="hidden" name="book_id" value="<?= $book->getId() ?>">
                                    <input type="number" name="quantity" value="1" min="1" max="99"
                                           style="width:50px; padding:0.3rem; margin-right:0.5rem;">
                                    <button type="submit" name="action" value="add" class="btn btn-secondary">
                                        🛒 Mandje
                                    </button>
                                </form>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            <?php endif; ?>
        </section>
    </div>
</main>

<footer class="site-footer">
    <div class="container">
        <p>&copy; <?= date('Y') ?> Boekhandel | Gemaakt voor Digital Experience Design</p>
    </div>
</footer>
</body>
</html>
