<?php
require_once __DIR__ . '/classes/Database.php';
require_once __DIR__ . '/classes/User.php'; 
require_once __DIR__ . '/classes/AuthService.php';

$db = new Database();
$auth = new AuthService($db);

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'login') {
            $email = $_POST['email'] ?? '';
            $password = $_POST['password'] ?? '';
            
            if ($auth->login($email, $password)) {
                header('Location: index.php');
                exit;
            } else {
                $error = 'Ongeldige email of wachtwoord';
            }
        } elseif ($_POST['action'] === 'logout') {
            $auth->logout();
            header('Location: index.php');
            exit;
        }
    }
}

$currentUser = $auth->getCurrentUser();
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Boekhandel</title>
    <link rel="stylesheet" href="assets/css/auth.css">
</head>
<body>
    <div class="card">
        <h1>🔐 Login</h1>

        <?php if ($currentUser): ?>
            <!-- Ingelogd -->
            <div class="user-info">
                <p><strong>Welkom, <?= htmlspecialchars($currentUser->getFirstname() . ' ' . $currentUser->getLastname()) ?>!</strong></p>
                <?php if ($currentUser->isAdmin()): ?>
                    <p style="color: #059669; font-weight: bold;">👑 Admin modus actief</p>
                <?php endif; ?>
                <form method="post" style="display: inline-block; margin-right: 1rem;">
                    <input type="hidden" name="action" value="logout">
                    <button type="submit">Uitloggen</button>
                </form>
                <a href="index.php" class="btn btn-secondary">← Winkel</a>
            </div>
        <?php else: ?>
            <!-- Login formulier -->
            <?php if ($error): ?>
                <div class="error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="post">
                <input type="hidden" name="action" value="login">
                <div class="form-group">
                    <label for="email">Emailadres</label>
                    <input type="email" id="email" name="email" required 
                           value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label for="password">Wachtwoord</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <button type="submit">Inloggen</button>
            </form>

            <div class="links">
                <p><a href="register.php">Nog geen account? Registreer gratis</a></p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
