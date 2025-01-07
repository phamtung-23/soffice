<?php
session_start();

// Check if the user is logged in; if not, redirect to login
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'accountant') {
  echo "<script>alert('Bạn chưa đăng nhập! Vui lòng đăng nhập lại.'); window.location.href = '../index.php';</script>";
  exit();
}

include('../helper/general.php');

// Retrieve full name from session
$fullName = $_SESSION['full_name'];
$userEmail = $_SESSION['user_id']; // operator_email matches user_id

// Read JSON files in the directory
// $files = glob('../database/payment_*.json');
$selectedYear = date('Y');

// If a year is selected, update the year
if (isset($_POST['year'])) {
  $selectedYear = $_POST['year'];
}

// Read data from the selected JSON file
$directory = "../../../private_data/soffice_database/payment/data/$selectedYear";

$resData =  getAllDataFiles($directory);
$filteredRequests = [];
if ($resData['status'] === 'success') {
  $requests = $resData['data'];
  // Filter requests to only those matching the operator's email
  foreach ($requests as $request) {
    if ($request['approval'][3]['status'] === 'approved') {
      $filteredRequests[] = $request;
    }
  }
}


$directoriesName = getDirectories('../../../private_data/soffice_database/payment/data');

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
      /* word-break: break-all; */
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
    <a href="index.php">Home</a>
    <a href="all_request.php">Quản lý phiếu tạm ứng</a>
    <a href="all_payment.php">Quản lý phiếu thanh toán</a>
    <a href="finance.php">Quản lý tài chính</a>
    <a href="../update_signature.php">Cập nhật hình chữ ký</a>
    <a href="../update_idtelegram.php">Cập nhật ID Telegram</a>
    <a href="../logout.php" class="logout">Đăng xuất</a>
  </div>

  <div class="container">
    <div class="welcome-message">
      <p>Xin chào, <?php echo $fullName; ?>!</p>
    </div>

    <div class="content">
      <h2>Báo cáo thanh toán</h2>

      <!-- Year Selection -->
      <form method="POST">
        <label for="year">Chọn năm:</label>
        <select id="year" name="year" onchange="this.form.submit()">
          <?php
          foreach ($directoriesName as $directoryName) {
            echo "<option value=\"$directoryName\" " . ($directoryName == $selectedYear ? 'selected' : '') . ">$directoryName</option>";
          }
          ?>
        </select>
      </form>

      <div style="margin-top: 10px; margin-bottom: 10px; gap: 10px; display: flex; flex-direction: row;">
        <div>
          <label for="min">Từ ngày:</label>
          <input type="date" id="min" name="min">
        </div>
        <div>
          <label for="max">Đến ngày:</label>
          <input type="date" id="max" name="max">
        </div>
      </div>

      <!-- Data Table -->
      <table id="requestsTable" class="display">
        <thead>
          <tr>
            <th>ID</th>
            <th>Chi tiết</th>
            <th>Ngày bắt đầu</th>
            <th>Số file</th>
            <th>Hóa đơn</th>
            <th>OPS</th>
            <th>Số tiền</th>
            <th></th>
          </tr>
          <tr>
            <!-- Add search inputs for each column -->
            <?php for ($i = 0; $i < 8; $i++): ?>
              <th><input type="text" placeholder="Tìm kiếm" /></th>
            <?php endfor; ?>
          </tr>
        </thead>
        <tbody>
          <?php
          $totalAmount = 0; // Initialize total amount
          if (!empty($filteredRequests)) {
            $index = 0;
            foreach ($filteredRequests as $request) {
              foreach ($request['expenses'] as $expense) {
                if (isset($expense['expense_ops']) && $expense['expense_ops'] === 'on') {
                  echo "<tr>";
                  echo "<td>" . $index + 1 . "</td>";
                  echo "<td>" . $request['shipper'] . "</td>";
                  echo "<td>" . explode(" ", $request['created_at'])[0] . "</td>";
                  echo "<td>" . $request['v_id'] . "</td>";
                  echo "<td>" . ($expense['so_hoa_don'] ?? null) . "</td>";
                  echo "<td>" . $request['operator_name'] . " - UNI</td>";
                  echo "<td>" . number_format($expense['expense_amount'], 0, ',', '.') . "</td>";
                  // Add button to show detail
                  echo "<td style='text-align: center;'>";
                  echo "<button style='background-color:#808080;
                      color: white;
                      border: none;
                      border-radius: 5px;
                      padding: 5px 10px;
                      cursor: pointer;' 
                    onclick='handleShowDetail(" . $request['instruction_no'] . ")'>Chi tiết</button>";
                  echo "</td>";
                  echo "</tr>";
                  $index++;
                }
              }
              // Add to total amount
              $totalAmount = !empty($request['amount']) ? $totalAmount + $request['amount'] : $totalAmount;
            }
          } else {
            echo "<tr><td colspan='7'>Không có yêu cầu nào.</td></tr>";
          }

          ?>
        </tbody>
      </table>
      <tr>
        <td colspan="6" style="text-align: right;"><strong>Tổng số tiền thanh toán (VNĐ):</strong></td>
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

      // Listen for changes in min and max date inputs
      $('#min, #max').on('change', function() {
        reRenderTable();
      });

      // Function to re-render table rows based on selected date range
      function reRenderTable() {
        let minDate = new Date($('#min').val());
        let maxDate = new Date($('#max').val());

        // Clear the table
        table.clear();

        // Iterate through PHP data passed as JSON (filteredRequests)
        <?php if (!empty($filteredRequests)) : ?>
          const requests = <?= json_encode($filteredRequests) ?>;
          let index = 0;
          requests.forEach((request) => {
            request.expenses.forEach((expense) => {
              if (expense.expense_ops === 'on') {
                let rowDate = new Date(request.created_at);
                // Check if the row date is within the selected range
                if (
                  (isNaN(minDate.getTime()) || rowDate >= minDate) &&
                  (isNaN(maxDate.getTime()) || rowDate <= maxDate)
                ) {
                  index++;
                  // Add the row back to the table
                  table.row.add([
                    index,
                    request.shipper,
                    request.created_at,
                    request.v_id,
                    expense.so_hoa_don ?? '',
                    `${request.operator_name} - UNI`,
                    parseFloat(expense.expense_amount).toLocaleString('DE-de'),
                    `<button style="background-color:#808080; color: white; border: none; border-radius: 5px; padding: 5px 10px; cursor: pointer;" onclick="handleShowDetail(${request.instruction_no})">Chi tiết</button>`
                  ]);
                }
              }
            });
          });
        <?php endif; ?>

        // Redraw the table
        table.draw();

        // Recalculate the total amount
        calculateTotal();
      }

      // Initial total calculation
      calculateTotal();
    });

    // Function to filter table rows by date range
    function filterByDate() {
      let minDate = new Date($('#min').val());
      let maxDate = new Date($('#max').val());
      let rows = $('#requestsTable tbody tr');

      rows.each(function() {
        let rowDate = new Date($(this).find('td:nth-child(3)').text()); // Adjust index for the 'Start date' column
        if (
          (!isNaN(minDate) && rowDate < minDate) ||
          (!isNaN(maxDate) && rowDate > maxDate)
        ) {
          $(this).hide(); // Hide rows outside the date range
        } else {
          $(this).show(); // Show rows within the date range
        }
      });
    }

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
          let amountCell1 = tr[i].getElementsByTagName('td')[6]; // Adjust index for "Số tiền xin tạm ứng (VNĐ)"
          console.log(amountCell1);
          if (amountCell1) {
            let amount = parseFloat(amountCell1.innerText.replace(/\./g, '')); // Remove commas for parsing
            totalAmount += isNaN(amount) ? 0 : amount; // Ensure valid number
          }
        }
      }
      // Calculate total outstanding amount (số tiền nợ)
      let totalDebtAmount = totalReceivedAmount - totalRefundedAmount;

      // Update the total amount display
      document.getElementById('totalAmount').innerText = totalAmount.toLocaleString(); // Format for display
    }

    // Handle showing the detail of a request
    function handleShowDetail(instructionNo) {
      const year = <?= $selectedYear ?>;
      window.location.href = `./payment-statement/detail?instruction_no=${instructionNo}&year=${year}`;
    }
  </script>

  <div class="footer">
    <p>© 2024 Phần mềm soffice phát triển bởi Hienlm 0988838487</p>
  </div>
</body>

</html>