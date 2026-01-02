<?php

class Publisher
{
    private int $id;
    private string $name;
    private ?string $website;

    // Getters
    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getWebsite(): ?string
    {
        return $this->website;
    }

    // Setters
    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function setWebsite(?string $website): void
    {
        $this->website = $website;
    }

    public static function findById(Database $db, int $id): ?Publisher
    {
        $pdo = $db->getConnection();
        $stmt = $pdo->prepare('SELECT id, name, website FROM publishers WHERE id = :id');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();

        if (!$row) {
            return null;
        }

        $publisher = new Publisher();
        $publisher->setId((int)$row['id']);
        $publisher->setName($row['name']);
        $publisher->setWebsite($row['website'] ?? null);

        return $publisher;
    }
}
