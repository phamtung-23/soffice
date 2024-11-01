<?php
session_start();

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $otpInput = $_POST['otp'];
    if (isset($_SESSION['otp']) && $otpInput == $_SESSION['otp']) {
        // OTP is correct
        header("Location: set_new_password.php");
        exit;
    } else {
        $_SESSION['error'] = "Invalid OTP.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Smart Office Verify OTP</title>

    <!-- Font Icon -->
    <link rel="stylesheet" href="fonts/material-icon/css/material-design-iconic-font.min.css">

    <!-- Main css -->
    <link rel="stylesheet" href="css/style.css">

    <style>
        .sign-up-section {
            display: none;
        }
        .footer {
            text-align: center;
            margin-top: 40px;
            font-size: 14px;
            color: #888;
        }
    .menu {
            margin-bottom: 20px;
        }
        .menu a {
            text-decoration: none;
            color: #007bff;
            font-size: 16px;
        }
    </style>
</head>
<body>
<!-- Menu to return to Home -->
<div class="menu">
    <a href="index.php">Home</a>
</div>

<section class="verify-otp-section">
    <div class="container">
        <div class="verify-otp-content">
            <h2 class="form-title">Verify OTP</h2>
            <div id="message" style="color: red;">
                <?php
                session_start();
                if (isset($_SESSION['error'])) {
                    echo "<script>alert('" . addslashes($_SESSION['error']) . "');</script>";
                    unset($_SESSION['error']);
                }
                ?>
            </div>
            <div id="verified-email" style="margin-bottom: 20px;">
                <?php
                // Display the email being verified
                if (isset($_SESSION['email'])) {
                    echo "Verifying for email: <strong>" . htmlspecialchars($_SESSION['email']) . "</strong>";
                    echo "<br>";
                    echo "Check your email from Soffice for get OTP.";
                }
             else
             {
                unset($_SESSION['email']);
                unset($_SESSION['success']);
                header("Location: set_new_password.php");  
             }
                ?>
            </div>
            <form method="POST" action="verify_otp.php" id="verify-otp-form">
                <div class="form-group">
                    <label for="otp"><i class="zmdi zmdi-check"></i></label>
                    <input type="text" name="otp" id="otp" placeholder="Enter OTP" required/>
                </div>
                <div class="form-group form-button">
                    <input type="submit" name="verify" id="verify" class="form-submit" value="Verify OTP"/>
                </div>
            </form>
        </div>
    </div>
</section>

</body>
</html>
