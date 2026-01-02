<?php

class Category
{
    public int $id;
    public string $name;
    public string $slug;
    public bool $isMainCategory;  // ← Nieuw: onderscheid hoofd/sub

    public static function findAll(Database $db): array
    {
        $pdo = $db->getConnection();
        $stmt = $pdo->query("SELECT id, name, slug FROM categories ORDER BY name");
        $categories = [];

        while ($row = $stmt->fetch()) {
            $cat = new Category();
            $cat->id = (int)$row['id'];
            $cat->name = $row['name'];
            $cat->slug = $row['slug'];
            $cat->isMainCategory = in_array($cat->id, [1,2,3,4]);  // ← Jouw 4 hoofdcats
            $categories[] = $cat;
        }

        return $categories;
    }

    public static function findSubcategories(Database $db, int $mainCatId): array
    {
        // Vaste mapping: welke subcats horen bij welke hoofdcategorie
        $subcatMapping = [
            1 => [5,6,7],  // Fictie: Thrillers, Fantasy, Romans
            2 => [8,9,10], // Non-fictie: Biografie, Geschiedenis, Zelfhulp
            3 => [11,12],  // Jeugd: Prentenboeken, Jeugdromans
            4 => [13,14,15] // Studie: IT, Webdesign, Economie
        ];

        $subIds = $subcatMapping[$mainCatId] ?? [];
        if (empty($subIds)) return [];

        $pdo = $db->getConnection();
        $placeholders = implode(',', array_fill(0, count($subIds), '?'));
        $stmt = $pdo->prepare("SELECT id, name, slug FROM categories WHERE id IN ($placeholders) ORDER BY name");
        $stmt->execute($subIds);

        $subs = [];
        while ($row = $stmt->fetch()) {
            $sub = new Category();
            $sub->id = (int)$row['id'];
            $sub->name = $row['name'];
            $sub->slug = $row['slug'];
            $subs[] = $sub;
        }
        return $subs;
    }
}