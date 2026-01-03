<?php
require_once __DIR__ . '/classes/Database.php';
require_once __DIR__ . '/classes/Book.php';
require_once __DIR__ . '/classes/Category.php';
require_once __DIR__ . '/classes/User.php';
require_once __DIR__ . '/classes/AuthService.php';
require_once __DIR__ . '/classes/Review.php';

$db   = new Database();
$auth = new AuthService($db);
$currentUser = $auth->getCurrentUser();

$bookId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($bookId <= 0) {
    header('Location: index.php');
    exit;
}

$pdo = $db->getConnection();

// Boek ophalen
$stmt = $pdo->prepare("
    SELECT b.*, 
           CONCAT(COALESCE(a.firstname, ''), ' ', COALESCE(a.lastname, '')) AS author_name,
           c.name AS category_name
    FROM books b
    LEFT JOIN authors a ON b.author_id = a.id
    LEFT JOIN categories c ON b.category_id = c.id
    WHERE b.id = :id
");
$stmt->execute([':id' => $bookId]);
$bookRow = $stmt->fetch();

if (!$bookRow) {
    header('HTTP/1.0 404 Not Found');
    echo 'Boek niet gevonden.';
    exit;
}

// Reviews ophalen
$reviews = Review::findByBook($db, $bookId);

// Heeft de ingelogde gebruiker dit boek gekocht?
$hasPurchased = false;
if ($currentUser) {
    $stmtBought = $pdo->prepare("
        SELECT COUNT(*) AS cnt
        FROM orders o
        JOIN order_items oi ON o.id = oi.order_id
        WHERE o.user_id = :user_id AND oi.book_id = :book_id
    ");
    $stmtBought->execute([
        ':user_id' => $currentUser->getId(),
        ':book_id' => $bookId
    ]);
    $hasPurchased = ((int)$stmtBought->fetchColumn() > 0);
}
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($bookRow['title']) ?> - Boekhandel</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<header class="site-header">
    <div class="container">
        <h1 class="logo">📚 Boekhandel</h1>
        <nav class="main-nav">
            <a href="index.php">Home</a>
            <a href="winkelmandje.php">🛒</a>
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

<main class="site-main">
    <div class="container book-page">
        <!-- Linkerkolom: cover + actie -->
        <div>
            <img src="assets/images/<?= htmlspecialchars($bookRow['cover_image'] ?? 'default-book.jpg') ?>"
                 alt="<?= htmlspecialchars($bookRow['title']) ?>"
                 class="book-cover-large">

            <div class="book-actions">
                <form method="post" action="winkelmandje.php">
                    <input type="hidden" name="book_id" value="<?= (int)$bookRow['id'] ?>">
                    <input type="hidden" name="quantity" value="1">
                    <button type="submit" name="action" value="add" class="btn btn-secondary">
                        🛒 Voeg toe aan mandje
                    </button>
                </form>
            </div>
        </div>

        <!-- Rechterkolom: info + reviews -->
        <div>
            <div class="book-meta">
                <h1><?= htmlspecialchars($bookRow['title']) ?></h1>
                <p><strong>Auteur:</strong> <?= htmlspecialchars($bookRow['author_name'] ?: 'Onbekend') ?></p>
                <p><strong>Categorie:</strong> <?= htmlspecialchars($bookRow['category_name'] ?? '') ?></p>
                <p><strong>Prijs:</strong> € <?= number_format($bookRow['price'], 2, ',', '.') ?></p>
                <p><strong>Taal:</strong> <?= htmlspecialchars($bookRow['language']) ?></p>
                <p><strong>Jaar:</strong> <?= htmlspecialchars($bookRow['publication_year']) ?></p>
                <p><strong>ISBN:</strong> <?= htmlspecialchars($bookRow['isbn']) ?></p>
            </div>

            <div class="book-description">
                <h2>Beschrijving</h2>
                <p><?= nl2br(htmlspecialchars($bookRow['description'])) ?></p>
            </div>

            <div class="reviews-section" id="reviews">
                <h2>Reviews</h2>

                <?php if (empty($reviews)): ?>
                    <p id="no-reviews-text">Er zijn nog geen reviews voor dit boek.</p>
                <?php else: ?>
                    <div id="reviews-list">
                        <?php foreach ($reviews as $review): ?>
                            <div class="review-card">
                                <div class="review-header">
                                    <span><?= htmlspecialchars($review['firstname'] . ' ' . $review['lastname']) ?></span>
                                    <span>⭐ <?= (int)$review['rating'] ?>/5 · <?= htmlspecialchars($review['review_date']) ?></span>
                                </div>
                                <div class="review-comment">
                                    <?= nl2br(htmlspecialchars($review['comment'])) ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <!-- Review formulier / voorwaarden -->
                <?php if (!$currentUser): ?>
                    <p style="margin-top:1rem;">Log in om een review te schrijven.</p>
                <?php elseif (!$hasPurchased): ?>
                    <p style="margin-top:1rem; color:#9f1239;">
                        Je kunt alleen een review plaatsen als je dit boek hebt gekocht.
                    </p>
                <?php else: ?>
                    <div class="review-form" style="margin-top:1rem;">
                        <h3>Schrijf een review</h3>
                        <div id="review-error" style="color:#b91c1c; margin-bottom:0.5rem; display:none;"></div>

                        <form method="post" action="review_submit.php" id="review-form">
                            <input type="hidden" name="book_id" value="<?= (int)$bookRow['id'] ?>">

                            <label for="rating">Rating</label>
                            <select name="rating" id="rating" required>
                                <option value="5">5 - Uitstekend</option>
                                <option value="4">4 - Goed</option>
                                <option value="3">3 - Oké</option>
                                <option value="2">2 - Matig</option>
                                <option value="1">1 - Slecht</option>
                            </select>
                            <br><br>

                            <label for="comment">Commentaar</label>
                            <textarea name="comment" id="comment" required></textarea>
                            <br>

                            <button type="submit" class="btn btn-primary">Review plaatsen</button>
                        </form>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('review-form');
    if (!form) return;

    const errorBox   = document.getElementById('review-error');
    const reviewsDiv = document.getElementById('reviews-list');
    const noReviews  = document.getElementById('no-reviews-text');

    form.addEventListener('submit', function (e) {
        e.preventDefault(); // normale submit tegenhouden (AJAX)

        if (errorBox) {
            errorBox.style.display = 'none';
            errorBox.textContent = '';
        }

        const formData = new FormData(form);

        fetch('api_review.php', {
            method: 'POST',
            body: formData,
        })
        .then(response => response.json())
        .then(data => {
            if (!data.success) {
                if (errorBox) {
                    errorBox.textContent = data.error || 'Er ging iets mis bij het opslaan van je review.';
                    errorBox.style.display = 'block';
                }
                return;
            }

            if (noReviews) {
                noReviews.style.display = 'none';
            }

            let list = reviewsDiv;
            if (!list) {
                list = document.createElement('div');
                list.id = 'reviews-list';
                const section = document.getElementById('reviews');
                if (section) {
                    section.appendChild(list);
                }
            }

            const card = document.createElement('div');
            card.className = 'review-card';

            const header = document.createElement('div');
            header.className = 'review-header';

            const nameSpan = document.createElement('span');
            nameSpan.textContent = data.userName || 'Onbekende gebruiker';

            const metaSpan = document.createElement('span');
            metaSpan.textContent = '⭐ ' + data.rating + '/5 · ' + (data.date || '');

            header.appendChild(nameSpan);
            header.appendChild(metaSpan);

            const commentDiv = document.createElement('div');
            commentDiv.className = 'review-comment';
            commentDiv.textContent = data.comment;

            card.appendChild(header);
            card.appendChild(commentDiv);

            // Nieuwste review bovenaan
            list.prepend(card);

            form.reset();
        })
        .catch(err => {
            console.error(err);
            if (errorBox) {
                errorBox.textContent = 'Er ging iets mis bij het versturen. Probeer later opnieuw.';
                errorBox.style.display = 'block';
            }
        });
    });
});
</script>
</body>
</html>
