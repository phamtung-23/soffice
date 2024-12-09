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
$files = glob('../database/payment_*.json');
$selectedYear = date('Y');

// If a year is selected, update the year
if (isset($_POST['year'])) {
  $selectedYear = $_POST['year'];
}

// Read data from the selected JSON file
$file = "../database/payment_$selectedYear.json";

if (file_exists($file)) {
  $jsonData = file_get_contents($file);
  $requests = json_decode($jsonData, true);

  // Filter requests to only those matching the operator's email
  $filteredRequests = array_filter($requests, function ($request) use ($userEmail) {
    return $request['approval'][0]['email'] === $userEmail;
  });
} else {
  $filteredRequests = [];
}

function getApprovalStatus($item)
{
  $hasPending = false;

  foreach ($item['approval'] as $approval) {
    if ($approval['status'] === "rejected") {
      return "Từ chối";
    }
    if ($approval['status'] === "pending") {
      $hasPending = true;
    }
  }

  return $hasPending ? "Chờ duyệt" : "Đã duyệt";
}

?>

<!DOCTYPE html>
<html lang="vi">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Trang chủ quản lý phiếu thanh toán</title>
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

      .menu {
        background-color: #333;
        overflow: hidden;
        display: block;
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


  <!-- DataTables CSS and jQuery -->
  <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.css">
  <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
  <script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.js"></script>

</head>

<body>

  <div class="header">
    <h1>Quản lý phiếu Thanh toán</h1>
  </div>

  <div class="menu">
    <span class="hamburger" onclick="toggleMenu()">&#9776;</span>
    <div class='icon'>
      <img src="../images/uniIcon.png" alt="Home Icon" class="menu-icon">
    </div>
    <a href="./index.php">Home</a>
    <a href="all_request.php">Danh sách phiếu tạm ứng</a>
    <a href="all_payment.php">Danh sách phiếu thanh toán</a>
    <a href="../update_signature.php">Cập nhật hình chữ ký</a>
    <a href="../update_idtelegram.php">Cập nhật ID Telegram</a>
    <a href="../logout.php" class="logout">Đăng xuất</a>
  </div>

  <div class="container">
    <div class="welcome-message">
      <p>Xin chào, <?php echo $fullName; ?>!</p>
    </div>

    <div class="content">
      <h2>Danh sách các phiếu đề nghị thanh toán</h2>

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
            <th>Số tờ khai</th>
            <th>Số tiền thanh toán (VNĐ)</th>
            <th>Thời gian gửi yêu cầu</th>
            <th>Thời gian Leader duyệt</th>
            <th>Thời gian Sale duyệt</th>
            <th>Thời gian Giám đốc duyệt</th>
            <th>Thời gian kế toán duyệt</th>
            <th>Trạng thái</th>
            <th>Phiếu đã duyệt</th>
          </tr>
          <tr>
            <!-- Add search inputs for each column -->
            <?php for ($i = 0; $i < 12; $i++): ?>
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
              echo "<td>" . $request['instruction_no'] . "</td>";
              echo "<td>" . $request['operator_name'] . "</td>";
              echo "<td>" . $request['shipper'] . "</td>";
              echo "<td>" . $request['customs_manifest_on'] . "</td>";
              echo "<td>" . (!empty($request['amount']) ? number_format($request['amount']) : "") . "</td>";
              echo "<td>" . (!empty($request['created_at']) ? date("d/m/Y", strtotime($request['created_at'])) : "") . "</td>";
              echo "<td>" . (!empty($request['approval'][0]['time']) ? date("d/m/Y", strtotime($request['approval'][0]['time'])) : "") . "</td>";
              echo "<td>" . (!empty($request['approval'][1]['time']) ? date("d/m/Y", strtotime($request['approval'][1]['time'])) : "") . "</td>";
              echo "<td>" . (!empty($request['approval'][2]['time']) ? date("d/m/Y", strtotime($request['approval'][2]['time'])) : "") . "</td>";
              echo "<td>" . (!empty($request['approval'][3]['time']) ? date("d/m/Y", strtotime($request['approval'][3]['time'])) : "") . "</td>";
              echo "<td>" . getApprovalStatus($request) . "</td>";
              if (!empty($request['file_path'])) {
                echo "<td><a href=\"../database/payment/exports/" . $request['file_path'] . "\" target=\"_blank\">Xem Phiếu</a></td>";
              } else {
                echo "<td></td>"; // Empty cell if there's no filename
              }
              echo "</tr>";
              // Add to total amount
              $totalAmount = !empty($request['amount']) ? $totalAmount + $request['amount'] : $totalAmount;
            }
          } else {
            echo "<tr><td colspan='15'>Không có yêu cầu nào.</td></tr>";
          }

          ?>
        </tbody>
      </table>
      <tr>
        <td colspan="6" style="text-align: right;"><strong>Tổng số tiền đã được duyệt (VNĐ):</strong></td>
        <td><strong id="totalAmount">0</strong></td>
        <td colspan="6"></td> <!-- Empty cells to align with the table structure -->
      </tr>
      <!-- <br>
            <tr>
                <td colspan="6" style="text-align: right;"><strong>Tổng số tiền được duyệt (VNĐ):</strong></td>
                <td><strong id="totalApprovedAmount">0</strong></td>
                <td colspan="6"></td> <!-- Empty cells to align with the table structure 
            </tr>
            <br>
            <tr>
                <td colspan="6" style="text-align: right;"><strong>Tổng số tiền đã được nhận (VNĐ):</strong></td>
                <td><strong id="totalReceivedAmount">0</strong></td>
                <td colspan="6"></td> <!-- Empty cells to align with the table structure
            </tr>
            <br>
            <tr>
                <td colspan="6" style="text-align: right;"><strong>Tổng số tiền đã hoàn (VNĐ):</strong></td>
                <td><strong id="totalRefundedAmount">0</strong></td>
                <td colspan="6"></td> <!-- Empty cells to align with the table structure
            </tr>
            <br>
            <tr>
                <td colspan="6" style="text-align: right;"><strong>Tổng số tiền nợ (VNĐ):</strong></td>
                <td><strong id="totalDebtAmount" style="color: red;">0</strong></td>
                <td colspan="6"></td> <!-- Empty cells to align with the table structure
            </tr> -->
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
      $('#requestsTable thead tr:eq(1) th').each(function(i) {
        $('input', this).on('keyup change', function() {
          if (table.column(i).search() !== this.value) {
            table.column(i).search(this.value).draw();
          }
          calculateTotal(); // Calculate total after filtering
        });
      });


      // Initial total calculation
      calculateTotal();
    });

    // Toggle the responsive class to show/hide the menu
    function toggleMenu() {
      var menu = document.querySelector('.menu');
      menu.classList.toggle('responsive');
    }

    function calculateTotal() {
      let table = document.getElementById('requestsTable');
      let tr = table.getElementsByTagName('tr');
      let totalAmount = 0; // Total requested amount
      let totalApprovedAmount = 0; // Total approved amount
      let totalReceivedAmount = 0; // Total received amount
      let totalRefundedAmount = 0; // Total refunded amount

      for (let i = 1; i < tr.length; i++) { // Start from 1 to skip the header
        if (tr[i].style.display !== 'none') { // Only consider visible rows
          // Get the "Số tiền xin tạm ứng (VNĐ)" column
          let amountCell1 = tr[i].getElementsByTagName('td')[4]; // Adjust index for "Số tiền xin tạm ứng (VNĐ)"
          console.log(amountCell1);
          if (amountCell1) {
            let amount = parseFloat(amountCell1.innerText.replace(/,/g, '')); // Remove commas for parsing
            totalAmount += isNaN(amount) ? 0 : amount; // Ensure valid number
          }
        }
      }
      // Calculate total outstanding amount (số tiền nợ)
      let totalDebtAmount = totalReceivedAmount - totalRefundedAmount;

      // Update the total amount display
      document.getElementById('totalAmount').innerText = totalAmount.toLocaleString(); // Format for display
    }
  </script>

  <div class="footer">
    <p>© 2024 Phần mềm soffice phát triển bởi Hienlm 0988838487</p>
  </div>
</body>

</html>