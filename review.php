<?php
declare(strict_types=1);

header('Content-Type: application/json');

require_once __DIR__ . '/config/database.php';

function ensure_schema(mysqli $connection): void
{
    $createTable = "CREATE TABLE IF NOT EXISTS reviews (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        rating INT NOT NULL,
        comment TEXT,
        hidden TINYINT(1) NOT NULL DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";

    if (!$connection->query($createTable)) {
        throw new RuntimeException('Error creating table: ' . $connection->error);
    }

    $columnResult = $connection->query("SHOW COLUMNS FROM reviews LIKE 'hidden'");
    if ($columnResult instanceof mysqli_result && $columnResult->num_rows === 0) {
        $connection->query("ALTER TABLE reviews ADD COLUMN hidden TINYINT(1) NOT NULL DEFAULT 0");
    }

    $indexResult = $connection->query("SHOW INDEX FROM reviews WHERE Key_name = 'idx_hidden'");
    if ($indexResult instanceof mysqli_result && $indexResult->num_rows === 0) {
        $connection->query("CREATE INDEX idx_hidden ON reviews (hidden)");
    }
}

function fetch_rating_stats(mysqli $connection, bool $includeHidden = false): array
{
    $visibilityCondition = $includeHidden ? '' : 'hidden = 0';

    $totalQuery = 'SELECT COUNT(*) AS total FROM reviews' . ($visibilityCondition ? ' WHERE ' . $visibilityCondition : '');
    $totalResult = $connection->query($totalQuery);
    $total = (int) ($totalResult->fetch_assoc()['total'] ?? 0);

    $stats = [];
    for ($rating = 1; $rating <= 5; $rating++) {
        $ratingQuery = 'SELECT COUNT(*) AS count FROM reviews WHERE rating = ' . (int) $rating;
        if ($visibilityCondition) {
            $ratingQuery .= ' AND ' . $visibilityCondition;
        }

        $countResult = $connection->query($ratingQuery);
        $count = (int) ($countResult->fetch_assoc()['count'] ?? 0);
        $stats[$rating] = $total > 0 ? ($count / $total) * 100 : 0;
    }

    return $stats;
}

function fetch_reviews(mysqli $connection, bool $includeHidden = false, int $limit = 10): array
{
    $visibilityCondition = $includeHidden ? '' : ' WHERE hidden = 0';
    $limitClause = $limit > 0 ? ' LIMIT ' . $limit : '';
    $query = "SELECT * FROM reviews{$visibilityCondition} ORDER BY created_at DESC{$limitClause}";

    $result = $connection->query($query);
    if (!$result) {
        throw new RuntimeException('Error fetching reviews: ' . $connection->error);
    }

    $reviews = [];
    while ($row = $result->fetch_assoc()) {
        $reviews[] = $row;
    }

    return $reviews;
}

try {
    $conn = get_mysqli_connection();
    ensure_schema($conn);

    $method = $_SERVER['REQUEST_METHOD'];
    $action = $_GET['action'] ?? '';

    if ($method === 'POST' && $action === 'setVisibility') {
        $payload = json_decode(file_get_contents('php://input'), true);
        if (!is_array($payload)) {
            throw new InvalidArgumentException('Invalid JSON payload.');
        }

        $id = (int) ($payload['id'] ?? 0);
        $hidden = $payload['hidden'] ?? null;

        if ($id <= 0 || !in_array($hidden, [0, 1, '0', '1', true, false], true)) {
            throw new InvalidArgumentException('Review id and hidden flag are required.');
        }

        $hiddenValue = (int) (bool) $hidden;

        $stmt = $conn->prepare('UPDATE reviews SET hidden = ? WHERE id = ?');
        if (!$stmt) {
            throw new RuntimeException('Prepare failed: ' . $conn->error);
        }

        $stmt->bind_param('ii', $hiddenValue, $id);
        if (!$stmt->execute()) {
            throw new RuntimeException('Execute failed: ' . $stmt->error);
        }

        $stmt->close();

        echo json_encode([
            'status' => 'success',
            'message' => 'Review visibility updated.',
            'stats' => fetch_rating_stats($conn),
        ]);
    } elseif ($method === 'POST') {
        $name = $conn->real_escape_string(trim($_POST['name'] ?? ''));
        $rating = (int) ($_POST['rating'] ?? 0);
        $comment = $conn->real_escape_string(trim($_POST['comment'] ?? ''));

        if ($name === '' || $rating < 1 || $rating > 5) {
            throw new InvalidArgumentException('Invalid input: Name and rating (1-5) are required.');
        }

        $stmt = $conn->prepare('INSERT INTO reviews (name, rating, comment, hidden) VALUES (?, ?, ?, 0)');
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
    } elseif ($method === 'GET' && $action === 'getReviews') {
        echo json_encode([
            'status' => 'success',
            'reviews' => fetch_reviews($conn),
            'stats' => fetch_rating_stats($conn),
        ]);
    } elseif ($method === 'GET' && $action === 'adminReviews') {
        echo json_encode([
            'status' => 'success',
            'reviews' => fetch_reviews($conn, true, 100),
            'stats' => fetch_rating_stats($conn, true),
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
