<?php
session_start();

// Check if the user is logged in; if not, redirect to login
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'director') {
    echo "<script>alert('Bạn chưa đăng nhập! Vui lòng đăng nhập lại.'); window.location.href = '../index.php';</script>";
    exit();
}

include('../helper/general.php');

// Retrieve full name and email from session
$fullName = $_SESSION['full_name'];
$userEmail = $_SESSION['user_id']; // operator_email matches user_id

// Get the current year or the selected year from the form
$selectedYear = isset($_POST['year']) ? $_POST['year'] : date('Y');

// Function to read and filter data from JSON files
function getDataFromJsonRequest($filePath)
{
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
$requestData = getDataFromJsonRequest($requestFile, $userEmail);
// $paymentData = getDataFromJsonRequest($paymentFile, $userEmail);

// Function to get counts based on status

function getStatusCounts($data, $statusField, $statusValue = null)
{
    return count(array_filter($data, function ($item) use ($statusField, $statusValue) {
        if ($statusValue === null) {
            // Trường hợp cần đếm số phiếu đang chờ (check_status chưa có thông tin)
            return !isset($item[$statusField]) || $item[$statusField] === '';
        } else {
            // Trường hợp đếm theo trạng thái đã chỉ định
            return isset($item[$statusField]) && $item[$statusField] === $statusValue;
        }
    }));
}

function countApprovalsByRoleAndStatus($data, $role, $status)
{
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

function countApprovalsByRoleDirector($data, $role, $status)
{
    // check role leader must be approved then count role sale
    $count = 0;
    foreach ($data as $item) {
        if (isset($item['approval'])) {
            foreach ($item['approval'] as $approval) {
                if ($item['approval'][0]['status'] === 'approved' && $item['approval'][1]['status'] === 'approved') {
                    if ($approval['role'] === $role && $approval['status'] === $status) {
                        $count++;
                    }
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
$requestWaitingDirector = $requestApprovedLeader - $requestApprovedDirector - $requestRejectedDirector;

// $paymentTotal =  isset($paymentData) ? count($paymentData) : 0;
// $paymentApprovedLeader =  isset($paymentData) ? countApprovalsByRoleAndStatus($paymentData, 'leader', 'approved') : 0;
// $paymentApprovedDirector =  isset($paymentData) ? countApprovalsByRoleAndStatus($paymentData, 'director', 'approved') : 0;
// $paymentRejectedLeader =  isset($paymentData) ? countApprovalsByRoleAndStatus($paymentData, 'leader', 'rejected') : 0;
// $paymentRejectedDirector =  isset($paymentData) ? countApprovalsByRoleAndStatus($paymentData, 'director', 'rejected') : 0;
// $paymentApprovedSale =  isset($paymentData) ? countApprovalsByRoleAndStatus($paymentData, 'sale', 'approved') : 0;
// $paymentRejectedSale =  isset($paymentData) ? countApprovalsByRoleAndStatus($paymentData, 'sale', 'rejected') : 0;
// $paymentWaitingDirector =  isset($paymentData) ? countApprovalsByRoleDirector($paymentData, 'director', 'pending') : 0;

// get year of payment data
$directoriesName = getDirectories('../../../private_data/soffice_database/payment/data');

$filePath = "../../../private_data/soffice_database/payment/status/$selectedYear/status.json";
$paymentDataStatusRes = getDataFromJson($filePath);
$paymentDataStatus = $paymentDataStatusRes['data'];

$paymentApprovedLeader =  isset($paymentDataStatus['approved_leader']) ? $paymentDataStatus['approved_leader']['number'] : 0;
$paymentApprovedDirector =  isset($paymentDataStatus['approved_director']) ? $paymentDataStatus['approved_director']['number'] : 0;
$paymentRejectedLeader =  isset($paymentDataStatus['rejected_leader']) ? $paymentDataStatus['rejected_leader']['number'] : 0;
$paymentRejectedDirector =  isset($paymentDataStatus['rejected_director']) ? $paymentDataStatus['rejected_director']['number'] : 0;
$paymentWaitingLeader =  isset($paymentDataStatus['pending_leader']) ? $paymentDataStatus['pending_leader']['number'] : 0;
$paymentWaitingSale =  isset($paymentDataStatus['pending_leader']) ? $paymentDataStatus['pending_sale']['number'] : 0;
$paymentApprovedSale =  isset($paymentDataStatus['approved_sale']) ? $paymentDataStatus['approved_sale']['number'] : 0;
$paymentRejectedSale =  isset($paymentDataStatus['rejected_sale']) ? $paymentDataStatus['rejected_sale']['number'] : 0;
$paymentWaitingDirector =  isset($paymentDataStatus['pending_director']) ? $paymentDataStatus['pending_director']['number'] : 0;
$paymentTotal = $paymentApprovedLeader + $paymentRejectedLeader + $paymentWaitingLeader;
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trang chủ director</title>
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
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .icon {
            padding: 10px 20px;
        }

        .menu-icon {
            width: 40px;
            height: 40px;
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
            overflow-x: auto;
        }

        table,
        th,
        td {
            border: 1px solid #ddd;
        }

        th,
        td {
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

        /* Hamburger icon (hidden by default) */
        .hamburger {
            display: none;
            float: right;
            font-size: 28px;
            cursor: pointer;
            color: white;
            padding: 10px 20px;
        }

        /* Basic responsive adjustments */
        @media (max-width: 950px) {

            /* Header and menu adjustments */
            .header {
                padding: 20px;
                font-size: 1.5em;
            }

            .header h1 {
                font-size: 1.2em;
            }

            .menu {
                background-color: #333;
                overflow: hidden;
                display: block;
            }

            .menu a {
                float: none;
                display: block;
                text-align: left;
                padding: 10px;
            }

            .menu a.logout {
                float: none;
                background-color: #f44336;
                text-align: center;
            }

            /* Container adjustments */
            .container {
                padding: 10px;
            }

            .welcome-message {
                font-size: 18px;
                text-align: center;
            }

            /* Content adjustments */
            .content {
                padding: 10px;
                margin-top: 15px;
            }

            /* Table adjustments */
            .table-wrapper {
                overflow-x: auto;
            }

            table,
            th,
            td {
                font-size: 0.9em;
            }

            .menu a {
                display: none;
                /* Hide menu links */
            }

            .menu a.logout {
                display: none;
            }

            .hamburger {
                display: block;
                /* Show hamburger icon */
            }

            .menu.responsive a {
                float: none;
                /* Make links stack vertically */
                display: block;
                text-align: left;
            }

            .menu.responsive .logout {
                float: none;
            }
        }

        @media (max-width: 480px) {

            /* Smaller screens (mobile) */
            .header h1 {
                font-size: 1.2em;
            }

            .menu {
                background-color: #333;
                overflow: hidden;
                display: block;
            }

            .menu a {
                font-size: 0.9em;
            }

            .welcome-message {
                font-size: 16px;
            }

            table,
            th,
            td {
                font-size: 0.9em;
                padding: 6px;
            }

            .content h2 {
                font-size: 1em;
            }

            .footer {
                font-size: 12px;
            }

            .menu a {
                display: none;
                /* Hide menu links */
            }

            .menu a.logout {
                display: none;
            }

            .hamburger {
                display: block;
                /* Show hamburger icon */
            }

            .menu.responsive a {
                float: none;
                /* Make links stack vertically */
                display: block;
                text-align: left;
            }

            .menu.responsive .logout {
                float: none;
            }
        }
    </style>
</head>

<body>

    <div class="header">
        <h1>Director Dashboard</h1>
    </div>

    <div class="menu">
        <span class="hamburger" onclick="toggleMenu()">&#9776;</span>
        <div class='icon'>
            <img src="../images/uniIcon.png" alt="Home Icon" class="menu-icon">
        </div>
        <a href="index.php">Home</a>
        <a href="all_request.php">Quản lý phiếu tạm ứng</a>
        <a href="all_payment.php">Quản lý phiếu thanh toán</a>
        <a href="finance.php">Quản lý tài chính</a>
        <a href="../update_signature.php">Cập nhật hình chữ ký</a>
        <a href="../update_idtelegram.php">Cập nhật ID Telegram</a>
        <a href="admin.php">Quản lý account</a>
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
                    $yearList = [];
                    if (isset($matches[1])) {
                        $year = $matches[1];
                        $yearList[] = $year;
                    }
                    // add $directoriesName to $yearList
                    $yearList = array_merge($yearList, $directoriesName);
                    // Remove duplicates
                    $yearList = array_unique($yearList);
                    // Sort the years in descending order
                    rsort($yearList);
                }
                // Display the year options
                foreach ($yearList as $year) {
                    echo "<option value=\"$year\" " . ($year == $selectedYear ? 'selected' : '') . ">$year</option>";
                }
                ?>
            </select>
        </form>

        <!-- Management Table -->
        <div class="content">
            <table>
                <tr>
                    <th>Loại phiếu</th>
                    <th>Số phiếu đã được Leader duyệt</th>
                    <th>Số phiếu đã được Sale duyệt</th>
                    <th>Số phiếu đã được GĐ duyệt</th>
                    <th>Số phiếu bị Giám đốc từ chối</th>
                    <th>Số phiếu chờ duyệt GĐ duyệt</th>
                    <th>Link quản lý</th>
                </tr>
                <tr>
                    <td>Phiếu tạm ứng</td>
                    <td><?php echo $requestApprovedLeader; ?></td>
                    <td style="background-color:#c0c0c0">Null</td>
                    <td><?php echo $requestApprovedDirector; ?></td>
                    <td><?php echo $requestRejectedDirector; ?></td>
                    <td><?php echo $requestWaitingDirector; ?></td>
                    <td><a href="request_management.php">Quản lý phiếu tạm ứng chờ duyệt</a></td>
                </tr>
                <tr>
                    <td>Phiếu thanh toán</td>
                    <td><?php echo $paymentApprovedLeader; ?></td>
                    <td><?php echo $paymentApprovedSale; ?></td>
                    <td><?php echo $paymentApprovedDirector; ?></td>
                    <td><?php echo $paymentRejectedDirector; ?></td>
                    <td><?php echo $paymentWaitingDirector; ?></td>
                    <td><a href="payment-statement/list">Quản lý phiếu thanh toán chờ duyệt</a></td>
                </tr>
            </table>
        </div>

        <div class="footer">
            <p>© 2024 Phần mềm soffice phát triển bởi Hienlm 0988838487</p>
        </div>
    </div>
    <script>
        // Toggle the responsive class to show/hide the menu
        function toggleMenu() {
            var menu = document.querySelector('.menu');
            menu.classList.toggle('responsive');
        }
    </script>
</body>

</html>