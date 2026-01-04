<?php
require_once __DIR__ . '/classes/Database.php';
require_once __DIR__ . '/classes/AuthService.php';
require_once __DIR__ . '/classes/User.php';

$db   = new Database();
$auth = new AuthService($db);

$currentUser = $auth->getCurrentUser();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'logout') {
        $auth->logout();
        header('Location: login.php?logged_out=1');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Uitloggen - Boekhandel</title>
    <link rel="stylesheet" href="assets/css/auth.css">
</head>
<body>
    <div class="card">
        <h1>👋 Uitloggen</h1>

        <?php if ($currentUser): ?>
            <p><strong>Je bent ingelogd als <?= htmlspecialchars($currentUser->getFullName()) ?>.</strong></p>
            <p>Weet je zeker dat je wilt uitloggen?</p>

            <form method="post" style="margin-top:1rem;">
                <input type="hidden" name="action" value="logout">
                <button type="submit">Ja, log mij uit</button>
            </form>

            <div class="links" style="margin-top:1rem;">
                <p><a href="index.php">← Terug naar winkel</a></p>
            </div>
        <?php else: ?>
            <p>Je bent al uitgelogd.</p>
            <div class="links" style="margin-top:1rem;">
                <p><a href="login.php">→ Naar login</a></p>
                <p><a href="index.php">← Terug naar winkel</a></p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
