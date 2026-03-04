<?php

require_once __DIR__ . '/GuestModel.php';
require_once __DIR__ . '/RoomModel.php';
require_once __DIR__ . '/UserModel.php';

try {
    $g = new GuestModel();
    $r = new RoomModel();
    $u = new UserModel();

    // Create a guest
    $guestId = $g->create(['first_name' => 'Alice', 'last_name' => 'Test', 'email' => 'alice.test@example.com', 'phone' => '555-0100', 'address' => 'Test St']);
    echo "Created guest with ID: $guestId\n";

    // Create a user linked to the guest
    $userId = $u->create(['guest_id' => $guestId, 'username' => 'alicetest', 'password' => 'password', 'email' => 'alice.test@example.com', 'role' => 'guest']);
    echo "Created user with ID: $userId\n";

    // Create a cottage
    $roomId = $r->create(['room_number' => '101-TEST', 'type_id' => 1, 'price_per_night' => '120.00', 'number_of_beds' => 1, 'status' => 'Available']);
    echo "Created room with ID: $roomId\n";

    // Fetch them back
    $guest = $g->getById($guestId);
    $user = $u->getById($userId);
    $room = $r->getById($roomId);

    echo "Guest:\n"; print_r($guest);
    echo "User (joined with Guest):\n"; print_r($user);
    echo "Room (joined with Type):\n"; print_r($room);

} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . "\n" . $e->getTraceAsString() . "\n";
}

