<?php
declare(strict_types=1);

require_once __DIR__ . '/config/env.php';

header('Content-Type: application/json');

set_error_handler(static function ($errno, $errstr, $errfile, $errline) {
    throw new ErrorException($errstr, $errno, 0, $errfile, $errline);
});

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        "success" => false,
        "message" => "Direct access not allowed",
    ]);
    exit;
}

try {
    validate_required_env(['BOOKING_API_URL']);
    $apiEndpoint = require_env('BOOKING_API_URL');

    $rawData = file_get_contents("php://input");
    if ($rawData === false || $rawData === '') {
        throw new InvalidArgumentException("No data received");
    }

    $data = json_decode($rawData, true, 512, JSON_THROW_ON_ERROR);

    $requiredFields = ['client_name', 'client_email', 'client_mobile', 'specialty', 'name', 'date', 'time'];
    $missingFields = array_filter($requiredFields, static fn($field) => empty($data[$field]));
    if (!empty($missingFields)) {
        throw new InvalidArgumentException("Missing required fields: " . implode(', ', $missingFields));
    }

    $barberName = substr(htmlspecialchars(trim($data['name'])), 0, 100);
    $date = date('Y-m-d', strtotime($data['date']));
    $time = date('H:i', strtotime($data['time']));
    $service = substr(htmlspecialchars(trim($data['specialty'])), 0, 100);
    $clientName = substr(htmlspecialchars(trim($data['client_name'])), 0, 255);
    $clientEmail = substr(htmlspecialchars(trim($data['client_email'])), 0, 255);
    $clientPhone = substr(htmlspecialchars(trim($data['client_mobile'])), 0, 15);

    if ($date === false || $time === false) {
        throw new InvalidArgumentException("Invalid date or time format");
    }

    if (!filter_var($clientEmail, FILTER_VALIDATE_EMAIL)) {
        throw new InvalidArgumentException("Invalid email format.");
    }

    $selectedDay = date('l', strtotime($date));

    $barberAvailability = [
        "Barber Angelo" => [
            "Sunday" => ['09:00', '10:00', '11:00', '13:00', '14:00', '15:00', '16:00'],
            "Monday" => ['11:00', '14:00', '15:00', '16:00', '17:00'],
            "Tuesday" => ['09:00', '10:00', '11:00', '13:00', '14:00', '15:00', '16:00'],
            "Wednesday" => [],
            "Thursday" => ['11:00', '14:00', '15:00', '16:00', '17:00'],
            "Friday" => ['09:00', '10:00', '11:00', '13:00', '14:00', '15:00', '16:00'],
            "Saturday" => ['11:00', '14:00', '15:00', '16:00', '17:00']
        ],
        "Barber Reymart" => [
            "Sunday" => ['11:00', '14:00', '15:00', '16:00', '17:00'],
            "Monday" => ['09:00', '10:00', '11:00', '13:00', '14:00', '15:00', '16:00'],
            "Tuesday" => [],
            "Wednesday" => ['11:00', '14:00', '15:00', '16:00', '17:00'],
            "Thursday" => ['11:00', '14:00', '15:00', '16:00', '17:00'],
            "Friday" => ['11:00', '14:00', '15:00', '16:00', '17:00'],
            "Saturday" => ['09:00', '10:00', '11:00', '13:00', '14:00', '15:00', '16:00']
        ],
        "Barber Rod" => [
            "Sunday" => ['11:00', '14:00', '15:00', '16:00', '17:00'],
            "Monday" => ['11:00', '14:00', '15:00', '16:00', '17:00'],
            "Tuesday" => ['11:00', '14:00', '15:00', '16:00', '17:00'],
            "Wednesday" => ['09:00', '10:00', '11:00', '13:00', '14:00', '15:00', '16:00'],
            "Thursday" => [],
            "Friday" => ['11:00', '14:00', '15:00', '16:00', '17:00'],
            "Saturday" => ['09:00', '10:00', '11:00', '13:00', '14:00', '15:00', '16:00']
        ],
        "Barber Lyndon" => [
            "Sunday" => ['09:00', '10:00', '11:00', '13:00', '14:00', '15:00', '16:00'],
            "Monday" => [],
            "Tuesday" => ['11:00', '14:00', '15:00', '16:00', '17:00'],
            "Wednesday" => ['11:00', '14:00', '15:00', '16:00', '17:00'],
            "Thursday" => ['09:00', '10:00', '11:00', '13:00', '14:00', '15:00', '16:00'],
            "Friday" => ['09:00', '10:00', '11:00', '13:00', '14:00', '15:00', '16:00'],
            "Saturday" => ['11:00', '14:00', '15:00', '16:00', '17:00']
        ],
        "Barber Ed" => [
            "Sunday" => ['09:00', '10:00', '11:00', '13:00', '14:00', '15:00', '16:00'],
            "Monday" => ['09:00', '10:00', '11:00', '13:00', '14:00', '15:00', '16:00'],
            "Tuesday" => ['09:00', '10:00', '11:00', '13:00', '14:00', '15:00', '16:00'],
            "Wednesday" => ['09:00', '10:00', '11:00', '13:00', '14:00', '15:00', '16:00'],
            "Thursday" => ['09:00', '10:00', '11:00', '13:00', '14:00', '15:00', '16:00'],
            "Friday" => [],
            "Saturday" => ['09:00', '10:00', '11:00', '13:00', '14:00', '15:00', '16:00']
        ]
    ];

    if (!isset($barberAvailability[$barberName][$selectedDay]) ||
        !in_array($time, $barberAvailability[$barberName][$selectedDay], true)) {
        throw new InvalidArgumentException("The selected time is not available for {$barberName} on {$date}.");
    }

    $appointmentData = [
        'name' => $barberName,
        'date' => $date,
        'time' => $time,
        'specialty' => $service,
        'client_name' => $clientName,
        'client_email' => $clientEmail,
        'client_mobile' => $clientPhone,
    ];

    $ch = curl_init($apiEndpoint);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($appointmentData, JSON_THROW_ON_ERROR));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

    $response = curl_exec($ch);
    if ($response === false) {
        throw new RuntimeException("API request failed: " . curl_error($ch));
    }

    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        throw new RuntimeException("API request failed with status code: {$httpCode}");
    }

    $apiResponse = json_decode($response, true);
    if (!is_array($apiResponse)) {
        throw new RuntimeException("Invalid response from API");
    }

    echo json_encode([
        "success" => true,
        "message" => "Appointment added successfully.",
        "appointment" => [
            "name" => $barberName,
            "date" => $date,
            "time" => $time,
            "specialty" => $service,
            "client_name" => $clientName,
            "client_email" => $clientEmail,
            "client_mobile" => $clientPhone,
        ],
    ]);
} catch (Throwable $exception) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => $exception->getMessage(),
    ]);
}

exit;
