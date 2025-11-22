<?php
declare(strict_types=1);

/**
 * Helper functions for working with environment variables.
 * These are kept lightweight to avoid external dependencies while still
 * ensuring required configuration is present at runtime.
 */

if (!function_exists('env_value')) {
    function env_value(string $key, ?string $default = null): ?string
    {
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
