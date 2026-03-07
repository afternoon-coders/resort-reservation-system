<?php
require_once __DIR__ . '/../helpers/RoomModel.php';

header('Content-Type: application/json');

$checkIn = $_GET['check_in'] ?? null;
$checkOut = $_GET['check_out'] ?? null;

if (!$checkIn || !$checkOut) {
    echo json_encode(['error' => 'Missing check_in or check_out parameters']);
    exit;
}

try {
    $roomModel = new RoomModel();
    $availableTypes = $roomModel->getAvailableTypes($checkIn, $checkOut);
    
    echo json_encode([
        'success' => true,
        'types' => $availableTypes
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'An error occurred while fetching available rooms.'
    ]);
}
