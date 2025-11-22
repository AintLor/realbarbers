<?php
declare(strict_types=1);

require_once __DIR__ . '/config/db.php';
require_once dirname(__DIR__) . '/config/schema.php';

header("Content-Type: application/json");

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(["message" => "Invalid request method"]);
        exit;
    }

    $input = json_decode(file_get_contents('php://input'), true);
    if (!is_array($input)) {
        throw new InvalidArgumentException('Invalid JSON payload.');
    }

    $requiredFields = ['date', 'time', 'name', 'specialty', 'client_name', 'client_email', 'client_mobile'];
    $missing = array_filter($requiredFields, static fn($field) => empty($input[$field]));

    if (!empty($missing)) {
        throw new InvalidArgumentException('Missing required fields: ' . implode(', ', $missing));
    }

    // Normalize inputs
    $date = date('Y-m-d', strtotime($input['date']));
    $time = date('H:i', strtotime($input['time']));
    $scheduledAt = $date . ' ' . $time . ':00';
    $barberName = substr(trim($input['name']), 0, 120);
    $serviceName = substr(trim($input['specialty']), 0, 120);
    $clientName = substr(trim($input['client_name']), 0, 120);
    $clientEmail = substr(trim($input['client_email']), 0, 190);
    $clientPhone = substr(trim($input['client_mobile']), 0, 40);

    if (!filter_var($clientEmail, FILTER_VALIDATE_EMAIL)) {
        throw new InvalidArgumentException('Invalid email format.');
    }

    // Ensure schema exists (mysqli) then use PDO for writes.
    $mysqli = get_mysqli_connection();
    ensure_core_schema($mysqli);
    seed_default_barbers($mysqli);
    $mysqli->close();

    $db = new Database();
    $conn = $db->getConnection();
    $conn->beginTransaction();

    // Resolve or upsert barber
    $barberId = isset($input['barber_id']) ? (int) $input['barber_id'] : null;
    if ($barberId) {
        $stmt = $conn->prepare('SELECT name FROM barbers WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $barberId]);
        $existingName = $stmt->fetchColumn();
        if ($existingName) {
            $barberName = $existingName;
        } else {
            $barberId = null; // fallback to name upsert
        }
    }

    if ($barberId === null) {
        $stmt = $conn->prepare('SELECT id FROM barbers WHERE name = :name LIMIT 1');
        $stmt->execute([':name' => $barberName]);
        $barberId = $stmt->fetchColumn() ?: null;
        if ($barberId === null) {
            $stmt = $conn->prepare('INSERT INTO barbers (name, active) VALUES (:name, 1)');
            $stmt->execute([':name' => $barberName]);
            $barberId = (int) $conn->lastInsertId();
        }
    }

    // Upsert service
    $serviceId = null;
    $stmt = $conn->prepare('SELECT id FROM services WHERE name = :name LIMIT 1');
    $stmt->execute([':name' => $serviceName]);
    $serviceId = $stmt->fetchColumn() ?: null;
    if ($serviceId === null) {
        $stmt = $conn->prepare('INSERT INTO services (name, duration_min, price_cents, active) VALUES (:name, 30, 0, 1)');
        $stmt->execute([':name' => $serviceName]);
        $serviceId = (int) $conn->lastInsertId();
    }

    // Upsert user by email
    $stmt = $conn->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
    $stmt->execute([':email' => $clientEmail]);
    $userId = $stmt->fetchColumn() ?: null;
    if ($userId === null) {
        $stmt = $conn->prepare('INSERT INTO users (role, name, email, phone) VALUES (\'customer\', :name, :email, :phone)');
        $stmt->execute([':name' => $clientName, ':email' => $clientEmail, ':phone' => $clientPhone]);
        $userId = (int) $conn->lastInsertId();
    } else {
        $stmt = $conn->prepare('UPDATE users SET name = :name, phone = :phone WHERE id = :id');
        $stmt->execute([':name' => $clientName, ':phone' => $clientPhone, ':id' => $userId]);
    }

    // Validate availability exists for the barber on that day/time.
    $dayName = date('l', strtotime($date));
    $stmt = $conn->prepare(
        "SELECT id FROM barber_availability WHERE barber_id = :barber_id AND weekday = :weekday AND time_slot = :time_slot AND active = 1 LIMIT 1"
    );
    $stmt->execute([
        ':barber_id' => $barberId,
        ':weekday' => $dayName,
        ':time_slot' => $time,
    ]);
    $availabilityId = $stmt->fetchColumn() ?: null;
    if ($availabilityId === null) {
        throw new InvalidArgumentException('Selected time is not available for this barber.');
    }

    // Prevent double booking
    $stmt = $conn->prepare(
        "SELECT COUNT(*) FROM reservations WHERE barber_id = :barber_id AND scheduled_at = :scheduled_at AND status != 'canceled'"
    );
    $stmt->execute([':barber_id' => $barberId, ':scheduled_at' => $scheduledAt]);
    $count = (int) $stmt->fetchColumn();
    if ($count > 0) {
        throw new InvalidArgumentException('That slot is already booked. Please choose another time.');
    }

    // Insert reservation
    $stmt = $conn->prepare(
        "INSERT INTO reservations (user_id, service_id, barber_id, scheduled_at, status, notes)
         VALUES (:user_id, :service_id, :barber_id, :scheduled_at, 'pending', :notes)"
    );
    $stmt->execute([
        ':user_id' => $userId,
        ':service_id' => $serviceId,
        ':barber_id' => $barberId,
        ':scheduled_at' => $scheduledAt,
        ':notes' => $input['notes'] ?? null,
    ]);

    $reservationId = (int) $conn->lastInsertId();
    $conn->commit();

    echo json_encode([
        "message" => "Appointment added successfully",
        "status" => "success",
        "reservation" => [
            'id' => $reservationId,
            'date' => $date,
            'time' => $time,
            'barber_id' => $barberId,
            'barber_name' => $barberName,
            'service_id' => $serviceId,
            'service_name' => $serviceName,
            'client_name' => $clientName,
            'client_email' => $clientEmail,
            'client_mobile' => $clientPhone,
        ],
    ]);
} catch (Throwable $exception) {
    if (isset($conn) && $conn instanceof PDO && $conn->inTransaction()) {
        $conn->rollBack();
    }
    http_response_code(400);
    echo json_encode([
        "message" => $exception->getMessage(),
        "status" => "error",
    ]);
}
