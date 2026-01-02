<?php

class Order
{
    private int $id;
    private int $userId;
    private string $orderDate;
    private float $totalPrice;

    // Getters
    public function getId(): int { return $this->id; }
    public function getUserId(): int { return $this->userId; }
    public function getOrderDate(): string { return $this->orderDate; }
    public function getTotalPrice(): float { return $this->totalPrice; }

    // Setters
    public function setId(int $id): void { $this->id = $id; }
    public function setUserId(int $userId): void { $this->userId = $userId; }
    public function setOrderDate(string $orderDate): void { $this->orderDate = $orderDate; }
    public function setTotalPrice(float $totalPrice): void { $this->totalPrice = $totalPrice; }

    public static function createFromCart(Database $db, User $user, Cart $cart): ?Order
    {
        $pdo = $db->getConnection();
        $pdo->beginTransaction();

        try {
            $total = $cart->getTotal();

            $stmt = $pdo->prepare(
                'INSERT INTO orders (user_id, order_date, total_price)
                 VALUES (:user_id, NOW(), :total_price)'
            );
            $stmt->execute([
                ':user_id'     => $user->getId(),
                ':total_price' => $total,
            ]);

            $orderId = (int)$pdo->lastInsertId();

            $items = $cart->getDetailedItems();
            $itemStmt = $pdo->prepare(
                'INSERT INTO order_items (order_id, book_id, quantity, unit_price)
                 VALUES (:order_id, :book_id, :quantity, :unit_price)'
            );

            foreach ($items as $item) {
                $itemStmt->execute([
                    ':order_id'   => $orderId,
                    ':book_id'    => $item['id'],
                    ':quantity'   => $item['quantity'],
                    ':unit_price' => $item['price'],
                ]);
            }

            $pdo->commit();

            $order = new Order();
            $order->setId($orderId);
            $order->setUserId($user->getId());
            $order->setOrderDate(date('Y-m-d H:i:s'));
            $order->setTotalPrice($total);

            $cart->clear();

            return $order;
        } catch (Throwable $e) {
            $pdo->rollBack();
            return null;
        }
    }
}
