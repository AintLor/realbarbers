<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/env.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo "<div class='error-message'>Unsupported request method.</div>";
    exit;
}

$errors = [];

$name = $_POST['name'] ?? '';
$email = $_POST['email'] ?? '';
$phone = $_POST['phone'] ?? '';
$service = $_POST['service'] ?? '';
$barber = $_POST['barber'] ?? '';
$appointmentDate = $_POST['appointment_date'] ?? '';
$appointmentTime = $_POST['appointment_time'] ?? '';
$notes = $_POST['notes'] ?? '';

if ($name === '') {
    $errors[] = "Full name is required.";
}
if ($email === '') {
    $errors[] = "Email address is required.";
}
if ($phone === '') {
    $errors[] = "Phone number is required.";
}
if ($service === '') {
    $errors[] = "Service selection is required.";
}
if ($barber === '') {
    $errors[] = "Please select a barber.";
}
if ($appointmentDate === '') {
    $errors[] = "Preferred appointment date is required.";
}
if ($appointmentTime === '') {
    $errors[] = "Preferred appointment time is required.";
}

if (!empty($errors)) {
    echo "<div class='error-message'>" . implode("<br>", $errors) . "</div>";
    exit;
}

$recipient = env_value('CONTACT_EMAIL', 'admin@realbarbers.com');
$subject = "New Appointment Booking";
$message = "
New booking request:
Name: {$name}
Email: {$email}
Phone: {$phone}
Service: {$service}
Barber: {$barber}
Date: {$appointmentDate}
Time: {$appointmentTime}
Notes: {$notes}
";
$headers = "From: no-reply@realbarbers.com";

if (mail($recipient, $subject, $message, $headers)) {
    echo "<div class='success-message'>Thank you! Your appointment with {$barber} has been booked successfully.</div>";
} else {
    echo "<div class='error-message'>Error: Unable to process your request. Please try again later.</div>";
}
