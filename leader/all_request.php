<?php
session_start();

// Check if the user is logged in; if not, redirect to login
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'leader') {
    echo "<script>alert('Bạn chưa đăng nhập! Vui lòng đăng nhập lại.'); window.location.href = '../index.php';</script>";
    exit();
}

// Retrieve full name from session
$fullName = $_SESSION['full_name'];
$userEmail = $_SESSION['user_id']; // operator_email matches user_id

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

    // Filter requests to only those matching the operator's email
    $filteredRequests = array_filter($requests, function($request) use ($userEmail) {
        return $request['leader_email'] === $userEmail;
    });
} else {
    $filteredRequests = [];
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
            overflow-x: auto; /* Enable horizontal scrolling */
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
            white-space: nowrap; /* Prevent text from wrapping */
        }

       th {
        font-size: 6px; /* Adjust this value as needed */
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
        }.table-wrapper {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        max-width: 100%;
        margin: auto;
    }

    table {
        width: 100%;
        border-collapse: collapse;
        table-layout: fixed; /* Forces table columns to fit evenly */
    }

    th, td {
        padding: 8px;
        text-align: left;
        border: 1px solid #ddd;
        font-size: 0.85em;
        min-width: 100px; /* Adjust based on content */
        word-wrap: break-word;
        word-break: break-all; /* Ensures long words break within cell */
        white-space: normal; /* Allows text wrapping */
    }

    th {
        background-color: #f2f2f2;
    }

    /* Optional: Wrapping long text within cells */
    .wrap-text {
        white-space: normal;
    }
    </style>
    </style>
    
    <!-- DataTables CSS and jQuery -->
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.css">
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.js"></script>

</head>
<body>

    <div class="header">
        <h1>Quản lý phiếu xin tạm ứng</h1>
    </div>

    <div class="menu">
        <a href="index.php">Home</a>
        <a href="../update_signature.php">Cập nhật hình chữ ký</a>
        <a href="../logout.php" class="logout">Đăng xuất</a>
    </div>

    <div class="container">
        <div class="welcome-message">
            <p>Xin chào, <?php echo $fullName; ?>!</p>
        </div>

        <div class="content">
            <h2>Danh sách các yêu cầu xin tạm ứng</h2>

            <!-- Year Selection -->
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

            <!-- Data Table -->
            <table id="requestsTable" class="display">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Họ tên operator</th>
                        <th>Khách hàng</th>
                        <th>Số Bill/Booking</th>
                        <th>Số lượng (container)</th>
                        <th>Đơn vị (feet)</th>
                        <th>Số tiền xin tạm ứng (VNĐ)</th>
                        <th>Số tiền được duyệt (VNĐ)</th>h>
                        <th>Nội dung yêu cầu</th>
                        <th>Thời gian gửi yêu cầu</th>
                        <th>Thời gian Leader duyệt</th>
                        <th>Thời gian Giám đốc duyệt</th>
                        <th>Thời gian Kế toán chi tiền</th>
                        <th>Thời gian Kế toán thu tiền</th>
                        <th>Phiếu tạm ứng</th>
                    </tr>
                    <tr>
                        <!-- Add search inputs for each column -->
                        <?php for ($i = 0; $i < 15; $i++): ?>
                            <th><input type="text" placeholder="Tìm kiếm" /></th>
                        <?php endfor; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php
                     $totalAmount = 0; // Initialize total amount
                    if (!empty($filteredRequests)) {
                        foreach ($filteredRequests as $request) {
                            echo "<tr>";
                            echo "<td>" . $request['id'] . "</td>";
                            echo "<td>" . $request['full_name'] . "</td>";
                            echo "<td>" . $request['customer_name'] . "</td>";
                            echo "<td>" . $request['lot_number'] . "</td>";
                            echo "<td>" . $request['quantity'] . "</td>";
                            echo "<td>" . $request['unit'] . "</td>";
                            echo "<td>" . number_format($request['advance_amount']) . "</td>";
                            echo "<td>" . (isset($request['approved_amount']) ? number_format($request['approved_amount']) : 'null') . "</td>";
                            echo "<td>" . $request['advance_description'] . "</td>";
                            echo "<td>" . date("d/m/Y H:i:s", strtotime($request['date_time'])) . "</td>";
                            echo "<td>" . $request['check_approval_time'] . "</td>";
                            echo "<td>" . $request['approval_time'] . "</td>";
                            echo "<td>" . $request['payment_time'] . "</td>";
                            echo "<td>" . $request['payment_refund_time'] . "</td>";
                              // Only display the link if 'approved_filename' is not empty
    if (!empty($request['approved_filename'])) {
        echo "<td><a href=\"../director/pdfs/" . $request['approved_filename'] . "\" target=\"_blank\">Xem Phiếu</a></td>";
    } else {
        echo "<td></td>"; // Empty cell if there's no filename
    }
                            echo "</tr>";
                            // Add to total amount
            $totalAmount += $request['advance_amount'];
                        }
                    } else {
                        echo "<tr><td colspan='13'>Không có yêu cầu nào.</td></tr>";
                    }
                    
                    ?>
                </tbody>
            </table>
      <tr>
    <td colspan="6" style="text-align: right;"><strong>Tổng số tiền xin tạm ứng (VNĐ):</strong></td>
    <td><strong id="totalAmount">0</strong></td>
    <td colspan="6"></td> <!-- Empty cells to align with the table structure -->
</tr>
<br>
<tr>
    <td colspan="6" style="text-align: right;"><strong>Tổng số tiền được duyệt (VNĐ):</strong></td>
    <td><strong id="totalApprovedAmount">0</strong></td>
    <td colspan="6"></td> <!-- Empty cells to align with the table structure -->
</tr>
<br>
<tr>
    <td colspan="6" style="text-align: right;"><strong>Tổng số tiền đã được nhận (VNĐ):</strong></td>
    <td><strong id="totalReceivedAmount">0</strong></td>
    <td colspan="6"></td> <!-- Empty cells to align with the table structure -->
</tr>
<br>
<tr>
    <td colspan="6" style="text-align: right;"><strong>Tổng số tiền đã hoàn (VNĐ):</strong></td>
    <td><strong id="totalRefundedAmount">0</strong></td>
    <td colspan="6"></td> <!-- Empty cells to align with the table structure -->
</tr>
<br>
<tr>
    <td colspan="6" style="text-align: right;"><strong>Tổng số tiền nợ (VNĐ):</strong></td>
    <td><strong id="totalDebtAmount" style="color: red;">0</strong></td>
    <td colspan="6"></td> <!-- Empty cells to align with the table structure -->
</tr>
        </div>
      
    </div>

   <script>
    $(document).ready(function() {
        // Initialize DataTable with individual column search
        var table = $('#requestsTable').DataTable({
            "language": {
                "search": "Tìm kiếm nhanh:",
                "lengthMenu": "Hiển thị _MENU_ phiếu trên mỗi trang",
                "zeroRecords": "Không tìm thấy phiếu nào",
                "info": "Hiển thị _START_ đến _END_ của _TOTAL_ phiếu",
                "infoEmpty": "Hiển thị 0 đến 0 của 0 phiếu",
                "infoFiltered": "(lọc từ _MAX_ phiếu)"
            }
        });

        // Apply column search on each input field in the header
        $('#requestsTable thead tr:eq(1) th').each(function (i) {
            $('input', this).on('keyup change', function () {
                if (table.column(i).search() !== this.value) {
                    table.column(i).search(this.value).draw();
                }
                calculateTotal(); // Calculate total after filtering
            });
        });

        // Initial total calculation
        calculateTotal();
    });

    function calculateTotal() {
    let table = document.getElementById('requestsTable');
    let tr = table.getElementsByTagName('tr');
    let totalAmount = 0;             // Total requested amount
    let totalApprovedAmount = 0;     // Total approved amount
    let totalReceivedAmount = 0;      // Total received amount
    let totalRefundedAmount = 0;      // Total refunded amount

    for (let i = 1; i < tr.length; i++) { // Start from 1 to skip the header
        if (tr[i].style.display !== 'none') { // Only consider visible rows
            // Get the "Số tiền xin tạm ứng (VNĐ)" column
            let amountCell1 = tr[i].getElementsByTagName('td')[6]; // Adjust index for "Số tiền xin tạm ứng (VNĐ)"
            if (amountCell1) {
                let amount = parseFloat(amountCell1.innerText.replace(/,/g, '')); // Remove commas for parsing
                totalAmount += isNaN(amount) ? 0 : amount; // Ensure valid number
            }
            // Get the "Số tiền được duyệt (VNĐ)" column
            let amountCell2 = tr[i].getElementsByTagName('td')[7]; // Adjust index for "Số tiền được duyệt (VNĐ)"
            if (amountCell2) {
                let approvedAmount = parseFloat(amountCell2.innerText.replace(/,/g, '')); // Remove commas for parsing
                totalApprovedAmount += isNaN(approvedAmount) ? 0 : approvedAmount; // Ensure valid number
                
                // Calculate received amount based on column 12
                let receivedCell = tr[i].getElementsByTagName('td')[12]; // Adjust index for "Số tiền đã được nhận (VNĐ)"
                if (receivedCell && receivedCell.innerText) {
                    totalReceivedAmount += approvedAmount; // Set to approved amount if there's a value
                } else {
                    totalReceivedAmount += 0; // Set to 0 if no value
                }
                
                // Calculate refunded amount based on column 13
                let refundedCell = tr[i].getElementsByTagName('td')[13]; // Adjust index for "Số tiền đã hoàn (VNĐ)"
                if (refundedCell && refundedCell.innerText) {
                    totalRefundedAmount += approvedAmount; // Set to approved amount if there's a value
                } else {
                    totalRefundedAmount += 0; // Set to 0 if no value
                }
            }
        }
    }
     // Calculate total outstanding amount (số tiền nợ)
    let totalDebtAmount = totalReceivedAmount - totalRefundedAmount;

    // Update the total amount display
    document.getElementById('totalAmount').innerText = totalAmount.toLocaleString(); // Format for display
    document.getElementById('totalApprovedAmount').innerText = totalApprovedAmount.toLocaleString(); // Format for display
    document.getElementById('totalReceivedAmount').innerText = totalReceivedAmount.toLocaleString(); // Format for display
    document.getElementById('totalRefundedAmount').innerText = totalRefundedAmount.toLocaleString(); // Format for display
    document.getElementById('totalDebtAmount').innerText = totalDebtAmount.toLocaleString(); // Format for display
}
</script>
    
    <div class="footer">
        <p>© 2024 Phần mềm soffice phát triển bởi Hienlm 0988838487</p>
    </div>
</body>
</html>
