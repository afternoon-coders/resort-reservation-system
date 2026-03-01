<?php
session_start();
require_once __DIR__ . '/../helpers/UserModel.php';
require_once __DIR__ . '/../helpers/GuestModel.php';

$error = '';
$success = '';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}

// Handle POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = $_POST['fullName'] ?? '';
    $email = $_POST['email'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirmPassword'] ?? '';

    // Validation
    if (empty($fullName) || empty($email) || empty($password) || empty($confirmPassword)) {
        $error = 'All fields are required.';
    } elseif ($password !== $confirmPassword) {
        $error = 'Passwords do not match.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters long.';
    } else {
        $userModel = new UserModel();
        $guestModel = new GuestModel();

        // Check if email already exists
        $existingUser = $userModel->getByEmail($email);
        if ($existingUser) {
            $error = 'Email already registered.';
        } else {
            try {
                // Create user account
                $username = explode('@', $email)[0] . '_' . substr(md5($email), 0, 6);
                $userId = $userModel->create([
                    'username' => $username,
                    'email' => $email,
                    'password' => $password,
                    'role' => 'guest'
                ]);

                // Create guest profile linked to user
                $guestId = $guestModel->create([
                    'user_id' => $userId,
                    'name' => $fullName,
                    'email' => $email,
                    'phone' => $phone
                ]);

                // Log in the new user
                $_SESSION['user_id'] = $userId;
                $_SESSION['username'] = $username;
                $_SESSION['email'] = $email;
                $_SESSION['role'] = 'guest';

                header('Location: ../index.php');
                exit;
            } catch (Exception $e) {
                $error = 'An error occurred during registration. Please try again.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Luxury Resort</title>

    <link rel="stylesheet" href="\admin\static\css\signup.css">
    
    <style>
        .error-message {
            color: #d32f2f;
            background-color: #ffebee;
            padding: 12px;
            border-radius: 4px;
            margin-bottom: 16px;
            border-left: 4px solid #d32f2f;
        }
        .success-message {
            color: #388e3c;
            background-color: #e8f5e9;
            padding: 12px;
            border-radius: 4px;
            margin-bottom: 16px;
            border-left: 4px solid #388e3c;
        }
    </style>
</head>
<body>

    <div class="container">
        <div class="signup-card">
            
            <img src="\static\img\lepaseo_logo.jpg" alt="Logo" class="logo-img">

            <h1>Create Account</h1>
            <p>Join us for the best hotel experience</p>

            <?php if ($error): ?>
                <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="success-message"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label for="fullName">Full Name</label>
                    <input type="text" id="fullName" name="fullName" required class="form-input">
                </div>

                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" required class="form-input">
                </div>

                <div class="form-group">
                    <label for="phone">Phone Number</label>
                    <input type="tel" id="phone" name="phone" required class="form-input">
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required class="form-input">
                </div>

                <div class="form-group">
                    <label for="confirmPassword">Confirm Password</label>
                    <input type="password" id="confirmPassword" name="confirmPassword" required class="form-input">
                </div>

                <div class="form-check">
                    <input type="checkbox" id="terms" name="terms" required class="checkbox-input">
                    <label for="terms">I agree to the <a href="#">Terms and Conditions</a></label>
                </div>

                <button type="submit" class="btn-signup">Create Account</button>
            </form>

            <div class="footer-text">
                <p>Already have an account? <a href="login.php">Sign in here</a></p>
            </div>

            <div id="errorMessage" class="error-message" style="display:none;"></div>
            <div id="successMessage" class="success-message" style="display:none;"></div>
    
        </div>
    </div>


    <script src="app.js"></script>
    <script src="auth.js"></script>
</body>
</html>
