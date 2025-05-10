<?php
session_start();

// Check if the user is logged in; if not, redirect to login
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'pic') {
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
$bookingIndex = -1;
$bookings = [];
$yearFile = "../database/bookings/bookings_{$year}.json";

if (file_exists($yearFile)) {
  $jsonContent = file_get_contents($yearFile);
  $bookings = json_decode($jsonContent, true);

  if (is_array($bookings)) {
    // Find the specific booking by ID and its index
    foreach ($bookings as $index => $bookingData) {
      if (isset($bookingData['id']) && $bookingData['id'] === $bookingId) {
        $booking = $bookingData;
        $bookingIndex = $index;
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
        foreach ($checkBookings as $index => $bookingData) {
          if (isset($bookingData['id']) && $bookingData['id'] === $bookingId) {
            $booking = $bookingData;
            $bookingIndex = $index;
            $year = $checkYear; // Update the year for saving purposes
            $bookings = $checkBookings;
            $yearFile = $checkFile;
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

// Process form submission
$successMessage = '';
$errorMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // Get form data
  $newBookingNumber = $_POST['booking_number'] ?? '';
  $vesselName = $_POST['vessel_name'] ?? '';
  $voyageNumber = $_POST['voyage_number'] ?? '';
  $shippingLine = $_POST['shipping_line'] ?? '';
  $quantity = $_POST['quantity'] ?? '';
  $pod = $_POST['pod'] ?? '';
  $etdStart = $_POST['etd_start'] ?? '';
  $etdEnd = $_POST['etd_end'] ?? '';
  $picId = $_POST['pic_email'] ?? '';
  $status = $_POST['status'] ?? 'pending';
  $notes = $_POST['notes'] ?? '';
  $delayDate = $_POST['delay_date'] ?? '';
  $salesId = $_POST['sales'] ?? ''; // Add this line to capture sales from form

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

  // Create attachments directory if it doesn't exist
  $attachmentsDir = '../database/bookings/attachments';
  if (!is_dir($attachmentsDir)) {
    mkdir($attachmentsDir, 0777, true);
  }

  // Handle file upload
  $hasNewAttachment = false;
  $attachmentPath = isset($booking['attachment']) ? $booking['attachment'] : '';
  $attachmentFileId = isset($booking['attachment_file_id']) ? $booking['attachment_file_id'] : '';

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
      $newFilename = $booking['id'] . '_' . date('Ymd') . '.' . $fileExt;
      $uploadPath = $attachmentsDir . '/' . $newFilename;

      if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
        // Delete old file from Google Drive if it exists
        if (!empty($attachmentFileId)) {
          $deleteResult = deleteFileFromGoogleDrive($attachmentFileId);
          if ($deleteResult) {
            // Log deletion success if needed
            error_log("Successfully deleted old file from Google Drive: " . $attachmentFileId);
          } else {
            // Log deletion failure but continue with upload
            error_log("Failed to delete old file from Google Drive: " . $attachmentFileId);
          }
        }

        // Upload to Google Drive
        $gDriveFolderId = '175l19YFsHesJmn5yKVO8XV-H5RZp8ron'; // Google Drive folder ID
        $uploadResult = uploadFileToGoogleDrive($uploadPath, $newFilename, $gDriveFolderId);

        if ($uploadResult) {
          // Use the Google Drive link instead of local path
          $attachmentPath = $uploadResult['link'];
          $attachmentFileId = $uploadResult['id'];
          $hasNewAttachment = true;

          // Delete the temporary local file
          if (file_exists($uploadPath)) {
            unlink($uploadPath);
          }
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

  // Create updated booking data structure
  $updatedBooking = $booking; // Start with existing data

  // Update fields
  $updatedBooking['booking_number'] = $newBookingNumber; // Update booking number
  $updatedBooking['vessel_name'] = $vesselName;
  $updatedBooking['voyage_number'] = $voyageNumber;
  $updatedBooking['shipping_line'] = $shippingLine;
  $updatedBooking['quantity'] = $quantity;
  $updatedBooking['pod'] = $pod;
  $updatedBooking['etd_start'] = $etdStart;
  $updatedBooking['etd_end'] = $etdEnd;
  $updatedBooking['pic'] = $picName;
  $updatedBooking['pic_email'] = $picId;
  $updatedBooking['sales'] = $salesName;
  $updatedBooking['sales_email'] = $salesId;
  $updatedBooking['status'] = $status;
  $updatedBooking['notes'] = $notes;
  $updatedBooking['delay_date'] = $delayDate;
  $updatedBooking['attachment'] = $attachmentPath;
  $updatedBooking['attachment_file_id'] = $attachmentFileId;
  $updatedBooking['updated_at'] = date('Y-m-d H:i:s');

  // Update the booking in the array
  $bookings[$bookingIndex] = $updatedBooking;

  // Save updated data back to the file
  if (file_put_contents($yearFile, json_encode($bookings, JSON_PRETTY_PRINT))) {
    $successMessage = 'Đã cập nhật thông tin booking thành công: ' . $newBookingNumber;

    // Log the booking update
    $logDir = '../logs';

    // Create logs directory if it doesn't exist
    if (!is_dir($logDir)) {
      mkdir($logDir, 0777, true);
    }

    // Create log entry
    $timestamp = date('Y-m-d H:i:s');

    $booking = $updatedBooking; // Update local variable for display

    // If booking number was changed, update the URL parameter
    if ($newBookingNumber !== $bookingId) {
      $bookingId = $newBookingNumber; // Update for use in page title and links
    }

    // Send Telegram notification to Sales if delay date is entered or updated
    $hasDelayDateChanged = !empty($delayDate);

    if ($hasDelayDateChanged && !empty($salesId)) {
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
          "📦 Số Booking: " . $newBookingNumber . "\n" .
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

        // Log only to telegram log file
        $telegramLog = "[$timestamp] TELEGRAM NOTIFICATION SENT\n" .
          "Booking Number: {$newBookingNumber}\n" .
          "Recipient: {$salesUserData['fullname']} ({$salesUserData['email']})\n" .
          "Telegram ID: {$salesUserData['phone']}\n" .
          "Message: {$telegramMessage}\n" .
          "Response Code: {$httpCode}\n" .
          "Response: {$result}\n" .
          "------------------------------------\n\n";

        $telegramLogFile = $logDir . '/telegram_notifications.log';
        // uncomment the line below to log to file
        // file_put_contents($telegramLogFile, $telegramLog, FILE_APPEND);
      } else {
        // Log failure to send Telegram notification due to missing ID
        $telegramErrorLog = "[$timestamp] TELEGRAM NOTIFICATION FAILED\n" .
          "Booking Number: {$newBookingNumber}\n" .
          "Recipient: " . ($salesUserData ? $salesUserData['fullname'] . ' (' . $salesUserData['email'] . ')' : 'Unknown user') . "\n" .
          "Reason: " . ($salesUserData ? 'Missing Telegram ID' : 'User not found') . "\n" .
          "------------------------------------\n\n";

        $telegramLogFile = $logDir . '/telegram_notifications.log';
        file_put_contents($telegramLogFile, $telegramErrorLog, FILE_APPEND);
      }
    }
  } else {
    $errorMessage = 'Lỗi: Không thể cập nhật thông tin booking';

    // Log the error
    $logDir = '../logs';
    $logFile = $logDir . '/booking_errors.log';

    // Create logs directory if it doesn't exist
    if (!is_dir($logDir)) {
      mkdir($logDir, 0777, true);
    }

    // Create error log entry
    $timestamp = date('Y-m-d H:i:s');
    $errorLogEntry = "[$timestamp] BOOKING UPDATE ERROR\n" .
      "User: {$fullName} ({$userEmail})\n" .
      "Booking Number: {$newBookingNumber}\n" .
      "Error: Could not write to file: {$yearFile}\n" .
      "------------------------------------\n\n";

    // Append to log file
    file_put_contents($logFile, $errorLogEntry, FILE_APPEND);
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
  <title>Cập nhật thông tin Booking: <?php echo htmlspecialchars($bookingId); ?></title>
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
    textarea {
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
    <h1>Cập nhật thông tin Booking Container</h1>
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
      <div class="booking-header">
        <div class="booking-title">
          <h2>Cập nhật Booking: <?php echo htmlspecialchars($booking['booking_number']); ?></h2>
          <p>ID: <?php echo isset($booking['id']) ? $booking['id'] : 'N/A'; ?></p>
        </div>
      </div>

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

      <form method="POST" action="" enctype="multipart/form-data">
        <div class="form-row">
          <div class="form-col">
            <div class="form-group">
              <label for="booking_number">Số Booking <span style="color: red;">*</span></label>
              <input type="text" id="booking_number" name="booking_number" value="<?php echo htmlspecialchars($booking['booking_number']); ?>" required>
            </div>
          </div>

          <div class="form-col">
            <div class="form-group">
              <label for="vessel_name">Tên Tàu <span style="color: red;">*</span></label>
              <input type="text" id="vessel_name" name="vessel_name" value="<?php echo htmlspecialchars($booking['vessel_name']); ?>" required>
            </div>
          </div>
        </div>

        <div class="form-row">
          <div class="form-col">
            <div class="form-group">
              <label for="voyage_number">Số Chuyến <span style="color: red;">*</span></label>
              <input type="text" id="voyage_number" name="voyage_number" value="<?php echo htmlspecialchars($booking['voyage_number']); ?>" required>
            </div>
          </div>

          <div class="form-col">
            <div class="form-group">
              <label for="shipping_line">Hãng Tàu <span style="color: red;">*</span></label>
              <input type="text" id="shipping_line" name="shipping_line" value="<?php echo htmlspecialchars($booking['shipping_line']); ?>" required>
            </div>
          </div>
        </div>

        <div class="form-row">
          <div class="form-col">
            <div class="form-group">
              <label for="quantity">Số Lượng Container <span style="color: red;">*</span></label>
              <input type="text" id="quantity" name="quantity" placeholder="Ví dụ: 1x40HC, 2x20GP" value="<?php echo htmlspecialchars($booking['quantity']); ?>" required>
            </div>
          </div>

          <div class="form-col">
            <div class="form-group">
              <label for="pod">Cảng Đích (POD) <span style="color: red;">*</span></label>
              <input type="text" id="pod" name="pod" value="<?php echo htmlspecialchars($booking['pod']); ?>" required>
            </div>
          </div>
        </div>

        <div class="form-row">
          <div class="form-col">
            <div class="form-group">
              <label for="etd_start">Ngày Khởi Hành Dự Kiến Từ (ETD) <span style="color: red;">*</span></label>
              <input type="text" id="etd_start" name="etd_start" placeholder="dd/mm/yyyy" value="<?php echo isset($booking['etd_start']) ? date('d/m/Y', strtotime($booking['etd_start'])) : (isset($booking['etd']) ? date('d/m/Y', strtotime($booking['etd'])) : ''); ?>" required>
            </div>
          </div>

          <div class="form-col">
            <div class="form-group">
              <label for="etd_end">Đến Ngày <span style="color: red;">*</span></label>
              <input type="text" id="etd_end" name="etd_end" placeholder="dd/mm/yyyy" value="<?php echo isset($booking['etd_end']) ? date('d/m/Y', strtotime($booking['etd_end'])) : (isset($booking['etd']) ? date('d/m/Y', strtotime($booking['etd'])) : ''); ?>" required>
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
                ?>
                    <option value="<?php echo $user['email']; ?>" <?php echo (isset($booking['sales_email']) && $booking['sales_email'] === $user['email']) ? 'selected' : ''; ?>>
                      <?php echo $user['fullname']; ?>
                    </option>
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
              <label for="status">Trạng Thái <span style="color: red;">*</span></label>
              <select id="status" name="status" required>
                <option value="pending" <?php echo strtolower($booking['status']) === 'pending' ? 'selected' : ''; ?>>Pending</option>
                <option value="confirmed" <?php echo strtolower($booking['status']) === 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                <option value="rejected" <?php echo strtolower($booking['status']) === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
              </select>
            </div>
          </div>

          <div class="form-col">
            <div class="form-group">
              <label for="delay_date">Ngày Delay</label>
              <input type="text" id="delay_date" name="delay_date" placeholder="dd/mm/yyyy" value="<?php echo isset($booking['delay_date']) && !empty($booking['delay_date']) ? date('d/m/Y', strtotime($booking['delay_date'])) : ''; ?>">
            </div>
          </div>
        </div>

        <div class="form-row">
          <div class="form-col">
            <div class="form-group">
              <label for="attachment">File Đính Kèm</label>
              <input type="file" id="attachment" name="attachment" accept="application/pdf">
              <small style="display: block; margin-top: 5px; color: #666;">Chỉ hỗ trợ định dạng PDF</small>
              <?php if (isset($booking['attachment']) && !empty($booking['attachment'])): ?>
                <p style="margin-top: 10px; font-size: 14px;">
                  File hiện tại: <a href="<?php echo htmlspecialchars($booking['attachment']); ?>" target="_blank">Xem file</a>
                </p>
              <?php endif; ?>
            </div>
          </div>

          <div class="form-col">
            <!-- Placeholder for layout balance -->
          </div>
        </div>

        <div class="form-group">
          <label for="notes">Ghi Chú</label>
          <textarea id="notes" name="notes"><?php echo htmlspecialchars($booking['notes'] ?? ''); ?></textarea>
        </div>

        <div class="form-actions">
          <!-- <a href="booking_details.php?id=<?php echo urlencode($booking['id']); ?>&year=<?php echo $year; ?>" class="btn btn-cancel">Hủy</a> -->
          <a href="index.php" class="btn btn-cancel">Hủy</a>
          <button type="submit" class="btn">Cập nhật</button>
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

    // Also check when changing start date
    document.getElementById('etd_start').addEventListener('change', function() {
      const startDateInput = this;
      const endDateInput = document.getElementById('etd_end');

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