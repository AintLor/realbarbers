<?php
declare(strict_types=1);

header('Content-Type: application/json');

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Unsupported request method.']);
    exit;
}

try {
    require_admin_auth();
    $conn = get_mysqli_connection();

    $createTable = "CREATE TABLE IF NOT EXISTS appointments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        date DATE NOT NULL,
        time TIME NOT NULL,
        name VARCHAR(255) NOT NULL,
        specialty VARCHAR(255) NOT NULL,
        client_name VARCHAR(255) NOT NULL,
        client_email VARCHAR(255) NOT NULL,
        client_mobile VARCHAR(30) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_date_time (date, time)
    )";

    if (!$conn->query($createTable)) {
        throw new RuntimeException('Error ensuring appointments table exists: ' . $conn->error);
    }

    $result = $conn->query(
        'SELECT id, date, time, name, specialty, client_name, client_email, client_mobile, created_at
         FROM appointments
         ORDER BY date DESC, time DESC'
    );

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
