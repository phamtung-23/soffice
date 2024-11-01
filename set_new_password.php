<?php
session_start();
$successMessage = "";

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $newPassword = $_POST['new_password'];
    $confirmPassword = $_POST['confirm_password'];
    if ($newPassword !== $confirmPassword) {
        $_SESSION['error'] = "Passwords do not match.";
    } else {
        // Hash the new password and save it (you'll need to implement saving logic)
        $email = $_SESSION['email'];
        $hashed_password = password_hash($newPassword, PASSWORD_DEFAULT);
        // Load users, update the password, and save back to the JSON file
        $users = json_decode(file_get_contents('database/users.json'), true);
        foreach ($users as &$user) {
            if ($user['email'] === $email) {
                $user['password'] = $hashed_password; // Update password
                break;
            }
        }
        file_put_contents('database/users.json', json_encode($users, JSON_PRETTY_PRINT));
        $successMessage = "Password has been successfully updated.";
        unset($_SESSION['otp']); // Clear OTP session
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Smart Office Set New Password</title>

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
<div class="menu">
    <a href="index.php">Home</a>
</div>
<section class="set-new-password-section">

    <div class="container">
        <div class="set-new-password-content">
            <h2 class="form-title">Set New Password</h2>
            <div id="message" style="color: red;">
                <?php
                if (isset($_SESSION['error'])) {
                    echo "<script>alert('" . addslashes($_SESSION['error']) . "');</script>";
                    unset($_SESSION['error']);
                } elseif ($successMessage) {
                   echo "<script>
                        alert('" . addslashes($successMessage) . "');
                        window.location.href = 'index.php';
                    </script>";

                }
                ?>
            </div>
            <div id="verified-email" style="margin-bottom: 20px;">
                <?php
                // Display the email being verified
                if (isset($_SESSION['email'])) {
                    echo "Verifying for email: <strong>" . htmlspecialchars($_SESSION['email']) . "</strong>";
                }
                ?>
            </div>
            <form method="POST" action="set_new_password.php" id="set-new-password-form">
                <div class="form-group">
                    <label for="new_password"><i class="zmdi zmdi-lock"></i></label>
                    <input type="password" name="new_password" id="new_password" placeholder="New Password" required/>
                </div>
                <div class="form-group">
                    <label for="confirm_password"><i class="zmdi zmdi-lock-outline"></i></label>
                    <input type="password" name="confirm_password" id="confirm_password" placeholder="Confirm Password" required/>
                </div>
                <div class="form-group form-button">
                    <input type="submit" name="set_password" id="set_password" class="form-submit" value="Set Password"/>
                </div>
                <p id="password-error" style="color: red; display: none;">Passwords do not match.</p>
            </form>
        </div>
    </div>
</section>
</body>
</html>
