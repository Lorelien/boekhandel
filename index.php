<?php
require_once __DIR__ . '/src/Database.php';
require_once __DIR__ . '/src/Book.php';
require_once __DIR__ . '/src/Category.php';

$db = new Database();

$categoryId = isset($_GET['category_id']) ? (int)$_GET['category_id'] : null;
$search = $_GET['q'] ?? null;

$categories = Category::findAll($db);
$books = Book::findAll($db, $categoryId, $search);

?><!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <title>Boekhandel</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<header class="site-header">
    <div class="container">
        <h1 class="logo">Boekhandel</h1>
        <nav class="main-nav">
            <a href="index.php">Home</a>
            <a href="winkelmandje.php">Winkelmandje</a>
            <a href="account.php">Account</a>
        </nav>
    </div>
</header>

<main class="site-main">
    <div class="container">
        <aside class="sidebar">
            <h2>Categorieën</h2>
            <ul class="category-list">
                <li><a href="?category=fictie">Fictie</a></li>
                <li><a href="?category=non-fictie">Non-fictie</a></li>
                <li><a href="?category=jeugd">Jeugd &amp; kinderboeken</a></li>
                <li><a href="?category=studie">Studie &amp; vakliteratuur</a></li>
            </ul>

            <h2>Zoeken</h2>
            <form action="index.php" method="get" class="search-form">
                <input type="text" name="q" placeholder="Zoek op titel of auteur">
                <button type="submit">Zoek</button>
            </form>
        </aside>

        <section class="product-list">
            <h2>Boeken</h2>

            <!-- Hier komen straks dynamisch de boeken uit PHP -->
            <article class="book-card">
                <img src="assets/images/example-cover.jpg" alt="Boektitel" class="book-cover">
                <div class="book-info">
                    <h3 class="book-title">Voorbeeld titel</h3>
                    <p class="book-author">Voorbeeld auteur</p>
                    <p class="book-price">€ 19,99</p>
                    <a href="boek.php?id=1" class="btn">Bekijk details</a>
                    <button class="btn btn-secondary">Toevoegen aan mandje</button>
                </div>
            </article>

        </section>
    </div>
</main>

<footer class="site-footer">
    <div class="container">
        <p>&copy; <?php echo date('Y'); ?> Boekhandel</p>
    </div>
</footer>
</body>
</html>
