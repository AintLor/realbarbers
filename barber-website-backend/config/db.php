<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/config/database.php';

class Database
{
    private ?PDO $conn = null;

    public function getConnection(): PDO
    {
        if ($this->conn === null) {
            $this->conn = get_pdo_connection();
        }

        return $this->conn;
    }

    public function testConnection(): string
    {
        try {
            $this->getConnection();
            return "Database connection successful!";
        } catch (PDOException $exception) {
            return "Connection failed: " . $exception->getMessage();
        }
    }

    public function closeConnection(): string
    {
        $this->conn = null;
        return "Database connection closed successfully!";
    }
}
