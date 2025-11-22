<?php
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database connection
$host = 'localhost';
$dbname = 'realbarbers_db'; // Your database name
$username = 'lorenz';       // Your database username
$password = 'lorenz@21';    // Your database password

try {
    // Establish database connection
    $conn = new mysqli($host, $username, $password, $dbname);

    // Check connection
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }

    // Create table if it doesn't exist
    $createTable = "CREATE TABLE IF NOT EXISTS reviews (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        rating INT NOT NULL,
        comment TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    
    if (!$conn->query($createTable)) {
        throw new Exception("Error creating table: " . $conn->error);
    }

    function getPostData($key) {
        return isset($_POST[$key]) ? trim($_POST[$key]) : '';
    }

    // Handle POST request (new review)
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $name = $conn->real_escape_string(getPostData('name'));
        $rating = intval(getPostData('rating'));
        $comment = $conn->real_escape_string(getPostData('comment'));

        // Validate input
        if (empty($name) || $rating < 1 || $rating > 5) {
            throw new Exception("Invalid input: Name and rating (1-5) are required");
        }

        // Insert review
        $sql = "INSERT INTO reviews (name, rating, comment) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);
        
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }

        $stmt->bind_param("sis", $name, $rating, $comment);
        
        if (!$stmt->execute()) {
            throw new Exception("Execute failed: " . $stmt->error);
        }

        // Get updated statistics
        $stats = [];
        $total = $conn->query("SELECT COUNT(*) as total FROM reviews")->fetch_assoc()['total'];
        
        for ($i = 1; $i <= 5; $i++) {
            $ratingCount = $conn->query("SELECT COUNT(*) as count FROM reviews WHERE rating = $i")->fetch_assoc()['count'];
            $stats[$i] = $total > 0 ? ($ratingCount / $total) * 100 : 0;
        }

        echo json_encode([
            'status' => 'success',
            'message' => 'Review submitted successfully',
            'stats' => $stats
        ]);

        $stmt->close();
    }
    // Handle GET request (fetch reviews)
    elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'getReviews') {
        $sql = "SELECT * FROM reviews ORDER BY created_at DESC LIMIT 10";
        $result = $conn->query($sql);
        
        if (!$result) {
            throw new Exception("Error fetching reviews: " . $conn->error);
        }

        $reviews = [];
        while ($row = $result->fetch_assoc()) {
            $reviews[] = $row;
        }

        // Get statistics
        $stats = [];
        $total = $conn->query("SELECT COUNT(*) as total FROM reviews")->fetch_assoc()['total'];
        
        for ($i = 1; $i <= 5; $i++) {
            $ratingCount = $conn->query("SELECT COUNT(*) as count FROM reviews WHERE rating = $i")->fetch_assoc()['count'];
            $stats[$i] = $total > 0 ? ($ratingCount / $total) * 100 : 0;
        }

        echo json_encode([
            'status' => 'success',
            'reviews' => $reviews,
            'stats' => $stats
        ]);
    }

} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}