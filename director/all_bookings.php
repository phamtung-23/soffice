<?php
session_start();

// Check if the user is logged in; if not, redirect to login
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'director') {
  echo "<script>alert('Bạn chưa đăng nhập! Vui lòng đăng nhập lại.'); window.location.href = '../index.php';</script>";
  exit();
}

include('../helper/general.php');

// Retrieve full name from session
$fullName = $_SESSION['full_name'];
$userEmail = $_SESSION['user_id']; // operator_email matches user_id

// Get selected year from query parameter, default to current year
$selectedYear = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');

// Dynamically get available years from the bookings directory
$availableYears = [];
$bookingsDir = "../database/bookings/";

// Check if directory exists
if (is_dir($bookingsDir)) {
  // Scan the directory for json files
  $files = scandir($bookingsDir);

  foreach ($files as $file) {
    // Check if it's a bookings_YYYY.json file
    if (preg_match('/bookings_(\d{4})\.json$/', $file, $matches)) {
      $availableYears[] = (int)$matches[1];
    }
  }

  // Sort years in descending order (newest first)
  rsort($availableYears);
}

// If no years found, default to current year
if (empty($availableYears)) {
  $availableYears = [date('Y')];
  // Make sure the selected year is in the list
  if (!in_array($selectedYear, $availableYears)) {
    $selectedYear = date('Y');
  }
}

// Load booking data from JSON file for the selected year
$bookingsData = [];
$yearFile = "../database/bookings/bookings_{$selectedYear}.json";

if (file_exists($yearFile)) {
  $jsonContent = file_get_contents($yearFile);
  $yearBookings = json_decode($jsonContent, true);
  if (is_array($yearBookings)) {
    $bookingsData = $yearBookings;
  }
}

// If no bookings found in JSON files, initialize empty array
if (empty($bookingsData)) {
  $bookingsData = [];
}

// // Filter containers by sales person
$filteredContainers = [];
// $filteredContainers = array_filter($bookingsData, function ($container) use ($userEmail) {
//   return (
//     // Match by sales_email (new format) or sales (old format) which contains full name
//     (isset($container['sales_email']) && $container['sales_email'] === $userEmail) || 
//     (isset($container['sales']) && $container['sales'] === $_SESSION['full_name'])
//   );
// });

$unfilteredContainers = $bookingsData;
$currentDate = date('Y-m-d');
$twoDaysFromNow = date('Y-m-d', strtotime('+2 days'));

foreach ($bookingsData as $booking) {
  // Check if etd_start exists and is greater than 2 days from now
  if (isset($booking['etd_start']) && strtotime($booking['etd_start']) > strtotime($twoDaysFromNow)) {
    $filteredContainers[] = $booking;
  }
}
// If no filtering is applied (for demo purposes), use all data
if (empty($filteredContainers)) {
  $filteredContainers = $bookingsData;
}

// Function to get status class for styling
function getStatusClass($status)
{
  // Convert to lowercase for case-insensitive comparison
  $status = strtolower($status);

  switch ($status) {
    case 'confirmed':
      return 'confirmed';
    case 'pending':
      return 'pending';
    case 'rejected':
      return 'rejected';
    case 'cancel':
      return 'rejected'; // Use rejected style for canceled bookings
    default:
      return '';
  }
}
?>

<!DOCTYPE html>
<html lang="vi">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Quản lý Container</title>
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

    .alert {
      padding: 15px;
      margin-bottom: 20px;
      border-radius: 8px;
      font-weight: 500;
      box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
      width: 100%;
      animation: fadeIn 0.5s ease-in-out;
    }

    @keyframes fadeIn {
      from {
        opacity: 0;
        transform: translateY(-10px);
      }

      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    .alert-info {
      margin-top: 10px;
      background-color: #d1ecf1;
      color: #0c5460;
      border-left: 5px solid #17a2b8;
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
    }

    th {
      font-size: 14px;
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

    .status {
      padding: 3px 8px;
      border-radius: 4px;
      font-weight: bold;
    }

    .confirmed {
      background-color: #d4edda;
      color: #155724;
    }

    .pending {
      background-color: #fff3cd;
      color: #856404;
    }

    .rejected {
      background-color: #f8d7da;
      color: #721c24;
    }

    .cancel {
      background-color: #e0e0e0;
      color: #b71c1c;
    }

    .action-button {
      background-color: #4CAF50;
      color: white;
      border: none;
      border-radius: 4px;
      padding: 5px 10px;
      cursor: pointer;
      margin-right: 5px;
    }

    .action-button.edit {
      background-color: #2196F3;
    }

    .action-button.details {
      background-color: #607D8B;
    }

    .action-button.delete {
      background-color: #f44336;
    }

    .create-button {
      display: inline-block;
      background-color: #4CAF50;
      color: white;
      text-decoration: none;
      padding: 10px 15px;
      border-radius: 4px;
      margin-bottom: 20px;
      margin-top: 10px;
      font-weight: bold;
    }

    .create-button:hover {
      background-color: #45a049;
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

  <!-- Excel Export Libraries -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
  <script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
  <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/FileSaver.js/2.0.5/FileSaver.min.js"></script>
  <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.dataTables.min.css">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

  <!-- Font Awesome CDN -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" />
</head>

<body>
  <div class="header">
    <h1>Quản lý Booking Container</h1>
  </div>

  <div class="menu">
    <span class="hamburger" onclick="toggleMenu()">&#9776;</span>
    <div class='icon'>
      <img src="../images/uniIcon.png" alt="Home Icon" class="menu-icon">
    </div>
    <a href="index.php">Home</a>
    <a href="all_request.php">Quản lý phiếu tạm ứng</a>
    <a href="all_payment.php">Quản lý phiếu thanh toán</a>
    <a href="all_bookings.php">Quản lý Booking</a>
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

    <div class="content">
      <h2>Danh sách Booking Container</h2>

      <!-- Display information about filtered bookings -->
      <div class="alert alert-info" id="filteredInfoBox">
        <?php
        $totalBookings = count($bookingsData);
        $filteredBookingsCount = count($filteredContainers);
        $excludedBookingsCount = $totalBookings - $filteredBookingsCount;
        echo "Hiển thị {$filteredBookingsCount} booking với ngày ETD sau 2 ngày kể từ hôm nay. ({$excludedBookingsCount} booking bị ẩn do không đáp ứng điều kiện)";
        ?>
      </div>

      <!-- Display success message if exists -->
      <?php if (isset($_SESSION['delete_success'])): ?>
        <div class="alert alert-success">
          <?php
          echo $_SESSION['delete_success'];
          unset($_SESSION['delete_success']); // Clear the message after displaying
          ?>
        </div>
      <?php endif; ?>

      <!-- Display error message if exists -->
      <?php if (isset($_SESSION['delete_error'])): ?>
        <div class="alert alert-danger">
          <?php
          echo $_SESSION['delete_error'];
          unset($_SESSION['delete_error']); // Clear the message after displaying
          ?>
        </div>
      <?php endif; ?>
      <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
        <!-- Year Filter Dropdown -->
        <div>
          <form method="GET" action="" id="yearFilterForm" style="display: flex; align-items: center;">
            <label for="year" style="margin-right: 10px; font-weight: bold;">Chọn năm:</label>
            <select name="year" id="year" onchange="this.form.submit()" style="padding: 8px; border-radius: 4px;">
              <?php foreach ($availableYears as $year): ?>
                <option value="<?php echo $year; ?>" <?php echo $selectedYear == $year ? 'selected' : ''; ?>>
                  <?php echo $year; ?>
                </option>
              <?php endforeach; ?>
            </select>
          </form>
        </div>

        <div class="create-button-container">
          <!-- Toggle Filter Button -->
          <button id="toggleFilter" class="create-button" type="button" style="background-color:#ff9800; color:white; min-width:180px; border: none;">Hiện tất cả booking</button>
          <button id="exportExcel" class="create-button" style="background-color: #4CAF50; border: none;"><i class="fa-solid fa-file-export"></i> Xuất Excel</button>
        </div>

        <!-- Create New Booking Button -->
        <!-- <a href="create_booking.php" class="create-button">+ Tạo Booking Mới</a> -->
      </div>

      <!-- Data Table -->
      <div class="table-wrapper" id="filteredTableWrapper">
        <table id="containersTable" class="display">
          <thead>
            <tr>
              <th>ID</th>
              <th>SỐ BKG</th>
              <th>TÊN TÀU</th>
              <th>SỐ CHUYẾN</th>
              <th>HÃNG TÀU</th>
              <th>SỐ LƯỢNG</th>
              <th>POD</th>
              <th>CUSTOMER</th>
              <th>ETD</th>
              <th>DELAY DATE</th>
              <th>SALES</th>
              <th>PIC</th>
              <th>TRẠNG THÁI</th>
              <th>NGÀY TẠO</th>
              <th>NGÀY CẬP NHẬT</th>
              <th>THAO TÁC</th>
            </tr>
            <tr>
              <!-- Add search inputs for each column -->
              <?php for ($i = 0; $i < 16; $i++) : ?>
                <th><input type="text" placeholder="Tìm kiếm" /></th>
              <?php endfor; ?>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($filteredContainers as $container) : ?>
              <tr>
                <td><?php echo isset($container['id']) ? substr($container['id'], 0, 8) : 'N/A'; ?></td>
                <td><?php echo $container['booking_number']; ?></td>
                <td><?php echo $container['vessel_name']; ?></td>
                <td><?php echo $container['voyage_number']; ?></td>
                <td><?php echo $container['shipping_line']; ?></td>
                <td><?php echo $container['quantity']; ?></td>
                <td><?php echo $container['pod']; ?></td>
                <td><?php echo $container['customer'] ?? 'N/A'; ?></td>
                <td>
                  <?php
                  if (isset($container['etd_start']) && isset($container['etd_end']) && !empty($container['etd_end'])) {
                    echo date("d/m/Y", strtotime($container['etd_start'])) . ' - ' .
                      date("d/m/Y", strtotime($container['etd_end']));
                  } else if (isset($container['etd_start'])) {
                    echo date("d/m/Y", strtotime($container['etd_start'])) . ' - N/A';
                  } else if (isset($container['etd'])) {
                    echo date("d/m/Y", strtotime($container['etd']));
                  } else {
                    echo 'N/A';
                  }
                  ?>
                </td>
                <td><?php echo isset($container['delay_date']) && $container['delay_date'] != ''  ? date("d/m/Y", strtotime($container['delay_date'])) : 'N/A'; ?></td>
                <td><?php echo $container['sales']; ?></td>
                <td><?php echo $container['pic']; ?></td>
                <td>
                  <span class="status <?php echo getStatusClass($container['status']); ?>">
                    <?php echo ucfirst($container['status']); ?>
                  </span>
                </td>
                <td><?php echo isset($container['created_at']) ? date("d/m/Y H:i", strtotime($container['created_at'])) : 'N/A'; ?></td>
                <td><?php echo isset($container['updated_at']) ? date("d/m/Y H:i", strtotime($container['updated_at'])) : 'N/A'; ?></td>
                <td>
                  <button class="action-button details" onclick="handleDetails('<?php echo $container['id']; ?>')">Chi tiết</button>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <!-- Data Table (Unfiltered, hidden by default) -->
      <div class="table-wrapper" id="unfilteredTableWrapper" style="display:none;">
        <table id="containersTableAll" class="display">
          <thead>
            <tr>
              <th>ID</th>
              <th>SỐ BKG</th>
              <th>TÊN TÀU</th>
              <th>SỐ CHUYẾN</th>
              <th>HÃNG TÀU</th>
              <th>SỐ LƯỢNG</th>
              <th>POD</th>
              <th>CUSTOMER</th>
              <th>ETD</th>
              <th>DELAY DATE</th>
              <th>SALES</th>
              <th>PIC</th>
              <th>TRẠNG THÁI</th>
              <th>NGÀY TẠO</th>
              <th>NGÀY CẬP NHẬT</th>
              <th>THAO TÁC</th>
            </tr>
            <tr>
              <?php for ($i = 0; $i < 16; $i++) : ?>
                <th><input type="text" placeholder="Tìm kiếm" /></th>
              <?php endfor; ?>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($unfilteredContainers as $container) : ?>
              <tr>
                <td><?php echo isset($container['id']) ? substr($container['id'], 0, 8) : 'N/A'; ?></td>
                <td><?php echo $container['booking_number']; ?></td>
                <td><?php echo $container['vessel_name']; ?></td>
                <td><?php echo $container['voyage_number']; ?></td>
                <td><?php echo $container['shipping_line']; ?></td>
                <td><?php echo $container['quantity']; ?></td>
                <td><?php echo $container['pod']; ?></td>
                <td><?php echo $container['customer'] ?? 'N/A'; ?></td>
                <td>
                  <?php
                  if (isset($container['etd_start']) && isset($container['etd_end']) && !empty($container['etd_end'])) {
                    echo date("d/m/Y", strtotime($container['etd_start'])) . ' - ' .
                      date("d/m/Y", strtotime($container['etd_end']));
                  } else if (isset($container['etd_start'])) {
                    echo date("d/m/Y", strtotime($container['etd_start'])) . ' - N/A';
                  } else if (isset($container['etd'])) {
                    echo date("d/m/Y", strtotime($container['etd']));
                  } else {
                    echo 'N/A';
                  }
                  ?>
                </td>
                <td><?php echo isset($container['delay_date']) && $container['delay_date'] != ''  ? date("d/m/Y", strtotime($container['delay_date'])) : 'N/A'; ?></td>
                <td><?php echo $container['sales']; ?></td>
                <td><?php echo $container['pic']; ?></td>
                <td>
                  <span class="status <?php echo getStatusClass($container['status']); ?>">
                    <?php echo ucfirst($container['status']); ?>
                  </span>
                </td>
                <td><?php echo isset($container['created_at']) ? date("d/m/Y H:i", strtotime($container['created_at'])) : 'N/A'; ?></td>
                <td><?php echo isset($container['updated_at']) ? date("d/m/Y H:i", strtotime($container['updated_at'])) : 'N/A'; ?></td>
                <td>
                  <button class="action-button details" onclick="handleDetails('<?php echo $container['id']; ?>')">Chi tiết</button>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
  <script>
    // Function to normalize Vietnamese text (remove accents)
    function normalizeVietnamese(str) {
      return str.normalize('NFD').replace(/\p{Diacritic}/gu, '').replace(/đ/g, 'd').replace(/Đ/g, 'D');
    }
    $(document).ready(function() {
      // Extend DataTables search to normalize Vietnamese for both data and input
      $.fn.dataTable.ext.type.search.string = function(data) {
        if (!data) return '';
        return normalizeVietnamese(data.toString().toLowerCase());
      };
      // Initialize DataTable with individual column search
      var table = $('#containersTable').DataTable({
        "language": {
          "search": "Tìm kiếm nhanh:",
          "lengthMenu": "Hiển thị _MENU_ booking trên mỗi trang",
          "zeroRecords": "Không tìm thấy booking nào",
          "info": "Hiển thị _START_ đến _END_ của _TOTAL_ booking",
          "infoEmpty": "Hiển thị 0 đến 0 của 0 booking",
          "infoFiltered": "(lọc từ _MAX_ booking)"
        },
        // Ensure search works on all data, not just visible data
        "search": {
          "smart": true,
          "caseInsensitive": true,
          "regex": false
        }
      });

      var tableAll = $('#containersTableAll').DataTable({
        "language": table.settings()[0].oLanguage,
        "search": table.settings()[0].oPreviousSearch,
        "search": {
          "smart": true,
          "caseInsensitive": true,
          "regex": false
        }
      });

      // Apply column search on each input field in the header
      $('#containersTable thead tr:eq(1) th').each(function(i) {
        $('input', this).on('keyup change', function() {
          // Normalize input value for Vietnamese search
          let searchVal = normalizeVietnamese(this.value.toLowerCase());
          if (table.column(i).search() !== searchVal) {
            table.column(i).search(searchVal).draw();
          }
        });
      });
      $('#containersTableAll thead tr:eq(1) th').each(function(i) {
        $('input', this).on('keyup change', function() {
          let searchVal = normalizeVietnamese(this.value.toLowerCase());
          if (tableAll.column(i).search() !== searchVal) {
            tableAll.column(i).search(searchVal).draw();
          }
        });
      });

      // Excel Export functionality
      $('#exportExcel').on('click', function() {
        // Get current date for the filename
        let today = new Date();
        let dd = String(today.getDate()).padStart(2, '0');
        let mm = String(today.getMonth() + 1).padStart(2, '0');
        let yyyy = today.getFullYear();
        let dateStr = yyyy + mm + dd;

        // Define column headers for Excel
        let headers = [
          'ID', 'SỐ BKG', 'TÊN TÀU', 'SỐ CHUYẾN', 'HÃNG TÀU',
          'SỐ LƯỢNG', 'POD', 'CUSTOMER', 'ETD', 'DELAY DATE', 'SALES',
          'PIC', 'TRẠNG THÁI', 'NGÀY TẠO', 'NGÀY CẬP NHẬT'
        ];


        // Determine which table is visible
        let tableToExport = $('#filteredTableWrapper').is(':visible') ? table : tableAll;

        // Create temporary table for export
        let $temp = $('<div>').css('display', 'none');
        let $table = $('<table>');
        let $thead = $('<thead>');
        let $headerRow = $('<tr>');

        // Add headers
        headers.forEach(header => {
          $headerRow.append(
            $('<th>').text(header).css({
              'border': '1px solid #000000',
              'font-weight': 'bold',
              'background-color': '#f2f2f2'
            })
          );
        });

        $thead.append($headerRow);
        $table.append($thead);

        // Add data rows - get ALL data from DataTable (not just visible page)
        let allData = tableToExport.rows().data();
        let $tbody = $('<tbody>'); // Process all rows from the DataTable
        for (let i = 0; i < allData.length; i++) {
          let rowData = allData[i];
          let $row = $('<tr>');

          // Add all columns except the last one (actions column)
          for (let j = 0; j < rowData.length - 1; j++) {
            // For ETD (column 7) and Delay Date (column 8), handle potentially empty values
            let cellContent = rowData[j];

            // Add additional cleanup for HTML entities or unwanted formatting if needed
            // This helps ensure the Excel export looks clean and consistent
            if (cellContent === 'N/A') {
              cellContent = ''; // Replace N/A with empty string for cleaner Excel export
            }

            $row.append(
              $('<td>').html(cellContent).css({
                'border': '1px solid #000000'
              })
            );
          }

          $tbody.append($row);
        }

        $table.append($tbody);
        $temp.append($table);
        $('body').append($temp);

        // Convert HTML table to workbook
        let wb = XLSX.utils.table_to_book($table[0], {
          sheet: "Booking_Report"
        });

        // Create filename and download
        const fileName = 'Booking_Report_' + dateStr + '_Year<?php echo $selectedYear; ?>.xlsx';
        XLSX.writeFile(wb, fileName);

        // Clean up temporary table
        $temp.remove();

        // Show success message
        Swal.fire({
          icon: 'success',
          title: 'Xuất Excel thành công',
          text: 'Dữ liệu đã được xuất thành công với ' + allData.length + ' dòng!'
        });
      });
    });

    // Toggle filter button logic
    let filterOn = true;
    $('#toggleFilter').on('click', function() {
      filterOn = !filterOn;
      if (filterOn) {
        $('#filteredTableWrapper').show();
        $('#unfilteredTableWrapper').hide();
        $("#filteredInfoBox").show();
        $(this).text('Hiện tất cả booking');
      } else {
        $('#filteredTableWrapper').hide();
        $('#unfilteredTableWrapper').show();
        $("#filteredInfoBox").hide();
        $(this).text('Chỉ hiện booking ETD > 2 ngày');
      }
    });

    // Toggle the responsive class to show/hide the menu
    function toggleMenu() {
      var menu = document.querySelector('.menu');
      menu.classList.toggle('responsive');
    }

    // Handle edit button click
    function handleEdit(bookingId) {
      window.location.href = `edit_booking.php?id=${bookingId}&year=${<?php echo $selectedYear; ?>}`;
    }

    // Handle details button click
    function handleDetails(bookingId) {
      window.location.href = `booking_details.php?id=${bookingId}&year=${<?php echo $selectedYear; ?>}`;
    }

    // Handle delete button click
    function handleDelete(bookingId, bookingNumber) {
      if (confirm('Bạn có chắc chắn muốn xóa booking số ' + bookingNumber + ' không?')) {
        // Redirect to delete_booking.php with the booking ID
        window.location.href = `delete_booking.php?id=${bookingId}&year=${<?php echo $selectedYear; ?>}`;
      }
    }
  </script>

  <div class="footer">
    <p>© 2024 Phần mềm soffice phát triển bởi Hienlm 0988838487</p>
  </div>
</body>

</html>