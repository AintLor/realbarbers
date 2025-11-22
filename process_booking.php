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
    $barberId = isset($data['barber_id']) ? (int) $data['barber_id'] : null;
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


    $appointmentData = [
        'name' => $barberName,
        'barber_id' => $barberId,
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
            "barber_id" => $barberId,
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
