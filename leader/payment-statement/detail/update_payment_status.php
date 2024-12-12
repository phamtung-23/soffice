<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'leader') {
  echo json_encode(['success' => false, 'message' => 'Unauthorized']);
  exit();
}

include('../../../helper/payment.php');
include('../../../helper/general.php');

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

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
  // Update the status for the matching instruction number
  $updated = false;
  foreach ($jsonData as &$entry) {
    if ($entry['instruction_no'] == $instructionNo) {
      // Collect expense information
      $newExpenses = [];
      if (isset($_POST['expense_kind'], $_POST['expense_amount'], $_POST['so_hoa_don'], $_POST['expense_payee'], $_POST['expense_doc'])) {
        for ($i = 0; $i < count($_POST['expense_kind']); $i++) {
          $expenseAmount = (float)str_replace('.', '', $_POST['expense_amount'][$i] ?? $entry['expenses'][$i]['expense_amount'] ?? "");
          $soHoaDon = $_POST['so_hoa_don'][$i] ?? $entry['expenses'][$i]['so_hoa_don'] ?? "";
          $expenseFile = $entry['expenses'][$i]['expense_files'] ?? [];
          // Store expense data
          $expense = [
            'expense_kind' => $_POST['expense_kind'][$i] ?? $entry['expenses'][$i]['expense_kind'] ?? null,
            'expense_amount' => $expenseAmount,
            'so_hoa_don' => $soHoaDon,
            'expense_payee' => $_POST['expense_payee'][$i] ?? $entry['expenses'][$i]['expense_payee'] ?? "",
            'expense_doc' => $_POST['expense_doc'][$i] ?? $entry['expenses'][$i]['expense_doc'] ?? "",
            'expense_files' => $expenseFile
          ];

          $newExpenses[] = $expense;
        }
      }

      if (empty($newExpenses)) {
        $newExpenses = $entry['expenses'];
      }

      $entry['expenses'] = $newExpenses;

      $fieldIgnore = ['expense_kind', 'expense_amount', 'so_hoa_don', 'expense_payee', 'expense_doc', 'customFieldName', 'customField', 'customVat', 'customContSet', 'customIncl', 'customExcl'];

      // Additional fields
      foreach ($_POST as $key => $value) {
        if ($key == "leader" || $key == "sale" || $key == "approval_status" || $key == "message" || $key == "instruction_no") {
          continue;
        } elseif (!in_array($key, $fieldIgnore)) {
          $entry[$key] = is_array($value) ? $value : trim($value);
        }
      }

      $entry['total_actual'] = (float)str_replace('.', '', $entry['total_actual'] ?? '0');


      // get data payment
      // Extract custom fields
      $customFieldNames = $_POST['customFieldName'] ?? [];
      $customFields = $_POST['customField'] ?? [];
      $customVats = $_POST['customVat'] ?? [];
      $customContSetRadios = $_POST['customContSet'] ?? [];
      $customIncl = $_POST['customIncl'] ?? [];
      $customExcl = $_POST['customExcl'] ?? [];

      // Prepare an array to store custom fields
      $customData = [];

      logEntry("customInclude: " . json_encode($customIncl));
      logEntry("customExcl: " . json_encode($customExcl));

      foreach ($customFieldNames as $index => $name) {
        logEntry("Processing custom field: $name");
        $customData[] = [
          'name' => $name,
          'value' => (float)str_replace('.', '', $customFields[$index]),
          'vat' => $customVats[$index] ?? '',
          'contSet' => isset($customContSetRadios[$index]) && $customContSetRadios[$index] === 'cont' ? 'cont' : 'set',
          'incl' => $customIncl[$index] ?? '',
          'excl' => $customExcl[$index] ?? ''
        ];
      }

      if (empty($customData)) {
        $customData = $entry['payment'];
      }
      // Save to entry
      $entry['payment'] = $customData;

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
