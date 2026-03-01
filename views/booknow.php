<?php
require_once 'helpers/RoomModel.php';
require_once 'helpers/GuestModel.php';
require_once 'helpers/ReservationModel.php';

$roomModel = new RoomModel();
$guestModel = new GuestModel();
$reservationModel = new ReservationModel();

$selectedRoomId = $_GET['room_id'] ?? null;
$allRooms = $roomModel->getAll(['status' => 'available']);

$firstName = '';
$lastName = '';
$email = '';
$phone = '';
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
    $phoneNumber = $_POST['phone_number'] ?? '';
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
                $existingGuest = $guestModel->getByUserId($userId);
                if ($existingGuest) {
                    $guestId = $existingGuest['guest_id'];
                }
            }

            if (!$guestId) {
                $guestId = $guestModel->create([
                    'user_id' => $userId,
                    'first_name' => $fName,
                    'last_name' => $lName,
                    'email' => $contactEmail,
                    'phone' => $phoneNumber
                ]);
            }

            $reservationId = $reservationModel->create([
                'guest_id' => $guestId,
                'room_id' => $roomId,
                'check_in_date' => $checkIn,
                'check_out_date' => $checkOut,
                'total_amount' => $totalAmount,
                'status' => 'Pending'
            ]);

            if ($reservationId) {
                $msg = "Reservation successful! Your booking ID is <strong>#{$reservationId}</strong>.";
                $msgType = "success";
                $selectedRoomId = null;
            } else {
                $msg = "Failed to create reservation. Please try again.";
                $msgType = "error";
            }
        } catch (Exception $e) {
            $msg = "An error occurred: " . $e->getMessage();
            $msgType = "error";
        }
    }
}

if (isLoggedIn()) {
    $currentUser = getCurrentUser();
    $guest = $guestModel->getByUserId($currentUser['user_id']);
    if ($guest) {
        $firstName = $guest['first_name'] ?? '';
        $lastName = $guest['last_name'] ?? '';
        $email = $guest['contact_email'] ?? '';
        $phone = $guest['phone_number'] ?? '';
    } else {
        $email = $currentUser['email'] ?? '';
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
    <style>
        .msg-container { 
            padding: 16px 20px; 
            border-radius: 12px; 
            margin-bottom: 24px; 
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 15px;
            line-height: 1.4;
        }
        .success { 
            background-color: #ecfdf5; 
            color: #065f46; 
            border: 1px solid #a7f3d0; 
        }
        .error { 
            background-color: #fef2f2; 
            color: #991b1b; 
            border: 1px solid #fecaca; 
        }
        
        .booknow-submit:disabled {
            background-color: #94a3b8;
            cursor: not-allowed;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        
        .spinner {
            width: 18px;
            height: 18px;
            border: 2px solid rgba(255,255,255,0.3);
            border-radius: 50%;
            border-top-color: #fff;
            animation: spin 0.8s linear infinite;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        .booknow-input:focus, .booknow-select:focus, .booknow-textarea:focus {
            outline: none;
            border-color: #007ea7;
            box-shadow: 0 0 0 3px rgba(0, 126, 167, 0.1);
            background-color: #fff;
        }

        .summary-header {
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #64748b;
            margin-bottom: 12px;
            font-weight: 600;
        }

        .secure-badge {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
            color: #059669;
            background: #ecfdf5;
            padding: 8px 12px;
            border-radius: 8px;
            margin-top: 15px;
        }
    </style>
</head>
<body>

    <div class="booknow-container">
        
        <!-- LEFT: BOOKING FORM -->
        <div class="booknow-card">
            
            <?php if ($msg): ?>
                <div class="msg-container <?php echo $msgType; ?>">
                    <i class="fa-solid <?php echo $msgType === 'success' ? 'fa-circle-check' : 'fa-circle-exclamation'; ?>"></i>
                    <span><?php echo $msg; ?></span>
                </div>
            <?php endif; ?>

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
                        <input type="date" name="check_in" id="checkInDate" class="booknow-input" onchange="updateSummary()" min="<?php echo $today; ?>" required>
                    </div>
                    <div>
                        <label class="booknow-label">Check-out Date</label>
                        <input type="date" name="check_out" id="checkOutDate" class="booknow-input" onchange="updateSummary()" min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>" required>
                    </div>
                    <div>
                        <label class="booknow-label">Number of Guests</label>
                        <select name="guests" id="guestSelect" class="booknow-select" required>
                            <option value="">Select guests</option>
                        </select>
                    </div>
                </div>

                <div style="margin-top:16px; max-width:260px;">
                    <label class="booknow-label">Room Type</label>
                    <select name="room_id" class="booknow-select" id="roomType" onchange="updateSummary()" required>
                        <option value="">Select a room</option>
                        <?php foreach ($allRooms as $room): ?>
                            <option value="<?php echo $room['cottage_id']; ?>" 
                                    data-price="<?php echo (int)$room['base_price']; ?>"
                                    data-max-occupancy="<?php echo (int)$room['max_occupancy']; ?>"
                                    <?php echo ($selectedRoomId == $room['cottage_id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($room['name']); ?>
                            </option>
                        <?php endforeach; ?>
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
                        <input type="tel" name="phone_number" class="booknow-input" value="<?php echo htmlspecialchars($phone); ?>" placeholder="Enter phone number">
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
                <p><i class="fa-solid fa-circle-check" style="color: #22c55e; margin-right: 6px;"></i> No booking fees</p>
            </div>
        </div>

    </div>

    <script>
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
                        <div style="display:flex; justify-content:space-between; margin-bottom:12px; font-size: 14px;">
                            <span style="color: #475569;">${selectedOption.text} × ${diffDays} ${diffDays === 1 ? 'night' : 'nights'}</span>
                            <span style="font-weight: 500;">₱${price.toLocaleString()}</span>
                        </div>
                        <div style="height: 1px; background: #e2e8f0; margin: 16px 0;"></div>
                        <div style="display:flex; justify-content:space-between; font-weight:700; font-size:1.4rem; color:#0f172a; margin-top:10px;">
                            <span>Total</span>
                            <span>₱${total.toLocaleString()}</span>
                        </div>
                        <p style="font-size:0.8rem; color:#64748b; margin-top:10px;">
                            <i class="fa-solid fa-info-circle"></i> Rates include service charges & government taxes.
                        </p>
                    `;
                } else if (diffDays === 0) {
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

    // Handle form loading state
    document.getElementById('reservationForm').addEventListener('submit', function(e) {
        const btn = document.getElementById('submitBtn');
        const btnText = btn.querySelector('.btn-text');
        
        btn.disabled = true;
        btnText.innerHTML = '<div class="spinner"></div> Creating reservation...';
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
        updateSummary();
    });

    // Initialize summary if a room is pre-selected
    document.addEventListener('DOMContentLoaded', updateSummary);
    </script>
</body>
</html>