<?php
session_start();

// Thiết lập thời gian session tồn tại là 1 giờ (3600 giây)
ini_set('session.gc_maxlifetime', 3600); 
session_set_cookie_params(3600); // Đảm bảo cookie session cũng hết hạn sau 1 giờ

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['login_email'];
    $password = $_POST['your_pass'];

    // Đọc file users.json
    $file = 'database/users.json';
    if (file_exists($file)) {
        $users = json_decode(file_get_contents($file), true);
    } else {
       echo "<script>
                alert('Không tìm thấy danh sách tài khoản!');
                window.location.href = 'index.php';
              </script>";
        exit();
    }

    // Kiểm tra tài khoản
    foreach ($users as $user) {
        if ($user['email'] === $email) {
            // Kiểm tra mật khẩu đã hash
            if (password_verify($password, $user['password'])) { // So sánh mật khẩu nhập vào với mật khẩu hash
                // Lưu thông tin người dùng vào session
                $_SESSION['user_id'] = $user['email'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['full_name'] = $user['fullname']; // Giả sử 'full_name' có trong dữ liệu người dùng

                // Ghi nhận thời gian bắt đầu phiên
                $_SESSION['login_time'] = time();

                // Chuyển hướng dựa trên vai trò
                if ($user['role'] == 'sale') {
                    header("Location: sale");
                } elseif ($user['role'] == 'operator') {
                    header("Location: operator");
                } elseif ($user['role'] == 'director') {
                    header("Location: director");
                } elseif ($user['role'] == 'accountant') {
                    header("Location: accountant");
                } elseif ($user['role'] == 'leader') {
                    header("Location: leader");
                }
                exit();
            } else {
               
                 echo "<script>
                        alert('Sai mật khẩu!');
                        window.location.href = 'index.php';
                      </script>";
                exit();
            }
        }
    }

    echo "<script>
            alert('Không tìm thấy tài khoản!');
            window.location.href = 'index.php';
          </script>";
}
?>
