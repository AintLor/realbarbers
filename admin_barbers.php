<?php
declare(strict_types=1);

header('Content-Type: application/json');

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/schema.php';
require_once __DIR__ . '/auth.php';

try {
    require_admin_auth();
    $conn = get_mysqli_connection();
    ensure_core_schema($conn);

    $method = $_SERVER['REQUEST_METHOD'];
    $action = $_GET['action'] ?? '';

    if ($method === 'GET') {
        echo json_encode([
            'status' => 'success',
            'barbers' => fetch_barbers_with_availability($conn),
        ]);
        exit;
    }

    $payload = json_decode(file_get_contents('php://input'), true);
    if (!is_array($payload)) {
        throw new InvalidArgumentException('Invalid JSON payload.');
    }

    if ($method === 'POST' && $action === 'create') {
        $response = create_barber($conn, $payload);
    } elseif ($method === 'POST' && $action === 'update') {
        $response = update_barber($conn, $payload);
    } elseif ($method === 'POST' && $action === 'delete') {
        $response = delete_barber($conn, $payload);
    } else {
        http_response_code(405);
        echo json_encode(['status' => 'error', 'message' => 'Unsupported request method.']);
        exit;
    }

    echo json_encode($response);
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

function fetch_barbers_with_availability(mysqli $conn): array
{
    $barbers = [];
    $result = $conn->query('SELECT id, name, specialty, active, created_at FROM barbers ORDER BY name ASC');
    while ($row = $result->fetch_assoc()) {
        $row['availability'] = [];
        $barbers[(int) $row['id']] = $row;
    }
    if (empty($barbers)) {
        return [];
    }

    $ids = implode(',', array_map('intval', array_keys($barbers)));
    $availability = $conn->query(
        "SELECT barber_id, weekday, time_slot FROM barber_availability WHERE barber_id IN ({$ids}) ORDER BY FIELD(weekday,'Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'), time_slot ASC"
    );
    while ($slot = $availability->fetch_assoc()) {
        $bid = (int) $slot['barber_id'];
        $day = $slot['weekday'];
        $time = substr($slot['time_slot'], 0, 5);
        $barbers[$bid]['availability'][$day][] = $time;
    }

    return array_values($barbers);
}

function create_barber(mysqli $conn, array $payload): array
{
    $name = trim((string) ($payload['name'] ?? ''));
    $specialty = trim((string) ($payload['specialty'] ?? ''));
    $availability = $payload['availability'] ?? [];

    if ($name === '') {
        throw new InvalidArgumentException('Barber name is required.');
    }

    $stmt = $conn->prepare('INSERT INTO barbers (name, specialty, active) VALUES (?, ?, 1)');
    if (!$stmt) {
        throw new RuntimeException('Prepare failed: ' . $conn->error);
    }
    $stmt->bind_param('ss', $name, $specialty);
    if (!$stmt->execute()) {
        throw new RuntimeException('Execute failed: ' . $stmt->error);
    }
    $barberId = (int) $conn->insert_id;
    $stmt->close();

    upsert_availability($conn, $barberId, $availability, true);

    return [
        'status' => 'success',
        'message' => 'Barber created.',
        'barber' => ['id' => $barberId, 'name' => $name, 'specialty' => $specialty, 'active' => 1],
    ];
}

function update_barber(mysqli $conn, array $payload): array
{
    $id = (int) ($payload['id'] ?? 0);
    $name = trim((string) ($payload['name'] ?? ''));
    $specialty = trim((string) ($payload['specialty'] ?? ''));
    $availability = $payload['availability'] ?? [];
    $active = isset($payload['active']) ? (int) (bool) $payload['active'] : 1;

    if ($id <= 0 || $name === '') {
        throw new InvalidArgumentException('Barber id and name are required.');
    }

    $stmt = $conn->prepare('UPDATE barbers SET name = ?, specialty = ?, active = ? WHERE id = ?');
    if (!$stmt) {
        throw new RuntimeException('Prepare failed: ' . $conn->error);
    }
    $stmt->bind_param('ssii', $name, $specialty, $active, $id);
    if (!$stmt->execute()) {
        throw new RuntimeException('Execute failed: ' . $stmt->error);
    }
    $stmt->close();

    upsert_availability($conn, $id, $availability, true);

    return [
        'status' => 'success',
        'message' => 'Barber updated.',
    ];
}

function delete_barber(mysqli $conn, array $payload): array
{
    $id = (int) ($payload['id'] ?? 0);
    if ($id <= 0) {
        throw new InvalidArgumentException('Barber id is required.');
    }

    $stmt = $conn->prepare('DELETE FROM barbers WHERE id = ?');
    if (!$stmt) {
        throw new RuntimeException('Prepare failed: ' . $conn->error);
    }
    $stmt->bind_param('i', $id);
    if (!$stmt->execute()) {
        throw new RuntimeException('Execute failed: ' . $stmt->error);
    }
    $stmt->close();

    return [
        'status' => 'success',
        'message' => 'Barber removed.',
    ];
}

function normalize_availability_payload(array $availability): array
{
    $validDays = ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];
    $normalized = [];

    foreach ($availability as $day => $slots) {
        if (!in_array($day, $validDays, true)) {
            continue;
        }

        if (is_string($slots)) {
            $slots = array_map('trim', explode(',', $slots));
        }

        if (!is_array($slots)) {
            continue;
        }

        foreach ($slots as $slot) {
            if (!is_string($slot) || $slot === '') {
                continue;
            }
            $slot = substr($slot, 0, 5);
            if (!preg_match('/^(2[0-3]|[01][0-9]):[0-5][0-9]$/', $slot)) {
                continue;
            }
            $normalized[$day][] = $slot;
        }
    }

    return $normalized;
}

function upsert_availability(mysqli $conn, int $barberId, array $availability, bool $replaceExisting): void
{
    $availability = normalize_availability_payload($availability);
    if ($replaceExisting) {
        $stmtDelete = $conn->prepare('DELETE FROM barber_availability WHERE barber_id = ?');
        $stmtDelete->bind_param('i', $barberId);
        $stmtDelete->execute();
        $stmtDelete->close();
    }

    if (empty($availability)) {
        return;
    }

    $stmt = $conn->prepare('INSERT IGNORE INTO barber_availability (barber_id, weekday, time_slot, active) VALUES (?, ?, ?, 1)');
    if (!$stmt) {
        throw new RuntimeException('Prepare failed: ' . $conn->error);
    }

    foreach ($availability as $day => $slots) {
        foreach ($slots as $slot) {
            $stmt->bind_param('iss', $barberId, $day, $slot);
            $stmt->execute();
        }
    }
    $stmt->close();
}
