<?php

class Database
{
    private string $host = 'localhost';
    private string $dbName = 'webshop';
    private string $user = 'root';
    private string $password = '';
    private ?PDO $connection = null;

    public function getConnection(): PDO
    {
        if ($this->connection === null) {
            $dsn = "mysql:host={$this->host};dbname={$this->dbName};charset=utf8mb4";
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ];

            $this->connection = new PDO($dsn, $this->user, $this->password, $options);
        }

        return $this->connection;
    }
}