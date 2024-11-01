
<?php
session_start();
unset($_SESSION['success']);
// Kiểm tra xem người dùng đã đăng nhập hay chưa
if (isset($_SESSION['user_id'])) {
    // Lấy vai trò của người dùng
    $role = $_SESSION['role'];

    // Chuyển hướng người dùng đến trang tương ứng dựa trên vai trò
    switch ($role) {
        case 'sale':
            header("Location: sale");
            exit();
        case 'operator':
            header("Location: operator");
            exit();
        case 'director':
            header("Location: director");
            exit();
        case 'accountant':
            header("Location: accountant");
            exit();
        case 'leader':
            header("Location: leader");
            exit();
        default:
            // Nếu role không hợp lệ, đăng xuất người dùng
            header("Location: logout.php");
            exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Smart Office</title>

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
    </style>
</head>
<body>

    <div class="main">

        <!-- Sign up form -->
        <section class="signup sign-up-section">
            <div class="container">
                <div class="signup-content">
                    <div class="signup-form">
                        <h2 class="form-title">Sign up</h2>
                        <form method="POST" action="signup.php" class="register-form" id="register-form">
                           
                            <div class="form-group">
                                <label for="email"><i class="zmdi zmdi-email"></i></label>
                                <input type="email" name="email" id="email" placeholder="Your Email" required/>
                            </div>
                            <div class="form-group">
                                <label for="full_name"><i class="zmdi zmdi-account-box"></i></label>
                                <input type="text" name="full_name" id="full_name" placeholder="Full Name" required/>
                            </div>
                            <div class="form-group">
                                <label for="pass"><i class="zmdi zmdi-lock"></i></label>
                                <input type="password" name="pass" id="pass" placeholder="Password" required/>
                            </div>
                            <div class="form-group">
                                <label for="re-pass"><i class="zmdi zmdi-lock-outline"></i></label>
                                <input type="password" name="re_pass" id="re_pass" placeholder="Repeat your password" required/>
                            </div>
                            <div class="form-group">
                                <label for="phone"><i class="zmdi zmdi-phone"></i></label>
                                <input type="text" name="phone" id="phone" placeholder="ID Telegram" required/>
                                 <a href="idtelegram.html" target="_blank" style="color: #0088cc; text-decoration: underline; font-size: 14px;">Hướng dẫn lấy ID Telegram</a>
                        </div>

                     <div class="form-group">
                            </div>
                            <div class="form-group">
                                <label for="role"><i class="zmdi zmdi-view-list"></i></label>
                                <select name="role" id="role" required>
                                    <option value="sale">Sale</option>
                                    <option value="operator">Operator</option>
                                    <option value="leader">Leader</option>
                                    <option value="accountant">Accountant</option>
                                    <option value="director">Director</option>
                                </select>
                            </div>
                            <div class="form-group form-button">
                                <input type="submit" name="signup" id="signup" class="form-submit" value="Register"/>
                            </div>
                        </form>
                    </div>
                    <div class="signup-image">
                        <figure><img src="images/signup-image.jpg" alt="sing up image"></figure>
                        <a href="#" class="signup-image-link">I am already member</a>
                    </div>
                    
                </div>
            </div>
             <div class="footer">
                        <p>© 2024 Phần mềm soffice phát triển bởi Hienlm 0988838487</p>
                    </div>
        </section>

        <!-- Sign in Form -->
        <section class="sign-in">
            <div class="container">
                <div class="signin-content">
                    <div class="signin-image">
                        <figure><img src="images/signin-image.jpg" alt="sign in image"></figure>
                        <a href="reset_password.php" class="signup-image-link">Forgot Password?</a>
                        <a href="#" class="signup-image-link" id="show-signup">Create an account</a>
                    </div>

                    <div class="signin-form">
                        <h2 class="form-title">Sign in</h2>
                        <form method="POST" action="signin.php" class="register-form" id="login-form">
                            <div class="form-group">
                                <label for="email"><i class="zmdi zmdi-email"></i></label>
                                <input type="email" name="login_email" id="login_email" placeholder="Email" required/>
                            </div>
                            <div class="form-group">
                                <label for="your_pass"><i class="zmdi zmdi-lock"></i></label>
                                <input type="password" name="your_pass" id="your_pass" placeholder="Password" required/>
                            </div>
                        
                            <div class="form-group form-button">
                                <input type="submit" name="signin" id="signin" class="form-submit" value="Log in"/>
                            </div>
                      
                        </form>
                    </div>
                </div>
            </div>
                  <div class="footer">
                        <p>© 2024 Phần mềm soffice phát triển bởi Hienlm 0988838487</p>
                    </div>
        </section>

    </div>

    <!-- JS -->
    <script src="vendor/jquery/jquery.min.js"></script>
    <script src="js/main.js"></script>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            
            const signInSection = document.querySelector('.sign-in');
            const signUpSection = document.querySelector('.signup');
            const showSignUpBtn = document.getElementById('show-signup');
            const backToSignInLink = document.querySelector('.signup-image-link');

            // Show sign-up form when clicking "Create an account"
            showSignUpBtn.addEventListener('click', function(event) {
                event.preventDefault();
                signInSection.style.display = 'none';
                signUpSection.style.display = 'block';
            });

            // Go back to sign-in form
            backToSignInLink.addEventListener('click', function(event) {
                event.preventDefault();
                signUpSection.style.display = 'none';
                signInSection.style.display = 'block';
            });
        });
    </script>
</body>
</html>
