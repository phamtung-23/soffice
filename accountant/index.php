<?php
session_start();

// Check if the user is logged in; if not, redirect to login
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'accountant') {
    echo "<script>alert('Bạn chưa đăng nhập! Vui lòng đăng nhập lại.'); window.location.href = '../index.php';</script>";
    exit();
}

// Retrieve full name and email from session
$fullName = $_SESSION['full_name'];
$userEmail = $_SESSION['user_id']; // operator_email matches user_id

// Get the current year or the selected year from the form
$selectedYear = isset($_POST['year']) ? $_POST['year'] : date('Y');

// Function to read and filter data from JSON files
function getDataFromJson($filePath) {
    if (file_exists($filePath)) {
        $jsonData = file_get_contents($filePath);
        return json_decode($jsonData, true); // Trả về tất cả dữ liệu mà không lọc
    }
    return [];
}

// Paths for request and payment files
$requestFile = "../database/request_$selectedYear.json";
$paymentFile = "../database/payment_$selectedYear.json";
$files = glob('../database/request_*.json');
// Read and filter data
$requestData = getDataFromJson($requestFile, $userEmail);
$paymentData = getDataFromJson($paymentFile, $userEmail);

// Function to get counts based on status

function getStatusCounts($data, $statusField, $statusValue = null) {
    return count(array_filter($data, function($item) use ($statusField, $statusValue) {
        if ($statusValue === null) {
            // Trường hợp cần đếm số phiếu đang chờ (check_status chưa có thông tin)
            return !isset($item[$statusField]) || $item[$statusField] === '';
        } else {
            // Trường hợp đếm theo trạng thái đã chỉ định
            return isset($item[$statusField]) && $item[$statusField] === $statusValue;
        }
    }));
}

// Calculate counts for request and payment data
$requestApprovedDirector = getStatusCounts($requestData, 'status', 'Phê duyệt');
$requestPaid= getStatusCounts($requestData, 'payment_status', 'Chi tiền');
$requestWaitingPay= $requestApprovedDirector - $requestPaid;
$requestRefunded= getStatusCounts($requestData, 'payment_refund_status', 'Đã hoàn tiền');
$requestWaitingRefund= $requestPaid - $requestRefunded;


$paymentApprovedDirector = getStatusCounts($paymentData, 'status', 'Phê duyệt');
$paymentPaid= getStatusCounts($paymentData, 'payment_status', 'Chi tiền');
$paymentWaitingPay= $paymentApprovedDirector - $paymentPaid;

?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trang chủ Accountant</title>
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
    <h1>Accountant Dashboard</h1>
</div>

<div class="menu">
    <a href="">Home</a>
    <a href="all_request.php">Quản lý phiếu tạm ứng</a>
    <a href="all_payment.php">Quản lý phiếu thanh toán</a>
    <a href="../update_signature.php">Cập nhật hình chữ ký</a>
    <a href="../logout.php" class="logout">Đăng xuất</a>
</div>

<div class="container">
    <div class="welcome-message">
        <p>Xin chào, <?php echo $fullName; ?>!</p>
    </div>

    <!-- Year Selection Form -->
    <form method="POST" action="">
        <label for="year">Chọn năm:</label>
        <select name="year" id="year" onchange="this.form.submit()">
             <?php
                    foreach ($files as $file) {
                        // Lấy năm từ tên file
                        preg_match('~request_(\d{4})\.json~', $file, $matches);
                        if (isset($matches[1])) {
                            $year = $matches[1];
                            echo "<option value=\"$year\" " . ($year == $selectedYear ? 'selected' : '') . ">$year</option>";
                        }
                    }
             ?>
        </select>
    </form>

    <!-- Management Table -->
    <div class="content">
        <table>
            <tr>
                <th>Loại phiếu</th>
                <th>Tổng số phiếu đã được GĐ duyệt</th>
                <th>Số phiếu đã chi tiền</th>
                <th>Số phiếu đã hoàn tiền</th>
                <th>Số phiếu chờ chi tiền</th>
                <th>Số phiếu chờ hoàn tiền</th>
                <th>Link quản lý phiếu chờ chi</th>
                <th>Link quản lý phiếu chờ hoàn</th>
            </tr>

            <tr>
                <td>Phiếu tạm ứng</td>
                <td><?php echo $requestApprovedDirector; ?></td>
                <td><?php echo $requestPaid; ?></td>
                <td><?php echo $requestRefunded; ?></td>
                <td><?php echo $requestWaitingPay; ?></td>
                <td><?php echo $requestWaitingRefund; ?></td>
                <td><a href="payment_request.php">Quản lý phiếu tạm ứng chờ chi</a></td>
                <td><a href="refund_request.php">Quản lý phiếu tạm ứng chờ hoàn</a></td>
            </tr>
           <tr>
                <td>Phiếu thanh toán</td>
                <td><?php echo $paymentApprovedDirector; ?></td>
                <td><?php echo $paymentPaid; ?></td>
                <td>Null</td>
                <td><?php echo $paymentWaitingPay; ?></td>
                <td>Null</td>
                <td><a href="payment_payment.php">Quản lý phiếu thanh toán chờ chi</a></td>
                 
            </tr>
        </table>
    </div>

    <div class="footer">
        <p>© 2024 Phần mềm soffice phát triển bởi Hienlm 0988838487</p>
    </div>
</div>

</body>
</html>
