<?php

class Order
{
    public int $id;
    public int $userId;
    public string $orderDate;
    public float $totalPrice;

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
                ':user_id'     => $user->id,
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
            $order->id = $orderId;
            $order->userId = $user->id;
            $order->orderDate = date('Y-m-d H:i:s');
            $order->totalPrice = $total;

            $cart->clear();

            return $order;
        } catch (Throwable $e) {
            $pdo->rollBack();
            return null;
        }
    }
}