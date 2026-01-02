<?php

class Cart
{
    private const SESSION_KEY = 'cart';
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION[self::SESSION_KEY])) {
            $_SESSION[self::SESSION_KEY] = [];
        }
    }

    public function addItem(int $bookId, int $quantity = 1): void
    {
        if ($quantity < 1) {
            return;
        }

        if (!isset($_SESSION[self::SESSION_KEY][$bookId])) {
            $_SESSION[self::SESSION_KEY][$bookId] = 0;
        }

        $_SESSION[self::SESSION_KEY][$bookId] += $quantity;
    }

    public function removeItem(int $bookId): void
    {
        unset($_SESSION[self::SESSION_KEY][$bookId]);
    }

    public function clear(): void
    {
        $_SESSION[self::SESSION_KEY] = [];
    }

    public function getItems(): array
    {
        return $_SESSION[self::SESSION_KEY];
    }

    public function getDetailedItems(): array
    {
        $items = $this->getItems();
        if (empty($items)) {
            return [];
        }

        $pdo = $this->db->getConnection();
        $ids = array_keys($items);
        $placeholders = implode(',', array_fill(0, count($ids), '?'));

        $stmt = $pdo->prepare("SELECT id, title, price, cover_image FROM books WHERE id IN ($placeholders)");
        $stmt->execute($ids);
        $result = [];

        while ($row = $stmt->fetch()) {
            $bookId = (int)$row['id'];
            $quantity = $items[$bookId] ?? 0;
            $result[] = [
                'id'         => $bookId,
                'title'      => $row['title'],
                'price'      => (float)$row['price'],
                'cover_image'=> $row['cover_image'],
                'quantity'   => $quantity,
                'subtotal'   => $quantity * (float)$row['price'],
            ];
        }

        return $result;
    }

    public function getTotal(): float
    {
        $total = 0.0;
        foreach ($this->getDetailedItems() as $item) {
            $total += $item['subtotal'];
        }
        return $total;
    }
}