<?php
require_once __DIR__ . '/classes/Database.php';
require_once __DIR__ . '/classes/Book.php';
require_once __DIR__ . '/classes/Category.php';
require_once __DIR__ . '/classes/Cart.php';

$db = new Database();
$cart = new Cart($db);

$categoryId = isset($_GET['category_id']) ? (int)$_GET['category_id'] : null;
$search = $_GET['q'] ?? null;

$categories = Category::findAll($db);
$books = Book::findAll($db, $categoryId, $search);
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Boekhandel</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: system-ui, sans-serif; background: #f5f5f5; color: #222; line-height: 1.5; }
        .container { max-width: 1200px; margin: 0 auto; padding: 0 1rem; }
        .site-header { background: #1f2933; color: #fff; padding: 1rem 0; }
        .site-header .container { display: flex; justify-content: space-between; align-items: center; }
        .logo { font-size: 1.5rem; font-weight: 700; }
        .main-nav a { color: #e5e7eb; padding: 0.25rem 0.5rem; margin-left: 1rem; }
        .main-nav a:hover { color: #fff; }
        .site-main .container { display: grid; grid-template-columns: 280px 1fr; gap: 2rem; padding: 2rem 0; }
        .sidebar h2 { font-size: 1.1rem; margin-bottom: 0.5rem; }
        .category-list, .subcategory-list { list-style: none; }
        .category-list li { margin-bottom: 0.75rem; }
        .category-list strong { display: block; font-size: 1.1rem; color: #111; margin-bottom: 0.25rem; }
        .subcategory-list { padding-left: 1rem; font-size: 0.95rem; }
        .subcategory-list li { margin-bottom: 0.25rem; }
        .subcategory-list a { color: #6b7280; }
        .subcategory-list a:hover { color: #2563eb; font-weight: 500; }
        .search-form { display: flex; gap: 0.5rem; margin-top: 1rem; }
        .search-form input { flex: 1; padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 4px; }
        .search-form button { padding: 0.5rem 1rem; background: #2563eb; color: #fff; border: none; border-radius: 4px; cursor: pointer; }
        .product-list h2 { margin-bottom: 1rem; }
        .book-card { display: flex; gap: 1rem; padding: 1rem; margin-bottom: 1rem; background: #fff; border-radius: 6px; box-shadow: 0 1px 3px rgba(0,0,0,0.06); }
        .book-cover { width: 110px; height: 160px; object-fit: cover; border-radius: 4px; background: #e5e7eb; }
        .book-info h3 { font-size: 1.1rem; margin-bottom: 0.25rem; }
        .book-info p { color: #6b7280; margin-bottom: 0.5rem; }
        .book-price { font-weight: 600; font-size: 1.1rem; color: #059669; }
        .btn { display: inline-block; padding: 0.4rem 0.8rem; background: #2563eb; color: #fff; text-decoration: none; border-radius: 4px; margin-right: 0.5rem; }
        .btn-secondary { background: #10b981; }
        .cart-actions { display: flex; gap: 0.5rem; align-items: center; }
        .site-footer { text-align: center; padding: 2rem; color: #6b7280; border-top: 1px solid #e5e7eb; }
        @media (max-width: 800px) { .site-main .container { grid-template-columns: 1fr; } .book-card { flex-direction: column; } }
    </style>
</head>
<body>
    <header class="site-header">
        <div class="container">
            <h1 class="logo">📚 Boekhandel</h1>
            <nav class="main-nav">
                <a href="index.php">Home</a>
                <a href="winkelmandje.php">🛒 (<?= count($cart->getItems()) ?>)</a>
                <a href="login.php">Login</a>
                <a href="admin.php">Admin</a>
            </nav>
        </div>
    </header>

    <main class="site-main">
        <div class="container">
            <!-- Sidebar: Hoofdcategorieën met subcategorieën -->
            <aside class="sidebar">
                <h2>Navigeer</h2>
                <ul class="category-list">
                    <?php foreach ($categories as $cat): ?>
                        <?php if ($cat->isMainCategory): // Alleen hoofdcategorieën (id 1-4) ?>
                            <li>
                                <strong><?= htmlspecialchars($cat->name) ?></strong>
                                
                                <!-- Subcategorieën voor deze hoofdcategorie -->
                                <?php $subcats = Category::findSubcategories($db, $cat->id); ?>
                                <?php if (!empty($subcats)): ?>
                                    <ul class="subcategory-list">
                                        <?php foreach ($subcats as $subcat): ?>
                                            <li>
                                                <a href="?category_id=<?= $subcat->id ?>">
                                                    📖 <?= htmlspecialchars($subcat->name) ?>
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
                    <input type="text" name="q" value="<?= htmlspecialchars($search ?? '') ?>" placeholder="Titel of auteur...">
                    <button type="submit">Zoek</button>
                </form>
            </aside>

            <!-- Boekenlijst -->
            <section class="product-list">
                <h2>
                    <?php 
                    if ($categoryId) {
                        $selectedCat = array_filter($categories, fn($c) => $c->id === $categoryId)[0] ?? null;
                        echo '📚 Boeken in: ' . ($selectedCat->name ?? 'Onbekende categorie');
                    } else {
                        echo '📚 Alle boeken (' . count($books) . ')';
                    }
                    ?>
                </h2>

                <?php if (empty($books)): ?>
                    <p style="padding: 2rem; text-align: center; color: #6b7280;">
                        Geen boeken gevonden. Probeer een andere categorie of zoekterm.
                    </p>
                <?php else: ?>
                    <?php foreach ($books as $book): ?>
                        <article class="book-card">
                            <img src="assets/images/<?= htmlspecialchars($book->coverImage ?? 'default-book.jpg') ?>"
                                 alt="<?= htmlspecialchars($book->title) ?>"
                                 class="book-cover">

                            <div class="book-info">
                                <h3><?= htmlspecialchars($book->title) ?></h3>
                                <p><strong>Auteur:</strong> <?= htmlspecialchars($book->authorName ?? 'Onbekend') ?></p>
                                <p class="book-price">€ <?= number_format($book->price, 2, ',', '.') ?></p>
                                
                                <div class="cart-actions">
                                    <a href="boek.php?id=<?= $book->id ?>" class="btn">👁️ Details</a>
                                    
                                    <!-- Winkelmandje toevoegen -->
                                    <form method="post" action="winkelmandje.php" style="display:inline;">
                                        <input type="hidden" name="book_id" value="<?= $book->id ?>">
                                        <input type="number" name="quantity" value="1" min="1" max="99" 
                                               style="width: 50px; padding: 0.3rem; margin-right: 0.5rem;">
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
