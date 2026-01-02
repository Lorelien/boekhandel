<?php

class User
{
    public int $id;
    public string $firstname;
    public string $lastname;
    public string $email;
    public ?string $phone;
    public string $passwordHash;
    public string $role; // 'user' of 'admin'

    public static function findByEmail(Database $db, string $email): ?User
    {
        $pdo = $db->getConnection();
        $stmt = $pdo->prepare('SELECT * FROM users WHERE email = :email');
        $stmt->execute([':email' => $email]);
        $row = $stmt->fetch();

        if (!$row) {
            return null;
        }

        return self::fromRow($row);
    }

    public static function findById(Database $db, int $id): ?User
    {
        $pdo = $db->getConnection();
        $stmt = $pdo->prepare('SELECT * FROM users WHERE id = :id');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();

        if (!$row) {
            return null;
        }

        return self::fromRow($row);
    }

    private static function fromRow(array $row): User
    {
        $user = new User();
        $user->id = (int)$row['id'];
        $user->firstname = $row['firstname'];
        $user->lastname = $row['lastname'];
        $user->email = $row['email'];
        $user->phone = $row['phone'] ?? null;
        $user->passwordHash = $row['password_hash'];
        $user->role = $row['role'] ?? 'user';

        return $user;
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }
}