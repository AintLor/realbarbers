<?php
declare(strict_types=1);

header('Content-Type: application/json');

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/schema.php';

try {
    $conn = get_mysqli_connection();
    ensure_core_schema($conn);
    seed_default_barbers($conn);

    $result = $conn->query("SELECT id, name, specialty FROM barbers WHERE active = 1 ORDER BY name ASC");

    if (!$result) {
        throw new RuntimeException('Query failed: ' . $conn->error);
    }

    $barbers = [];
    while ($row = $result->fetch_assoc()) {
        $barbers[] = $row;
    }

    echo json_encode([
        'success' => count($barbers) > 0,
        'barbers' => $barbers,
        'message' => count($barbers) > 0 ? null : 'No barbers found in the database.',
    ]);
} catch (Throwable $exception) {
    echo json_encode([
        'success' => false,
        'message' => $exception->getMessage(),
    ]);
} finally {
    if (isset($conn) && $conn instanceof mysqli) {
        $conn->close();
    }
}
