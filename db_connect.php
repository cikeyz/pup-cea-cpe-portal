<?php

function activity08_load_env_file($path) {
    if (!is_file($path)) {
        return;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines === false) {
        return;
    }

    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#') {
            continue;
        }

        $parts = explode('=', $line, 2);
        if (count($parts) !== 2) {
            continue;
        }

        $key = trim($parts[0]);
        $value = trim($parts[1]);
        if ($key === '') {
            continue;
        }

        if ((strlen($value) >= 2) && (($value[0] === '"' && substr($value, -1) === '"') || ($value[0] === "'" && substr($value, -1) === "'"))) {
            $value = substr($value, 1, -1);
        }

        putenv($key . '=' . $value);
        $_ENV[$key] = $value;
        $_SERVER[$key] = $value;
    }
}

function activity08_env($key, $default) {
    $value = getenv($key);
    if ($value !== false && $value !== '') {
        return $value;
    }
    if (isset($_ENV[$key]) && $_ENV[$key] !== '') {
        return $_ENV[$key];
    }
    if (isset($_SERVER[$key]) && $_SERVER[$key] !== '') {
        return $_SERVER[$key];
    }
    return $default;
}

activity08_load_env_file(__DIR__ . '/.env');

$host = activity08_env('DB_HOST', 'localhost');
$user = activity08_env('DB_USER', 'root');
$pass = activity08_env('DB_PASS', '');
$name = activity08_env('DB_NAME', 'wms_activity08');
$port = (int) activity08_env('DB_PORT', '3306');

$conn = new mysqli($host, $user, $pass, $name, $port);

if ($conn->connect_errno) {
    http_response_code(500);
    die('Database connection failed: ' . $conn->connect_error);
}

$conn->set_charset('utf8mb4');
