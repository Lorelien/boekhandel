<?php
class Category
{
    private int $id;
    private string $name;
    private string $slug;
    private bool $isMainCategory;

    // Getters
    public function getId(): int 
    { 
        return $this->id; 
    }

    public function getName(): string 
    { 
        return $this->name; 
    }

    public function getSlug(): string 
    { 
        return $this->slug; 
    }

    public function isMainCategory(): bool 
    { 
        return $this->isMainCategory; 
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

    public function setSlug(string $slug): void 
    { 
        $this->slug = $slug; 
    }

    public function setIsMainCategory(bool $isMain): void 
    { 
        $this->isMainCategory = $isMain; 
    }

    // Static methods blijven hetzelfde
    public static function findAll(Database $db): array
    {
        $pdo = $db->getConnection();
        $stmt = $pdo->query("SELECT id, name, slug FROM categories ORDER BY name");
        $categories = [];

        while ($row = $stmt->fetch()) {
            $cat = new Category();
            $cat->setId((int)$row['id']);
            $cat->setName($row['name']);
            $cat->setSlug($row['slug']);
            $cat->setIsMainCategory(in_array($cat->getId(), [1,2,3,4])); // Jouw 4 hoofdcats
            $categories[] = $cat;
        }
        return $categories;
    }

    public static function findSubcategories(Database $db, int $mainCatId): array
    {
        // Vaste mapping subcategorieën per hoofdcategorie
        $subcatMapping = [
            1 => [5,6,7],   // Fictie: Thrillers(5), Fantasy(6), Romans(7)
            2 => [8,9,10],  // Non-fictie: Biografie(8), Geschiedenis(9), Zelfhulp(10)
            3 => [11,12],   // Jeugd: Prentenboeken(11), Jeugdromans(12)
            4 => [13,14,15] // Studie: IT(13), Webdesign(14), Economie(15)
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
            $sub->setId((int)$row['id']);
            $sub->setName($row['name']);
            $sub->setSlug($row['slug']);
            $subs[] = $sub;
        }
        return $subs;
    }
}
