<?php
require_once __DIR__ . '/../helpers/RoomModel.php';

header('Content-Type: application/json');

$checkIn = $_GET['check_in'] ?? null;
$checkOut = $_GET['check_out'] ?? null;

if (!$checkIn || !$checkOut) {
    echo json_encode(['error' => 'Missing check_in or check_out parameters']);
    exit;
}

// Validate date format
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $checkIn) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $checkOut)) {
    echo json_encode(['error' => 'Invalid date format. Use YYYY-MM-DD.']);
    exit;
}

if ($checkIn >= $checkOut) {
    echo json_encode(['error' => 'Check-in date must be before check-out date.']);
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
