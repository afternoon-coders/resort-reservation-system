<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/phpmailer/phpmailer/src/Exception.php';
require_once __DIR__ . '/../vendor/phpmailer/phpmailer/src/PHPMailer.php';
require_once __DIR__ . '/../vendor/phpmailer/phpmailer/src/SMTP.php';
require_once __DIR__ . '/env.php';

loadEnv(__DIR__ . '/../.env');

class Mailer {
    public static function sendConfirmationEmail($toEmail, $recipientName, $reservationId, $token) {
        $mail = new PHPMailer(true);

        try {
            // Server settings
            $mail->isSMTP();
            $mail->Host       = $_ENV['SMTP_HOST'];
            $mail->SMTPAuth   = true;
            $mail->Username   = $_ENV['SMTP_USER'];
            $mail->Password   = $_ENV['SMTP_PASS'];
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = $_ENV['SMTP_PORT'];

            // Recipients — sender from .env
            $senderEmail = $_ENV['MAIL_FROM_ADDRESS'] ?? $_ENV['SMTP_USER'] ?? 'noreply@example.com';
            $senderName  = $_ENV['MAIL_FROM_NAME'] ?? 'Le Paseo Isla Andis Resort';
            $mail->setFrom($senderEmail, $senderName);
            $mail->addAddress($toEmail, $recipientName);

            // Build confirmation link using APP_URL from .env (defaults to HTTPS)
            $appUrl = rtrim($_ENV['APP_URL'] ?? ('https://' . ($_SERVER['HTTP_HOST'] ?? 'localhost')), '/');
            $confirmationLink = $appUrl . "/auth/confirm_booking.php?token=" . urlencode($token);
            
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
