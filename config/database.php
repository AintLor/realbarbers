<?php
declare(strict_types=1);

require_once __DIR__ . '/env.php';

function database_config(): array
{
    validate_required_env(['DB_HOST', 'DB_NAME', 'DB_USERNAME', 'DB_PASSWORD']);

    return [
        'host' => require_env('DB_HOST'),
        'name' => require_env('DB_NAME'),
        'user' => require_env('DB_USERNAME'),
        'password' => require_env('DB_PASSWORD'),
        'port' => (int) env_value('DB_PORT', '3306'),
        'charset' => env_value('DB_CHARSET', 'utf8mb4'),
    ];
}

function get_mysqli_connection(): mysqli
{
    $config = database_config();

    $connection = new mysqli(
        $config['host'],
        $config['user'],
        $config['password'],
        $config['name'],
        $config['port']
    );

    if ($connection->connect_error) {
        throw new RuntimeException('Database connection failed: ' . $connection->connect_error);
    }

    $connection->set_charset($config['charset']);

    return $connection;
}

function get_pdo_connection(): PDO
{
    $config = database_config();

    $dsn = sprintf(
        'mysql:host=%s;dbname=%s;port=%d;charset=%s',
        $config['host'],
        $config['name'],
        $config['port'],
        $config['charset']
    );

    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ];

    return new PDO($dsn, $config['user'], $config['password'], $options);
}
