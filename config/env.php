<?php

if (!function_exists('cre8connect_load_env')) {
    function cre8connect_load_env($path = null)
    {
        static $loaded = false;

        if ($loaded) {
            return;
        }

        $loaded = true;
        $path = $path ?: dirname(__DIR__) . DIRECTORY_SEPARATOR . '.env';

        if (!is_readable($path)) {
            return;
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if (!is_array($lines)) {
            return;
        }

        foreach ($lines as $line) {
            $line = trim((string) $line);
            if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
                continue;
            }

            [$key, $value] = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);

            if ($key === '') {
                continue;
            }

            if ((str_starts_with($value, '"') && str_ends_with($value, '"'))
                || (str_starts_with($value, "'") && str_ends_with($value, "'"))
            ) {
                $value = substr($value, 1, -1);
            }

            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
            putenv($key . '=' . $value);
        }
    }
}

if (!function_exists('cre8connect_env')) {
    function cre8connect_env($key, $default = null)
    {
        $value = getenv($key);
        if ($value === false || $value === null || $value === '') {
            $value = $_SERVER[$key] ?? $_ENV[$key] ?? null;
        }
        if ($value !== false && $value !== null && $value !== '') {
            return $value;
        }

        cre8connect_load_env();

        $value = getenv($key);
        if ($value === false || $value === null || $value === '') {
            $value = $_SERVER[$key] ?? $_ENV[$key] ?? null;
        }

        return ($value === false || $value === null || $value === '') ? $default : $value;
    }
}

if (!function_exists('envValue')) {
    function envValue($key, $default = null)
    {
        return cre8connect_env($key, $default);
    }
}
