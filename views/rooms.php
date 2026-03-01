<?php
require_once 'helpers/RoomModel.php';

// Fetch rooms from database
$roomModel = new RoomModel();
$dbRooms = $roomModel->getAll(['status' => 'available']);

// Format rooms for JavaScript
$formattedRooms = array_map(function($room) {
    return [
        'room_id' => $room['cottage_id'] ?? $room['room_id'],
        'name' => $room['name'] ?? $room['room_type'],
        'price' => (int)($room['base_price'] ?? $room['price_per_night'] ?? 0),
        'image' => 'static/img/' . strtolower(str_replace(' ', '_', $room['name'] ?? $room['room_type'] ?? 'cottage')) . '.jpg',
        'description' => 'Beautiful ' . ($room['name'] ?? $room['room_type'] ?? 'cottage') . ' with premium amenities and stunning views.',
        'beds' => (int)($room['max_occupancy'] ?? $room['number_of_beds'] ?? 0)
    ];
}, $dbRooms);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rooms - Luxury Resort</title>
    <link rel="stylesheet" href="static/css/style.css">
</head>
<body>
    
    <section class="room_header">
        <div class="container">

            <h2 class="section-title-rooms">Our Accommodations</h2>
            <p class="section-subtitle">From ocean-view rooms to private villas, find your perfect island retreat</p>

        </div>
    </section>


    <section class="rooms-section">
        <div class="container">
            <!-- <div class="filter-bar">
                <input type="date" id="checkIn" placeholder="Check-in Date" class="filter-input">
                <input type="date" id="checkOut" placeholder="Check-out Date" class="filter-input">
                <button onclick="filterRooms()" class="btn btn-primary">Search</button>
            </div> -->

            <div class="rooms-list" id="roomsList">
                <!-- Rooms will be populated by JavaScript -->
            </div>
        </div>
    </section>

    
    <section class="rooms-amenities">
        <div class="container">
            <h2 class="rooms-amenities-section-title">All Rooms Include</h2>
            

            <div class="rooms-amenities-grid">

                <div class="rooms-amenity-card">
                    <span><svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#0985af"><path d="M409-149q-29-29-29-71t29-71q29-29 71-29t71 29q29 29 29 71t-29 71q-29 29-71 29t-71-29ZM254-346l-84-86q59-59 138.5-93.5T480-560q92 0 171.5 35T790-430l-84 84q-44-44-102-69t-124-25q-66 0-124 25t-102 69ZM84-516 0-600q92-94 215-147t265-53q142 0 265 53t215 147l-84 84q-77-77-178.5-120.5T480-680q-116 0-217.5 43.5T84-516Z"/></svg></span>
                    <h3>Free Wifi</h3>
                </div>

                <div class="rooms-amenity-card">
                    <span><svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#0985af"><path d="M440-240q-117 0-198.5-81.5T160-520v-240q0-33 23.5-56.5T240-840h500q58 0 99 41t41 99q0 58-41 99t-99 41h-20v40q0 117-81.5 198.5T440-240ZM240-640h400v-120H240v120Zm200 320q83 0 141.5-58.5T640-520v-40H240v40q0 83 58.5 141.5T440-320Zm280-320h20q25 0 42.5-17.5T800-700q0-25-17.5-42.5T740-760h-20v120ZM160-120v-80h640v80H160Zm280-440Z"/></svg></span>
                    <h3>Welcome Drinks</h3>
                </div>

                <div class="rooms-amenity-card">
                    <span><svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#0985af"><path d="M223.5-623.5Q200-647 200-680t23.5-56.5Q247-760 280-760t56.5 23.5Q360-713 360-680t-23.5 56.5Q313-600 280-600t-56.5-23.5ZM200-80q-17 0-28.5-11.5T160-120q-33 0-56.5-23.5T80-200v-240h120v-30q0-38 26-64t64-26q20 0 37 8t31 22l56 62q8 8 15.5 15t16.5 13h274v-326q0-14-10-24t-24-10q-6 0-11.5 2.5T664-790l-50 50q5 17 2 33.5T604-676L494-788q14-9 30-11.5t32 3.5l50-50q16-16 36.5-25t43.5-9q48 0 81 33t33 81v326h80v240q0 33-23.5 56.5T800-120q0 17-11.5 28.5T760-80H200Zm-40-120h640v-160H160v160Zm0 0h640-640Z"/></svg></span>
                    <h3>Bath</h3>
                </div>

                <div class="rooms-amenity-card">
                    <span><svg xmlns="http://www.w3.org/2000/svg" height="24px" viewBox="0 -960 960 960" width="24px" fill="#0985af"><path d="M40-160v-112q0-34 17.5-62.5T104-378q62-31 126-46.5T360-440q66 0 130 15.5T616-378q29 15 46.5 43.5T680-272v112H40Zm720 0v-120q0-44-24.5-84.5T666-434q51 6 96 20.5t84 35.5q36 20 55 44.5t19 53.5v120H760ZM247-527q-47-47-47-113t47-113q47-47 113-47t113 47q47 47 47 113t-47 113q-47 47-113 47t-113-47Zm466 0q-47 47-113 47-11 0-28-2.5t-28-5.5q27-32 41.5-71t14.5-81q0-42-14.5-81T544-792q14-5 28-6.5t28-1.5q66 0 113 47t47 113q0 66-47 113ZM120-240h480v-32q0-11-5.5-20T580-306q-54-27-109-40.5T360-360q-56 0-111 13.5T140-306q-9 5-14.5 14t-5.5 20v32Zm296.5-343.5Q440-607 440-640t-23.5-56.5Q393-720 360-720t-56.5 23.5Q280-673 280-640t23.5 56.5Q327-560 360-560t56.5-23.5ZM360-240Zm0-400Z"/></svg></span>
                    <h3>24/7 Service</h3>
                </div>

            </div>
        </div>
    </section>

    

    <script src="app.js"></script>
    
    <!-- Pass rooms data to JavaScript -->
    <script>
        const rooms = <?php echo json_encode($formattedRooms); ?>;
    </script>
    
    <script src="static/js/rooms.js"></script>
</body>
</html>
