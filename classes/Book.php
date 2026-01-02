<?php
class Book
{
    private int $id;
    private string $title;
    private float $price;
    private ?string $coverImage;
    private ?string $authorName;

    // Getters
    public function getId(): int { return $this->id; }
    public function getTitle(): string { return $this->title; }
    public function getPrice(): float { return $this->price; }
    public function getCoverImage(): ?string { return $this->coverImage; }
    public function getAuthorName(): ?string { return $this->authorName; }

    // Setters
    public function setId(int $id): void { $this->id = $id; }
    public function setTitle(string $title): void { $this->title = $title; }
    public function setPrice(float $price): void { $this->price = $price; }
    public function setCoverImage(?string $coverImage): void { $this->coverImage = $coverImage; }
    public function setAuthorName(?string $authorName): void { $this->authorName = $authorName; }

    public static function findAll(Database $db, ?int $categoryId = null, ?string $search = null): array
    {
        $pdo = $db->getConnection();
        $sql = "SELECT b.id, b.title, b.price, COALESCE(b.cover_image, 'default-book.jpg') AS cover_image, 
                       CONCAT(COALESCE(a.firstname, ''), ' ', COALESCE(a.lastname, 'Onbekend')) AS author_name
                FROM books b LEFT JOIN authors a ON b.author_id = a.id WHERE 1=1";

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
            $book->setId((int)$row['id']);
            $book->setTitle($row['title']);
            $book->setPrice((float)$row['price']);
            $book->setCoverImage($row['cover_image']);
            $book->setAuthorName($row['author_name']);
            $books[] = $book;
        }
        return $books;
    }
}
