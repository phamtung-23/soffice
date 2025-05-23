<?php
session_start();
date_default_timezone_set('Asia/Bangkok'); // Set timezone to UTC+7

// Check if the user is logged in; if not, redirect to login
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'sale') {
  echo "<script>alert('Bạn chưa đăng nhập! Vui lòng đăng nhập lại.'); window.location.href = '../index.php';</script>";
  exit();
}

include('../helper/general.php');

// Retrieve full name and email from session
$fullName = $_SESSION['full_name'];
$userEmail = $_SESSION['user_id'];

// Process form submission
$successMessage = '';
$errorMessage = '';

// Create attachments directory if it doesn't exist
$attachmentsDir = '../database/bookings/attachments';
if (!is_dir($attachmentsDir)) {
  mkdir($attachmentsDir, 0777, true);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // Get form data
  $bookingNumber = $_POST['booking_number'] ?? '';
  $vesselName = $_POST['vessel_name'] ?? '';
  $voyageNumber = $_POST['voyage_number'] ?? '';
  $shippingLine = $_POST['shipping_line'] ?? '';
  $quantity = $_POST['quantity'] ?? '';
  $pod = $_POST['pod'] ?? '';
  $etdStart = $_POST['etd_start'] ?? '';
  $etdEnd = $_POST['etd_end'] ?? '';
  $picId = $_POST['pic'] ?? '';
  $status = $_POST['status'] ?? 'pending';
  $delayDate = $_POST['delay_date'] ?? '';
  $notes = $_POST['notes'] ?? '';
  $customer = $_POST['customer'] ?? ''; // New customer field

  // Generate a unique ID for this booking
  $uniqueId = generateUniqueId();

  // Handle file upload
  $attachmentPath = '';
  $hasAttachment = false;

  if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
    $allowedTypes = ['application/pdf'];

    $file = $_FILES['attachment'];
    $fileType = $file['type'];
    $fileName = $file['name'];
    $fileExt = pathinfo($fileName, PATHINFO_EXTENSION);

    // Validate file type
    if (!in_array($fileType, $allowedTypes)) {
      $errorMessage = 'Lỗi: Loại file không được hỗ trợ. Vui lòng sử dụng PDF.';
    } else {
      // Generate a unique filename to prevent duplicates
      $newFilename = $uniqueId . '_' . date('Ymd') . '.' . $fileExt;
      $uploadPath = $attachmentsDir . '/' . $newFilename;

      if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
        $attachmentPath = 'attachments/' . $newFilename;
        $hasAttachment = true;
      } else {
        $errorMessage = 'Lỗi: Không thể tải file lên. Vui lòng thử lại sau.';
      }
    }
  }

  if (empty($errorMessage)) {
    // Get PIC's name from id
    $picName = '';
    $picResult = getUsersByRole('pic');
    if ($picResult['status'] === 'success') {
      foreach ($picResult['data'] as $user) {
        if ($user['email'] === $picId) {
          $picName = $user['fullname'];
          break;
        }
      }
    }

    // Create booking data structure
    $bookingData = [
      'id' => $uniqueId,
      'booking_number' => $bookingNumber,
      'vessel_name' => $vesselName,
      'voyage_number' => $voyageNumber,
      'shipping_line' => $shippingLine,
      'quantity' => $quantity,
      'pod' => $pod,
      'customer' => $customer, // Add customer
      'etd_start' => $etdStart,
      'etd_end' => $etdEnd, // Optional
      'sales' => $fullName,
      'sales_email' => $userEmail,
      'pic' => $picName,
      'pic_email' => $picId,
      'status' => $status,
      'delay_date' => $delayDate,
      'notes' => $notes,
      'created_at' => date('Y-m-d H:i:s'),
      'updated_at' => date('Y-m-d H:i:s')
    ];

    // Add attachment path if a file was uploaded
    if ($hasAttachment) {
      $bookingData['attachment'] = $attachmentPath;
    }

    // Save booking data
    $saveResult = saveBookingData($bookingData);

    if ($saveResult['status'] === 'success') {
      $successMessage = 'Đã tạo booking thành công với số: ' . $bookingNumber;
    } else {
      $errorMessage = 'Lỗi: ' . ($saveResult['message'] ?? 'Không thể tạo booking');
    }
  }
}

// Get all PIC users for dropdown
$picUsersResult = getUsersByRole('pic');
$picUsers = [];
if ($picUsersResult['status'] === 'success') {
  $picUsers = $picUsersResult['data'];
}
?>

<!DOCTYPE html>
<html lang="vi">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Tạo Booking Container Mới</title>
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

    .form-group {
      margin-bottom: 15px;
    }

    label {
      display: block;
      margin-bottom: 5px;
      font-weight: bold;
    }

    input[type="text"],
    input[type="date"],
    select,
    textarea,
    input[type="file"] {
      width: 100%;
      padding: 8px;
      border: 1px solid #ddd;
      border-radius: 4px;
      font-size: 16px;
    }

    textarea {
      height: 100px;
    }

    .form-row {
      display: flex;
      flex-wrap: wrap;
      margin: 0 -10px;
    }

    .form-col {
      flex: 1;
      padding: 0 10px;
      min-width: 200px;
    }

    .btn {
      padding: 10px 15px;
      background-color: #4CAF50;
      color: white;
      border: none;
      border-radius: 4px;
      cursor: pointer;
      font-size: 16px;
    }

    .btn:hover {
      background-color: #45a049;
    }

    .btn-cancel {
      background-color: #f44336;
      margin-right: 10px;
    }

    .btn-cancel:hover {
      background-color: #d32f2f;
    }

    .alert {
      padding: 15px;
      margin-bottom: 20px;
      border-radius: 4px;
    }

    .alert-success {
      background-color: #dff0d8;
      color: #3c763d;
      border: 1px solid #d6e9c6;
    }

    .alert-danger {
      background-color: #f2dede;
      color: #a94442;
      border: 1px solid #ebccd1;
    }

    .footer {
      text-align: center;
      margin-top: 40px;
      font-size: 14px;
      color: #888;
    }

    .form-actions {
      margin-top: 20px;
      text-align: right;
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

    .booking-form {
      margin-top: 20px;
    }

    .booking-title {
      margin-bottom: 10px;
    }

    /* Basic responsive adjustments */
    @media (max-width: 950px) {
      .form-col {
        flex: 100%;
        margin-bottom: 15px;
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
    <h1>Tạo Booking Container Mới</h1>
  </div>

  <div class="menu">
    <span class="hamburger" onclick="toggleMenu()">&#9776;</span>
    <div class='icon'>
      <img src="../images/uniIcon.png" alt="Home Icon" class="menu-icon">
    </div>
    <a href="./index.php">Home</a>
    <a href="all_payment.php">Danh sách phiếu thanh toán</a>
    <a href="all_bookings.php">Booking container</a>
    <a href="../update_signature.php">Cập nhật hình chữ ký</a>
    <a href="../update_idtelegram.php">Cập nhật ID Telegram</a>
    <a href="../logout.php" class="logout">Đăng xuất</a>
  </div>

  <div class="container">
    <div class="welcome-message">
      <p>Xin chào, <?php echo $fullName; ?>!</p>
    </div>

    <div class="content">
      <h2 class="booking-title">Tạo Booking Container Mới</h2>

      <?php if ($successMessage): ?>
        <div class="alert alert-success">
          <?php echo $successMessage; ?>
        </div>
      <?php endif; ?>

      <?php if ($errorMessage): ?>
        <div class="alert alert-danger">
          <?php echo $errorMessage; ?>
        </div>
      <?php endif; ?>

      <form method="POST" action="" class="booking-form" enctype="multipart/form-data">
        <div class="form-row">
          <div class="form-col">
            <div class="form-group">
              <label for="booking_number">Số Booking <span style="color: red;">*</span></label>
              <input type="text" id="booking_number" name="booking_number" placeholder="Nhập số booking" required>
            </div>
          </div>

          <div class="form-col">
            <div class="form-group">
              <label for="vessel_name">Tên Tàu <span style="color: red;">*</span></label>
              <input type="text" id="vessel_name" name="vessel_name" required>
            </div>
          </div>
        </div>

        <div class="form-row">
          <div class="form-col">
            <div class="form-group">
              <label for="voyage_number">Số Chuyến <span style="color: red;">*</span></label>
              <input type="text" id="voyage_number" name="voyage_number" required>
            </div>
          </div>

          <div class="form-col">
            <div class="form-group">
              <label for="shipping_line">Hãng Tàu <span style="color: red;">*</span></label>
              <input type="text" id="shipping_line" name="shipping_line" required>
            </div>
          </div>
        </div>

        <div class="form-row">
          <div class="form-col">
            <div class="form-group">
              <label for="quantity">Số Lượng Container <span style="color: red;">*</span></label>
              <input type="text" id="quantity" name="quantity" placeholder="Ví dụ: 1x40HC, 2x20GP" required>
            </div>
          </div>

          <div class="form-col">
            <div class="form-group">
              <label for="pod">Cảng Đích (POD) <span style="color: red;">*</span></label>
              <input type="text" id="pod" name="pod" required>
            </div>
          </div>
        </div>

        <div class="form-row">
          <div class="form-col">
            <div class="form-group">
              <label for="etd_start">Ngày Khởi Hành Dự Kiến Từ (ETD) <span style="color: red;">*</span></label>
              <input type="text" id="etd_start" name="etd_start" placeholder="dd/mm/yyyy" required>
            </div>
          </div>          <div class="form-col">
            <div class="form-group">
              <label for="etd_end">Đến Ngày</label>
              <input type="text" id="etd_end" name="etd_end" placeholder="dd/mm/yyyy">
            </div>
          </div>
        </div>

        <div class="form-row">
          <div class="form-col">
            <div class="form-group">
              <label for="sales">Sales</label>
              <input type="text" id="sales" name="sales" value="<?php echo $fullName; ?>" readonly>
            </div>
          </div>

          <div class="form-col">
            <div class="form-group">
              <label for="pic">PIC (Người Phụ Trách) <span style="color: red;">*</span></label>
              <select id="pic" name="pic" required>
                <option value="">-- Chọn PIC --</option>
                <?php foreach ($picUsers as $user): ?>
                  <option value="<?php echo $user['email']; ?>"><?php echo $user['fullname']; ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>
        </div>

        <div class="form-row">
          <div class="form-col">
            <div class="form-group">
              <label for="customer">Khách hàng</label>
              <input type="text" id="customer" name="customer">
            </div>
          </div>
        </div>

        <div class="form-row">
          <div class="form-col">
            <div class="form-group">
              <label for="status">Trạng thái <span style="color: red;">*</span></label>
              <select id="status" name="status" required>
                <option value="confirmed">Confirmed</option>
                <option value="pending">Pending</option>
                <option value="cancel">Cancel</option>
              </select>
            </div>
          </div>

          <div class="form-col">
            <div class="form-group">
              <label for="delay_date">Ngày Delay <span style="color: red;">*</span></label>
              <input type="text" id="delay_date" name="delay_date" placeholder="dd/mm/yyyy" required>
            </div>
          </div>
        </div>

        <div class="form-row">
          <div class="form-col">
            <div class="form-group">
              <label for="attachment">File Đính Kèm</label>
              <input type="file" id="attachment" name="attachment" accept="application/pdf">
              <small style="display: block; margin-top: 5px; color: #666;">Chỉ hỗ trợ định dạng PDF</small>
            </div>
          </div>

          <div class="form-col">
            <!-- Placeholder for layout balance -->
          </div>
        </div>

        <div class="form-group">
          <label for="notes">Ghi Chú</label>
          <textarea id="notes" name="notes"></textarea>
        </div>

        <div class="form-actions">
          <a href="all_bookings.php" class="btn btn-cancel">Hủy</a>
          <button type="submit" class="btn">Tạo Booking</button>
        </div>
      </form>
    </div>
  </div>

  <script>
    // Toggle the responsive class to show/hide the menu
    function toggleMenu() {
      var menu = document.querySelector('.menu');
      menu.classList.toggle('responsive');
    }

    // Add date format validation and conversion
    document.addEventListener('DOMContentLoaded', function() {
      // Date input formatting for dd/mm/yyyy
      const dateInputs = document.querySelectorAll('input[name="etd_start"], input[name="etd_end"], input[name="delay_date"]');

      dateInputs.forEach(input => {
        // Add input validation
        input.addEventListener('input', function(e) {
          let value = e.target.value;

          // Remove any character that's not a number or slash
          value = value.replace(/[^\d\/]/g, '');

          // Remove all slashes first
          value = value.replace(/\//g, '');

          // Re-add slashes in the correct positions
          if (value.length > 2) {
            value = value.substring(0, 2) + '/' + value.substring(2);
          }
          if (value.length > 5) {
            value = value.substring(0, 5) + '/' + value.substring(5);
          }

          // Limit the length to ensure we don't exceed dd/mm/yyyy format (10 chars)
          if (value.length > 10) {
            value = value.substring(0, 10);
          }

          e.target.value = value;
        });

        // Validate the date format when leaving the field
        input.addEventListener('blur', function() {
          const value = this.value;
          if (!value) return; // Skip if empty

          // Check if the format is dd/mm/yyyy
          const regex = /^(\d{2})\/(\d{2})\/(\d{4})$/;
          if (!regex.test(value)) {
            alert('Vui lòng nhập ngày theo định dạng dd/mm/yyyy (ví dụ: 15/05/2025)');
            this.value = '';
            return;
          }

          const parts = value.split('/');
          const day = parseInt(parts[0], 10);
          const month = parseInt(parts[1], 10) - 1; // JavaScript months are 0-11
          const year = parseInt(parts[2], 10);

          // Create date object and check if it's valid
          const date = new Date(year, month, day);
          if (
            date.getDate() !== day ||
            date.getMonth() !== month ||
            date.getFullYear() !== year ||
            year < 2000 ||
            year > 2100
          ) {
            alert('Ngày không hợp lệ. Vui lòng kiểm tra lại.');
            this.value = '';
          }
        });
      });

      // Handle form submission - convert dd/mm/yyyy to yyyy-mm-dd for server processing
      document.querySelector('form').addEventListener('submit', function(e) {
        e.preventDefault();

        const startDateInput = document.getElementById('etd_start');
        const endDateInput = document.getElementById('etd_end');
        const delayDateInput = document.getElementById('delay_date');

        if (startDateInput.value) {
          const parts = startDateInput.value.split('/');
          if (parts.length === 3) {
            // Convert from dd/mm/yyyy to yyyy-mm-dd
            const serverFormat = `${parts[2]}-${parts[1]}-${parts[0]}`;
            startDateInput.value = serverFormat;
          }
        }

        if (endDateInput.value) {
          const parts = endDateInput.value.split('/');
          if (parts.length === 3) {
            // Convert from dd/mm/yyyy to yyyy-mm-dd
            const serverFormat = `${parts[2]}-${parts[1]}-${parts[0]}`;
            endDateInput.value = serverFormat;
          }
        }

        if (delayDateInput.value) {
          const parts = delayDateInput.value.split('/');
          if (parts.length === 3) {
            // Convert from dd/mm/yyyy to yyyy-mm-dd
            const serverFormat = `${parts[2]}-${parts[1]}-${parts[0]}`;
            delayDateInput.value = serverFormat;
          }
        }

        this.submit();
      });
    });

    // Validate that end date is not before start date
    document.getElementById('etd_end').addEventListener('change', function() {
      const startDateInput = document.getElementById('etd_start');
      const endDateInput = this;

      if (!startDateInput.value || !endDateInput.value) return;

      const startParts = startDateInput.value.split('/');
      const endParts = endDateInput.value.split('/');

      if (startParts.length !== 3 || endParts.length !== 3) return;

      // Create date objects (day, month, year)
      const startDate = new Date(
        parseInt(startParts[2]),
        parseInt(startParts[1]) - 1,
        parseInt(startParts[0])
      );

      const endDate = new Date(
        parseInt(endParts[2]),
        parseInt(endParts[1]) - 1,
        parseInt(endParts[0])
      );

      if (endDate < startDate) {
        alert("Ngày kết thúc không thể trước ngày bắt đầu.");
        endDateInput.value = '';
      }
    });
  </script>

  <div class="footer">
    <p>© 2024 Phần mềm soffice phát triển bởi Hienlm 0988838487</p>
  </div>
</body>

</html>