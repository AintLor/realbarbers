<?php
class Database {
    private $host = "localhost";
    private $database = "realbarbers_db";
    private $username = "lorenz";
    private $password = "lorenz@21";
    public $conn;

    public function getConnection() {
        $this->conn = null;
        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->database,
                $this->username,
                $this->password
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $e) {
            echo "Connection error: " . $e->getMessage();
        }
        return $this->conn;
    }

    // Test the connection
    public function testConnection() {
        try {
            $this->getConnection();
            return "Database connection successful!";
        } catch(PDOException $e) {
            return "Connection failed: " . $e->getMessage();
        }
    }

    // Close the database connection
    public function closeConnection() {
        if ($this->conn !== null) {
            $this->conn = null;
            return "Database connection closed successfully!";
        }
        return "No active connection to close.";
    }
}
