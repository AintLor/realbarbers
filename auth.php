<?php
declare(strict_types=1);

require_once __DIR__ . '/config/env.php';

function require_admin_auth(bool $asJson = true): void
{
    $username = env_value('ADMIN_USERNAME');
    $password = env_value('ADMIN_PASSWORD');

    if ($username === null || $password === null) {
        http_response_code(500);
        $message = 'ADMIN_USERNAME and ADMIN_PASSWORD environment variables are required for admin access.';
        if ($asJson) {
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'message' => $message]);
        } else {
            header('Content-Type: text/plain');
            echo $message;
        }
        exit;
    }

    $providedUser = $_SERVER['PHP_AUTH_USER'] ?? '';
    $providedPass = $_SERVER['PHP_AUTH_PW'] ?? '';

    $authorized = $providedUser === $username && hash_equals($password, $providedPass);

    if (!$authorized) {
        header('WWW-Authenticate: Basic realm="RealBarbers Admin"');
        http_response_code(401);
        if ($asJson) {
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
        } else {
            header('Content-Type: text/plain');
            echo 'Unauthorized';
        }
        exit;
    }
}

