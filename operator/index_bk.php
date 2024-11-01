<?php
session_start();

// Kiểm tra nếu người dùng đã đăng nhập, thì tiếp tục trang, nếu không thì chuyển hướng về trang login
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'operator') {
    echo "<script>alert('Bạn chưa đăng nhập! Vui lòng đăng nhập lại.'); window.location.href = '../index.php';</script>";
    exit();
}


// Lấy tên đầy đủ từ session
$fullName = $_SESSION['full_name'];
$userEmail = $_SESSION['user_id']; // opeator_email trùng với user_id

// Đọc dữ liệu từ file JSON
$file = 'request.json'; // Đường dẫn đến file JSON

if (!file_exists($file)) {
    die("File không tồn tại.");
}

$jsonData = file_get_contents($file);
$requests = json_decode($jsonData, true);

// Lọc các yêu cầu operator_email trùng với session['user_id']
$filteredRequests = array_filter($requests, function($request) use ($userEmail) {
    return $request['operator_email'] === $userEmail;
});

?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trang chủ</title>
    <style>
        /* Basic styles for layout */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
        }

        .header {
            background-color: #4CAF50;
            color: white;
            padding: 10px 20px;
            text-align: center;
        }

        .menu {
            background-color: #333;
            overflow: hidden;
        }

        .menu a {
            float: left;
            display: block;
            color: white;
            text-align: center;
            padding: 14px 20px;
            text-decoration: none;
            font-size: 17px;
        }

        .menu a:hover {
            background-color: #575757;
        }

        .container {
            padding: 20px;
        }

        .welcome-message {
            font-size: 24px;
            margin-bottom: 20px;
        }

        .menu a.logout {
            float: right;
            background-color: #f44336;
        }

        .menu a.logout:hover {
            background-color: #d32f2f;
        }

        .content {
            background-color: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.1);
            margin-top: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        table, th, td {
            border: 1px solid #ddd;
        }

        th, td {
            padding: 8px;
            text-align: left;
        }

        th {
            background-color: #f2f2f2;
        }

        input[type="text"] {
            width: 100%;
            padding: 8px;
            margin: 10px 0;
            border: 1px solid #ddd;
            border-radius: 4px;
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

    <div class="header">
        <h1>Trang chủ</h1>
    </div>

    <div class="menu">
        <a href="">Home</a>
        <a href="request.php">Tạo phiếu xin tạm ứng</a>
        <a href="../update_signature.php">Cập nhật hình chữ ký</a>
        <a href="../logout.php" class="logout">Đăng xuất</a>
    </div>

    <div class="container">
        <div class="welcome-message">
            <p>Xin chào, <?php echo $fullName; ?>!</p>
        </div>

        <div class="content">
            <h2>Danh sách các yêu cầu xin tạm ứng</h2>

            <!-- Tìm kiếm yêu cầu -->
            <input type="text" id="searchInput" onkeyup="searchTable()" placeholder="Tìm kiếm yêu cầu...">

            <table id="requestsTable">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Họ tên operator</th>
                        <th>Khách hàng</th>
                        <th>Số thứ tự lô</th>
                        <th>Số lượng (container)</th>
                        <th>Đơn vị (feet)</th>
                        <th>Số tiền (VNĐ)</th>
                        <th>Nội dung yêu cầu</th>
                        <th>Ngày yêu cầu</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if (!empty($filteredRequests)) {
                        foreach ($filteredRequests as $request) {
                            echo "<tr>";
                            echo "<td>" . $request['id'] . "</td>";
                            echo "<td>" . $request['full_name'] . "</td>";
                            echo "<td>" . $request['customer_name'] . "</td>";
                            echo "<td>" . $request['lot_number'] . "</td>";
                            echo "<td>" . $request['quantity'] . "</td>";
                            echo "<td>" . $request['unit'] . "</td>";
                            echo "<td>" . number_format($request['advance_amount']) . " VNĐ</td>";
                            echo "<td>" . $request['advance_description'] . "</td>";
                            echo "<td>" . date("d/m/Y H:i:s", strtotime($request['date_time'])) . "</td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='7'>Không có yêu cầu nào.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>

        </div>
    </div>

    <script>
        function searchTable() {
            let input = document.getElementById('searchInput');
            let filter = input.value.toUpperCase();
            let table = document.getElementById('requestsTable');
            let tr = table.getElementsByTagName('tr');

            for (let i = 1; i < tr.length; i++) {
                let td = tr[i].getElementsByTagName('td')[2]; // Tìm kiếm theo tên khách hàng (column 2)
                if (td) {
                    let txtValue = td.textContent || td.innerText;
                    if (txtValue.toUpperCase().indexOf(filter) > -1) {
                        tr[i].style.display = '';
                    } else {
                        tr[i].style.display = 'none';
                    }
                }
            }
        }
    </script>
 <div class="footer">
        <p>© 2024 Phần mềm soffice phát triển bởi Hienlm 0988838487</p>
    </div>
</body>
</html>
