<?php
// CLI script to create an admin user
// Usage:
//   php scripts/create_admin.php <username> <email> <password>
//   php scripts/create_admin.php <username> <first_name> <last_name> <email> <password>

require_once __DIR__ . '/../helpers/DB.php';

if (php_sapi_name() !== 'cli') {
    echo "This script must be run from the command line.\n";
    exit(1);
}

$argc = $_SERVER['argc'];
$argv = $_SERVER['argv'];

if ($argc !== 4 && $argc !== 6) {
    echo "Usage:\n";
    echo "  php scripts/create_admin.php <username> <email> <password>\n";
    echo "  php scripts/create_admin.php <username> <first_name> <last_name> <email> <password>\n";
    exit(1);
}

$username = trim($argv[1]);
$firstName = null;
$lastName = null;

if ($argc === 6) {
    $firstName = trim($argv[2]);
    $lastName = trim($argv[3]);
    $email = trim($argv[4]);
    $password = $argv[5];
} else {
    $email = trim($argv[2]);
    $password = $argv[3];
}

if ($username === '' || $email === '' || $password === '') {
    echo "Username, email and password are required.\n";
    exit(1);
}

if ($argc === 6 && ($firstName === '' || $lastName === '')) {
    echo "First name and last name cannot be empty when provided.\n";
    exit(1);
}

try {
    $pdo = DB::getPDO();

    // Check for existing username or account_email
    $stmt = $pdo->prepare('SELECT user_id FROM Users WHERE username = :u OR account_email = :e LIMIT 1');
    $stmt->execute([':u' => $username, ':e' => $email]);
    $exists = $stmt->fetch();
    if ($exists) {
        echo "A user with that username or email already exists (id: " . ($exists['user_id'] ?? '?') . ").\n";
        exit(1);
    }

    $pdo->beginTransaction();

    $guestId = null;
    if ($argc === 6) {
        $guestInsert = $pdo->prepare(
            'INSERT INTO Guests (first_name, last_name, email) VALUES (:fn, :ln, :e)'
        );
        $guestInsert->execute([
            ':fn' => $firstName,
            ':ln' => $lastName,
            ':e' => $email,
        ]);
        $guestId = (int)$pdo->lastInsertId();
    }

    $hash = password_hash($password, PASSWORD_BCRYPT);
    $ins = $pdo->prepare(
        'INSERT INTO Users (guest_id, username, password_hash, account_email, role) VALUES (:gid, :u, :p, :e, :r)'
    );
    $ins->execute([
        ':gid' => $guestId,
        ':u' => $username,
        ':p' => $hash,
        ':e' => $email,
        ':r' => 'admin',
    ]);

    $id = (int)$pdo->lastInsertId();
    $pdo->commit();

    echo "Admin user created successfully with id: {$id}";
    if ($guestId !== null) {
        echo " (guest profile id: {$guestId})";
    }
    echo "\n";
    exit(0);
} catch (Exception $e) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    fwrite(STDERR, "Error: " . $e->getMessage() . "\n");
    exit(1);
}
