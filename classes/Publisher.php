<?php

class Publisher
{
    public int $id;
    public string $name;
    public ?string $website;

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
        $publisher->id = (int)$row['id'];
        $publisher->name = $row['name'];
        $publisher->website = $row['website'] ?? null;

        return $publisher;
    }
}