<?php

class Category
{
    public int $id;
    public string $name;
    public string $slug;

    public static function findAll(Database $db): array
    {
        $pdo = $db->getConnection();
        $stmt = $pdo->query("SELECT id, name, slug FROM categories ORDER BY name");
        $categories = [];

        while ($row = $stmt->fetch()) {
            $category = new Category();
            $category->id = (int)$row['id'];
            $category->name = $row['name'];
            $category->slug = $row['slug'];
            $categories[] = $category;
        }

        return $categories;
    }
}
