<?php

class Author
{
    public int $id;
    public string $firstname;
    public string $lastname;

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
        $author->id = (int)$row['id'];
        $author->firstname = $row['firstname'];
        $author->lastname = $row['lastname'];

        return $author;
    }

    public function getFullName(): string
    {
        return $this->firstname . ' ' . $this->lastname;
    }
}
