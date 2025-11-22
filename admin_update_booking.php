<?php
declare(strict_types=1);

header('Content-Type: application/json');

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/schema.php';
require_once __DIR__ . '/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Unsupported request method.']);
    exit;
}

try {
    require_admin_auth();
    $conn = get_mysqli_connection();
    ensure_core_schema($conn);

    $payload = json_decode(file_get_contents('php://input'), true);
    if (!is_array($payload)) {
        throw new InvalidArgumentException('Invalid JSON payload.');
    }

    $id = (int) ($payload['id'] ?? 0);
    $status = $payload['status'] ?? 'completed';
    $allowedStatuses = ['completed'];

    if ($id <= 0 || !in_array($status, $allowedStatuses, true)) {
        throw new InvalidArgumentException('A valid booking id and status are required.');
    }

    $stmt = $conn->prepare('UPDATE reservations SET status = ? WHERE id = ?');
    if (!$stmt) {
        throw new RuntimeException('Prepare failed: ' . $conn->error);
    }
    $stmt->bind_param('si', $status, $id);
    if (!$stmt->execute()) {
        throw new RuntimeException('Execute failed: ' . $stmt->error);
    }
    $stmt->close();

    echo json_encode(['status' => 'success', 'message' => 'Booking marked as completed.']);
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
