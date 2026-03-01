<?php
session_start();
require_once __DIR__ . '/../helpers/UserModel.php';

$error = '';
$success = '';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}

// Handle POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Email and password are required.';
    } else {
        $userModel = new UserModel();
        
        // Find user by account email
        $user = $userModel->getByEmail($email);

        if ($user && $userModel->verifyPassword($password, $user['password_hash'])) {
            // Login successful
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['email'] = $user['account_email'] ?? $user['email'] ?? null;
            $_SESSION['role'] = $user['role'] ?? 'guest';

            if ($_SESSION['role'] === 'admin') {
                header('Location: ../admin/index.php');
            } else {
                header('Location: ../index.php');
            }

            exit;
        } else {
            $error = 'Invalid email or password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login</title>

<!-- Link External CSS -->
<link rel="stylesheet" href="\admin\static\css\login.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
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

        <div class="login-card">
            <img src="\static\img\lepaseo_logo.jpg" alt="Logo" class="logo-img">

            <h1>Welcome</h1>
            <p class="subtitle">Sign in to manage your reservations</p>

            <?php if ($error): ?>
                <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="success-message"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" placeholder="you@example.com" class="form-input" required>
                
                    <label>
                        Password 
                        <a href="#" class="forgot">Forgot password?</a>
                    </label>

                    <div class="password-wrapper">
                        <input type="password" name="password" placeholder="Enter your password" class="form-input" required>
                        <span class="eye-icon" onclick="togglePassword()"><i class="fa fa-eye"></i></span>
                    </div>
                    
                </div>

                <button type="submit" class="btn-login">Sign In</button>
            </form>

            <div class="footer-text">
                Don't have an account? <a href="register.php">Create account</a>
            </div>

        </div>
    </div>

    <script>
    function togglePassword() {
        const input = document.querySelector('.password-wrapper input');
        input.type = input.type === "password" ? "text" : "password";
    }
    </script>

</body>
</html>
