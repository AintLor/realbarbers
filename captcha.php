<?php
declare(strict_types=1);

header('Content-Type: application/json');

session_start();

$form = isset($_GET['form']) ? preg_replace('/[^a-zA-Z0-9_-]/', '', $_GET['form']) : 'general';
if ($form === '') {
    $form = 'general';
}

$a = random_int(1, 9);
$b = random_int(1, 9);
$answer = $a + $b;

$_SESSION['captcha'][$form] = $answer;

echo json_encode([
    'status' => 'success',
    'question' => "What is {$a} + {$b}?",
]);
