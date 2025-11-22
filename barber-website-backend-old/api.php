<?php

include 'config/db.php';

header("Content-Type: application/json");

$db = new Database();
$conn = $db->getConnection();

$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true);

switch ($method) {
    case 'POST':
        $date = $input['date'];
        $time = $input['time'];
        $name = $input['name'];
        $specialty = $input['specialty'];
        $client_name = $input['client_name'];
        $client_email = $input['client_email'];
        $client_mobile = $input['client_mobile'];

        // Validate data lengths
        if (strlen($name) > 100 || strlen($specialty) > 100 ||
            strlen($client_name) > 255 || strlen($client_email) > 255 ||
            strlen($client_mobile) > 15) {
            echo json_encode([
                "message" => "Input data exceeds maximum length",
                "status" => "error"
            ]);
            break;
        }
        
       
        
        $conn->query("INSERT INTO appointments (date, time, name, specialty, client_name, client_email, client_mobile) 
                     VALUES ('$date', '$time', '$name', '$specialty', '$client_name', '$client_email', '$client_mobile')");
        echo json_encode(["message" => "Appointment added successfully"]);
        break;

    default:
        echo json_encode(["message" => "Invalid request method"]);
        break;
}

$db->closeConnection();
?>
