<?php
declare(strict_types=1);

header('Content-Type: application/json');

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/schema.php';
require_once __DIR__ . '/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Unsupported request method.']);
    exit;
}

try {
    require_admin_auth();
    $conn = get_mysqli_connection();
    ensure_core_schema($conn);

    $query = "SELECT r.id, DATE(r.scheduled_at) AS date, TIME(r.scheduled_at) AS time,
                     b.name AS barber_name, s.name AS service_name,
                     u.name AS client_name, u.email AS client_email, u.phone AS client_mobile,
                     r.status, r.created_at
              FROM reservations r
              LEFT JOIN barbers b ON r.barber_id = b.id
              LEFT JOIN services s ON r.service_id = s.id
              JOIN users u ON r.user_id = u.id
              ORDER BY r.scheduled_at DESC";

    $result = $conn->query($query);
    if (!$result) {
        throw new RuntimeException('Error fetching appointments: ' . $conn->error);
    }

    $bookings = [];
    while ($row = $result->fetch_assoc()) {
        $bookings[] = $row;
    }

    echo json_encode([
        'status' => 'success',
        'bookings' => $bookings,
    ]);
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
