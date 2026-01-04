<?php
require_once __DIR__ . '/auth.admin.php';
require_once __DIR__ . '/classes/Book.php';
require_once __DIR__ . '/classes/Category.php';
require_once __DIR__ . '/classes/Author.php';
require_once __DIR__ . '/classes/Publisher.php';
require_once __DIR__ . '/classes/User.php'; 

$pdo = $db->getConnection();

// 1. Boeken ophalen (simpel overzicht)
$stmt = $pdo->query("
    SELECT b.id, b.title, b.price, b.isbn, c.name AS category_name
    FROM books b
    JOIN categories c ON b.category_id = c.id
    ORDER BY b.id DESC
");
$books = $stmt->fetchAll();

// 2. Categorieën ophalen voor dropdown
$categories = Category::findAll($db);

// 2b. Authors en publishers ophalen voor dropdowns
$authorStmt = $pdo->query("
    SELECT id, firstname, lastname 
    FROM authors 
    ORDER BY lastname ASC, firstname ASC
");
$authors = $authorStmt->fetchAll();

$publisherStmt = $pdo->query("
    SELECT id, name 
    FROM publishers 
    ORDER BY name ASC
");
$publishers = $publisherStmt->fetchAll();

// 3. Boek toevoegen / verwijderen
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'create') {
        $title       = trim($_POST['title'] ?? '');
        $price       = (float)($_POST['price'] ?? 0);
        $categoryId  = (int)($_POST['category_id'] ?? 0);
        $isbn        = trim($_POST['isbn'] ?? '');
        $authorId    = (int)($_POST['author_id'] ?? 0);
        $publisherId = (int)($_POST['publisher_id'] ?? 0);

        $newAuthorFirstname = trim($_POST['new_author_firstname'] ?? '');
        $newAuthorLastname  = trim($_POST['new_author_lastname'] ?? '');

        if ($title === '' || $price <= 0 || $categoryId <= 0) {
            $error = 'Titel, prijs (>0) en categorie zijn verplicht.';
        } else {

            if ($newAuthorFirstname !== '' && $newAuthorLastname !== '') {
            $stmtAuthor = $pdo->prepare("
                INSERT INTO authors (firstname, lastname)
                VALUES (:firstname, :lastname)
            ");
            $stmtAuthor->execute([
                ':firstname' => $newAuthorFirstname,
                ':lastname'  => $newAuthorLastname,
            ]);

            $authorId = (int)$pdo->lastInsertId();
            }
            $stmtCreate = $pdo->prepare("
                INSERT INTO books (category_id, author_id, publisher_id, title, isbn, description, price, language, publication_year, cover_image)
                VALUES (:category_id, :author_id, :publisher_id, :title, :isbn, :description, :price, :language, :year, :cover_image)
            ");

            $ok = $stmtCreate->execute([
                ':category_id' => $categoryId,
                ':author_id'   => $authorId ?: null,
                ':publisher_id'=> $publisherId ?: null,
                ':title'       => $title,
                ':isbn'        => $isbn,
                ':description' => $_POST['description'] ?? '',
                ':price'       => $price,
                ':language'    => $_POST['language'] ?? 'Nederlands',
                ':year'        => (int)($_POST['publication_year'] ?? date('Y')),
                ':cover_image' => $_POST['cover_image'] ?? 'default-book.jpg',
            ]);

            if ($ok) {
                $success = 'Boek succesvol toegevoegd.';
                header('Location: admin.php');
                exit;
            } else {
                $error = 'Kon boek niet opslaan.';
            }
        }
    }

    if (isset($_POST['action']) && $_POST['action'] === 'delete') {
        $bookId = (int)($_POST['book_id'] ?? 0);
        if ($bookId > 0) {
            $stmtDel = $pdo->prepare('DELETE FROM books WHERE id = :id');
            $stmtDel->execute([':id' => $bookId]);
            $success = 'Boek verwijderd.';
            header('Location: admin.php');
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <title>Admin - Boekhandel</title>
    <link rel="stylesheet" href="assets/css/style.css">   <!-- algemene layout -->
    <link rel="stylesheet" href="assets/css/admin.css">   <!-- admin-specifiek -->
</head>

<body>
<?php include __DIR__ . '/nav.inc.php'; ?>

<main class="site-main">
    <div class="container admin-layout">

        <!-- Overzicht boeken -->
        <section class="admin-card">
    <h2>📚 Bestaande boeken</h2>

    <?php if ($error): ?><div class="error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <?php if ($success): ?><div class="success"><?= htmlspecialchars($success) ?></div><?php endif; ?>

    <!-- Scroll-wrapper rond de tabel -->
    <div style="overflow-x: auto;">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Titel</th>
                    <th>Categorie</th>
                    <th>Prijs</th>
                    <th>ISBN</th>
                    <th>Acties</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($books as $row): ?>
                    <tr>
                        <td><?= (int)$row['id'] ?></td>
                        <td><?= htmlspecialchars($row['title']) ?></td>
                        <td><span class="badge"><?= htmlspecialchars($row['category_name']) ?></span></td>
                        <td>€ <?= number_format($row['price'], 2, ',', '.') ?></td>
                        <td><?= htmlspecialchars($row['isbn']) ?></td>
                        <td>
                            <a href="admin_edit_book.php?id=<?= (int)$row['id'] ?>" class="btn-secondary">
                                Bewerken
                            </a>
                            <form method="post" style="display:inline;"
                                  onsubmit="return confirm('Boek verwijderen?');">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="book_id" value="<?= (int)$row['id'] ?>">
                                <button type="submit" class="btn-danger">Verwijder</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($books)): ?>
                    <tr><td colspan="6">Nog geen boeken aanwezig.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

        <!-- Nieuw boek toevoegen -->
        <section class="admin-card">
            <h2>➕ Nieuw boek toevoegen</h2>
            <form method="post">
                <input type="hidden" name="action" value="create">
                <div class="form-grid">
                    <div>
                        <label for="title">Titel *</label>
                        <input type="text" id="title" name="title" required>
                    </div>
                    <div>
                        <label for="price">Prijs (€) *</label>
                        <input type="number" step="0.01" min="0" id="price" name="price" required>
                    </div>
                    <div>
                        <label for="category_id">Categorie *</label>
                        <select id="category_id" name="category_id" required>
                            <option value="">-- Kies subcategorie --</option>
                            <?php foreach ($categories as $cat): ?>
                                <?php if (!$cat->isMainCategory()): // enkel subcategorieën ?>
                                    <option value="<?= $cat->getId() ?>">
                                        <?= htmlspecialchars($cat->getName()) ?>
                                    </option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label for="isbn">ISBN</label>
                        <input type="text" id="isbn" name="isbn">
                    </div>
                    <div>
                        <label for="language">Taal</label>
                        <input type="text" id="language" name="language" value="Nederlands">
                    </div>
                    <div>
                        <label for="publication_year">Jaar</label>
                        <input type="number" id="publication_year" name="publication_year" value="<?= date('Y') ?>">
                    </div>
                    <div>
                        <label for="cover_image">Cover bestandsnaam</label>
                        <input type="text" id="cover_image" name="cover_image" value="default-book.jpg">
                    </div>
                    <div>
                        <label for="author_id">Auteur (optioneel)</label>
                        <select id="author_id" name="author_id">
                            <option value="0">-- Kies auteur --</option>
                            <?php foreach ($authors as $author): ?>
                                <option value="<?= (int)$author['id'] ?>">
                                    <?= htmlspecialchars($author['lastname'] . ' ' . $author['firstname']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label for="new_author_firstname">Nieuwe auteur voornaam (optioneel)</label>
                        <input type="text" id="new_author_firstname" name="new_author_firstname">
                    </div>
                    <div>
                        <label for="new_author_lastname">Nieuwe auteur achternaam (optioneel)</label>
                        <input type="text" id="new_author_lastname" name="new_author_lastname">
                    </div>

                    <div>
                        <label for="publisher_id">Uitgever (optioneel)</label>
                        <select id="publisher_id" name="publisher_id">
                            <option value="0">-- Kies uitgever --</option>
                            <?php foreach ($publishers as $pub): ?>
                                <option value="<?= (int)$pub['id'] ?>">
                                    <?= htmlspecialchars($pub['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label for="description">Beschrijving</label>
                        <textarea id="description" name="description"></textarea>
                    </div>
                </div>

                <button type="submit" class="btn-primary">Boek opslaan</button>
            </form>
        </section>
    </div>
</main>
</body>
</html>
