<?php
require_once __DIR__ . '/classes/Database.php';
require_once __DIR__ . '/classes/AuthService.php';
require_once __DIR__ . '/classes/User.php'; 

$db = new Database();
$auth = new AuthService($db);

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstname = trim($_POST['firstname'] ?? '');
    $lastname = trim($_POST['lastname'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($firstname) || empty($lastname) || empty($email) || empty($password)) {
        $error = 'Alle velden zijn verplicht';
    } elseif (strlen($password) < 4) {
        $error = 'Wachtwoord moet minstens 4 tekens zijn';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Geen geldig emailadres';
    } elseif ($auth->register($firstname, $lastname, $email, $password)) {
        $success = 'Account succesvol aangemaakt! <a href="login.php">Log nu in</a>';
    } else {
        $error = 'Dit emailadres bestaat al. <a href="login.php">Inloggen?</a>';
    }
}
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registreren - Boekhandel</title>
    <link rel="stylesheet" href="assets/css/auth.css">
</head>

<body>
    <div class="card">
        <h1>👤 Nieuw account</h1>

        <?php if ($error): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="success"><?= $success ?></div>
        <?php else: ?>
            <form method="post">
                <div class="form-group">
                    <label for="firstname">Voornaam *</label>
                    <input type="text" id="firstname" name="firstname" value="<?= htmlspecialchars($_POST['firstname'] ?? '') ?>" required>
                </div>

                <div class="form-group">
                    <label for="lastname">Achternaam *</label>
                    <input type="text" id="lastname" name="lastname" value="<?= htmlspecialchars($_POST['lastname'] ?? '') ?>" required>
                </div>

                <div class="form-group">
                    <label for="email">Emailadres *</label>
                    <input type="email" id="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                </div>

                <div class="form-group">
                    <label for="phone">Telefoon (optioneel)</label>
                    <input type="tel" id="phone" name="phone" placeholder="+32 ...">
                </div>

                <div class="form-group">
                    <label for="password">Wachtwoord * (min. 4 tekens)</label>
                    <input type="password" id="password" name="password" required minlength="4">
                </div>

                <button type="submit">Account aanmaken</button>
            </form>
        <?php endif; ?>

        <div class="links">
            <p><a href="login.php">← Ik heb al een account</a></p>
            <p><a href="index.php">← Terug naar winkel</a></p>
        </div>
    </div>
</body>
</html>
