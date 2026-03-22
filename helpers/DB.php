<?php

class DB {
    private static $pdo = null;

    /**
     * Return a shared PDO instance using project config/database.php settings.
     * This expects `config/database.php` to define $db_server, $db_user, $db_pass and $db_name
     */
    public static function getPDO()
    {
        if (self::$pdo instanceof PDO) {
            return self::$pdo;
        }

        // Load DB config (must NOT echo anything)
        $configPath = __DIR__ . '/../config/database.php';
        if (!file_exists($configPath)) {
            throw new RuntimeException("Database config not found at $configPath");
        }

        // include the config file to get variables
        include $configPath; // provides $db_server, $db_user, $db_pass, $db_name

        $dsn = "mysql:host={$db_server};dbname={$db_name};charset=utf8mb4";

        try {
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];

            self::$pdo = new PDO($dsn, $db_user, $db_pass, $options);
            return self::$pdo;
        } catch (PDOException $e) {
            // Bubble up the exception for callers to handle
            throw new RuntimeException('Database connection failed: ' . $e->getMessage());
        }
    }
}
