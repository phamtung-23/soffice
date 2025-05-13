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
$userEmail = $_SESSION['user_id'];

// Get booking ID and year from query parameters
$bookingId = $_GET['id'] ?? '';
$year = $_GET['year'] ?? date('Y');

// Redirect to all bookings if no booking ID provided
if (empty($bookingId)) {
  header('Location: all_bookings.php');
  exit();
}

// Load booking data from JSON file for the specified year
$booking = null;
$yearFile = "../database/bookings/bookings_{$year}.json";

if (file_exists($yearFile)) {
  $jsonContent = file_get_contents($yearFile);
  $yearBookings = json_decode($jsonContent, true);

  if (is_array($yearBookings)) {
    // Find the specific booking by ID
    foreach ($yearBookings as $bookingData) {
      if (isset($bookingData['id']) && $bookingData['id'] === $bookingId) {
        $booking = $bookingData;
        break;
      }
    }
  }
}

// If booking not found in the specified year, check adjacent years
if (!$booking) {
  // Check current year, current-1, and current+1 (if not already checked)
  $currentYear = date('Y');
  $yearsToCheck = [$currentYear];

  if ($currentYear - 1 != $year) {
    $yearsToCheck[] = $currentYear - 1;
  }

  if ($currentYear + 1 != $year) {
    $yearsToCheck[] = $currentYear + 1;
  }

  foreach ($yearsToCheck as $checkYear) {
    $checkFile = "../database/bookings/bookings_{$checkYear}.json";

    if (file_exists($checkFile)) {
      $jsonContent = file_get_contents($checkFile);
      $checkBookings = json_decode($jsonContent, true);

      if (is_array($checkBookings)) {
        foreach ($checkBookings as $bookingData) {
          if (isset($bookingData['id']) && $bookingData['id'] === $bookingId) {
            $booking = $bookingData;
            $year = $checkYear; // Update the year for display purposes
            break 2; // Break out of both loops
          }
        }
      }
    }
  }
}

// If still not found, redirect to all bookings with an error message
if (!$booking) {
  echo "<script>alert('Không tìm thấy thông tin booking với ID: " . htmlspecialchars($bookingId) . "'); window.location.href = 'all_bookings.php';</script>";
  exit();
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
  <title>Chi tiết Booking: <?php echo htmlspecialchars($bookingId); ?></title>
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
      max-width: 1000px;
      margin: 0 auto;
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

    .booking-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 20px;
      border-bottom: 1px solid #ddd;
      padding-bottom: 10px;
    }

    .booking-title h2 {
      margin: 0;
      font-size: 24px;
    }

    .booking-title p {
      color: #666;
      margin: 5px 0 0 0;
    }

    .back-button {
      background-color: #4CAF50;
      color: white;
      border: none;
      border-radius: 4px;
      padding: 10px 15px;
      cursor: pointer;
      text-decoration: none;
      display: inline-block;
    }

    .back-button:hover {
      background-color: #45a049;
    }

    .booking-details {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
      gap: 20px;
    }

    .detail-card {
      border: 1px solid #ddd;
      border-radius: 8px;
      padding: 15px;
      background-color: #f9f9f9;
    }

    .detail-card h3 {
      margin-top: 0;
      font-size: 18px;
      color: #333;
      border-bottom: 1px solid #eee;
      padding-bottom: 10px;
      margin-bottom: 15px;
    }

    .detail-item {
      margin-bottom: 10px;
    }

    .detail-item .label {
      font-weight: bold;
      display: block;
      margin-bottom: 5px;
      color: #555;
    }

    .detail-item .value {
      display: block;
      color: #333;
    }

    .status-badge {
      display: inline-block;
      padding: 5px 10px;
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

    .notes-section {
      margin-top: 20px;
      border-top: 1px solid #ddd;
      padding-top: 20px;
    }

    .notes-card {
      background-color: #f9f9f9;
      border-left: 4px solid #4CAF50;
      padding: 15px;
      border-radius: 0 4px 4px 0;
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
      .booking-details {
        grid-template-columns: 1fr;
      }

      .booking-header {
        display: flex;
        flex-direction: column;
        gap: 10px;
        /* justify-content: space-between; */
        align-items: center;
        margin-bottom: 20px;
        border-bottom: 1px solid #ddd;
        padding-bottom: 10px;
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

      .menu {
        background-color: #333;
        overflow: hidden;
        display: block;
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
      .menu {
        background-color: #333;
        overflow: hidden;
        display: block;
      }

      .menu a {
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
  </style>
</head>

<body>
  <div class="header">
    <h1>Chi tiết Booking Container</h1>
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
      <div class="booking-header">
        <div class="booking-title">
          <h2>Booking: <?php echo htmlspecialchars($booking['booking_number']); ?></h2>
          <p>ID: <?php echo isset($booking['id']) ? $booking['id'] : 'N/A'; ?></p>
        </div>
        <a href="all_bookings.php?year=<?php echo $year; ?>" class="back-button">Quay lại danh sách</a>
      </div>

      <div class="booking-details">
        <div class="detail-card">
          <h3>Thông tin cơ bản</h3>
          <div class="detail-item">
            <span class="label">Trạng thái:</span>
            <span class="value">
              <span class="status-badge <?php echo getStatusClass($booking['status']); ?>">
                <?php echo ucfirst($booking['status']); ?>
              </span>
            </span>
          </div>
          <div class="detail-item">
            <span class="label">Tên tàu:</span>
            <span class="value"><?php echo htmlspecialchars($booking['vessel_name']); ?></span>
          </div>
          <div class="detail-item">
            <span class="label">Số chuyến:</span>
            <span class="value"><?php echo htmlspecialchars($booking['voyage_number']); ?></span>
          </div>
          <div class="detail-item">
            <span class="label">Hãng tàu:</span>
            <span class="value"><?php echo htmlspecialchars($booking['shipping_line']); ?></span>
          </div>
          <div class="detail-item">
            <span class="label">Số lượng container:</span>
            <span class="value"><?php echo htmlspecialchars($booking['quantity']); ?></span>
          </div>
          <div class="detail-item">
            <span class="label">Cảng đích (POD):</span>
            <span class="value"><?php echo htmlspecialchars($booking['pod']); ?></span>
          </div>
          <div class="detail-item">
            <span class="label">Ngày Delay:</span>
            <span class="value">
              <?php echo isset($booking['delay_date']) ? date("d/m/Y", strtotime($booking['delay_date'])) : 'N/A'; ?>
            </span>
          </div>
          <?php if (isset($booking['attachment']) && !empty($booking['attachment'])): ?>
            <div class="detail-item">
              <span class="label">File đính kèm:</span>
              <span class="value">
                <a href="../database/bookings/<?php echo htmlspecialchars($booking['attachment']); ?>" target="_blank" style="color: #4CAF50; text-decoration: underline;">
                  Xem file PDF
                </a>
              </span>
            </div>
          <?php endif; ?>
        </div>

        <div class="detail-card">
          <h3>Thời gian và Người phụ trách</h3>
          <div class="detail-item">
            <span class="label">Ngày khởi hành dự kiến:</span>
            <span class="value">              <?php
              if (isset($booking['etd_start']) && isset($booking['etd_end']) && !empty($booking['etd_end'])) {
                echo date("d/m/Y", strtotime($booking['etd_start'])) . ' - ' .
                  date("d/m/Y", strtotime($booking['etd_end']));
              } else if (isset($booking['etd_start'])) {
                echo date("d/m/Y", strtotime($booking['etd_start']));
              } else if (isset($booking['etd'])) {
                echo date("d/m/Y", strtotime($booking['etd']));
              } else {
                echo 'N/A';
              }
              ?>
            </span>
          </div>
          <div class="detail-item">
            <span class="label">Sales:</span>
            <span class="value"><?php echo htmlspecialchars($booking['sales']); ?></span>
          </div>
          <div class="detail-item">
            <span class="label">Email Sales:</span>
            <span class="value"><?php echo isset($booking['sales_email']) ? htmlspecialchars($booking['sales_email']) : 'N/A'; ?></span>
          </div>
          <div class="detail-item">
            <span class="label">PIC:</span>
            <span class="value"><?php echo htmlspecialchars($booking['pic']); ?></span>
          </div>
          <div class="detail-item">
            <span class="label">Email PIC:</span>
            <span class="value"><?php echo isset($booking['pic_email']) ? htmlspecialchars($booking['pic_email']) : 'N/A'; ?></span>
          </div>
        </div>

        <div class="detail-card">
          <h3>Thời gian hệ thống</h3>
          <div class="detail-item">
            <span class="label">Ngày tạo:</span>
            <span class="value">
              <?php echo isset($booking['created_at']) ? date("d/m/Y H:i:s", strtotime($booking['created_at'])) : 'N/A'; ?>
            </span>
          </div>
          <div class="detail-item">
            <span class="label">Ngày cập nhật gần nhất:</span>
            <span class="value">
              <?php echo isset($booking['updated_at']) ? date("d/m/Y H:i:s", strtotime($booking['updated_at'])) : 'N/A'; ?>
            </span>
          </div>
        </div>
      </div>

      <?php if (!empty($booking['notes'])): ?>
        <div class="notes-section">
          <h3>Ghi chú</h3>
          <div class="notes-card">
            <?php echo nl2br(htmlspecialchars($booking['notes'])); ?>
          </div>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <script>
    // Toggle the responsive class to show/hide the menu
    function toggleMenu() {
      var menu = document.querySelector('.menu');
      menu.classList.toggle('responsive');
    }
  </script>

  <div class="footer">
    <p>© 2024 Phần mềm soffice phát triển bởi Hienlm 0988838487</p>
  </div>
</body>

</html>