<?php

class Review
{
    public int $id;
    public int $bookId;
    public int $userId;
    public string $comment;
    public int $rating; // 1-5
    public string $reviewDate;

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