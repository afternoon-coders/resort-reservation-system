<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/phpmailer/phpmailer/src/Exception.php';
require_once __DIR__ . '/../vendor/phpmailer/phpmailer/src/PHPMailer.php';
require_once __DIR__ . '/../vendor/phpmailer/phpmailer/src/SMTP.php';

function loadEnv($path) {
    if (!file_exists($path)) {
        return false;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue; // Skip comments

        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);

        if (!array_key_exists($name, $_SERVER) && !array_key_exists($name, $_ENV)) {
            putenv(sprintf('%s=%s', $name, $value));
            $_ENV[$name] = $value;
            $_SERVER[$name] = $value;
        }
    }
}

// Usage:
loadEnv(__DIR__ . '/../.env');

class Mailer {
    public static function sendConfirmationEmail($toEmail, $recipientName, $reservationId, $token) {
        $mail = new PHPMailer(true);

        try {
            // Server settings - These should be moved to a config file eventually
            $mail->isSMTP();
            $mail->Host       = $_ENV['SMTP_HOST']; // Replace with real SMTP host
            $mail->SMTPAuth   = true;
            $mail->Username   = $_ENV['SMTP_USER']; // Replace with real email
            $mail->Password   = $_ENV['SMTP_PASS']; // Replace with real password
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = $_ENV['SMTP_PORT'];

            // Recipients
            $mail->setFrom('barrmontlapaseo@gmail.com', 'Le Paseo Isla Andis Resort');
            $mail->addAddress($toEmail, $recipientName);

            // Content
            $confirmationLink = "http://" . $_SERVER['HTTP_HOST'] . "/auth/confirm_booking.php?token=" . $token;
            
            $mail->isHTML(true);
            $mail->Subject = 'Confirm Your Reservation #' . $reservationId;
            $mail->Body    = "
                <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #e2e8f0; border-radius: 12px;'>
                    <h2 style='color: #086584;'>Reservation Confirmation</h2>
                    <p>Dear {$recipientName},</p>
                    <p>Thank you for choosing <strong>Le Paseo Isla Andis Resort</strong>. We have received your booking request (ID: #{$reservationId}).</p>
                    <p>To finalize and confirm your reservation, please click the button below:</p>
                    <div style='text-align: center; margin: 30px 0;'>
                        <a href='{$confirmationLink}' style='background-color: #086584; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; font-weight: bold;'>Confirm My Booking</a>
                    </div>
                    <p style='color: #64748b; font-size: 14px;'>If you did not make this reservation, please ignore this email.</p>
                    <hr style='border: 0; border-top: 1px solid #e2e8f0; margin: 20px 0;'>
                    <p style='font-size: 12px; color: #94a3b8;'>© " . date('Y') . " Le Paseo Isla Andis Resort. All rights reserved.</p>
                </div>
            ";

            $mail->send();
            return true;
        } catch (\Exception $e) {
            // Log the actual error message
            error_log("Mailer Fatal Error: " . $e->getMessage());
            
            // FALLBACK: Save the "email" content to a file for testing even on fatal error
            if (isset($mail) && !empty($mail->Body)) {
                file_put_contents(__DIR__ . "/../logs/last_email_{$reservationId}.html", $mail->Body);
            }
            return false;
        }
    }
}
