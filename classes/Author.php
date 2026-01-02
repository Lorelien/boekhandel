<?php

class Author
{
    private int $id;
    private string $firstname;
    private string $lastname;

    // Getters
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

    public function getFullName(): string
    {
        return $this->firstname . ' ' . $this->lastname;
    }

    // Setters
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

    // Static DB‑methods
    public static function findById(Database $db, int $id): ?Author
    {
        $pdo = $db->getConnection();
        $stmt = $pdo->prepare('SELECT id, firstname, lastname FROM authors WHERE id = :id');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();

        if (!$row) {
            return null;
        }

        $author = new Author();
        $author->setId((int)$row['id']);
        $author->setFirstname($row['firstname']);
        $author->setLastname($row['lastname']);

        return $author;
    }
}
