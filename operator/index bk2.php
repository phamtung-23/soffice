<?php
session_start();

// Check if the user is logged in; if not, redirect to login
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'operator') {
    echo "<script>alert('Bạn chưa đăng nhập! Vui lòng đăng nhập lại.'); window.location.href = '../../index.php';</script>";
    exit();
}

// Retrieve full name and email from session
$fullName = $_SESSION['full_name'];
$userEmail = $_SESSION['user_id']; // operator_email matches user_id

// Get the current year or the selected year from the form
$selectedYear = isset($_POST['year']) ? $_POST['year'] : date('Y');

// echo $selectedYear;

// Function to read and filter data from JSON files
function getDataFromJson($filePath, $userEmail) {
    if (file_exists($filePath)) {
        $jsonData = file_get_contents($filePath);
        $data = json_decode($jsonData, true);
        
        // check data exists
        if ($data === null) {
            return [];
        }

        // Filter requests where operator_email matches the session user_id
        return array_filter($data, function($item) use ($userEmail) {
            return $item['operator_email'] === $userEmail;
        });
    }
    return [];
}

// Paths for request and payment files
$requestFile = "../database/request_$selectedYear.json";
$paymentFile = "../database/payment_$selectedYear.json";

// Read and filter data
$requestData = getDataFromJson($requestFile, $userEmail);
$paymentData = getDataFromJson($paymentFile, $userEmail);

// echo json_encode($paymentData);

// Function to get counts based on status
function getStatusCounts($data, $statusField, $statusValue) {
    return count(array_filter($data, function($item) use ($statusField, $statusValue) {
        return isset($item[$statusField]) && $item[$statusField] === $statusValue;
    }));
}

function countApprovalsByRoleAndStatus($data, $role, $status) {
    $count = 0;
    
    foreach ($data as $item) {
        if (isset($item['approval'])) {
            foreach ($item['approval'] as $approval) {
                if ($approval['role'] === $role && $approval['status'] === $status) {
                    $count++;
                }
            }
        }
    }

    return $count;
}

// Calculate counts for request and payment data
$requestTotal = count($requestData);
$requestApprovedLeader = getStatusCounts($requestData, 'check_status', 'Phê duyệt');
$requestApprovedDirector = getStatusCounts($requestData, 'status', 'Phê duyệt');
$requestRejectedLeader = getStatusCounts($requestData, 'check_status', 'Từ chối');
$requestRejectedDirector = getStatusCounts($requestData, 'status', 'Từ chối');

$paymentTotal = count($paymentData);
$paymentApprovedLeader = countApprovalsByRoleAndStatus($paymentData, 'leader', 'approved');
$paymentApprovedDirector = countApprovalsByRoleAndStatus($paymentData, 'director', 'approved');
$paymentRejectedLeader = countApprovalsByRoleAndStatus($paymentData, 'leader', 'rejected');
$paymentRejectedDirector = countApprovalsByRoleAndStatus($paymentData, 'director', 'rejected');
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trang chủ Operator</title>
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
    <h1>Operator Dashboard</h1>
</div>

<div class="menu">
    <a href="">Home</a>
    <a href="request.php">Tạo phiếu xin tạm ứng</a>
    <a href="payment-statement/create">Tạo phiếu thanh toán</a>
    <a href="../update_signature.php">Cập nhật hình chữ ký</a>
    <a href="../update_idtelegram.php">Cập nhật ID Telegram</a>
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
            <?php for ($i = date('Y'); $i >= date('Y') - 5; $i--): ?>
                <option value="<?php echo $i; ?>" <?php echo ($i == $selectedYear) ? 'selected' : ''; ?>>
                    <?php echo $i; ?>
                </option>
            <?php endfor; ?>
        </select>
    </form>

    <!-- Management Table -->
    <div class="content">
        <table>
            <tr>
                <th>Loại phiếu</th>
                <th>Tổng số phiếu</th>
                <th>Số phiếu đã được Leader duyệt</th>
                <th>Số phiếu đã được GĐ duyệt</th>
                <th>Số phiếu bị Leader từ chối</th>
                <th>Số phiếu bị Giám đốc từ chối</th>
                <th>Link quản lý</th>
            </tr>
            <tr>
                <td>Phiếu tạm ứng</td>
                <td><?php echo $requestTotal; ?></td>
                <td><?php echo $requestApprovedLeader; ?></td>
                <td><?php echo $requestApprovedDirector; ?></td>
                <td><?php echo $requestRejectedLeader; ?></td>
                <td><?php echo $requestRejectedDirector; ?></td>
                <td><a href="request_management.php">Quản lý phiếu tạm ứng</a></td>
            </tr>
            <tr>
                <td>Phiếu thanh toán</td>
                <td><?php echo $paymentTotal; ?></td>
                <td><?php echo $paymentApprovedLeader; ?></td>
                <td><?php echo $paymentApprovedDirector; ?></td>
                <td><?php echo $paymentRejectedLeader; ?></td>
                <td><?php echo $paymentRejectedDirector; ?></td>
                <td><a href="payment-statement/list">Quản lý phiếu thanh toán</a></td>
            </tr>
        </table>
    </div>

    <div class="footer">
        <p>© 2024 Phần mềm soffice phát triển bởi Hienlm 0988838487</p>
    </div>
</div>

</body>
</html>
