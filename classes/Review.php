<?php

class Review
{
    private int $id;
    private int $bookId;
    private int $userId;
    private string $comment;
    private int $rating;        // 1–5
    private string $reviewDate;

    // Getters
    public function getId(): int { return $this->id; }
    public function getBookId(): int { return $this->bookId; }
    public function getUserId(): int { return $this->userId; }
    public function getComment(): string { return $this->comment; }
    public function getRating(): int { return $this->rating; }
    public function getReviewDate(): string { return $this->reviewDate; }

    // Setters
    public function setId(int $id): void { $this->id = $id; }
    public function setBookId(int $bookId): void { $this->bookId = $bookId; }
    public function setUserId(int $userId): void { $this->userId = $userId; }
    public function setComment(string $comment): void { $this->comment = $comment; }
    public function setRating(int $rating): void { $this->rating = $rating; }
    public function setReviewDate(string $reviewDate): void { $this->reviewDate = $reviewDate; }

    public static function create(Database $db, int $bookId, int $userId, int $rating, string $comment): bool
    {
        $pdo = $db->getConnection();

        $stmt = $pdo->prepare(
            'INSERT INTO reviews (book_id, user_id, comment, rating, review_date)
             VALUES (:book_id, :user_id, :comment, :rating, NOW())'
        );

        return $stmt->execute([
            ':book_id' => $bookId,
            ':user_id' => $userId,
            ':comment' => $comment,
            ':rating'  => $rating,
        ]);
    }

    public static function findByBook(Database $db, int $bookId): array
    {
        $pdo = $db->getConnection();
        $stmt = $pdo->prepare(
            'SELECT r.*, u.firstname, u.lastname
             FROM reviews r
             JOIN users u ON r.user_id = u.id
             WHERE r.book_id = :book_id
             ORDER BY r.review_date DESC'
        );
        $stmt->execute([':book_id' => $bookId]);

        return $stmt->fetchAll();
    }
}
