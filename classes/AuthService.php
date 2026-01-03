<?php

class AuthService
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public function register(string $firstname, string $lastname, string $email, string $password): bool
    {
        $pdo = $this->db->getConnection();

        // check of email al bestaat
        $existing = User::findByEmail($this->db, $email);
        if ($existing !== null) {
            return false;
        }

        $hash = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $pdo->prepare(
            'INSERT INTO users (firstname, lastname, email, password_hash, created_at, role)
             VALUES (:firstname, :lastname, :email, :password_hash, NOW(), :role)'
        );

        return $stmt->execute([
            ':firstname'     => $firstname,
            ':lastname'      => $lastname,
            ':email'         => $email,
            ':password_hash' => $hash,
            ':role'          => 'user',
        ]);
    }

    public function login(string $email, string $password): bool
    {
        $user = User::findByEmail($this->db, $email);
        if ($user === null) {
            return false;
        }

        if (!password_verify($password, $user->getPasswordHash())) {
            return false;
        }

        $_SESSION['user_id'] = $user->getId();
        return true;
    }

    public function logout(): void
    {
        $_SESSION = [];
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
    }

    public function getCurrentUser(): ?User
    {
        if (empty($_SESSION['user_id'])) {
            return null;
        }

        return User::findById($this->db, (int)$_SESSION['user_id']);
    }

    public function verifyPassword(User $user, string $plainPassword): bool
{
    return password_verify($plainPassword, $user->getPasswordHash());
}

public function changePassword(User $user, string $newPlainPassword): bool
{
    $hash = password_hash($newPlainPassword, PASSWORD_DEFAULT);

    $pdo = $this->db->getConnection();
    $stmt = $pdo->prepare("UPDATE users SET password = :password WHERE id = :id");
    $ok = $stmt->execute([
        ':password' => $hash,
        ':id'       => $user->getId()
    ]);

    if ($ok) {
        $user->setPasswordHash($hash); 
    }

    return $ok;
}

}