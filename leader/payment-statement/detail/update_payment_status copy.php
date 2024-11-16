<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'leader') {
  echo json_encode(['success' => false, 'message' => 'Unauthorized']);
  exit();
}

function logEntry($message) {
  $logFile = 'logs/payment_update_log.txt';
  $timestamp = date("Y-m-d H:i:s");
  $logMessage = "[$timestamp] $message\n";
  file_put_contents($logFile, $logMessage, FILE_APPEND);
}

header('Content-Type: application/json');

// Get data from POST request
$instructionNo = $_POST['instruction_no'] ?? null;
$status = $_POST['approval_status'] ?? null;
$message = $_POST['message'] ?? null;

// Check if instruction number and status are provided
if ($instructionNo === null || $status === null) {
  echo json_encode(['success' => false, 'message' => 'Invalid data']);
  exit();
}

// Define file path
$year = date('Y');
$filePath = "../../../database/payment_$year.json";

// Check if file exists
if (!file_exists($filePath)) {
  echo json_encode(['success' => false, 'message' => 'Data file not found']);
  exit();
}

// Load JSON data
$jsonData = json_decode(file_get_contents($filePath), true);

// Update the status for the matching instruction number
$updated = false;
foreach ($jsonData as &$entry) {
  if ($entry['instruction_no'] == $instructionNo) {
    // Collect expense information
    if (isset($_POST['expense_kind'], $_POST['expense_amount'], $_POST['so_hoa_don'], $_POST['expense_payee'], $_POST['expense_doc'])) {
      for ($i = 0; $i < count($_POST['expense_kind']); $i++) {
        $expenseAmount = (float)str_replace(',', '', $_POST['expense_amount'][$i] ?? $entry['expenses'][$i]['expense_amount'] ?? null);
        $soHoaDon = $_POST['so_hoa_don'][$i] ?? $entry['expenses'][$i]['so_hoa_don'] ?? null;
        $expenseFile = $entry['expenses'][$i]['expense_file'] ?? null;

        logEntry("Processing expense for instruction_no=$instructionNo, expense_kind={$_POST['expense_kind'][$i]}, expense_amount=$expenseAmount, so_hoa_don=$soHoaDon");
        // Store expense data
        $expense = [
          'expense_kind' => $_POST['expense_kind'][$i] ?? $entry['expenses'][$i]['expense_kind'] ?? null,
          'expense_amount' => $expenseAmount,
          'so_hoa_don' => $soHoaDon,
          'expense_payee' => $_POST['expense_payee'][$i] ?? $entry['expenses'][$i]['expense_payee'] ?? null,
          'expense_doc' => $_POST['expense_doc'][$i] ?? $entry['expenses'][$i]['expense_doc'] ?? null,
          'expense_file' => $expenseFile
        ];

        $entry['expenses'][] = $expense;
      }
    }

    // Additional fields
    foreach ($_POST as $key => $value) {
      if ($key == "leader" || $key == "sale" || $key == "status" || $key == "message" || $key == "instruction_no") {
        continue;
      } elseif (!in_array($key, ['expense_kind', 'expense_amount', 'so_hoa_don', 'expense_payee', 'expense_doc'])) {
        $data[$key] = is_array($value) ? $value : trim($value);
      }
    }

    foreach ($entry['approval'] as &$approval) {
      if ($approval['role'] === 'leader' && $approval['email'] === $_SESSION['user_id']) {
        $approval['status'] = $status;
        $approval['time'] = date("Y-m-d H:i:s"); // Update with current timestamp
        $approval['comment'] = $message;
        $updated = true;
        break;
      }
    }
    break;
  }
}

if ($updated) {
  // Save the updated JSON data back to the file
  foreach ($jsonData as &$entry) {
    if ($entry['instruction_no'] == $instructionNo) {
      $updatedData = $entry;
      break;
    }
  }
  // Save the updated JSON data back to the file
  file_put_contents($filePath, json_encode($jsonData, JSON_PRETTY_PRINT));
  echo json_encode(['success' => true, 'message' => 'Status updated successfully', 'data' => $updatedData]);
} else {
  echo json_encode(['success' => false, 'message' => 'Approval entry not found or already updated']);
}
