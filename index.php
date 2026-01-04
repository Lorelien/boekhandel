<?php
require_once __DIR__ . '/classes/Database.php';
require_once __DIR__ . '/classes/User.php';
require_once __DIR__ . '/classes/AuthService.php';
require_once __DIR__ . '/classes/Cart.php';

$db   = new Database();
$auth = new AuthService($db);
$currentUser = $auth->getCurrentUser();
$cart = new Cart($db);

$pdo = $db->getConnection();

/**
 * 1. Categorieën ophalen en in boomstructuur steken
 *    Verwacht: categories(id, name, parent_id)
 */
$catStmt = $pdo->query("SELECT id, name, parent_id FROM categories ORDER BY name ASC");
$allCategories = $catStmt->fetchAll();

$mainCategories   = [];
$childrenByParent = [];

foreach ($allCategories as $cat) {
    if ($cat['parent_id'] === null) {
        $mainCategories[$cat['id']] = [
            'id'            => $cat['id'],
            'name'          => $cat['name'],
            'subcategories' => [],
        ];
    } else {
        $childrenByParent[$cat['parent_id']][] = $cat;
    }
}

foreach ($childrenByParent as $parentId => $subs) {
    if (isset($mainCategories[$parentId])) {
        $mainCategories[$parentId]['subcategories'] = $subs;
    }
}

/**
 * 2. Zoekterm en categorie-filter
 */
$search     = trim($_GET['q'] ?? '');
$categoryId = isset($_GET['category']) ? (int)$_GET['category'] : 0;

$params = [];
$sql = "
    SELECT b.*, 
           CONCAT(COALESCE(a.firstname, ''), ' ', COALESCE(a.lastname, '')) AS author_name,
           c.name AS category_name
    FROM books b
    LEFT JOIN authors a    ON b.author_id = a.id
    LEFT JOIN categories c ON b.category_id = c.id
    WHERE 1 = 1
";

if ($search !== '') {
    $sql .= " AND (b.title LIKE :q OR b.description LIKE :q) ";
    $params[':q'] = '%' . $search . '%';
}

if ($categoryId > 0) {
    $sql .= " AND b.category_id = :category_id ";
    $params[':category_id'] = $categoryId;
}

$sql .= " ORDER BY b.title ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$books = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <title>Home - Boekhandel</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .layout-two-columns {
            display: grid;
            grid-template-columns: 240px 1fr;
            gap: 2rem;
            align-items: flex-start;
        }

        .sidebar {
            background-color: #f9fafb;
            border-radius: 8px;
            padding: 1.25rem 1rem;
            border: 1px solid #e5e7eb;
        }

        .sidebar h2 {
            margin: 0 0 0.75rem;
            font-size: 1rem;
        }

        .sidebar-main-categories {
            list-style: none;
            margin: 0;
            padding: 0;
        }

        .sidebar-main-category {
            margin-bottom: 0.6rem;
        }

        .sidebar-main-link {
            display: block;
            text-decoration: none;
            color: #111827;
            font-size: 0.9rem;
            padding: 0.2rem 0.1rem;
        }

        .sidebar-main-link strong {
            font-weight: 600;
        }

        .sidebar-main-link:hover {
            text-decoration: underline;
        }

        .sidebar-subcategories {
            list-style: none;
            margin: 0.1rem 0 0.3rem 0.5rem;
            padding: 0;
        }

        .sidebar-subcategories li {
            margin-bottom: 0.25rem;
        }

        .sidebar-sub-link {
            display: block;
            text-decoration: none;
            color: #4b5563;
            font-size: 0.85rem;
            padding: 0.15rem 0.1rem;
        }

        .sidebar-sub-link:hover {
            text-decoration: underline;
        }

        .search-form {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin: 1rem 0 1.5rem;
        }

        .search-form input[type="text"],
        .search-form select {
            padding: 0.5rem 0.75rem;
            border-radius: 4px;
            border: 1px solid #d1d5db;
        }

        .book-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
            gap: 1rem;
        }

        .book-card {
            background: #ffffff;
            border-radius: 8px;
            padding: 0.75rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.06);
            display: flex;
            flex-direction: column;
        }

        .book-card__cover {
            width: 100%;
            height: auto;
            border-radius: 4px;
            margin-bottom: 0.5rem;
        }

        .book-card__title {
            font-size: 1rem;
            margin: 0 0 0.25rem;
        }

        .book-card__author,
        .book-card__category,
        .book-card__price {
            font-size: 0.85rem;
            margin: 0 0 0.2rem;
        }

        .book-card__cart-form {
            margin-top: 0.5rem;
        }

        @media (max-width: 800px) {
            .layout-two-columns {
                grid-template-columns: 1fr;
            }

            .sidebar {
                order: -1;
            }
        }
    </style>
</head>
<body>
<?php include __DIR__ . '/nav.inc.php'; ?>

<main class="site-main">
    <div class="container layout-two-columns">
        <!-- Sidebar met hoofdcategorieën en subcategorieën -->
        <aside class="sidebar">
            <h2>Categorieën</h2>

            <ul class="sidebar-main-categories">
                <li class="sidebar-main-category">
                    <a href="index.php" class="sidebar-main-link">
                        <strong>Alle boeken</strong>
                    </a>
                </li>

                <?php foreach ($mainCategories as $cat): ?>
                    <li class="sidebar-main-category">
                        <a href="index.php?category=<?= (int)$cat['id'] ?>" class="sidebar-main-link">
                            <strong><?= htmlspecialchars($cat['name']) ?></strong>
                        </a>

                        <?php if (!empty($cat['subcategories'])): ?>
                            <ul class="sidebar-subcategories">
                                <?php foreach ($cat['subcategories'] as $sub): ?>
                                    <li>
                                        <a href="index.php?category=<?= (int)$sub['id'] ?>" class="sidebar-sub-link">
                                            📚 <?= htmlspecialchars($sub['name']) ?>
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        </aside>

        <!-- Rechterkolom: zoekfunctie + boeken -->
        <section class="content">
            <form method="get" action="index.php" class="search-form">
                <input
                    type="text"
                    name="q"
                    placeholder="Zoek op titel of beschrijving..."
                    value="<?= htmlspecialchars($search) ?>"
                >
                <select name="category">
                    <option value="0">Alle categorieën</option>
                    <?php foreach ($allCategories as $cat): ?>
                        <option value="<?= (int)$cat['id'] ?>"
                            <?= $categoryId === (int)$cat['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($cat['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="btn btn-primary">Zoeken</button>
            </form>

            <?php if ($search !== '' || $categoryId > 0): ?>
                <p>
                    Resultaten
                    <?php if ($search !== ''): ?>
                        voor "<strong><?= htmlspecialchars($search) ?></strong>"
                    <?php endif; ?>
                    <?php if ($categoryId > 0): ?>
                        in categorie "<strong>
                            <?php
                            $catName = '';
                            foreach ($allCategories as $cat) {
                                if ((int)$cat['id'] === $categoryId) {
                                    $catName = $cat['name'];
                                    break;
                                }
                            }
                            echo htmlspecialchars($catName);
                            ?>
                        </strong>"
                    <?php endif; ?>
                    : <?= count($books) ?> gevonden.
                </p>
            <?php endif; ?>

            <?php if (empty($books)): ?>
                <p>Er zijn geen boeken gevonden.</p>
            <?php else: ?>
                <div class="book-grid">
                    <?php foreach ($books as $book): ?>
                        <article class="book-card">
                            <a href="boek.php?id=<?= (int)$book['id'] ?>" class="book-card__cover-link">
                                <img
                                    src="assets/images/<?= htmlspecialchars($book['cover_image'] ?? 'default-book.jpg') ?>"
                                    alt="<?= htmlspecialchars($book['title']) ?>"
                                    class="book-card__cover"
                                >
                            </a>
                            <div class="book-card__body">
                                <h2 class="book-card__title">
                                    <a href="boek.php?id=<?= (int)$book['id'] ?>">
                                        <?= htmlspecialchars($book['title']) ?>
                                    </a>
                                </h2>
                                <p class="book-card__author">
                                    <?= htmlspecialchars($book['author_name'] ?: 'Onbekende auteur') ?>
                                </p>
                                <p class="book-card__category">
                                    <?= htmlspecialchars($book['category_name'] ?? '') ?>
                                </p>
                                <p class="book-card__price">
                                    € <?= number_format($book['price'], 2, ',', '.') ?>
                                </p>

                                <form method="post" action="winkelmandje.php" class="book-card__cart-form">
                                    <input type="hidden" name="book_id" value="<?= (int)$book['id'] ?>">
                                    <input type="hidden" name="quantity" value="1">
                                    <button type="submit" name="action" value="add" class="btn btn-secondary">
                                        🛒 In mandje
                                    </button>
                                </form>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    </div>
</main>
<?php include __DIR__ . '/footer.inc.php'; ?>
</body>
</html>
