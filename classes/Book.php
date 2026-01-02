<?php

class Book
{
    public int $id;
    public string $title;
    public float $price;
    public ?string $coverImage = null;  // ← Nullable!
    public ?string $authorName = null;  // ← Nullable!

    public static function findAll(Database $db, ?int $categoryId = null, ?string $search = null): array
    {
        $pdo = $db->getConnection();

        $sql = "SELECT 
                    b.id,
                    b.title,
                    b.price,
                    COALESCE(b.cover_image, 'default-book.jpg') AS cover_image,  -- ← Fallback
                    CONCAT(COALESCE(a.firstname, ''), ' ', COALESCE(a.lastname, 'Onbekend')) AS author_name
                FROM books b
                LEFT JOIN authors a ON b.author_id = a.id  -- ← LEFT JOIN voor veiligheid
                WHERE 1=1";

        $params = [];

        if ($categoryId !== null) {
            $sql .= " AND b.category_id = :category_id";
            $params[':category_id'] = $categoryId;
        }

        if ($search !== null && trim($search) !== '') {
            $sql .= " AND (b.title LIKE :search OR a.lastname LIKE :search)";
            $params[':search'] = '%' . trim($search) . '%';
        }

        $sql .= " ORDER BY b.title";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        $books = [];
        while ($row = $stmt->fetch()) {
            $book = new Book();
            $book->id = (int)$row['id'];
            $book->title = $row['title'];
            $book->price = (float)$row['price'];
            $book->coverImage = $row['cover_image'];  // ← Nu altijd string
            $book->authorName = $row['author_name'];  // ← Nu altijd string
            $books[] = $book;
        }

        return $books;
    }
}
