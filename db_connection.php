<?php

// db_connection.php
$host = 'localhost';
$dbname = 'realbarbers_db'; // Your database name
$username = 'lorenz';       // Your database username
$password = 'lorenz@21';    // Your database password
$port = 3306;

// Create connection
$conn = new mysqli($host, $username, $password, $dbname, $port);

// Check the connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");
?>
