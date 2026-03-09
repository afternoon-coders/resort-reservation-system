<?php

    require_once __DIR__ . '/../helpers/env.php';

    loadEnv(__DIR__ . '/../.env');

    $db_server = $_ENV['DB_HOST'] ?? '';
    $db_user = $_ENV['DB_USER'] ?? '';
    $db_pass = $_ENV['DB_PASS'] ?? '';
    $db_name = $_ENV['DB_NAME'] ?? '';

    if ($db_server === '' || $db_user === '' || $db_name === '') {
        error_log('Database environment variables not set.');
        return;
    }

?>