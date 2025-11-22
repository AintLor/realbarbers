<?php
// process_booking.php

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Initialize error variable
    $errors = [];

    // Collect form data
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $service = $_POST['service'] ?? '';
    $barber = $_POST['barber'] ?? '';  // Added barber selection
    $appointment_date = $_POST['appointment_date'] ?? '';
    $appointment_time = $_POST['appointment_time'] ?? '';
    $notes = $_POST['notes'] ?? '';

    // Check if any required fields are empty
    if (empty($name)) {
        $errors[] = "Full name is required.";
    }
    if (empty($email)) {
        $errors[] = "Email address is required.";
    }
    if (empty($phone)) {
        $errors[] = "Phone number is required.";
    }
    if (empty($service)) {
        $errors[] = "Service selection is required.";
    }
    if (empty($barber)) {  // Validate barber selection
        $errors[] = "Please select a barber.";
    }
    if (empty($appointment_date)) {
        $errors[] = "Preferred appointment date is required.";
    }
    if (empty($appointment_time)) {
        $errors[] = "Preferred appointment time is required.";
    }

    // If there are errors, show them
    if (!empty($errors)) {
        echo "<div class='error-message'>" . implode("<br>", $errors) . "</div>";
    } else {
        // Process the booking (e.g., save to database or send email)
        $to = "admin@realbarbers.com"; // Replace with your email
        $subject = "New Appointment Booking";
        $message = "
        New booking request:
        Name: $name
        Email: $email
        Phone: $phone
        Service: $service
        Barber: $barber  <!-- Display the selected barber -->
        Date: $appointment_date
        Time: $appointment_time
        Notes: $notes
        ";
        $headers = "From: no-reply@realbarbers.com";

        // Send email
        if (mail($to, $subject, $message, $headers)) {
            echo "<div class='success-message'>Thank you! Your appointment with $barber has been booked successfully.</div>";
        } else {
            echo "<div class='error-message'>Error: Unable to process your request. Please try again later.</div>";
        }
    }
}
?>
