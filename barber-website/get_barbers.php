<?php
header('Content-Type: application/json');

// Database connection parameters
$host = 'localhost';
$dbname = 'realbarbers_db'; // Your database name
$username = 'lorenz';       // Your database username
$password = 'lorenz@21';    // Your database password

// Create connection
$conn = new mysqli($host, $username, $password, $dbname);

// Check the connection
if ($conn->connect_error) {
    echo json_encode(["success" => false, "message" => "Connection failed: " . $conn->connect_error]);
    exit();
}

// Execute the query to fetch barbers
$query = "SELECT id, name FROM barbers"; // Ensure this query is correct
$result = $conn->query($query);

$response = []; // Initialize response array

if ($result) {
    $response["barbers"] = []; // Initialize the barbers array

    while ($row = $result->fetch_assoc()) {
        $response["barbers"][] = $row; // Append each barber's details
    }

    if (count($response["barbers"]) > 0) {
        $response["success"] = true; // Set success to true if barbers exist
    } else {
        $response["success"] = false;
        $response["message"] = "No barbers found in the database.";
    }
} else {
    $response["success"] = false;
    $response["message"] = "Query failed: " . $conn->error;
}

// Close the database connection
$conn->close();

// Output the JSON response
echo json_encode($response);
?>