<?php
session_start();

// Include PHPMailer's classes
require 'director/mailer/src/Exception.php';
require 'director/mailer/src/PHPMailer.php';
require 'director/mailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Function to send OTP via email
function sendOtpEmail($email, $otp) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'nguyenlonggm2021@gmail.com';
        $mail->Password = 'hnuozppidlbfkmlm';
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;

        $mail->setFrom('your_email@gmail.com', 'Smart Office');
        $mail->addAddress($email);

        $mail->isHTML(true);
        $mail->Subject = 'Your Password Reset Code';
        $mail->Body = "Your OTP is: <b>$otp</b>";

        $mail->send();
        $_SESSION['success'] = "OTP has been sent to your email.";
    } catch (Exception $e) {
        $_SESSION['error'] = "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
    }
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $users = json_decode(file_get_contents('database/users.json'), true);
    $userFound = false;

    // Check if email exists
    foreach ($users as $user) {
        if ($user['email'] === $email) {
            $userFound = true;
            break;
        }
    }

    if (!$userFound) {
        $_SESSION['error'] = "Email not found.";
        header("Location: reset_password.php");
        exit;
    } else {
        $otp = rand(100000, 999999);
        $_SESSION['otp'] = $otp;
        $_SESSION['email'] = $email;
        sendOtpEmail($email, $otp);

        header("Location: verify_otp.php");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Smart Office Reset Password</title>
    <link rel="stylesheet" href="fonts/material-icon/css/material-design-iconic-font.min.css">
    <link rel="stylesheet" href="css/style.css">
    <style>
        .sign-up-section { display: none; }
        .footer { text-align: center; margin-top: 40px; font-size: 14px; color: #888; }
    </style>
</head>
<body>
<section class="reset-password-section">
    <div class="container">
        <div class="reset-password-content">
            <h2 class="form-title">Reset Password</h2>
            <div id="message" style="color: red;">
                <?php
                if (isset($_SESSION['error'])) {
                    echo "<script>alert('" . addslashes($_SESSION['error']) . "');</script>";
                    unset($_SESSION['error']);
                } elseif (isset($_SESSION['success'])) {
                    echo "<script>alert('" . addslashes($_SESSION['success']) . "');</script>";
                    unset($_SESSION['success']);
                }
                ?>
            </div>
            <form method="POST" action="reset_password.php" id="reset-password-form">
                <div class="form-group">
                    <label for="reset-email"><i class="zmdi zmdi-email"></i></label>
                    <input type="email" name="email" id="reset-email" placeholder="Your Email" required/>
                </div>
                <div class="form-group form-button">
                    <input type="submit" name="reset" id="reset" class="form-submit" value="Send OTP"/>
                </div>
            </form>
        </div>
    </div>
</section>
</body>
</html>
