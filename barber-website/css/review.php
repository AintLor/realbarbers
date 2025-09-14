<?php
// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "reviewsystem";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Function to safely get POST data
function getPostData($key) {
    return isset($_POST[$key]) ? trim($_POST[$key]) : '';
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $conn->real_escape_string(getPostData('name'));
    $rating = intval(getPostData('rating'));
    $comment = $conn->real_escape_string(getPostData('comment'));

    if (!empty($name) && $rating >= 1 && $rating <= 5) {
        $sql = "INSERT INTO reviews (name, rating, comment) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);
        
        if ($stmt) {
            $stmt->bind_param("sis", $name, $rating, $comment);
            
            if ($stmt->execute()) {
                header('Location: index.php?status=success');
                exit;
            } else {
                $error = "Error: " . $stmt->error;
            }
            $stmt->close();
        } else {
            $error = "Error in preparing statement: " . $conn->error;
        }
    } else {
        $error = "Invalid input data.";
    }
}

// Function to get rating statistics
function getRatingStats($conn) {
    $stats = array_fill(1, 5, 0);
    $total = 0;

    $sql = "SELECT rating, COUNT(*) as count FROM reviews GROUP BY rating ORDER BY rating DESC";
    $result = $conn->query($sql);

    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $rating = intval($row['rating']);
            if ($rating >= 1 && $rating <= 5) {
                $stats[$rating] = intval($row['count']);
                $total += $stats[$rating];
            }
        }
    }

    return ['stats' => $stats, 'total' => $total];
}

// Display rating bars
try {
    $ratingStats = getRatingStats($conn);
    $stats = $ratingStats['stats'];
    $total = $ratingStats['total'];

    for ($i = 5; $i >= 1; $i--) {
        $count = $stats[$i];
        $percentage = $total > 0 ? round(($count / $total) * 100) : 0;
        echo "<div class='rating-bar'>";
        echo "<span>{$i} Star" . ($i > 1 ? "s" : "") . "</span>";
        echo "<div class='progress-bar'>";
        echo "<div class='progress' style='width: {$percentage}%;'></div>";
        echo "</div>";
        echo "<span>{$percentage}% ({$count})</span>";
        echo "</div>";
    }
} catch (Exception $e) {
    echo "<p class='error'>Error displaying ratings: " . htmlspecialchars($e->getMessage()) . "</p>";
}

// Display error message if any
if (isset($error)) {
    echo "<p class='error'>" . htmlspecialchars($error) . "</p>";
}

$conn->close();
?>