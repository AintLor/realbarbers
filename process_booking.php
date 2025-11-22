<?php
// Disable output buffering
if (ob_get_length()) ob_end_clean();

// Enable error reporting for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Set JSON header immediately
header('Content-Type: application/json');

// Custom error handler
function handleError($errno, $errstr, $errfile, $errline) {
    $error = [
        "success" => false,
        "message" => "Server error occurred",
        "debug" => [
            "error" => $errstr,
            "file" => $errfile,
            "line" => $errline
        ]
    ];
    echo json_encode($error);
    exit;
}
set_error_handler("handleError");

// Custom exception handler
function handleException($exception) {
    $error = [
        "success" => false,
        "message" => "Server error occurred",
        "debug" => [
            "error" => $exception->getMessage(),
            "file" => $exception->getFile(),
            "line" => $exception->getLine()
        ]
    ];
    echo json_encode($error);
    exit;
}
set_exception_handler("handleException");

// Prevent direct access
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        "success" => false,
        "message" => "Direct access not allowed"
    ]);
    exit;
}

try {
    // Get and validate input
    $rawData = file_get_contents("php://input");
    if (!$rawData) {
        throw new Exception("No data received");
    }

    $data = json_decode($rawData, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception("Invalid JSON in request body: " . json_last_error_msg());
    }

    // Validate required fields
    $requiredFields = ['client_name', 'client_email', 'client_mobile', 'specialty', 'name', 'date', 'time'];
    $missingFields = array_filter($requiredFields, function ($field) use ($data) {
        return empty($data[$field]);
    });

    if (count($missingFields) > 0) {
        throw new Exception("Missing required fields: " . implode(', ', $missingFields));
    }

    // Validate and sanitize input
    $barberName = substr(htmlspecialchars(trim($data['name'])), 0, 100);
    
    // Validate date format
    $date = date('Y-m-d', strtotime($data['date']));
    if (!$date) {
        throw new Exception("Invalid date format");
    }
    
    // Validate time format
    $time = date('H:i', strtotime($data['time']));
    if (!$time) {
        throw new Exception("Invalid time format");
    }
    
    $service = substr(htmlspecialchars(trim($data['specialty'])), 0, 100);
    $clientName = substr(htmlspecialchars(trim($data['client_name'])), 0, 255);
    $clientEmail = substr(htmlspecialchars(trim($data['client_email'])), 0, 255);
    $clientPhone = substr(htmlspecialchars(trim($data['client_mobile'])), 0, 15);

    // Validate email format
    if (!filter_var($clientEmail, FILTER_VALIDATE_EMAIL)) {
        throw new Exception("Invalid email format.");
    }

    $selectedDay = date('l', strtotime($date));

    // Barber availability
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
    // Check availability
    if (!isset($barberAvailability[$barberName][$selectedDay]) || 
        !in_array($time, $barberAvailability[$barberName][$selectedDay])) {
        throw new Exception("The selected time is not available for $barberName on $date.");
    }

    // Prepare data for API request
    $appointmentData = [
        'name' => $barberName,
        'date' => $date,
        'time' => $time,
        'specialty' => $service,
        'client_name' => $clientName,
        'client_email' => $clientEmail,
        'client_mobile' => $clientPhone
    ];

    // Initialize cURL session
    $ch = curl_init('http://localhost/PROG%20Management/barber-website-backend/api.php');
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($appointmentData));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json'
    ]);

    // Execute API request
    $response = curl_exec($ch);
    
    if(curl_errno($ch)) {
        throw new Exception("API request failed: " . curl_error($ch));
    }
    
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if($httpCode !== 200) {
        throw new Exception("API request failed with status code: " . $httpCode);
    }

    $apiResponse = json_decode($response, true);
    if(!$apiResponse) {
        throw new Exception("Invalid response from API");
    }

    // Return success response
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
            "client_mobile" => $clientPhone
        ]
    ]);

} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "message" => $e->getMessage()
    ]);
}

exit();