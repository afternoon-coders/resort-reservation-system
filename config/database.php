<?php

    require_once __DIR__ . '/../helpers/env.php';

    loadEnv(__DIR__ . '/../.env');

    $db_server = $_ENV['DB_HOST'] ?? '';
    $db_user = $_ENV['DB_USER'] ?? '';
    $db_pass = $_ENV['DB_PASS'] ?? '';
    $db_name = $_ENV['DB_NAME'] ?? '';
    $conn = "";

    if ($db_server === '' || $db_user === '' || $db_name === '') {
        error_log('Database environment variables not set.');
        return;
    }

    $conn = mysqli_connect($db_server,
                            $db_user,
                            $db_pass,
                            $db_name);

    // Do not echo connection status here so this file can be safely included
    // by helper libraries. On failure, write to error log for diagnosis.
    if (!$conn) {
        error_log('Database connection failed: ' . mysqli_connect_error());
    }

?>