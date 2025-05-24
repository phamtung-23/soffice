<?php
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Lấy thông tin từ form
    $fullname = mb_strtoupper($_POST['full_name']); 
    $email = $_POST['email'];
    $password = $_POST['pass'];
    $confirm_password = $_POST['re_pass'];
    $phone = $_POST['phone'];
    $role = $_POST['role'];


    // Kiểm tra mật khẩu xác nhận
    if ($password === $confirm_password) {
        // Mã hóa mật khẩu
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        // Đọc file users.json
        $file = 'database/users.json';
        if (file_exists($file)) {
            $users = json_decode(file_get_contents($file), true);
        } else {
            $users = [];
        }

        // Kiểm tra email đã tồn tại chưa
        foreach ($users as $user) {
            if ($user['email'] === $email) {
                  echo "<script>
                alert('Email đã tồn tại!');
                window.location.href = 'index.php';
              </script>";
        exit();
            }
        }

        // Thêm người dùng mới vào mảng
        $newUser = [
            'fullname' => $fullname,
            'email' => $email,
            'password' => $hashed_password,
            'phone' => $phone,
            'role' => $role
        ];
        $users[] = $newUser;

        // Ghi lại vào file JSON với hỗ trợ ký tự tiếng Việt
        file_put_contents($file, json_encode($users, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

          echo "<script>
                alert('Tạo tài khoản thành công!');
                window.location.href = 'index.php';
              </script>";
        exit();
    
    } else {
    
        echo "<script>
                alert('Mật khẩu xác nhận không khớp!');
                window.location.href = 'index.php';
              </script>";
        exit();
    }
}
?>
