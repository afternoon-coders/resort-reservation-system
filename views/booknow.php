<?php
require_once __DIR__ . '/../helpers/RoomModel.php';
require_once __DIR__ . '/../helpers/GuestModel.php';
require_once __DIR__ . '/../helpers/ReservationModel.php';
require_once __DIR__ . '/../helpers/UserModel.php';
require_once __DIR__ . '/../auth/auth_functions.php';

$roomModel = new RoomModel();
$guestModel = new GuestModel();
$reservationModel = new ReservationModel();
$userModel = new UserModel();

$requestedRoomTypeId = $_POST['room_id'] ?? $_GET['room_type_id'] ?? $_GET['room_id'] ?? null;
$allRooms = $roomModel->getAll(['status' => 'available']);

$uniqueTypes = [];
$uniqueRooms = [];
foreach ($allRooms as $room) {
    if (!in_array($room['type_id'], $uniqueTypes)) {
        $uniqueTypes[] = $room['type_id'];
        $uniqueRooms[] = $room;
    }
}

$roomTypeCatalog = array_map(static function ($room) {
    return [
        'type_id' => (int)($room['type_id'] ?? 0),
        'name' => $room['name'] ?? 'Cottage',
        'base_price' => (float)($room['base_price'] ?? 0),
        'max_occupancy' => (int)($room['max_occupancy'] ?? 0),
    ];
}, $uniqueRooms);

$selectedRoomTypeId = null;
if ($requestedRoomTypeId !== null && ctype_digit((string)$requestedRoomTypeId)) {
    $selectedRoomTypeId = (int)$requestedRoomTypeId;
}

if ($selectedRoomTypeId !== null) {
    $validTypeIds = array_column($roomTypeCatalog, 'type_id');

    if (!in_array($selectedRoomTypeId, $validTypeIds, true)) {
        foreach ($allRooms as $room) {
            if ((int)($room['cottage_id'] ?? 0) === $selectedRoomTypeId) {
                $selectedRoomTypeId = (int)$room['type_id'];
                break;
            }
        }
    }

    if (!in_array($selectedRoomTypeId, $validTypeIds, true)) {
        $selectedRoomTypeId = null;
    }
}

$firstName = '';
$lastName = '';
$email = '';
$phone_number = '';
$msg = '';
$msgType = '';

// Today's date for constraints
$today = date('Y-m-d');

// Handle Registration/Reservation Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'reserve') {
    $roomId = $_POST['room_id'] ?? null;
    $checkIn = $_POST['check_in'] ?? null;
    $checkOut = $_POST['check_out'] ?? null;
    $guestsCount = $_POST['guests'] ?? null;
    $fName = $_POST['first_name'] ?? '';
    $lName = $_POST['last_name'] ?? '';
    $contactEmail = $_POST['email'] ?? '';
    $phone_number = $_POST['phone_number'] ?? '';
    $totalAmount = $_POST['total_amount'] ?? 0;
    $specialRequests = $_POST['special_requests'] ?? '';

    if (!$roomId || !$checkIn || !$checkOut || !$fName || !$lName || !$contactEmail) {
        $msg = "Please fill in all required fields.";
        $msgType = "error";
    } else {
        try {
            $guestId = null;
            $userId = isLoggedIn() ? getCurrentUser()['user_id'] : null;

            if ($userId) {
                // Get user info which includes guest_id
                $user = $userModel->getById($userId);
                if ($user && !empty($user['guest_id'])) {
                    $guestId = $user['guest_id'];
                }
            }

            $assignedCottageId = $reservationModel->getAvailableCottageByType((int)$roomId, $checkIn, $checkOut);

            if (!$assignedCottageId) {
                $msg = "Sorry, there are no cottages of this type available for the chosen dates. Please select different dates or another cottage type.";
                $msgType = "error";
            } else {
                if (!$guestId) {
                    $guestId = $guestModel->create([
                        'first_name' => $fName,
                        'last_name' => $lName,
                        'email' => $contactEmail,
                        'phone_number' => $phone_number,
                        'address' => '' 
                    ]);
                }

                $reservationId = $reservationModel->create([
                    'guest_id' => $guestId,
                    'room_id' => (int)$roomId,
                    'check_in_date' => $checkIn,
                    'check_out_date' => $checkOut,
                    'total_amount' => $totalAmount,
                    'status' => 'Pending',
                    'notes' => $specialRequests
                ]);

                if ($reservationId) {
                    $token = $reservationModel->getLastToken();
                    
                    $reservationDetails = $reservationModel->getById($reservationId);
                    
                    require_once __DIR__ . '/../helpers/Mailer.php';
                    $emailSent = Mailer::sendConfirmationEmail($contactEmail, $fName . ' ' . $lName, $reservationId, $token, $reservationDetails);
                    
                    header("Location: index.php?page=receipt&id=$reservationId&token=$token");
                    exit;
                } else {
                    $msg = "Failed to create reservation. Please try again.";
                    $msgType = "error";
                }
            }
        } catch (\Throwable $e) {
            $msg = "An error occurred: " . $e->getMessage();
            $msgType = "error";
        }
    }
}

if (isLoggedIn()) {
    $currentUser = getCurrentUser();
    // UserModel::getById now returns joined guest info
    $user = $userModel->getById($currentUser['user_id']);
    if ($user) {
        $firstName = $user['first_name'] ?? '';
        $lastName = $user['last_name'] ?? '';

        $email = $user['email'] ?? $user['account_email'] ?? '';
        $phone_number = $user['phone_number'] ?? '';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Complete Your Reservation | La Paseo Resort</title>
    <link rel="stylesheet" href="static/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
    <?php if ($msg): ?>
        <div id="msg-popup" class="msg-container <?php echo $msgType; ?>">
            <div class="msg-icon">
                <?php if ($msgType === 'success'): ?>
                    <i class="fa-solid fa-circle-check"></i>
                <?php else: ?>
                    <i class="fa-solid fa-circle-exclamation"></i>
                <?php endif; ?>
            </div>
            <div class="msg-content">
                <strong><?php echo $msgType === 'success' ? 'Almost there!' : 'Attention'; ?></strong>
                <p><?php echo $msg; ?></p>
            </div>
            <button onclick="closeMsg()" style="
                background: none;
                border: none;
                cursor: pointer;
                margin-left: auto;
                font-size: 18px;
                line-height: 1;
                color: inherit;
                opacity: 0.6;
            ">&times;</button>
        </div>

        <script>
            document.addEventListener('DOMContentLoaded', function() {
                // Auto hide after 5 seconds
                setTimeout(() => {
                    closeMsg();
                }, 5000);
            });

            function closeMsg() {
                const popup = document.getElementById('msg-popup');
                if (popup) {
                    popup.style.opacity = '0';
                    setTimeout(() => popup.style.display = 'none', 400);
                }
            }
        </script>
    <?php endif; ?>

    <div class="booknow-container">
        
        <!-- LEFT: BOOKING FORM -->
        <div class="booknow-card">

            <form method="POST" id="reservationForm">
                <input type="hidden" name="action" value="reserve">
                <input type="hidden" name="total_amount" id="totalAmountInput" value="0">

                <div class="booknow-section-title">
                    <span><i class="fa-solid fa-calendar-check" style="color: #086584;"></i></span>
                    Reservation Details
                </div>

                <div class="booknow-grid-3">
                    <div>
                        <label class="booknow-label">Check-in Date</label>
                        <input type="date" name="check_in" id="checkInDate" class="booknow-input" min="<?php echo $today; ?>" required>
                    </div>
                    <div>
                        <label class="booknow-label">Check-out Date</label>
                        <input type="date" name="check_out" id="checkOutDate" class="booknow-input" min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>" required>
                    </div>
                    <div>
                        <label class="booknow-label">Room Type</label>
                            <select name="room_id" class="booknow-select" id="roomType" onchange="updateSummary()" required>
                            <option value="">Select a room type</option>
                            <?php foreach ($roomTypeCatalog as $roomType): ?>
                                <option
                                    value="<?php echo (int)$roomType['type_id']; ?>"
                                    data-price="<?php echo htmlspecialchars((string)$roomType['base_price']); ?>"
                                    data-max-occupancy="<?php echo (int)$roomType['max_occupancy']; ?>"
                                    <?php echo $selectedRoomTypeId === (int)$roomType['type_id'] ? 'selected' : ''; ?>
                                >
                                    <?php echo htmlspecialchars($roomType['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if ($selectedRoomTypeId !== null): ?>
                            <p style="font-size: 12px; color: #64748b; margin-top: 8px;">Selected from the rooms page. Choose your dates to confirm availability.</p>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div style="margin-top:16px; max-width:260px;">
                    <label class="booknow-label">Number of Guests</label>
                    <select name="guests" id="guestSelect" class="booknow-select" required>
                        <option value="">Select guests</option>
                    </select>
            </div>

                <div class="booknow-divider"></div>

                <div class="booknow-section-title">
                    <span><i class="fa-solid fa-user" style="color: #086584;"></i></span>
                    Guest Information
                </div>

                <div class="booknow-grid-2">
                    <div>
                        <label class="booknow-label">First Name</label>
                        <input type="text" name="first_name" class="booknow-input" value="<?php echo htmlspecialchars($firstName); ?>" placeholder="Enter first name" required>
                    </div>
                    <div>
                        <label class="booknow-label">Last Name</label>
                        <input type="text" name="last_name" class="booknow-input" value="<?php echo htmlspecialchars($lastName); ?>" placeholder="Enter last name" required>
                    </div>
                    <div>
                        <label class="booknow-label">Email Address</label>
                        <input type="email" name="email" class="booknow-input" value="<?php echo htmlspecialchars($email); ?>" placeholder="Enter email address" required>
                    </div>
                    <div>
                        <label class="booknow-label">Phone Number</label>
                        <input type="tel" name="phone_number" class="booknow-input" value="<?php echo htmlspecialchars($phone_number); ?>" placeholder="Enter phone number">
                    </div>
                </div>

                <div style="margin-top:16px;">
                    <label class="booknow-label">Special Requests (Optional)</label>
                    <textarea name="special_requests" class="booknow-textarea" placeholder="Any special requirements or requests..."></textarea>
                </div>

                <div style="margin-top:24px;">
                    <button type="submit" class="booknow-submit" id="submitBtn">
                        <span class="btn-text">Complete Reservation</span>
                    </button>
                    <p style="font-size: 13px; color: #64748b; margin-top: 12px; text-align: center;">
                        <i class="fa-solid fa-shield-halved" style="margin-right: 4px;"></i> 
                        Your personal data is protected and encrypted
                    </p>
                </div>
            </form>

        </div>

        <!-- RIGHT: SUMMARY -->
        <div class="summary-card">
            <div class="booknow-section-title">
                <span><i class="fa-solid fa-file-invoice-dollar" style="color: #086584;"></i></span>
                Booking Summary
            </div>

            <div class="summary-header">Price Breakdown</div>
            <div class="booknow-summary-text">
                Select dates and a room type to see pricing
            </div>

            <div class="booknow-secure">
                <div class="secure-badge">
                    <i class="fa-solid fa-lock"></i>
                    <span>Secure payment processing</span>
                </div>
            </div>
            
            <div style="margin-top: 20px; font-size: 13px; color: #64748b;">
                <p style="margin-bottom: 8px;"><i class="fa-solid fa-circle-check" style="color: #22c55e; margin-right: 6px;"></i> Free cancellation within 48 hours</p>
                <p><i class="fa-solid fa-circle-check" style="color: #22c55e; margin-right: 6px;"></i> Online booking fees</p>
            </div>
        </div>

    </div>

    <script>
    const roomTypeCatalog = <?php echo json_encode($roomTypeCatalog, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
    const initialSelectedRoomTypeId = <?php echo json_encode($selectedRoomTypeId); ?>;

    function renderRoomTypeOptions(roomTypes, selectedValue = null, placeholder = 'Select a room type') {
        const roomSelect = document.getElementById('roomType');
        const normalizedSelectedValue = selectedValue === null || selectedValue === '' ? '' : String(selectedValue);
        let hasSelectedMatch = normalizedSelectedValue === '';
        let options = `<option value="">${placeholder}</option>`;

        roomTypes.forEach(type => {
            const typeId = String(type.type_id);
            const isSelected = normalizedSelectedValue !== '' && normalizedSelectedValue === typeId;
            if (isSelected) {
                hasSelectedMatch = true;
            }

            options += `<option value="${typeId}" data-price="${type.base_price}" data-max-occupancy="${type.max_occupancy}" ${isSelected ? 'selected' : ''}>${type.name}</option>`;
        });

        roomSelect.innerHTML = options;

        if (!hasSelectedMatch) {
            roomSelect.value = '';
        }

        return hasSelectedMatch && normalizedSelectedValue !== '';
    }

    function updateSummary() {
        const select = document.getElementById('roomType');
        const guestSelect = document.getElementById('guestSelect');
        const summaryText = document.querySelector('.booknow-summary-text');
        const checkIn = document.getElementById('checkInDate').value;
        const checkOut = document.getElementById('checkOutDate').value;
        const totalAmountInput = document.getElementById('totalAmountInput');
        
        const selectedOption = select.options[select.selectedIndex];
        
        if (selectedOption.value) {
            // Update Guest Options
            const maxOccupancy = parseInt(selectedOption.getAttribute('data-max-occupancy'));
            const currentGuests = guestSelect.value;
            
            let guestHtml = '<option value="">Select guests</option>';
            for (let i = 1; i <= maxOccupancy; i++) {
                guestHtml += `<option value="${i}" ${currentGuests == i ? 'selected' : ''}>${i}</option>`;
            }
            
            guestSelect.innerHTML = guestHtml;

            if (checkIn && checkOut) {
                const price = parseInt(selectedOption.getAttribute('data-price'));
                
                // Calculate nights
                const start = new Date(checkIn);
                const end = new Date(checkOut);
                const diffTime = end - start;
                const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
                
                let html = '';
                
                if (diffDays > 0) {
                    const total = price * diffDays;
                    totalAmountInput.value = total;
                    html = `
                        <div class="summary-item">
                            <span>${selectedOption.text}</span>
                            <span>₱${price.toLocaleString()}</span>
                        </div>
                        <div class="summary-item">
                            <span>Duration</span>
                            <span>${diffDays} ${diffDays === 1 ? 'night' : 'nights'}</span>
                        </div>
                        <div class="summary-total">
                            <span>Total</span>
                            <span>₱${total.toLocaleString()}</span>
                        </div>
                        <p style="font-size:0.8rem; color:#64748b; margin-top:15px; line-height: 1.5;">
                            <i class="fa-solid fa-circle-info" style="color: #086584; margin-right: 4px;"></i> 
                            Includes all service charges and government taxes. No hidden fees.
                        </p>
                    `;
                }
 else if (diffDays === 0) {
                    html = '<p style="color:#dc2626; font-size: 14px;"><i class="fa-solid fa-triangle-exclamation"></i> Check-out date must be after check-in date.</p>';
                    totalAmountInput.value = 0;
                } else {
                    html = '<p style="color:#dc2626; font-size: 14px;"><i class="fa-solid fa-triangle-exclamation"></i> Invalid date range selected.</p>';
                    totalAmountInput.value = 0;
                }
                
                summaryText.innerHTML = html;
            } else {
                summaryText.innerHTML = '<p style="font-style: italic; color: #94a3b8;">Select check-in and check-out dates to see total price.</p>';
                totalAmountInput.value = 0;
            }
        } else {
            summaryText.innerHTML = '<p style="font-style: italic; color: #94a3b8;">Select a room type to see pricing.</p>';
            guestSelect.innerHTML = '<option value="">Select guests</option>';
            totalAmountInput.value = 0;
        }
    }

    function fetchAvailableRooms() {
        const checkIn = document.getElementById('checkInDate').value;
        const checkOut = document.getElementById('checkOutDate').value;
        const roomSelect = document.getElementById('roomType');
        const summaryText = document.querySelector('.booknow-summary-text');
        const preferredRoomTypeId = roomSelect.value || initialSelectedRoomTypeId || '';
        
        if (!checkIn || !checkOut) {
            renderRoomTypeOptions(roomTypeCatalog, preferredRoomTypeId);
            updateSummary();
            return;
        }

        roomSelect.innerHTML = '<option value="">Loading available rooms...</option>';
        roomSelect.disabled = true;

        fetch(`/api/get_available_rooms.php?check_in=${checkIn}&check_out=${checkOut}`)
            .then(response => response.json())
            .then(data => {
                roomSelect.disabled = false;
                if (data.success && data.types.length > 0) {
                    const hasSelectedRoom = renderRoomTypeOptions(data.types, preferredRoomTypeId, 'Select a room');
                    updateSummary();

                    if (preferredRoomTypeId && !hasSelectedRoom) {
                        summaryText.innerHTML = '<p style="color:#b45309; font-size: 14px;"><i class="fa-solid fa-circle-info"></i> Selected room type is unavailable for the chosen dates. Please choose another room.</p>';
                    }
                } else {
                    roomSelect.innerHTML = '<option value="">No rooms available for these dates</option>';
                    updateSummary();
                }
            })
            .catch(error => {
                console.error('Error fetching rooms:', error);
                roomSelect.disabled = false;
                renderRoomTypeOptions(roomTypeCatalog, preferredRoomTypeId);
                updateSummary();
            });
    }

    // Handle form loading state
    document.getElementById('reservationForm').addEventListener('submit', function(e) {
        const btn = document.getElementById('submitBtn');
        const btnText = btn.querySelector('.btn-text');

        btn.disabled = true;
        btnText.innerHTML = '<span class="spinner"></span> Creating reservation...';

        // Re-enable button and restore text after 5 seconds
        // in case of validation errors or slow response
        setTimeout(() => {
            btn.disabled = false;
            btnText.innerHTML = 'Complete Reservation';
        }, 5000);
    });

    // Update check-out min date when check-in changes
    document.getElementById('checkInDate').addEventListener('change', function() {
        const checkIn = this.value;
        const checkOutInput = document.getElementById('checkOutDate');
        if (checkIn) {
            const nextDay = new Date(checkIn);
            nextDay.setDate(nextDay.getDate() + 1);
            checkOutInput.min = nextDay.toISOString().split('T')[0];
            
            if (checkOutInput.value && checkOutInput.value <= checkIn) {
                checkOutInput.value = checkOutInput.min;
            }
        }
        fetchAvailableRooms();
    });

    document.getElementById('checkOutDate').addEventListener('change', fetchAvailableRooms);

    // Initialize room selection and summary on page load.
    document.addEventListener('DOMContentLoaded', function() {
        renderRoomTypeOptions(roomTypeCatalog, initialSelectedRoomTypeId);
        updateSummary();
    });
    </script>
</body>
</html>