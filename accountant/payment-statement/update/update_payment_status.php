<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'accountant') {
  echo json_encode(['success' => false, 'message' => 'Unauthorized']);
  exit();
}

include '../../../helper/general.php';

header('Content-Type: application/json');

// Get data from POST request
$data = json_decode(file_get_contents("php://input"), true);
$instructionNo = $data['instruction_no'] ?? null;
$status = $data['approval_status'] ?? null;
$message = $data['message'] ?? null;
$groupedTotals = $data['grouped_totals'] ?? null; // Get grouped totals from the request

// Check if instruction number and status are provided
if ($instructionNo === null || $status === null) {
  echo json_encode(['success' => false, 'message' => 'Invalid data']);
  exit();
}

// Validate grouped totals
if ((!is_array($groupedTotals) || count($groupedTotals) === 0) && $status !== 'rejected') {
  echo json_encode(['success' => false, 'message' => 'Vui lòng chọn nhóm chi tiền!']);
  exit();
}

// Define file path
$year = date('Y');
$filePath = "../../../../../private_data/soffice_database/payment/data/$year/payment_$instructionNo.json";

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

$month = date('m'); // Lấy tháng hiện tại
$year = date('Y');  // Lấy năm hiện tại

foreach ($entry['approval'] as &$approval) {
  if ($approval['role'] === 'accountant') {
    $approval['email'] = $_SESSION['user_id'];
    $approval['status'] = $status;
    $approval['time'] = date("Y-m-d H:i:s"); // Update with current timestamp
    $approval['comment'] = $message;
    $updated = true;
    break;
  }
}

// Add history
$entry['history'][] = [
  'actor' => $_SESSION['user_id'],
  'time' => date('Y-m-d H:i:s'),
  'action' => 'Accountant ' . $status,
];

// Save grouped totals
if ($status === 'approved') {
  $pdfFileName = 'Phieu de nghi thanh toan_id_' . $entry['id'] . '_time_' . $month . '_' . $year . '.pdf';

  $entry['file_path'] = $pdfFileName;
  $entry['grouped_totals'] = $groupedTotals; // Add grouped totals to the JSON data
}

if ($updated) {
  // Update payment status
  $statusFilePath = '../../../../../private_data/soffice_database/payment/status/' . $year;
  updateStatusFile('accountant', $status, $instructionNo, $statusFilePath);
  
  // Save the updated JSON data back to the file
  $directory = '../../../../../private_data/soffice_database/payment/data/' . $year;
  $res = updateDataToJson($entry, $directory, 'payment_' . $instructionNo);
  echo json_encode(['success' => true, 'message' => 'Status updated successfully', 'data' => $res['data'] ?? []]);
} else {
  echo json_encode(['success' => false, 'message' => 'Approval entry not found or already updated']);
}