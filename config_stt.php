<?php

if (!function_exists('project_load_env')) {
    function project_load_env(string $path): void
    {
        if (!is_file($path) || !is_readable($path)) {
            return;
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            return;
        }

        foreach ($lines as $line) {
            $line = trim($line);

            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            $parts = explode('=', $line, 2);
            if (count($parts) !== 2) {
                continue;
            }

            $name = trim($parts[0]);
            $value = trim($parts[1]);

            if ($name === '') {
                continue;
            }

            if (
                (str_starts_with($value, '"') && str_ends_with($value, '"')) ||
                (str_starts_with($value, "'") && str_ends_with($value, "'"))
            ) {
                $value = substr($value, 1, -1);
            }

            if (getenv($name) === false) {
                putenv($name . '=' . $value);
                $_ENV[$name] = $value;
                $_SERVER[$name] = $value;
            }
        }
    }
}

project_load_env(__DIR__ . '/.env');

if (!defined('STT_API_BASE_URL')) {
    define('STT_API_BASE_URL', getenv('STT_API_BASE_URL') ?: 'https://api.groq.com/openai/v1');
}

if (!defined('STT_API_KEY')) {
    define('STT_API_KEY', getenv('STT_API_KEY') ?: '');
}

if (!defined('STT_MODEL')) {
    define('STT_MODEL', getenv('STT_MODEL') ?: 'whisper-large-v3-turbo');
}