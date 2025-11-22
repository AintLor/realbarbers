<?php
declare(strict_types=1);

require_once __DIR__ . '/config/db.php';

header("Content-Type: application/json");

try {
    $db = new Database();
    $conn = $db->getConnection();

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

    $stmt = $conn->prepare(
        "INSERT INTO appointments (date, time, name, specialty, client_name, client_email, client_mobile)
         VALUES (:date, :time, :name, :specialty, :client_name, :client_email, :client_mobile)"
    );

    $stmt->execute([
        ':date' => $input['date'],
        ':time' => $input['time'],
        ':name' => $input['name'],
        ':specialty' => $input['specialty'],
        ':client_name' => $input['client_name'],
        ':client_email' => $input['client_email'],
        ':client_mobile' => $input['client_mobile'],
    ]);

    echo json_encode(["message" => "Appointment added successfully", "status" => "success"]);
} catch (Throwable $exception) {
    http_response_code(500);
    echo json_encode([
        "message" => $exception->getMessage(),
        "status" => "error",
    ]);
}
