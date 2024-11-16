<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'operator') {
  echo json_encode(['success' => false, 'message' => 'Unauthorized']);
  exit();
}

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
  $errors = [];
  $targetDir = '../../../database/payment/uploads/';

  // Create uploads directory if not exists
  if (!is_dir($targetDir)) {
    mkdir($targetDir, 0777, true);
  }

  foreach ($jsonData as &$entry) {
    if ($entry['instruction_no'] == $instructionNo) {
      logEntry("Operator $instructionNo");
      // Collect expense information
      $newExpenses = [];
      if (isset($_POST['expense_kind'], $_POST['expense_amount'], $_POST['so_hoa_don'], $_POST['expense_payee'], $_POST['expense_doc'])) {
        for ($i = 0; $i < count($_POST['expense_kind']); $i++) {
          $expenseAmount = (float)str_replace(',', '', $_POST['expense_amount'][$i] ?? $entry['expenses'][$i]['expense_amount'] ?? "");
          $soHoaDon = $_POST['so_hoa_don'][$i] ?? $entry['expenses'][$i]['so_hoa_don'] ?? "";
          $fileUploaded = isset($_FILES['expense_file']['name'][$i]) && $_FILES['expense_file']['error'][$i] === UPLOAD_ERR_OK;
          $expenseFile = $fileUploaded ? $_FILES['expense_file']['name'][$i] : $entry['expenses'][$i]['expense_file'] ?? null;

          // Check conditional file upload requirement
          if (!empty($soHoaDon) && !$fileUploaded && !$expenseFile) {
            $errors[] = "Vui lòng tải tệp cho hóa đơn số {$soHoaDon}.";
            continue;
          }
          // $expenseFile = $entry['expenses'][$i]['expense_file'] ?? "";
          // Store expense data
          $expense = [
            'expense_kind' => $_POST['expense_kind'][$i] ?? $entry['expenses'][$i]['expense_kind'] ?? null,
            'expense_amount' => $expenseAmount,
            'so_hoa_don' => $soHoaDon,
            'expense_payee' => $_POST['expense_payee'][$i] ?? $entry['expenses'][$i]['expense_payee'] ?? "",
            'expense_doc' => $_POST['expense_doc'][$i] ?? $entry['expenses'][$i]['expense_doc'] ?? "",
            'expense_file' => $expenseFile
          ];

          // Move uploaded file
          if ($fileUploaded) {
            // Generate a unique name for the file
            $originalFileName = pathinfo($_FILES['expense_file']['name'][$i], PATHINFO_FILENAME);
            $fileExtension = pathinfo($_FILES['expense_file']['name'][$i], PATHINFO_EXTENSION);
            $formattedFileName = preg_replace('/[^A-Za-z0-9]/', '_', $originalFileName);
            $uniqueFileName = uniqid() . "_" . $formattedFileName . "." . $fileExtension;
            $targetFilePath = $targetDir . $uniqueFileName;

            if (move_uploaded_file($_FILES['expense_file']['tmp_name'][$i], $targetFilePath)) {
              $expense['expense_file'] = $uniqueFileName;
            } else {
              $errors[] = "Failed to upload file for expense item at row " . ($i + 1);
            }
          }

          $newExpenses[] = $expense;
        }
      }

      if (empty($newExpenses)) {
        $newExpenses = $entry['expenses'];
      }

      $entry['expenses'] = $newExpenses;

      // Additional fields
      foreach ($_POST as $key => $value) {
        if ($key == "leader" || $key == "sale" || $key == "approval_status" || $key == "message" || $key == "instruction_no") {
          continue;
        } elseif (!in_array($key, ['expense_kind', 'expense_amount', 'so_hoa_don', 'expense_payee', 'expense_doc'])) {
          $entry[$key] = is_array($value) ? $value : trim($value);
        }
      }

      $entry['total_actual'] = (float)str_replace(',', '', $entry['total_actual'] ?? '0');
      $entry['updated_at'] = date("Y-m-d H:i:s");

      foreach ($entry['approval'] as &$approval) {
        if (in_array($approval['role'], ['leader', 'sale'])) {
          $approval['status'] = $status;
          $updated = true;
        }
      }
      break;
    }
  }
}

logEntry(json_encode($errors));

if (!empty($errors)) {
  echo json_encode(['success' => false, 'message' => implode("\n", $errors)]);
  exit();
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
  logEntry("Operator $instructionNo updated successfully");

  echo json_encode(['success' => true, 'message' => 'Status updated successfully', 'data' => $updatedData]);
} else {
  logEntry("Operator $instructionNo update failed");
  echo json_encode(['success' => false, 'message' => 'Approval entry not found or already updated']);
}
