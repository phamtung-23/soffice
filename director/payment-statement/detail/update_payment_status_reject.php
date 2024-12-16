<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'director') {
  echo json_encode(['success' => false, 'message' => 'Unauthorized']);
  exit();
}

include '../../../helper/general.php';

function logEntry($message)
{
  $logFile = '../../../logs/payment_update_log.txt';
  $timestamp = date("Y-m-d H:i:s");
  // get full path
  $filePath = $_SERVER['PHP_SELF'];
  $logMessage = "[$timestamp] $filePath: $message\n";
  file_put_contents($logFile, $logMessage, FILE_APPEND);
}

header('Content-Type: application/json');

// Get data from POST request
$data = json_decode(file_get_contents("php://input"), true);
$instructionNo = $data['instruction_no'] ?? null;
$status = $data['approval_status'] ?? null;
$message = $data['message'] ?? null;
$amount = $data['amount'] ?? null;

// Check if instruction number and status are provided
if ($instructionNo === null || $status === null) {
  echo json_encode(['success' => false, 'message' => 'Invalid data']);
  exit();
}


// Define file path
$year = date('Y');
$filePath = "../../../database/payment/data/$year/payment_$instructionNo.json";

// Check if file exists
if (!file_exists($filePath)) {
  echo json_encode(['success' => false, 'message' => 'Data file not found']);
  exit();
}

// Load JSON data
$paymentIdRes = getDataFromJson($filePath);
$entry = $paymentIdRes['data'];

// Update the status for the matching instruction number
$updated = false;
$entry['amount'] = $amount;

// $month = date('m'); // Lấy tháng hiện tại
// $year = date('Y');  // Lấy năm hiện tại
// $pdfFileName = 'Phieu de nghi thanh toan_id_' . $entry['id'] . '_time_' . $month . '_' . $year . '.pdf';
// $entry['file_path'] = $pdfFileName;

// add history
$entry['history'][] = [
  'actor' => $_SESSION['user_id'],
  'time' => date('Y-m-d H:i:s'),
  'action' => 'Director rejected',
];
foreach ($entry['approval'] as &$approval) {
  if ($approval['role'] === 'director') {
    $approval['email'] = $_SESSION['user_id'];
    $approval['status'] = $status;
    $approval['time'] = date("Y-m-d H:i:s"); // Update with current timestamp
    $approval['comment'] = $message;
    $updated = true;
    break;
  }
}

if ($updated) {
  // update payment status
  $statusFilePath = '../../../database/payment/status/' . $year . '';
  updateStatusFile('director', 'rejected', $instructionNo, $statusFilePath);
  // Save the updated JSON data back to the file
  $directory = '../../../database/payment/data/' . $year;
  $res = updateDataToJson($entry, $directory, 'payment_' . $instructionNo);
  echo json_encode(['success' => true, 'message' => 'Status updated successfully', 'data' => $res['data'] ?? []]);
  logEntry("Payment status updated for instruction number: $instructionNo");
} else {
  echo json_encode(['success' => false, 'message' => 'Approval entry not found or already updated']);
}
