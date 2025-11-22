<?php
declare(strict_types=1);

header('Content-Type: application/json');

require_once __DIR__ . '/config/database.php';

function fetch_rating_stats(mysqli $connection): array
{
    $stats = [];
    $totalResult = $connection->query("SELECT COUNT(*) AS total FROM reviews");
    $total = (int) ($totalResult->fetch_assoc()['total'] ?? 0);

    for ($rating = 1; $rating <= 5; $rating++) {
        $countResult = $connection->query("SELECT COUNT(*) AS count FROM reviews WHERE rating = {$rating}");
        $count = (int) ($countResult->fetch_assoc()['count'] ?? 0);
        $stats[$rating] = $total > 0 ? ($count / $total) * 100 : 0;
    }

    return $stats;
}

function fetch_reviews(mysqli $connection): array
{
    $reviews = [];
    $result = $connection->query("SELECT * FROM reviews ORDER BY created_at DESC LIMIT 10");

    if (!$result) {
        throw new RuntimeException('Error fetching reviews: ' . $connection->error);
    }

    while ($row = $result->fetch_assoc()) {
        $reviews[] = $row;
    }

    return $reviews;
}

try {
    $conn = get_mysqli_connection();

    $createTable = "CREATE TABLE IF NOT EXISTS reviews (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        rating INT NOT NULL,
        comment TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";

    if (!$conn->query($createTable)) {
        throw new RuntimeException('Error creating table: ' . $conn->error);
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $name = $conn->real_escape_string(trim($_POST['name'] ?? ''));
        $rating = (int) ($_POST['rating'] ?? 0);
        $comment = $conn->real_escape_string(trim($_POST['comment'] ?? ''));

        if ($name === '' || $rating < 1 || $rating > 5) {
            throw new InvalidArgumentException('Invalid input: Name and rating (1-5) are required.');
        }

        $stmt = $conn->prepare("INSERT INTO reviews (name, rating, comment) VALUES (?, ?, ?)");
        if (!$stmt) {
            throw new RuntimeException('Prepare failed: ' . $conn->error);
        }

        $stmt->bind_param('sis', $name, $rating, $comment);
        if (!$stmt->execute()) {
            throw new RuntimeException('Execute failed: ' . $stmt->error);
        }

        $stats = fetch_rating_stats($conn);

        echo json_encode([
            'status' => 'success',
            'message' => 'Review submitted successfully',
            'stats' => $stats,
        ]);

        $stmt->close();
    } elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && ($_GET['action'] ?? '') === 'getReviews') {
        echo json_encode([
            'status' => 'success',
            'reviews' => fetch_reviews($conn),
            'stats' => fetch_rating_stats($conn),
        ]);
    } else {
        http_response_code(405);
        echo json_encode([
            'status' => 'error',
            'message' => 'Unsupported request method.',
        ]);
    }
} catch (Throwable $exception) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $exception->getMessage(),
    ]);
} finally {
    if (isset($conn) && $conn instanceof mysqli) {
        $conn->close();
    }
}
