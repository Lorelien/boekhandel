<?php
require_once __DIR__ . '/classes/Database.php';
require_once __DIR__ . '/classes/User.php';
require_once __DIR__ . '/classes/AuthService.php';

$db   = new Database();
$auth = new AuthService($db);
$currentUser = $auth->getCurrentUser();

if (!$currentUser) {
    header('Location: login.php');
    exit;
}

$successMessage = '';
$errorMessage   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword     = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if ($newPassword === '' || $confirmPassword === '' || $currentPassword === '') {
        $errorMessage = 'Gelieve alle velden in te vullen.';
    } elseif ($newPassword !== $confirmPassword) {
        $errorMessage = 'De nieuwe wachtwoorden komen niet overeen.';
    } elseif (strlen($newPassword) < 6) {
        $errorMessage = 'Het nieuwe wachtwoord moet minstens 6 tekens lang zijn.';
    } else {
        // Controleer of huidig wachtwoord correct is
        if (!$auth->verifyPassword($currentUser, $currentPassword)) {
            $errorMessage = 'Het huidige wachtwoord is niet correct.';
        } else {
            // Wachtwoord updaten
            if ($auth->changePassword($currentUser, $newPassword)) {
                $successMessage = 'Je wachtwoord is succesvol gewijzigd.';
            } else {
                $errorMessage = 'Er ging iets mis bij het wijzigen van je wachtwoord.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <title>Wachtwoord wijzigen - Boekhandel</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<?php include __DIR__ . '/nav.inc.php'; ?>

<main class="site-main">
    <div class="container">
        <h2>Wachtwoord wijzigen</h2>

        <?php if ($successMessage): ?>
            <p class="alert alert-success"><?= htmlspecialchars($successMessage) ?></p>
        <?php endif; ?>

        <?php if ($errorMessage): ?>
            <p class="alert alert-error"><?= htmlspecialchars($errorMessage) ?></p>
        <?php endif; ?>

        <form method="post" class="form">
            <div class="form-group">
                <label for="current_password">Huidig wachtwoord</label>
                <input type="password" id="current_password" name="current_password" required>
            </div>

            <div class="form-group">
                <label for="new_password">Nieuw wachtwoord</label>
                <input type="password" id="new_password" name="new_password" required>
            </div>

            <div class="form-group">
                <label for="confirm_password">Bevestig nieuw wachtwoord</label>
                <input type="password" id="confirm_password" name="confirm_password" required>
            </div>

            <button type="submit" class="btn btn-primary">Wachtwoord opslaan</button>
        </form>
    </div>
</main>
</body>
</html>
