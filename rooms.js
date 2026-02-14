// Hardcoded Rooms
const rooms = [
    {
        name: "Ocean View Deluxe",
        price: 4500,
        image: "https://images.unsplash.com/photo-1566665797739-1674de7a421a",
        description: "Spacious room with breathtaking ocean views and private balcony."
    },
    {
        name: "Garden Villa",
        price: 6500,
        image: "https://images.unsplash.com/photo-1582719478250-c89cae4dc85b",
        description: "Private villa surrounded by tropical gardens and luxury amenities."
    },
    {
        name: "Premium Suite",
        price: 8500,
        image: "https://images.unsplash.com/photo-1590490360182-c33d57733427",
        description: "Elegant suite with separate living area and island-inspired decor."
    },
    {
        name: "Standard Room",
        price: 3000,
        image: "https://images.unsplash.com/photo-1551882547-ff40c63fe5fa",
        description: "Comfortable and modern room perfect for short stays."
    }
];

// Load Rooms
function loadRooms() {
    const container = document.getElementById("roomsList");
    container.innerHTML = "";

    rooms.forEach(room => {
        container.innerHTML += `
            <div class="room-card">
                <img src="${room.image}" class="room-image">
                <div class="room-content">
                    <h3 class="room-title">${room.name}</h3>
                    <div class="room-price">₱${room.price} / night</div>
                    <p class="room-description">${room.description}</p>
                    <div class="button-group">
                        <a href="#" class="book-btn">Book Now</a>
                        <a href="#" class="details-btn">View Details</a>
                    </div>

                </div>
            </div>
        `;
    });
}

// Simple filter (just checks if date selected)
function filterRooms() {
    const checkIn = document.getElementById("checkIn").value;
    const checkOut = document.getElementById("checkOut").value;

    if (!checkIn || !checkOut) {
        alert("Please select check-in and check-out dates.");
        return;
    }

    alert("Rooms available for selected dates!");
}

// Load on page start
window.onload = loadRooms;
