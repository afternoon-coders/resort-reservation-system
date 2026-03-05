<?php
require_once __DIR__ . '/../helpers/ReservationModel.php';

$token = $_GET['token'] ?? '';
$success = false;
$error = '';

if ($token) {
    $reservationModel = new ReservationModel();
    if ($reservationModel->confirmByToken($token)) {
        $success = true;
    } else {
        $error = "Invalid or expired confirmation link. Please contact support if you believe this is an error.";
    }
} else {
    $error = "No confirmation token provided.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Confirmation | Le Paseo Isla Andis Resort</title>
    <link rel="stylesheet" href="/static/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        .confirm-container {
            max-width: 600px;
            margin: 100px auto;
            padding: 40px;
            text-align: center;
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.05);
        }
        .status-icon {
            font-size: 64px;
            margin-bottom: 24px;
        }
        .success-icon { color: #22c55e; }
        .error-icon { color: #ef4444; }
        h1 { color: #0f172a; margin-bottom: 16px; }
        p { color: #64748b; line-height: 1.6; margin-bottom: 30px; }
        .btn-home {
            display: inline-block;
            background: #086584;
            color: white;
            padding: 12px 30px;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            transition: background 0.2s;
        }
        .btn-home:hover { background: #064d64; }
    </style>
</head>
<body>
    <div class="confirm-container">
        <?php if ($success): ?>
            <div class="status-icon success-icon">
                <i class="fa-solid fa-circle-check"></i>
            </div>
            <h1>Booking Confirmed!</h1>
            <p>Your reservation has been successfully verified. We look forward to seeing you at Le Paseo Isla Andis Resort. You will receive a summary of your booking shortly.</p>
            <a href="/index.php" class="btn-home">Return Home</a>
        <?php else: ?>
            <div class="status-icon error-icon">
                <i class="fa-solid fa-circle-xmark"></i>
            </div>
            <h1>Confirmation Failed</h1>
            <p><?php echo htmlspecialchars($error); ?></p>
            <a href="/index.php" class="btn-home">Back to Home</a>
        <?php endif; ?>
    </div>
</body>
</html>
