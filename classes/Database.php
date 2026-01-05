<?php

class Database
{
    private string $host = 'sql103.infinityfree.com';
    private string $dbName = 'if0_40831482_Boekhandel';
    private string $user = 'if0_40831482';
    private string $password = 'z1qY37IhdyM6yic';
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