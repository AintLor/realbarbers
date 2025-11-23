<?php
declare(strict_types=1);

require_once __DIR__ . '/config/env.php';

const ADMIN_RATE_MAX_ATTEMPTS = 10;
const ADMIN_RATE_WINDOW_SECONDS = 300; // 5 minutes

final class RateLimitException extends RuntimeException
{
    private int $retryAfter;

    public function __construct(string $message, int $retryAfter)
    {
        parent::__construct($message);
        $this->retryAfter = $retryAfter;
    }

    public function getRetryAfter(): int
    {
        return $this->retryAfter;
    }
}

function ensure_session(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

function admin_auth_credentials(): array
{
    $username = env_value('BASIC_AUTH_USERNAME') ?? env_value('ADMIN_USERNAME');
    $password = env_value('BASIC_AUTH_PASSWORD') ?? env_value('ADMIN_PASSWORD');

    return [$username, $password];
}

function rate_limit_attempt(string $bucket, int $maxAttempts, int $windowSeconds): void
{
    $file = sys_get_temp_dir() . '/realbarbers_rate_' . md5($bucket) . '.json';
    $now = time();
    $attempts = [];

    if (is_file($file)) {
        $raw = file_get_contents($file);
        if ($raw !== false) {
            $data = json_decode($raw, true);
            if (is_array($data) && isset($data['attempts']) && is_array($data['attempts'])) {
                $attempts = array_map('intval', $data['attempts']);
            }
        }
    }

    $attempts = array_values(array_filter(
        $attempts,
        static fn($ts) => ($now - (int) $ts) < $windowSeconds
    ));

    if (count($attempts) >= $maxAttempts) {
        $retryAfter = max(1, $windowSeconds - ($now - (int) min($attempts)));
        throw new RateLimitException('Too many attempts. Try again in ' . $retryAfter . ' seconds.', $retryAfter);
    }

    $attempts[] = $now;
    file_put_contents($file, json_encode(['attempts' => $attempts]), LOCK_EX);
}

function reset_rate_limit(string $bucket): void
{
    $file = sys_get_temp_dir() . '/realbarbers_rate_' . md5($bucket) . '.json';
    if (is_file($file)) {
        @unlink($file);
    }
}

function enforce_basic_auth(bool $asJson = true, bool $persistSession = true): void
{
    ensure_session();

    [$username, $password] = admin_auth_credentials();

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

    if ($persistSession && !empty($_SESSION['admin_authenticated'])) {
        return;
    }

    $clientIp = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $providedUser = $_SERVER['PHP_AUTH_USER'] ?? '';
    $providedPass = $_SERVER['PHP_AUTH_PW'] ?? '';

    if ($providedUser === $username && hash_equals($password, $providedPass)) {
        reset_rate_limit('admin-basic:' . $clientIp);
        if ($persistSession) {
            $_SESSION['admin_authenticated'] = true;
            $_SESSION['admin_username'] = $providedUser;
        }
        return;
    }

    try {
        rate_limit_attempt('admin-basic:' . $clientIp, ADMIN_RATE_MAX_ATTEMPTS, ADMIN_RATE_WINDOW_SECONDS);
    } catch (RateLimitException $ex) {
        http_response_code(429);
        header('Retry-After: ' . $ex->getRetryAfter());
        if ($asJson) {
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'message' => $ex->getMessage()]);
        } else {
            header('Content-Type: text/plain');
            echo $ex->getMessage();
        }
        exit;
    }

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

function require_admin_auth(bool $asJson = true): void
{
    enforce_basic_auth($asJson, true);
}
