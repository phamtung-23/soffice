<?php
session_start();
date_default_timezone_set('Asia/Bangkok'); // Set timezone to UTC+7

// Check if the user is logged in; if not, redirect to login
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'pic') {
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
  $picId = $_POST['pic_email'] ?? '';
  $status = $_POST['status'] ?? 'pending';
  $delayDate = $_POST['delay_date'] ?? '';
  $notes = $_POST['notes'] ?? '';
  $salesId = $_POST['sales'] ?? '';
  $customer = $_POST['customer'] ?? '';

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

      // Move the uploaded file to a temporary location
      if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
        // Upload to Google Drive
        $gDriveFolderId = '175l19YFsHesJmn5yKVO8XV-H5RZp8ron'; // Google Drive folder ID
        $uploadResult = uploadFileToGoogleDriveGeneral($uploadPath, $newFilename, $gDriveFolderId);

        if ($uploadResult) {
          // Use the Google Drive link instead of local path
          $attachmentPath = $uploadResult['link'];
          $attachmentFileId = $uploadResult['id'];
          $hasAttachment = true;

          // Delete the temporary local file
          unlink($uploadPath);
        } else {
          $errorMessage = 'Lỗi: Không thể tải file lên Google Drive. Vui lòng thử lại sau.';
          // Remove local file if it exists
          if (file_exists($uploadPath)) {
            unlink($uploadPath);
          }
        }
      } else {
        $errorMessage = 'Lỗi: Không thể tải file lên. Vui lòng thử lại sau.';
      }
    }
  }

  if (empty($errorMessage)) {
    // Get Sales's name from id
    $salesName = '';
    $salesResult = getUsersByRole('sale');
    if ($salesResult['status'] === 'success') {
      foreach ($salesResult['data'] as $user) {
        if ($user['email'] === $salesId) {
          $salesName = $user['fullname'];
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
      'customer' => $customer,
      'etd_start' => $etdStart,
      'etd_end' => $etdEnd,
      'sales' => $salesName,
      'sales_email' => $salesId,
      'pic' => $fullName,
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
      $bookingData['attachment_file_id'] = $attachmentFileId;
    }    // Save booking data
    $saveResult = saveBookingData($bookingData);

    if ($saveResult['status'] === 'success') {
      $successMessage = 'Đã tạo booking thành công với số: ' . $bookingNumber;
      $createdBookingId = $saveResult['bookingId'];

      // Send Telegram notification to Sales if delay date is entered
      if (!empty($delayDate) && !empty($salesId)) {
        // Get Sales user data to get their Telegram ID
        $salesUserData = null;
        $allUsersData = json_decode(file_get_contents('../database/users.json'), true);

        if (is_array($allUsersData)) {
          foreach ($allUsersData as $user) {
            if ($user['email'] === $salesId) {
              $salesUserData = $user;
              break;
            }
          }
        }

        // If we have the Sales user's data and they have a Telegram ID
        if ($salesUserData && !empty($salesUserData['phone'])) {
          $telegramMessage = "**THÔNG BÁO DELAY BOOKING**\n\n" .
            "📦 Số Booking: " . $bookingNumber . "\n" .
            "🚢 Tên Tàu: " . $vesselName . "\n" .
            "🔢 Số Chuyến: " . $voyageNumber . "\n" .
            "📆 Ngày Delay: " . date('d/m/Y', strtotime($delayDate)) . "\n" .
            "🧳 Số Container: " . $quantity . "\n" .
            "📝 Ghi chú: " . $notes . "\n\n" .
            "👤 Người cập nhật: " . $fullName . "\n" .
            "⏱️ Thời gian cập nhật: " . date('d/m/Y H:i:s');

          // Send Telegram notification
          $telegramData = [
            'message' => $telegramMessage,
            'id_telegram' => $salesUserData['phone']
          ];

          // Use absolute path instead of relative path
          $serverName = $_SERVER['SERVER_NAME'];
          $serverPort = $_SERVER['SERVER_PORT'];
          $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
          $sendTelegramUrl = $protocol . $serverName . ($serverPort != 80 && $serverPort != 443 ? ":" . $serverPort : "") . "/soffice/sendTelegram.php";

          $ch = curl_init($sendTelegramUrl);
          curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
          curl_setopt($ch, CURLOPT_POST, true);
          curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($telegramData));
          curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
          $result = curl_exec($ch);
          $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
          curl_close($ch);

          // Log the Telegram notification
          $logDir = '../logs';
          if (!is_dir($logDir)) {
            mkdir($logDir, 0777, true);
          }

          $timestamp = date('Y-m-d H:i:s');
          $telegramLog = "[$timestamp] TELEGRAM NOTIFICATION SENT\n" .
            "Booking Number: {$bookingNumber}\n" .
            "Recipient: {$salesUserData['fullname']} ({$salesUserData['email']})\n" .
            "Telegram ID: {$salesUserData['phone']}\n" .
            "Message: {$telegramMessage}\n" .
            "Response Code: {$httpCode}\n" .
            "Response: {$result}\n" .
            "------------------------------------\n\n";

          $telegramLogFile = $logDir . '/telegram_notifications.log';
          file_put_contents($telegramLogFile, $telegramLog, FILE_APPEND);
        } else if (!empty($delayDate)) {
          // Log failure to send Telegram notification due to missing ID
          $logDir = '../logs';
          if (!is_dir($logDir)) {
            mkdir($logDir, 0777, true);
          }

          $timestamp = date('Y-m-d H:i:s');
          $telegramErrorLog = "[$timestamp] TELEGRAM NOTIFICATION FAILED\n" .
            "Booking Number: {$bookingNumber}\n" .
            "Recipient: " . ($salesUserData ? $salesUserData['fullname'] . ' (' . $salesUserData['email'] . ')' : 'Unknown user') . "\n" .
            "Reason: " . ($salesUserData ? 'Missing Telegram ID' : 'User not found') . "\n" .
            "------------------------------------\n\n";

          $telegramLogFile = $logDir . '/telegram_notifications.log';
          file_put_contents($telegramLogFile, $telegramErrorLog, FILE_APPEND);
        }
      }
    } else {
      $errorMessage = 'Lỗi: ' . ($saveResult['message'] ?? 'Không thể tạo booking');
    }
  }
}

// Handle duplicate booking pre-fill
$duplicateData = null;
if (isset($_GET['duplicate_id'])) {
    $dupId = $_GET['duplicate_id'];
    $dupYear = isset($_GET['year']) ? $_GET['year'] : date('Y');
    $dupFile = "../database/bookings/bookings_{$dupYear}.json";
    if (file_exists($dupFile)) {
        $json = file_get_contents($dupFile);
        $bookings = json_decode($json, true);
        foreach ($bookings as $b) {
            if ($b['id'] === $dupId) {
                $duplicateData = $b;
                break;
            }
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
    }    .btn-cancel:hover {
      background-color: #d32f2f;
    }
    
    .btn-duplicate {
      background-color: #ff9800;
      color: white;
      margin-left: 10px;
    }
    
    .btn-duplicate:hover {
      background-color: #e68a00;
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
    <a href="../update_idtelegram.php">Cập nhật ID Telegram</a>
    <a href="../logout.php" class="logout">Đăng xuất</a>
  </div>

  <div class="container">
    <div class="welcome-message">
      <p>Xin chào, <?php echo $fullName; ?>!</p>
    </div>

    <div class="content">
      <h2 class="booking-title">Tạo Booking Container Mới</h2>      <?php if ($successMessage): ?>
        <div class="alert alert-success">
          <?php echo $successMessage; ?>
          <?php
          // Extract booking ID from the saveResult for duplication
          if (isset($saveResult['bookingId'])) {
            $createdBookingId = $saveResult['bookingId'];
            echo '<div style="margin-top: 10px;"><a href="create_booking.php?duplicate_id=' . $createdBookingId . '&year=' . date('Y') . '" class="btn btn-duplicate" style="font-size: 14px; padding: 5px 10px;">Duplicate Booking</a></div>';
          }
          ?>
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
              <input type="text" id="booking_number" name="booking_number" placeholder="Nhập số booking" required value="<?php echo isset($duplicateData) ? htmlspecialchars($duplicateData['booking_number']) : ''; ?>">
            </div>
          </div>

          <div class="form-col">
            <div class="form-group">
              <label for="vessel_name">Tên Tàu <span style="color: red;">*</span></label>
              <input type="text" id="vessel_name" name="vessel_name" required value="<?php echo isset($duplicateData) ? htmlspecialchars($duplicateData['vessel_name']) : ''; ?>">
            </div>
          </div>
        </div>

        <div class="form-row">
          <div class="form-col">
            <div class="form-group">
              <label for="voyage_number">Số Chuyến <span style="color: red;">*</span></label>
              <input type="text" id="voyage_number" name="voyage_number" required value="<?php echo isset($duplicateData) ? htmlspecialchars($duplicateData['voyage_number']) : ''; ?>">
            </div>
          </div>

          <div class="form-col">
            <div class="form-group">
              <label for="shipping_line">Hãng Tàu <span style="color: red;">*</span></label>
              <input type="text" id="shipping_line" name="shipping_line" required value="<?php echo isset($duplicateData) ? htmlspecialchars($duplicateData['shipping_line']) : ''; ?>">
            </div>
          </div>
        </div>

        <div class="form-row">
          <div class="form-col">
            <div class="form-group">
              <label for="quantity">Số Lượng Container <span style="color: red;">*</span></label>
              <input type="text" id="quantity" name="quantity" placeholder="Ví dụ: 1x40HC, 2x20GP" required value="<?php echo isset($duplicateData) ? htmlspecialchars($duplicateData['quantity']) : ''; ?>">
            </div>
          </div>

          <div class="form-col">
            <div class="form-group">
              <label for="pod">Cảng Đích (POD) <span style="color: red;">*</span></label>
              <input type="text" id="pod" name="pod" required value="<?php echo isset($duplicateData) ? htmlspecialchars($duplicateData['pod']) : ''; ?>">
            </div>
          </div>
        </div>

        <div class="form-row">
          <div class="form-col">
            <div class="form-group">
              <label for="etd_start">Ngày Khởi Hành Dự KIến Từ (ETD) <span style="color: red;">*</span></label>
              <input type="text" id="etd_start" name="etd_start" placeholder="dd/mm/yyyy" required value="<?php echo isset($duplicateData) && !empty($duplicateData['etd_start']) ? date('d/m/Y', strtotime($duplicateData['etd_start'])) : ''; ?>">
            </div>
          </div>          <div class="form-col">
            <div class="form-group">
              <label for="etd_end">Đến Ngày</label>
              <input type="text" id="etd_end" name="etd_end" placeholder="dd/mm/yyyy" value="<?php echo isset($duplicateData) && !empty($duplicateData['etd_end']) ? date('d/m/Y', strtotime($duplicateData['etd_end'])) : ''; ?>">
            </div>
          </div>
        </div>

        <div class="form-row">
          <div class="form-col">
            <div class="form-group">
              <label for="sales">Sales (Người Bán Hàng) <span style="color: red;">*</span></label>
              <select id="sales" name="sales" required>
                <option value="">-- Chọn Sales --</option>
                <?php
                $salesUsersResult = getUsersByRole('sale');
                if ($salesUsersResult['status'] === 'success') {
                  foreach ($salesUsersResult['data'] as $user):
                    $selected = (isset($duplicateData) && isset($duplicateData['sales_email']) && $duplicateData['sales_email'] === $user['email']) ? 'selected' : '';
                ?>
                    <option value="<?php echo $user['email']; ?>" <?php echo $selected; ?>><?php echo $user['fullname']; ?></option>
                <?php
                  endforeach;
                }
                ?>
              </select>
            </div>
          </div>

          <div class="form-col">
            <div class="form-group">
              <label for="pic">PIC (Người Phụ Trách)</label>
              <input type="text" id="pic" name="pic" value="<?php echo $fullName; ?>" readonly>
              <input type="hidden" id="pic_email" name="pic_email" value="<?php echo $userEmail; ?>">
            </div>
          </div>
        </div>

        <div class="form-row">
          <div class="form-col">
            <div class="form-group">
              <label for="customer">Khách hàng (Customer)</label>
              <input type="text" id="customer" name="customer" placeholder="Nhập tên khách hàng" value="<?php echo isset($duplicateData) ? htmlspecialchars($duplicateData['customer']) : ''; ?>">
            </div>
          </div>

          <div class="form-col"><!-- empty for layout --></div>
        </div>

        <div class="form-row">
          <div class="form-col">
            <div class="form-group">
              <label for="status">Trạng Thái <span style="color: red;">*</span></label>
              <select id="status" name="status" required>
                <option value="pending" <?php echo (isset($duplicateData) && $duplicateData['status'] == 'pending') ? 'selected' : ''; ?>>Pending</option>
                <option value="confirmed" <?php echo (isset($duplicateData) && $duplicateData['status'] == 'confirmed') ? 'selected' : ''; ?>>Confirmed</option>
                <option value="cancel" <?php echo (isset($duplicateData) && $duplicateData['status'] == 'cancel') ? 'selected' : ''; ?>>Cancel</option>
              </select>
            </div>
          </div>

          <div class="form-col">
            <div class="form-group">
              <label for="delay_date">Ngày Delay</label>
              <input type="text" id="delay_date" name="delay_date" placeholder="dd/mm/yyyy" value="<?php echo isset($duplicateData) && !empty($duplicateData['delay_date']) ? date('d/m/Y', strtotime($duplicateData['delay_date'])) : ''; ?>">
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
          <textarea id="notes" name="notes"><?php echo isset($duplicateData) ? htmlspecialchars($duplicateData['notes']) : ''; ?></textarea>
        </div>        <div class="form-actions">
          <a href="index.php" class="btn btn-cancel">Hủy</a>
          <button type="submit" class="btn">Tạo Booking</button>          <?php if (isset($saveResult['bookingId'])): ?>
          <a href="create_booking.php?duplicate_id=<?php echo $saveResult['bookingId']; ?>&year=<?php echo date('Y'); ?>" class="btn btn-duplicate">Duplicate This Booking</a>
          <?php endif; ?>
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