<?php
session_start();

// Check if the user is logged in; if not, redirect to login
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'director') {
    echo "<script>alert('Bạn chưa đăng nhập! Vui lòng đăng nhập lại.'); window.location.href = '../index.php';</script>";
    exit();
}

// Retrieve full name from session
$fullName = $_SESSION['full_name'];
$userEmail = $_SESSION['user_id'];


// Load fund amount from fund.json
$fundFile = '../database/fund.json';
if (file_exists($fundFile)) {
    $fundData = json_decode(file_get_contents($fundFile), true);
    $totalFund = $fundData['total_fund'] ?? 0;
} else {
    $totalFund = 0; // Default to 0 if file doesn't exist
}
// Handle form submission to update the fund amount
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['updated_fund'])) {
    $updatedFund = str_replace(',', '', $_POST['updated_fund']); // Remove commas
    $fundData['total_fund'] = (int)$updatedFund;
    file_put_contents($fundFile, json_encode($fundData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    $totalFund = $fundData['total_fund'];
}

// Read JSON files in the directory
$files = glob('../database/request_*.json');
$selectedYear = date('Y');

// If a year is selected, update the year
if (isset($_POST['year'])) {
    $selectedYear = $_POST['year'];
}



// Read data from the selected JSON file
$file = "../database/request_$selectedYear.json";
if (file_exists($file)) {
    $jsonData = file_get_contents($file);
    $requests = json_decode($jsonData, true);

    // Initialize summary data
    $summaryData = [];
    $totalPaidAmount = 0;
    $totalReturnedAmount = 0;

    foreach ($requests as $request) {
        $operator = $request['full_name'];

        // Initialize data for operator if not exists
        if (!isset($summaryData[$operator])) {
            $summaryData[$operator] = [
                'operator' => $operator,
                'approved_count' => 0,
                'paid_count' => 0,
                'returned_count' => 0,
                'total_approved_amount' => 0,
                'total_paid_amount' => 0,
                'total_returned_amount' => 0,
                'total_debt' => 0,
            ];
        }

        // Calculate amounts per request
        if ($request['status'] === "Phê duyệt") {
            $summaryData[$operator]['approved_count']++;
            $summaryData[$operator]['total_approved_amount'] += $request['approved_amount'] ?? 0;
        }
        if (!empty($request['payment_time'])) {
            $summaryData[$operator]['paid_count']++;
            $summaryData[$operator]['total_paid_amount'] += $request['approved_amount'];
        }
        if (!empty($request['payment_refund_time'])) {
            $summaryData[$operator]['returned_count']++;
            $summaryData[$operator]['total_returned_amount'] += $request['approved_amount'] ?? 0;
        }
        $summaryData[$operator]['total_debt'] = $summaryData[$operator]['total_paid_amount'] - $summaryData[$operator]['total_returned_amount'];
    }

    // Calculate totals
    foreach ($summaryData as $data) {
        $totalPaidAmount += $data['total_paid_amount'];
        $totalReturnedAmount += $data['total_returned_amount'];
    }

    // Calculate remaining amount
    $remainingAmount = $totalFund - $totalPaidAmount + $totalReturnedAmount;
} else {
    $summaryData = [];
    $totalPaidAmount = 0;
    $totalReturnedAmount = 0;
    $remainingAmount = 0;
}
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trang chủ quản lý phiếu xin tạm ứng</title>
    <style>
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
            overflow-x: auto;
            /* Enable horizontal scrolling */
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
            white-space: nowrap;
            /* Prevent text from wrapping */
        }

        th {
            font-size: 6px;
            /* Adjust this value as needed */
            background-color: #f2f2f2;
            padding: 6px;
            text-align: left;
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

        .table-wrapper {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            max-width: 100%;
            margin: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            /* Forces table columns to fit evenly */
        }


        th,
        td {
            padding: 8px;
            text-align: left;
            border: 1px solid #ddd;
            font-size: 0.85em;
            min-width: 100px;
            /* Adjust based on content */
            word-wrap: break-word;
            word-break: break-all;
            /* Ensures long words break within cell */
            white-space: normal;
            /* Allows text wrapping */
        }

        th {
            background-color: #f2f2f2;
        }

        /* Optional: Wrapping long text within cells */
        .wrap-text {
            white-space: normal;
        }

        .update-fund-form {
            display: none;
            /* Hidden by default */
            margin-top: 10px;
        }

        input[type="text"].fund-input {
            width: 100%;
            padding: 8px;
            margin: 10px 0;
            border: 1px solid #ddd;
            border-radius: 4px;
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

            table {
                width: 100%;
                border-collapse: collapse;
                table-layout: auto;
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

            table {
                width: 100%;
                border-collapse: collapse;
                table-layout: auto;
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
    <script>
        // Function to format number with commas
        function formatNumberWithCommas(input) {
            // Remove any existing commas
            let value = input.value.replace(/\./g, '');

            // Format with commas every three digits
            input.value = value.replace(/\B(?=(\d{3})+(?!\d))/g, ".");
        }
        // Toggle the update form display
        function toggleUpdateForm() {
            const form = document.querySelector('.update-fund-form');
            form.style.display = form.style.display === 'none' ? 'block' : 'none';
        }
    </script>
</head>

<body>
    <div class="header">
        <h1>Quản lý Tài chính</h1>
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
        <a href="../logout.php" class="logout">Đăng xuất</a>
    </div>
    <div class="content">
        <div class="welcome-message">
            <p>Xin chào, <?php echo $fullName; ?>!</p>
        </div>
        <!-- Display summary -->
        <form method="POST">
            <label for="year">Chọn năm:</label>
            <select id="year" name="year" onchange="this.form.submit()">
                <?php
                foreach ($files as $file) {
                    preg_match('~request_(\d{4})\.json~', $file, $matches);
                    if (isset($matches[1])) {
                        $year = $matches[1];
                        echo "<option value=\"$year\" " . ($year == $selectedYear ? 'selected' : '') . ">$year</option>";
                    }
                }
                ?>
            </select>

        </form>
        <h3>Tổng kết:</h3>
        <p>Tổng tiền quỹ: <?php echo str_replace(',', '.', number_format($totalFund)); ?> VNĐ
        </p>

       
        <p>Tổng tiền đã chi: <?php echo str_replace(',', '.', number_format($totalPaidAmount)); ?> VNĐ</p>
        <p>Tổng tiền đã thu: <?php echo str_replace(',', '.', number_format($totalReturnedAmount)); ?> VNĐ</p>
        <?php
        $remainingAmountToCollect = $totalPaidAmount - $totalReturnedAmount;
        ?>

        <p>Số tiền còn lại cần phải thu:
            <span style="color: <?php echo $remainingAmount > 0 ? 'red' : 'black'; ?>;">
                <?php echo str_replace(',', '.', number_format($remainingAmountToCollect)); ?> VNĐ
            </span>
        </p>
        <?php
        $remainingAmount = $totalFund - $remainingAmountToCollect;
        ?>
        <p>Số tiền còn lại:  <?php echo str_replace(',', '.', number_format($remainingAmount)); ?> VNĐ</p>
        <h2>Tổng hợp tạm ứng theo từng operator</h2>


        <table>
            <thead>
                <tr>
                    <th>Operator</th>
                    <th>Số phiếu được duyệt</th>
                    <th>Số phiếu đã chi</th>
                    <th>Số phiếu đã hoàn tiền</th>
                    <th>Tổng tiền được duyệt (VNĐ)</th>
                    <th>Tổng tiền đã chi (VNĐ)</th>
                    <th>Tổng tiền đã hoàn (VNĐ)</th>
                    <th>Tổng tiền nợ (VNĐ)</th>
                </tr>
            </thead>
            <tbody>
                
                <?php
            if (!empty($summaryData)) {
               foreach ($summaryData as $data) {
            // Only show data if approved_count is greater than 0
            if ($data['approved_count'] > 0) {
                echo "<tr>";
                echo "<td>{$data['operator']}</td>";
                echo "<td>{$data['approved_count']}</td>";
                echo "<td>{$data['paid_count']}</td>";
                echo "<td>{$data['returned_count']}</td>";
                echo "<td>" . number_format($data['total_approved_amount'], 0, '.', '.') . "</td>";
                echo "<td>" . number_format($data['total_paid_amount'], 0, '.', '.') . "</td>";
                echo "<td>" . number_format($data['total_returned_amount'], 0, '.', '.') . "</td>";
                echo "<td style='color:" . ($data['total_debt'] > 0 ? "red" : "black") . ";'>" . number_format($data['total_debt'], 0, '.', '.') . "</td>";
                echo "</tr>";
            }
        }
    } else {
        echo "<tr><td colspan='8'>Không có dữ liệu.</td></tr>";
    }
    ?>
            </tbody>
        </table>



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