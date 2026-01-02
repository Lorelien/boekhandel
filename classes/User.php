<?php

class User
{
    private int $id;
    private string $firstname;
    private string $lastname;
    private string $email;
    private ?string $phone;
    private string $passwordHash;
    private string $role;
    private string $createdAt;

    // -------- Getters --------
    public function getId(): int
    {
        return $this->id;
    }

    public function getFirstname(): string
    {
        return $this->firstname;
    }

    public function getLastname(): string
    {
        return $this->lastname;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function getPasswordHash(): string
    {
        return $this->passwordHash;
    }

    public function getRole(): string
    {
        return $this->role;
    }

    public function getCreatedAt(): string
    {
        return $this->createdAt;
    }

    // -------- Setters --------
    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function setFirstname(string $firstname): void
    {
        $this->firstname = $firstname;
    }

    public function setLastname(string $lastname): void
    {
        $this->lastname = $lastname;
    }

    public function setEmail(string $email): void
    {
        $this->email = $email;
    }

    public function setPhone(?string $phone): void
    {
        $this->phone = $phone;
    }

    public function setPasswordHash(string $passwordHash): void
    {
        $this->passwordHash = $passwordHash;
    }

    public function setRole(string $role): void
    {
        $this->role = $role;
    }

    public function setCreatedAt(string $createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    // -------- Handige helper methods --------
    public function getFullName(): string
    {
        return $this->firstname . ' ' . $this->lastname;
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    // -------- Static factory methods (DB) --------
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

    // -------- Interne helper om DB-row naar User te maken --------
    private static function fromRow(array $row): User
    {
        $user = new User();
        $user->setId((int)$row['id']);
        $user->setFirstname($row['firstname']);
        $user->setLastname($row['lastname']);
        $user->setEmail($row['email']);
        $user->setPhone($row['phone'] ?? null);
        $user->setPasswordHash($row['password_hash']);
        $user->setRole($row['role'] ?? 'user');
        $user->setCreatedAt($row['created_at']);

        return $user;
    }
}
