<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rating = $_POST['rating'];
    $comment = $_POST['comment'];
    $customer_name = 'Anonymous';  // Optionally, you could add a field for customer name

    // Connect to your database
    $host = 'localhost';
    $dbname = 'realbarbers_db'; // Your database name
    $username = 'lorenz';       // Your database username
    $password = 'lorenz@21';    // Your database password
    

    try {
        $pdo = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Insert the new review into the database
        $stmt = $pdo->prepare("INSERT INTO reviews (rating, comment, customer_name) VALUES (?, ?, ?)");
        $stmt->execute([$rating, $comment, $customer_name]);

        // Redirect or show a success message
        header("Location: your_page_with_reviews.php");
    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
    }
}
?>
