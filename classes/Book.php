<?php

class Book
{
    public int $id;
    public string $title;
    public float $price;
    public string $coverImage;
    public string $authorName;

    public static function findAll(Database $db, ?int $categoryId = null, ?string $search = null): array
    {
        $pdo = $db->getConnection();

        $sql = "SELECT 
                    b.id,
                    b.title,
                    b.price,
                    b.cover_image,
                    CONCAT(a.firstname, ' ', a.lastname) AS author_name
                FROM books b
                JOIN authors a ON b.author_id = a.id
                WHERE 1=1";

        $params = [];

        if ($categoryId !== null) {
            $sql .= " AND b.category_id = :category_id";
            $params[':category_id'] = $categoryId;
        }

        if ($search !== null && $search !== '') {
            $sql .= " AND (b.title LIKE :search OR a.lastname LIKE :search)";
            $params[':search'] = '%' . $search . '%';
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
            $book->coverImage = $row['cover_image'];
            $book->authorName = $row['author_name'];
            $books[] = $book;
        }

        return $books;
    }
}
