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
$instructionNo = $_POST['instruction_no'] ?? null;
$status = $_POST['approval_status'] ?? null;
$message = $_POST['message'] ?? null;

$amount = $data['amount'] ?? null;

// Check if instruction number and status are provided
if ($instructionNo === null || $status === null) {
  echo json_encode(['success' => false, 'message' => 'Invalid data']);
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

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
  // Update the status for the matching instruction number
  $updated = false;
  $entry['amount'] = $amount;
  $month = date('m'); // Lấy tháng hiện tại
  $year = date('Y');  // Lấy năm hiện tại
  $pdfFileName = 'Phieu de nghi thanh toan_id_' . $entry['id'] . '_time_' . $month . '_' . $year . '.pdf';
  $entry['file_path'] = $pdfFileName;
  // Collect expense information
  $newExpenses = [];
  if (isset($_POST['expense_kind'], $_POST['expense_amount'], $_POST['so_hoa_don'], $_POST['expense_payee'], $_POST['expense_doc'])) {
    for ($i = 0; $i < count($_POST['expense_kind']); $i++) {
      $expenseAmount = (float)str_replace('.', '', $_POST['expense_amount'][$i] ?? $entry['expenses'][$i]['expense_amount'] ?? "");
      $soHoaDon = $_POST['so_hoa_don'][$i] ?? $entry['expenses'][$i]['so_hoa_don'] ?? "";
      $expenseFile = $entry['expenses'][$i]['expense_files'] ?? "";
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

  // get data payment
  // Extract custom fields
  $customFieldNames = $_POST['customFieldName'] ?? [];
  $customFields = $_POST['customField'] ?? [];
  $customUnits = $_POST['customUnit'] ?? [];
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
      'unit' => $customUnits[$index] ?? '',
      'vat' => $customVats[$index] ?? '',
      'contSet' => isset($customContSetRadios[$index]) && $customContSetRadios[$index] === 'cont' ? 'cont' : 'set',
      'incl' => $customIncl[$index] ?? '',
      'excl' => $customExcl[$index] ?? ''
    ];
  }
  // Save to entry
  $entry['payment'] = $customData;

  // Update total actual
  $entry['total_actual'] = (float)str_replace('.', '', $entry['total_actual'] ?? '0');

  // Update approval status
  foreach ($entry['approval'] as &$approval) {
    if ($approval['role'] === 'director') {
      $approval['status'] = $status;
      $approval['email'] = $_SESSION['user_id'];
      $approval['time'] = date("Y-m-d H:i:s"); // Update with current timestamp
      $approval['comment'] = $message;
      $updated = true;
      break;
    }
  }

  // Read and increment ID
  $idFile = '../../../../../private_data/soffice_database/payment/v_id.json';
  $jsonDataIdPayment = file_get_contents($idFile);
  $dataIdPayment = json_decode($jsonDataIdPayment, true);
  $newIdPayment = $dataIdPayment[$year]["id"] + 1;
  $dataIdPayment[$year]["id"] = $newIdPayment;
  file_put_contents($idFile, json_encode($dataIdPayment));

  $entry['v_id'] = formatNumberVID($newIdPayment);

  // add history
  $entry['history'][] = [
    'actor' => $_SESSION['user_id'],
    'time' => date('Y-m-d H:i:s'),
    'action' => 'Director approved',
  ];
}


// // Update the status for the matching instruction number
// $updated = false;
// foreach ($jsonData as &$entry) {
//   if ($entry['instruction_no'] == $instructionNo) {
//     $entry['amount'] = $amount;

//     $month = date('m'); // Lấy tháng hiện tại
//     $year = date('Y');  // Lấy năm hiện tại
//     $pdfFileName = 'Phieu de nghi thanh toan_id_' . $entry['id'] . '_time_' . $month . '_' . $year . '.pdf';
//     $entry['file_path'] = $pdfFileName;
//     foreach ($entry['approval'] as &$approval) {
//       if ($approval['role'] === 'director') {
//         $approval['email'] = $_SESSION['user_id'];
//         $approval['status'] = $status;
//         $approval['time'] = date("Y-m-d H:i:s"); // Update with current timestamp
//         $approval['comment'] = $message;
//         $updated = true;
//         break;
//       }
//     }
//     break;
//   }
// }

if ($updated) {
  // update payment status
  $statusFilePath = '../../../../../private_data/soffice_database/payment/status/' . $year . '';
  updateStatusFile('director', 'approved', $instructionNo, $statusFilePath);
  updateStatusFile('accountant', 'pending', $instructionNo, $statusFilePath);
  // Save the updated JSON data back to the file
  $directory = '../../../../../private_data/soffice_database/payment/data/' . $year;
  $res = updateDataToJson($entry, $directory, 'payment_' . $instructionNo);
  echo json_encode(['success' => true, 'message' => 'Status updated successfully', 'data' => $res['data'] ?? []]);
} else {
  echo json_encode(['success' => false, 'message' => 'Approval entry not found or already updated']);
}
