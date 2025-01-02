<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'sale') {
  echo json_encode(['success' => false, 'message' => 'Unauthorized']);
  exit();
}

include '../../../helper/payment.php';
include '../../../helper/general.php';
require '../../../library/google_api/vendor/autoload.php'; // Đảm bảo đường dẫn đúng

function uploadFileToGoogleDrive($filePath, $fileName, $folderId)
{
  $client = new Google_Client();
  $client->setAuthConfig('gdcredentials.json'); // Đường dẫn tới file credential
  $client->addScope(Google_Service_Drive::DRIVE_FILE);

  $service = new Google_Service_Drive($client);

  $fileMetadata = new Google_Service_Drive_DriveFile([
    'name' => $fileName,
    'parents' => [$folderId]
  ]);

  $content = file_get_contents($filePath);

  try {
    $file = $service->files->create($fileMetadata, [
      'data' => $content,
      'mimeType' => mime_content_type($filePath),
      'uploadType' => 'multipart'
    ]);
    return "https://drive.google.com/file/d/" . $file->id . "/view";
  } catch (Exception $e) {
    return null;
  }
}

header('Content-Type: application/json');

// Get data from POST request
$instructionNo = $_POST['instruction_no'] ?? null;
// $requestData = json_decode(file_get_contents("php://input"), true);
// $data = $requestData['data'] ?? null;
$isUpdate = isset($_POST['is_update']) ? $_POST['is_update'] : false;

// Check if instruction number and status are provided
if ($instructionNo === null) {
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
  $errors = [];
  $targetDir = '../../../../../private_data/soffice_database/payment/uploads/';

  // Create uploads directory if not exists
  if (!is_dir($targetDir)) {
    mkdir($targetDir, 0777, true);
  }
  // Collect expense information
  $newExpenses = [];
  if (isset($_POST['expense_kind'], $_POST['expense_amount'], $_POST['so_hoa_don'], $_POST['expense_payee'], $_POST['expense_doc'])) {
    for ($i = 0; $i < count($_POST['expense_kind']); $i++) {
      logEntry("expense_kind: " . $_POST['expense_kind'][$i]);
      $expenseAmount = (float)str_replace('.', '', $_POST['expense_amount'][$i]);
      $soHoaDon = $_POST['so_hoa_don'][$i];
      $uploadedFiles = $_FILES['expense_file']['name'][$i] == [""] ? [] : $_FILES['expense_file']['name'][$i];
      $uploadedFilesTmp = $_FILES['expense_file']['tmp_name'][$i] ?? [];
      $expenseFiles = $entry['expenses'][$i]['expense_files'] ?? [];

      // logEntry("uploadedFiles: " . json_encode($uploadedFiles));

      // Check conditional file upload requirement
      if (!empty($soHoaDon) && empty($uploadedFiles) && empty($expenseFiles)) {
        $errors[] = "Vui lòng tải tệp cho hóa đơn số {$soHoaDon}.";
        continue;
      }

      // Process multiple files for this expense row
      if (!empty($uploadedFiles)) {
        foreach ($uploadedFiles as $fileIndex => $fileName) {
          if ($_FILES['expense_file']['error'][$i][$fileIndex] === UPLOAD_ERR_OK) {
            $originalFileName = pathinfo($fileName, PATHINFO_FILENAME);
            $fileExtension = pathinfo($fileName, PATHINFO_EXTENSION);
            $formattedFileName = preg_replace('/[^A-Za-z0-9]/', '_', $originalFileName);
            $uniqueFileName = "Payment" . uniqid() . "_" . $formattedFileName . "." . $fileExtension;
            $targetFilePath = $targetDir . $uniqueFileName;

            // if (move_uploaded_file($uploadedFilesTmp[$fileIndex], $targetFilePath)) {
            //   $expenseFiles[] = $uniqueFileName;
            // } else {
            //   $errors[] = "Failed to upload file: {$fileName} for row " . ($i + 1);
            // }

            $folderId = '175l19YFsHesJmn5yKVO8XV-H5RZp8ron';
            $fileTmpName = $uploadedFilesTmp[$fileIndex];
            if (move_uploaded_file($fileTmpName, $targetFilePath)) {
              // get file name = stationName + fileKey + index + fileName
              $fileNameGGDrive = $uniqueFileName;
              $linkImg = uploadFileToGoogleDrive($targetFilePath, $fileNameGGDrive, $folderId);
              if ($linkImg) {
                $expenseFiles[] = $linkImg;
                unlink($targetFilePath);
              } else {
                $errors[] = "Failed to upload file: {$fileName} for row " . ($i + 1);
                // remove file if upload fail
                unlink($targetFilePath);
              }
            }
          }
        }
      }

      // Store expense data
      // if new expense_amount and old expense_amount are not the same
      if (isset($entry['expenses'][$i]) && $expenseAmount != $entry['expenses'][$i]['expense_amount']) {
        // add $expense with expense_amount_old, is_update = true, time_update=1
        $expense = [
          'expense_kind' => $_POST['expense_kind'][$i] ?? $entry['expenses'][$i]['expense_kind'] ?? null,
          'expense_amount' => $expenseAmount,
          'so_hoa_don' => $soHoaDon,
          'expense_payee' => $_POST['expense_payee'][$i] ?? $entry['expenses'][$i]['expense_payee'] ?? "",
          'expense_doc' => $_POST['expense_doc'][$i] ?? $entry['expenses'][$i]['expense_doc'] ?? "",
          'expense_vat' => $_POST['expense_vat'][$i] ?? $entry['expenses'][$i]['expense_vat'] ?? "",
          'expense_ops' => $_POST['expense_ops'][$i] ?? $entry['expenses'][$i]['expense_ops'] ?? "",
          'expense_files' => $expenseFiles,
          'expense_amount_old' => $entry['expenses'][$i]['expense_amount'] ?? 0,
          'is_update' => true,
          'time_update' => isset($entry['expenses'][$i]['time_update']) ? $entry['expenses'][$i]['time_update'] + 1 : 1
        ];
      } else {
        // Store expense data
        $expense = [
          'expense_kind' => $_POST['expense_kind'][$i] ?? $entry['expenses'][$i]['expense_kind'] ?? null,
          'expense_amount' => $expenseAmount,
          'so_hoa_don' => $soHoaDon,
          'expense_payee' => $_POST['expense_payee'][$i] ?? $entry['expenses'][$i]['expense_payee'] ?? "",
          'expense_doc' => $_POST['expense_doc'][$i] ?? $entry['expenses'][$i]['expense_doc'] ?? "",
          'expense_vat' => $_POST['expense_vat'][$i] ?? $entry['expenses'][$i]['expense_vat'] ?? "",
          'expense_ops' => $_POST['expense_ops'][$i] ?? $entry['expenses'][$i]['expense_ops'] ?? "",
          'expense_files' => $expenseFiles,
          'expense_amount_old' => $entry['expenses'][$i]['expense_amount_old'] ?? $entry['expenses'][$i]['expense_amount'] ?? 0,
          'is_update' => $entry['expenses'][$i]['is_update'] ?? false,
          'time_update' => $entry['expenses'][$i]['time_update'] ?? 0
        ];
      }

      $newExpenses[] = $expense;
    }
  }

  if (empty($newExpenses)) {
    $newExpenses = $entry['expenses'];
  }

  $entry['expenses'] = $newExpenses;

  $fieldIgnore = ['expense_kind', 'expense_amount', 'so_hoa_don', 'expense_payee', 'expense_doc', 'customFieldName', 'customField', 'customVat', 'customContSet', 'customIncl', 'customExcl', 'customUnit', 'expense_vat', 'expense_ops'];

  // Additional fields
  foreach ($_POST as $key => $value) {
    if ($key == "leader" || $key == "sale" || $key == "approval_status" || $key == "message" || $key == "instruction_no") {
      continue;
    } elseif (!in_array($key, $fieldIgnore) && strpos($key, 'customUnit') === false) {
      $entry[$key] = is_array($value) ? $value : trim($value);
    }
  }

  $entry['total_actual'] = (float)str_replace('.', '', $entry['total_actual'] ?? '0');


  // get data payment
  // Extract custom fields
  $customFieldNames = $_POST['customFieldName'] ?? [];
  $customFields = $_POST['customField'] ?? [];
  // $customUnits = $_POST['customUnit'] ?? [];
  $customVats = $_POST['customVat'] ?? [];
  $customContSetRadios = $_POST['customContSet'] ?? [];
  $customIncl = $_POST['customIncl'] ?? [];
  $customExcl = $_POST['customExcl'] ?? [];

  // Prepare an array to store custom fields
  $customData = [];

  // logEntry("customInclude: " . json_encode($customIncl));
  // logEntry("customExcl: " . json_encode($customExcl));

  foreach ($customFieldNames as $index => $name) {
    $newValue = (float)str_replace('.', '', $customFields[$index]);
    $newUnit = $_POST['customUnit_'.($index+1)] ?? '';
    $newVat = $customVats[$index] ?? '';
    $newContSet = isset($customContSetRadios[$index]) && $customContSetRadios[$index] === 'cont' ? 'cont' : 'set';
    $newIncl = $customIncl[$index] ?? '';
    $newExcl = $customExcl[$index] ?? '';

    $existingData = $entry['payment'][$index] ?? [];
    logEntry("existingData: " . json_encode($existingData));
    $oldValue = $existingData['value'] ?? 0;
    $oldUnit = $existingData['unit'] ?? '';
    $oldVat = $existingData['vat'] ?? '';

    $isUpdated = false;

    $customData[] = [
      'name' => $name,
      'value' => $newValue,
      'unit' => $newUnit,
      'vat' => $newVat,
      'contSet' => $newContSet,
      'incl' => $newIncl,
      'excl' => $newExcl,
      'value_old' => $oldValue != $newValue ? $oldValue : $existingData['value_old'] ?? null,
      'unit_old' => $oldUnit != $newUnit ? $oldUnit : $existingData['unit_old'] ?? null,
      'vat_old' => $oldVat != $newVat ? $oldVat : $existingData['vat_old'] ?? null,
      'is_update' => ($oldValue != $newValue || $oldUnit != $newUnit || $oldVat != $newVat) ? true : $existingData['is_update'] ?? false,
      'time_update' => isset($existingData['time_update']) ? $existingData['time_update'] + ($oldValue != $newValue || $oldUnit != $newUnit || $oldVat != $newVat ? 1 : 0) : ($oldValue != $newValue || $oldUnit != $newUnit || $oldVat != $newVat ? 1 : 0),
    ];
  }
  // Save to entry
  $entry['payment'] = $customData;

  // add history
  $entry['history'][] = [
    'actor' => $_SESSION['user_id'],
    'time' => date('Y-m-d H:i:s'),
    'action' => 'Sale updated',
  ];

  foreach ($entry['approval'] as &$approval) {
    if ($isUpdate) {
      if ($approval['role'] === 'sale' && $approval['email'] === $_SESSION['user_id']) {
        $approval['status'] = 'approved';
        $approval['time'] = date("Y-m-d H:i:s"); // Update with current timestamp
      }
      if ($approval['role'] === 'director') {
        $approval['status'] = 'pending';
      }
    } else {
      if ($approval['role'] === 'sale' && $approval['email'] === $_SESSION['user_id']) {
        $approval['status'] = 'approved';
        $approval['time'] = date("Y-m-d H:i:s"); // Update with current timestamp
        break;
      }
    }
  }
  $updated = true;
}

if (!empty($errors)) {
  echo json_encode(['success' => false, 'message' => implode("\n", $errors)]);
  exit();
}


if ($updated) {
  // update payment status
  $statusFilePath = '../../../../../private_data/soffice_database/payment/status/' . $year . '';
  updateStatusFile('sale', 'approved', $instructionNo, $statusFilePath);
  updateStatusFile('director', 'pending', $instructionNo, $statusFilePath);
  // Save the updated JSON data back to the file
  $directory = '../../../../../private_data/soffice_database/payment/data/' . $year;
  $res = updateDataToJson($entry, $directory, 'payment_' . $instructionNo);
  echo json_encode(['success' => true, 'message' => 'Status updated successfully', 'data' => $res['data'] ?? []]);
} else {
  echo json_encode(['success' => false, 'message' => 'Approval entry not found or already updated']);
}
