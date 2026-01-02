<?php

class OrderItem
{
    private int $id;
    private int $orderId;
    private int $bookId;
    private int $quantity;
    private float $unitPrice;

    // Getters
    public function getId(): int { return $this->id; }
    public function getOrderId(): int { return $this->orderId; }
    public function getBookId(): int { return $this->bookId; }
    public function getQuantity(): int { return $this->quantity; }
    public function getUnitPrice(): float { return $this->unitPrice; }

    // Setters
    public function setId(int $id): void { $this->id = $id; }
    public function setOrderId(int $orderId): void { $this->orderId = $orderId; }
    public function setBookId(int $bookId): void { $this->bookId = $bookId; }
    public function setQuantity(int $quantity): void { $this->quantity = $quantity; }
    public function setUnitPrice(float $unitPrice): void { $this->unitPrice = $unitPrice; }
}
