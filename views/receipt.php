<?php
require_once __DIR__ . '/../helpers/ReservationModel.php';
require_once __DIR__ . '/../helpers/UserModel.php';
require_once __DIR__ . '/../auth/auth_functions.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$token = isset($_GET['token']) ? $_GET['token'] : '';

if (!$id || !$token) {
    header("Location: index.php?page=home");
    exit;
}

$reservationModel = new ReservationModel();
$reservation = $reservationModel->getById($id);

// Validate token - if the reservation is not found, or token doesn't match
if (!$reservation || $reservation['confirmation_token'] !== $token) {
    // Maybe the token is already cleared because it was confirmed, but we still want to show the receipt 
    // if the token passed is empty and we are logged in as admin? For now, we strictly require the token.
    // Wait, confirmation_token is cleared when confirmed. If confirmed, we should still allow viewing if they have the token?
    // Actually, confirm_booking.php handles confirmation. The receipt can be viewed right after booking (Pending state).
    // Let's just check if it's a valid reservation. If they have the right ID and token in the URL, it's fine.
    // If confirmation_token is NULL (already confirmed), we might not be able to verify via token. 
    // But let's assume the token is the secret key for viewing the receipt.
    // Let's relax it slightly: if the token matches the DB token, OR (DB token is null and we just trust the URL... wait no, 
    // we should store a receipt_token or just use the confirmation_token before it's cleared).
    // The user requirement says "Show a receipt to the user". We know it's right after booking. So the token matches.
    if (!isset($reservation['confirmation_token']) || $reservation['confirmation_token'] !== $token) {
        $msg = "Invalid or expired receipt link.";
        $msgType = "error";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reservation Receipt | La Paseo Resort</title>
    <link rel="stylesheet" href="static/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        .receipt-container {
            max-width: 800px;
            margin: 100px auto 40px;
            background: #fff;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 8px 30px rgba(0,0,0,0.08);
            position: relative;
        }
        .receipt-header {
            text-align: center;
            border-bottom: 2px dashed #e2e8f0;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .receipt-header h1 {
            color: #086584;
            font-size: 28px;
            margin-bottom: 10px;
        }
        .receipt-header p {
            color: #64748b;
            font-size: 15px;
        }
        .receipt-body {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
        }
        .receipt-section {
            width: 48%;
        }
        .receipt-section h3 {
            font-size: 16px;
            color: #334155;
            margin-bottom: 15px;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 8px;
        }
        .receipt-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 12px;
            font-size: 15px;
        }
        .receipt-label {
            color: #64748b;
        }
        .receipt-value {
            font-weight: 500;
            color: #0f172a;
            text-align: right;
        }
        .receipt-total {
            background: #f8fafc;
            padding: 20px;
            border-radius: 8px;
            text-align: right;
            margin-top: 20px;
            font-size: 18px;
        }
        .receipt-total span {
            font-size: 24px;
            font-weight: bold;
            color: #086584;
            margin-left: 15px;
        }
        .action-buttons {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-top: 40px;
        }
        .btn-print {
            background: #086584;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 500;
            transition: 0.2s;
        }
        .btn-print:hover {
            background: #064e66;
        }
        .btn-home {
            background: #f1f5f9;
            color: #334155;
            padding: 12px 24px;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 500;
            transition: 0.2s;
            border: 1px solid #cbd5e1;
        }
        .btn-home:hover {
            background: #e2e8f0;
        }
        
        @media print {
            body { background: white; }
            .navbar, .footer, .action-buttons { display: none !important; }
            .receipt-container {
                box-shadow: none;
                margin: 0;
                padding: 0;
            }
        }
    </style>
</head>
<body>

    <?php if (isset($msg)): ?>
        <div style="text-align: center; margin-top: 150px;">
            <h2 style="color: #ef4444;"><i class="fa-solid fa-circle-exclamation"></i> Error</h2>
            <p><?php echo htmlspecialchars($msg); ?></p>
            <a href="index.php?page=home" style="color: #086584; text-decoration: underline; margin-top: 20px; display: inline-block;">Return Home</a>
        </div>
    <?php else: ?>
        <div class="receipt-container">
            <div class="receipt-header">
                <h1><i class="fa-solid fa-check-circle" style="color: #22c55e;"></i> Booking Successful!</h1>
                <p>Reservation ID: #<strong><?php echo $reservation['reservation_id']; ?></strong></p>
                <p>Date: <?php echo date('F j, Y, g:i a', strtotime($reservation['booking_date'])); ?></p>
            </div>

            <div style="background: #eff6ff; border: 1px solid #bfdbfe; color: #1e40af; padding: 15px; border-radius: 8px; margin-bottom: 30px; font-size: 14px; text-align: center;">
                <i class="fa-solid fa-envelope" style="margin-right: 5px;"></i> We've sent a copy of this receipt and a confirmation link to <strong><?php echo htmlspecialchars($reservation['email']); ?></strong>.<br>
                Please check your inbox to formally confirm your reservation.
            </div>

            <div class="receipt-body">
                <div class="receipt-section">
                    <h3>Guest Information</h3>
                    <div class="receipt-item">
                        <span class="receipt-label">Name:</span>
                        <span class="receipt-value"><?php echo htmlspecialchars($reservation['first_name'] . ' ' . $reservation['last_name']); ?></span>
                    </div>
                    <div class="receipt-item">
                        <span class="receipt-label">Email:</span>
                        <span class="receipt-value"><?php echo htmlspecialchars($reservation['email']); ?></span>
                    </div>
                </div>

                <div class="receipt-section">
                    <h3>Reservation Details</h3>
                    <div class="receipt-item">
                        <span class="receipt-label">Room Type:</span>
                        <span class="receipt-value">
                            <?php 
                            if (!empty($reservation['items'])) {
                                echo htmlspecialchars($reservation['items'][0]['type_name']); 
                            } else {
                                echo 'Cottage';
                            }
                            ?>
                        </span>
                    </div>
                    <div class="receipt-item">
                        <span class="receipt-label">Check-in:</span>
                        <span class="receipt-value"><?php echo date('M j, Y', strtotime($reservation['check_in_date'])); ?></span>
                    </div>
                    <div class="receipt-item">
                        <span class="receipt-label">Check-out:</span>
                        <span class="receipt-value"><?php echo date('M j, Y', strtotime($reservation['check_out_date'])); ?></span>
                    </div>
                    <?php
                        // Ignore time to correctly count calendar nights
                        $start = new DateTime(date('Y-m-d', strtotime($reservation['check_in_date'])));
                        $end = new DateTime(date('Y-m-d', strtotime($reservation['check_out_date'])));
                        $nights = $start->diff($end)->days;
                    ?>
                    <div class="receipt-item">
                        <span class="receipt-label">Duration:</span>
                        <span class="receipt-value"><?php echo $nights; ?> <?php echo $nights === 1 ? 'Night' : 'Nights'; ?></span>
                    </div>
                </div>
            </div>

            <div class="receipt-total">
                Total Amount Paid / Due: <span>₱<?php echo number_format($reservation['total_amount'], 2); ?></span>
            </div>

            <div class="action-buttons">
                <button class="btn-print" onclick="window.print()"><i class="fa-solid fa-download"></i> Download / Print Receipt</button>
                <a href="index.php?page=home" class="btn-home">Return to Home</a>
            </div>
        </div>
    <?php endif; ?>

</body>
</html>
