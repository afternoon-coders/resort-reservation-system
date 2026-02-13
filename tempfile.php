<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rooms - Luxury Resort</title>
    <link rel="stylesheet" href="static/css/style.css">
</head>
<body>
    
    <section class="page-header">
        <div class="container">
            <h1>Our Rooms</h1>
            <p>Choose from our selection of luxury accommodations</p>
        </div>
    </section>

    <section class="rooms-section">
        <div class="container">
            <div class="filter-bar">
                <input type="date" id="checkIn" placeholder="Check-in Date" class="filter-input">
                <input type="date" id="checkOut" placeholder="Check-out Date" class="filter-input">
                <button onclick="filterRooms()" class="btn btn-primary">Search</button>
            </div>

            <div class="rooms-list" id="roomsList">
                <!-- Rooms will be populated by JavaScript -->
            </div>
        </div>
    </section>

    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h4>About Us</h4>
                    <p>Luxury Resort offers world-class hospitality and unforgettable experiences.</p>
                </div>
                <div class="footer-section">
                    <h4>Quick Links</h4>
                    <ul>
                        <li><a href="rooms.php">Rooms</a></li>
                        <li><a href="amenities.php">Amenities</a></li>
                        <li><a href="contact.php">Contact</a></li>
                    </ul>
                </div>
                <div class="footer-section">
                    <h4>Contact</h4>
                    <p>Email: info@luxuryresort.com</p>
                    <p>Phone: +1-800-RESORT</p>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2024 Luxury Resort. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script src="app.js"></script>
    <script src="rooms.js"></script>
</body>
</html>
    