<?php
// CLI script to create an admin user
// Usage: php scripts/create_admin.php <username> <email> <password>

require_once __DIR__ . '/../helpers/DB.php';

if (php_sapi_name() !== 'cli') {
    echo "This script must be run from the command line.\n";
    exit(1);
}

$argc = $_SERVER['argc'];
$argv = $_SERVER['argv'];

if ($argc < 4) {
    echo "Usage: php scripts/create_admin.php <username> <email> <password>\n";
    exit(1);
}

$username = trim($argv[1]);
$email = trim($argv[2]);
$password = $argv[3];

if ($username === '' || $email === '' || $password === '') {
    echo "Username, email and password are required.\n";
    exit(1);
}

try {
    $pdo = DB::getPDO();

    // Check for existing username or email
    $stmt = $pdo->prepare('SELECT user_id FROM users WHERE username = :u OR email = :e LIMIT 1');
    $stmt->execute([':u' => $username, ':e' => $email]);
    $exists = $stmt->fetch();
    if ($exists) {
        echo "A user with that username or email already exists (id: " . ($exists['user_id'] ?? '?') . ").\n";
        exit(1);
    }

    $hash = password_hash($password, PASSWORD_BCRYPT);
    $ins = $pdo->prepare('INSERT INTO users (username, password, email, role) VALUES (:u, :p, :e, :r)');
    $ins->execute([':u' => $username, ':p' => $hash, ':e' => $email, ':r' => 'admin']);

    $id = (int)$pdo->lastInsertId();
    echo "Admin user created successfully with id: {$id}\n";
    exit(0);

} catch (Exception $e) {
    fwrite(STDERR, "Error: " . $e->getMessage() . "\n");
    exit(1);
}
