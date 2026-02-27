<?php
// Runner to execute all seed scripts in order
$root = __DIR__;
$cmds = [
    "php $root/seed_users.php",
    "php $root/seed_cottages.php",
    "php $root/seed_guests.php",
    "php $root/seed_reservations.php",
    "php $root/seed_payments.php",
];

foreach ($cmds as $c) {
    echo "\n== Running: $c ==\n";
    passthru($c, $ret);
    if ($ret !== 0) {
        echo "Command failed with code $ret: $c\n";
        exit($ret);
    }
}

echo "\nAll seeding scripts completed.\n";
