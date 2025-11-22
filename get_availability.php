<?php
declare(strict_types=1);

header('Content-Type: application/json');

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/schema.php';

try {
    $conn = get_mysqli_connection();
    ensure_core_schema($conn);
    seed_default_barbers($conn);

    $barberId = isset($_GET['barber_id']) ? (int) $_GET['barber_id'] : 0;
    $barberName = trim($_GET['barber_name'] ?? '');
    $date = trim($_GET['date'] ?? '');

    if ($date === '') {
        throw new InvalidArgumentException('date is required');
    }

    if ($barberId <= 0 && $barberName === '') {
        throw new InvalidArgumentException('barber_id or barber_name is required');
    }

    // Resolve barber ID by name if needed.
    if ($barberId <= 0 && $barberName !== '') {
        $stmt = $conn->prepare('SELECT id FROM barbers WHERE name = ? LIMIT 1');
        $stmt->bind_param('s', $barberName);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res->fetch_assoc();
        if ($row) {
            $barberId = (int) $row['id'];
        }
        $stmt->close();
    }

    if ($barberId <= 0) {
        throw new InvalidArgumentException('Barber not found');
    }

    $dayName = date('l', strtotime($date));

    $stmt = $conn->prepare(
        "SELECT TIME_FORMAT(time_slot, '%H:%i') AS time_slot
         FROM barber_availability
         WHERE barber_id = ? AND weekday = ? AND active = 1
         ORDER BY time_slot ASC"
    );
    $stmt->bind_param('is', $barberId, $dayName);
    $stmt->execute();
    $availabilityResult = $stmt->get_result();
    $availableSlots = [];
    while ($row = $availabilityResult->fetch_assoc()) {
        $availableSlots[] = $row['time_slot'];
    }
    $stmt->close();

    if (empty($availableSlots)) {
        echo json_encode(['success' => true, 'available_times' => []]);
        exit;
    }

    // Remove already-booked slots.
    $dayStart = date('Y-m-d 00:00:00', strtotime($date));
    $dayEnd = date('Y-m-d 23:59:59', strtotime($date));

    $stmt = $conn->prepare(
        "SELECT DATE_FORMAT(scheduled_at, '%H:%i') AS booked
         FROM reservations
         WHERE barber_id = ? AND scheduled_at BETWEEN ? AND ? AND status != 'canceled'"
    );
    $stmt->bind_param('iss', $barberId, $dayStart, $dayEnd);
    $stmt->execute();
    $bookedRes = $stmt->get_result();
    $booked = [];
    while ($row = $bookedRes->fetch_assoc()) {
        $booked[] = $row['booked'];
    }
    $stmt->close();

    $freeSlots = array_values(array_diff($availableSlots, $booked));

    echo json_encode([
        'success' => true,
        'available_times' => $freeSlots,
    ]);
} catch (Throwable $exception) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $exception->getMessage(),
    ]);
}
