<?php
require_once __DIR__ . '/auth.admin.php';
require_once __DIR__ . '/classes/Category.php';

$pdo = $db->getConnection();

$bookId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($bookId <= 0) {
    header('Location: admin.php');
    exit;
}

// Boek ophalen
$stmt = $pdo->prepare("
    SELECT id, category_id, title, isbn, description, price, language, publication_year, cover_image
    FROM books
    WHERE id = :id
");
$stmt->execute([':id' => $bookId]);
$book = $stmt->fetch();

if (!$book) {
    header('Location: admin.php');
    exit;
}

// Categorieën (zoals in admin.php)
$categories = Category::findAll($db);

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title      = trim($_POST['title'] ?? '');
    $price      = (float)($_POST['price'] ?? 0);
    $categoryId = (int)($_POST['category_id'] ?? 0);
    $isbn       = trim($_POST['isbn'] ?? '');
    $language   = trim($_POST['language'] ?? 'Nederlands');
    $year       = (int)($_POST['publication_year'] ?? date('Y'));
    $cover      = trim($_POST['cover_image'] ?? 'default-book.jpg');
    $description = $_POST['description'] ?? '';

    if ($title === '' || $price <= 0 || $categoryId <= 0) {
        $error = 'Titel, prijs (>0) en categorie zijn verplicht.';
    } else {
        $stmtUpdate = $pdo->prepare("
            UPDATE books
            SET category_id = :category_id,
                title       = :title,
                isbn        = :isbn,
                description = :description,
                price       = :price,
                language    = :language,
                publication_year = :year,
                cover_image = :cover_image
            WHERE id = :id
        ");

        $ok = $stmtUpdate->execute([
            ':category_id' => $categoryId,
            ':title'       => $title,
            ':isbn'        => $isbn,
            ':description' => $description,
            ':price'       => $price,
            ':language'    => $language,
            ':year'        => $year,
            ':cover_image' => $cover,
            ':id'          => $bookId,
        ]);

        if ($ok) {
            // Terug naar admin‑overzicht met succesmelding
            header('Location: admin.php');
            exit;
        } else {
            $error = 'Kon boek niet bijwerken.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <title>Boek bewerken - Admin</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/admin.css">
</head>
<body class="admin-edit-book-page">
<?php include __DIR__ . '/nav.inc.php'; ?>

<main class="site-main">
    <div class="container">
        <section class="admin-card">
            <h2>✏ Boek bewerken #<?= (int)$book['id'] ?></h2>

            <?php if ($error): ?>
                <div class="error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="post">
                <div class="form-grid">
                    <div>
                        <label for="title">Titel *</label>
                        <input type="text" id="title" name="title"
                               value="<?= htmlspecialchars($book['title']) ?>" required>
                    </div>

                    <div>
                        <label for="price">Prijs (€) *</label>
                        <input type="number" step="0.01" min="0" id="price" name="price"
                               value="<?= htmlspecialchars($book['price']) ?>" required>
                    </div>

                    <div>
                        <label for="category_id">Categorie *</label>
                        <select id="category_id" name="category_id" required>
                            <option value="">-- Kies subcategorie --</option>
                            <?php foreach ($categories as $cat): ?>
                                <?php if (!$cat->isMainCategory()): ?>
                                    <option value="<?= $cat->getId() ?>"
                                        <?= $book['category_id'] == $cat->getId() ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($cat->getName()) ?>
                                    </option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label for="isbn">ISBN</label>
                        <input type="text" id="isbn" name="isbn"
                               value="<?= htmlspecialchars($book['isbn']) ?>">
                    </div>

                    <div>
                        <label for="language">Taal</label>
                        <input type="text" id="language" name="language"
                               value="<?= htmlspecialchars($book['language']) ?>">
                    </div>

                    <div>
                        <label for="publication_year">Jaar</label>
                        <input type="number" id="publication_year" name="publication_year"
                               value="<?= htmlspecialchars($book['publication_year']) ?>">
                    </div>

                    <div>
                        <label for="cover_image">Cover bestandsnaam</label>
                        <input type="text" id="cover_image" name="cover_image"
                               value="<?= htmlspecialchars($book['cover_image']) ?>">
                    </div>

                    <div>
                        <label for="description">Beschrijving</label>
                        <textarea id="description" name="description"><?= htmlspecialchars($book['description']) ?></textarea>
                    </div>
                </div>

                <button type="submit" class="btn-primary">Wijzigingen opslaan</button>
                <a href="admin.php" class="btn-secondary" style="margin-left:1rem;">Annuleren</a>
            </form>
        </section>
    </div>
</main>

<?php include __DIR__ . '/footer.inc.php'; ?>
</body>
</html>
