<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/phpmailer/phpmailer/src/Exception.php';
require_once __DIR__ . '/../vendor/phpmailer/phpmailer/src/PHPMailer.php';
require_once __DIR__ . '/../vendor/phpmailer/phpmailer/src/SMTP.php';
require_once __DIR__ . '/env.php';

loadEnv(__DIR__ . '/../.env');

class Mailer {
    public static function sendConfirmationEmail($toEmail, $recipientName, $reservationId, $token, $reservationDetails = null) {
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
            
            $receiptHtml = '';
            if ($reservationDetails) {
                $checkIn = date('M j, Y', strtotime($reservationDetails['check_in_date']));
                $checkOut = date('M j, Y', strtotime($reservationDetails['check_out_date']));
                $total = number_format($reservationDetails['total_amount'], 2);
                $roomName = !empty($reservationDetails['items']) ? $reservationDetails['items'][0]['type_name'] : 'Cottage';

                $receiptHtml = "
                    <div style='background: #f8fafc; padding: 15px; border-radius: 8px; margin: 20px 0;'>
                        <h3 style='margin-top: 0; color: #334155; border-bottom: 1px solid #e2e8f0; padding-bottom: 10px;'>Reservation Summary</h3>
                        <p style='margin: 5px 0;'><strong>Room Type:</strong> {$roomName}</p>
                        <p style='margin: 5px 0;'><strong>Check-in:</strong> {$checkIn}</p>
                        <p style='margin: 5px 0;'><strong>Check-out:</strong> {$checkOut}</p>
                        <p style='margin: 15px 0 5px; font-size: 18px; color: #086584;'><strong>Total Amount:</strong> ₱{$total}</p>
                    </div>
                ";
            }

            $mail->isHTML(true);
            $mail->Subject = 'Confirm Your Reservation #' . $reservationId;
            $mail->Body    = "
                <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #e2e8f0; border-radius: 12px;'>
                    <h2 style='color: #086584;'>Reservation Confirmation</h2>
                    <p>Dear {$recipientName},</p>
                    <p>Thank you for choosing <strong>Le Paseo Isla Andis Resort</strong>. We have received your booking request (ID: #{$reservationId}).</p>
                    {$receiptHtml}
                    <p>To finalize and confirm your booking, please click the button below:</p>
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
