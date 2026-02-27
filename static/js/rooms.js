// Rooms data is now provided by rooms.php via JSON
// const rooms = [...] - This is now injected from PHP

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
                    <p class="room-meta">Beds: ${room.beds}</p>
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
