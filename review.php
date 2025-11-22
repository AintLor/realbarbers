<?php
declare(strict_types=1);

header('Content-Type: application/json');

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/schema.php';
require_once __DIR__ . '/auth.php';

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
    ensure_core_schema($conn);

    $method = $_SERVER['REQUEST_METHOD'];
    $action = $_GET['action'] ?? '';

    $isAdminAction = ($method === 'POST' && $action === 'setVisibility') || ($method === 'GET' && $action === 'adminReviews');
    if ($isAdminAction) {
        require_admin_auth();
    }

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
        $email = trim($_POST['email'] ?? '');
        $reservationId = isset($_POST['reservation_id']) ? (int) $_POST['reservation_id'] : null;
        $userId = null;

        if ($name === '' || $rating < 1 || $rating > 5) {
            throw new InvalidArgumentException('Invalid input: Name and rating (1-5) are required.');
        }

        if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
            // Fetch or create user for linkage
            $stmt = $conn->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
            $stmt->bind_param('s', $email);
            $stmt->execute();
            $res = $stmt->get_result();
            $row = $res->fetch_assoc();
            if ($row) {
                $userId = (int) $row['id'];
            } else {
                $stmtInsert = $conn->prepare('INSERT INTO users (role, name, email) VALUES (\'customer\', ?, ?)');
                $stmtInsert->bind_param('ss', $name, $email);
                $stmtInsert->execute();
                $userId = $conn->insert_id;
                $stmtInsert->close();
            }
            $stmt->close();
        }

        $stmt = $conn->prepare('INSERT INTO reviews (reservation_id, user_id, name, rating, comment, hidden) VALUES (?, ?, ?, ?, ?, 0)');
        if (!$stmt) {
            throw new RuntimeException('Prepare failed: ' . $conn->error);
        }

        // i = reservation_id, i = user_id, s = name, i = rating, s = comment
        $stmt->bind_param('iisis', $reservationId, $userId, $name, $rating, $comment);
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
