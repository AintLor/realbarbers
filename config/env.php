<?php
declare(strict_types=1);

/**
 * Helper functions for working with environment variables.
 * These are kept lightweight to avoid external dependencies while still
 * ensuring required configuration is present at runtime.
 */

if (!function_exists('load_project_env')) {
    function load_project_env(): void
    {
        static $loaded = false;
        if ($loaded) {
            return;
        }

        $envPath = dirname(__DIR__) . '/.env';
        if (!is_file($envPath)) {
            $loaded = true;
            return;
        }

        $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            if (!str_contains($line, '=')) {
                continue;
            }

            [$key, $value] = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);

            // Strip surrounding quotes if present.
            if ((str_starts_with($value, '"') && str_ends_with($value, '"')) ||
                (str_starts_with($value, "'") && str_ends_with($value, "'"))) {
                $value = substr($value, 1, -1);
            }

            if ($key === '') {
                continue;
            }

            // Do not override already-set env values.
            if (getenv($key) === false && !isset($_ENV[$key])) {
                putenv($key . '=' . $value);
                $_ENV[$key] = $value;
            }
        }

        $loaded = true;
    }
}

if (!function_exists('env_value')) {
    function env_value(string $key, ?string $default = null): ?string
    {
        load_project_env();
        $value = getenv($key);
        if ($value === false) {
            $value = $_ENV[$key] ?? null;
        }

        if ($value === null || $value === '') {
            return $default;
        }

        return $value;
    }
}

if (!function_exists('require_env')) {
    function require_env(string $key): string
    {
        $value = env_value($key);
        if ($value === null) {
            throw new RuntimeException("Environment variable {$key} is required but missing.");
        }

        return $value;
    }
}

if (!function_exists('validate_required_env')) {
    function validate_required_env(array $variables): void
    {
        $missing = [];
        foreach ($variables as $variable) {
            if (env_value($variable) === null) {
                $missing[] = $variable;
            }
        }

        if (!empty($missing)) {
            throw new RuntimeException('Missing required environment variables: ' . implode(', ', $missing));
        }
    }
}
