<?php
session_start();

// Check if the user is logged in; if not, redirect to login
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'sale') {
  echo "<script>alert('Bạn chưa đăng nhập! Vui lòng đăng nhập lại.'); window.location.href = '../index.php';</script>";
  exit();
}

include('../helper/general.php');

// Retrieve full name and email from session
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

// Store booking number for the success message
$bookingNumber = $booking['booking_number'];

// Check if there's an attachment file and delete it
if (isset($booking['attachment']) && !empty($booking['attachment'])) {
  $attachmentPath = "../database/bookings/" . $booking['attachment'];
  if (file_exists($attachmentPath)) {
    unlink($attachmentPath);
  }
}

// Remove the booking from the array
array_splice($bookings, $bookingIndex, 1);

// Save the updated array back to the JSON file
if (file_put_contents($yearFile, json_encode($bookings, JSON_PRETTY_PRINT))) {
  // Success - redirect to booking list with success message
  $_SESSION['delete_success'] = "Đã xóa booking số {$bookingNumber} thành công.";
} else {
  // Error - redirect to booking list with error message
  $_SESSION['delete_error'] = "Không thể xóa booking số {$bookingNumber}. Vui lòng thử lại sau.";
}

// Redirect back to the booking list page
header("Location: all_bookings.php?year={$year}");
exit();
?>