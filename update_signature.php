<?php
session_start();

// Kiểm tra nếu người dùng đã đăng nhập, thì tiếp tục trang, nếu không thì chuyển hướng về trang login
if (!isset($_SESSION['user_id'])) {
    echo "<script>alert('Bạn chưa đăng nhập! Vui lòng đăng nhập lại.'); window.location.href = 'index.php';</script>";
    exit();
}

// Lấy tên đầy đủ từ session
$fullName = $_SESSION['full_name'];
$email = $_SESSION['user_id']; // sale_email trùng với user_id

// Đường dẫn lưu file
$uploadsDir = 'signatures/';
$fileName = md5($email) . '.jpg'; // Đặt tên file theo chuỗi md5 của email
$filePath = $uploadsDir . $fileName;

// Kiểm tra nếu file ảnh của người dùng đã tồn tại
if (file_exists($filePath)) {
    $signatureImage = $filePath;
} else {
    $signatureImage = 'signatures/sign.png'; // Đường dẫn đến ảnh mẫu
}

// Xử lý khi người dùng submit form upload ảnh
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_FILES['signature']) && $_FILES['signature']['error'] === UPLOAD_ERR_OK) {
        // Kiểm tra loại file upload
        $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png'];
        $fileType = mime_content_type($_FILES['signature']['tmp_name']);

        if (in_array($fileType, $allowedTypes)) {
            // Di chuyển file upload đến thư mục 'signatures'
            if (move_uploaded_file($_FILES['signature']['tmp_name'], $filePath)) {
                echo "<script>alert('Tải lên chữ ký thành công!');</script>";
            } else {
                echo "<script>alert('Có lỗi khi tải lên chữ ký.');</script>";
            }
        } else {
            echo "<script>alert('Chỉ cho phép các định dạng .jpg, .jpeg, .png.');</script>";
        }
    } else {
        echo "<script>alert('Vui lòng chọn một file hình ảnh.');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cập nhật chữ ký</title>
    <style>
       body {
    font-family: Arial, sans-serif;
    background-color: #f4f4f4;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    height: 100vh;
    margin: 0;
}

#content {
    background-color: white;
    padding: 40px;
    border-radius: 10px;
    box-shadow: 0px 0px 15px rgba(0, 0, 0, 0.1);
    width: 70%; /* Chiếm 70% màn hình chiều rộng */
    max-width: 800px; /* Đặt giới hạn chiều rộng tối đa */
    min-width: 300px; /* Đặt giới hạn chiều rộng tối thiểu cho màn hình nhỏ */
    text-align: center;
    box-sizing: border-box; /* Đảm bảo padding không ảnh hưởng tới kích thước hộp */
}

h2 {
    margin-bottom: 20px;
    font-size: 24px; /* Kích thước chữ đủ lớn cho tiêu đề */
}

.form-group {
    margin-bottom: 15px;
    text-align: left; /* Canh trái cho nhãn và input */
    width: 100%; /* Giữ form-group rộng theo chiều ngang */
}

label {
    display: block;
    margin-bottom: 5px;
    font-size: 16px;
}

input[type="file"] {
    padding: 10px;
    font-size: 16px;
    border-radius: 5px;
    border: 1px solid #ccc;
    width: 100%; /* Đảm bảo input file rộng theo khung cha */
    box-sizing: border-box;
}

button {
    padding: 10px 20px;
    font-size: 16px;
    background-color: #4CAF50;
    color: white;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    width: 100%; /* Nút rộng toàn chiều ngang */
    max-width: 200px; /* Nhưng giới hạn chiều rộng tối đa */
    margin: 0 auto; /* Canh giữa nút bấm */
}

button:hover {
    background-color: #45a049;
}

.signature-preview {
    margin-top: 20px;
}

.signature-preview img {
    max-width: 100%;
    height: auto;
    border: 1px solid #ddd;
    border-radius: 5px;
}
 .menu {
    background-color: #333;
    overflow: hidden;
    text-align: center;
    padding: 10px 0;
    border-radius: 10px;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    margin-bottom: 20px;
    width: 100%; /* Đảm bảo menu chiếm toàn bộ chiều rộng của phần tử cha */
}

.menu a {
    display: inline-block;
    color: white;
    text-align: center;
    padding: 14px 20px;
    text-decoration: none;
    font-size: 16px; /* Kích thước chữ mặc định */
    margin: 0 10px;
    transition: background-color 0.3s ease, color 0.3s ease;
    white-space: nowrap; /* Đảm bảo nội dung không bị xuống dòng */
}

.menu a:hover {
    background-color: #575757;
    color: #f1f1f1;
    border-radius: 5px;
}

.menu .logout {
    background-color: #f44336;
    padding: 14px 30px;
    border-radius: 5px;
}

.menu .logout:hover {
    background-color: #d32f2f;
}

/* Media query cho màn hình nhỏ hơn */
@media (max-width: 768px) {
    .menu a {
        font-size: 14px; /* Giảm kích thước chữ khi màn hình nhỏ hơn */
        padding: 12px 15px; /* Điều chỉnh lại khoảng cách padding */
    }

    .menu .logout {
        padding: 12px 20px; /* Điều chỉnh padding cho nút logout */
    }
}

@media (max-width: 480px) {
    .menu a {
        font-size: 12px; /* Tiếp tục giảm kích thước chữ với màn hình nhỏ hơn nữa */
        padding: 10px 10px;
    }

    .menu .logout {
        padding: 10px 15px; /* Điều chỉnh kích thước nút logout */
    }
}
 .footer {
            text-align: center;
            margin-top: 40px;
            font-size: 14px;
            color: #888;
        }
.signature-preview img {
    max-width: 60%; /* Giới hạn chiều rộng tối đa */
    max-height: 60%; /* Giới hạn chiều cao tối đa */
    width: auto; /* Để kích thước tự động thay đổi theo tỉ lệ */
    height: auto; /* Để kích thước tự động thay đổi theo tỉ lệ */
    border: 1px solid #ddd;
    border-radius: 5px;
}
    </style>
</head>
<body>
    <div id="content">
        <div class="menu">
            <a href="index.php">Home</a>
            <a href="logout.php" class="logout">Đăng xuất</a>
        </div>
        <h2>Cập nhật hình chữ ký cho <?= htmlspecialchars($email) ?></h2>
        <form action="update_signature.php" method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label for="signature">Tải lên hình ảnh chữ ký của bạn:</label>
                <input type="file" name="signature" id="signature" accept="image/*" required>
            </div>

            <button type="submit">Cập nhật chữ ký</button>
        </form>

      <div class="signature-preview">
    <h3>Chữ ký hiện tại</h3>
    <img src="<?= htmlspecialchars($signatureImage) ?>?t=<?= time() ?>" alt="Chữ ký hiện tại">
</div>
    </div>
    <div class="footer">
        <p>© 2024 Phần mềm soffice phát triển bởi Hienlm 0988838487</p>
    </div>
</body>
</html>
