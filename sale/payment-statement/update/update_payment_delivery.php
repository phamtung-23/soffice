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
      $expense = [
        'expense_kind' => $_POST['expense_kind'][$i],
        'expense_amount' => $expenseAmount,
        'so_hoa_don' => $soHoaDon,
        'expense_payee' => $_POST['expense_payee'][$i],
        'expense_doc' => $_POST['expense_doc'][$i],
        'expense_files' => $expenseFiles // Store all uploaded files for this expense
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
  $customUnits = $_POST['customUnit'] ?? [];
  $customVats = $_POST['customVat'] ?? [];
  $customContSetRadios = $_POST['customContSet'] ?? [];
  $customIncl = $_POST['customIncl'] ?? [];
  $customExcl = $_POST['customExcl'] ?? [];

  // Prepare an array to store custom fields
  $customData = [];

  // logEntry("customInclude: " . json_encode($customIncl));
  // logEntry("customExcl: " . json_encode($customExcl));

  foreach ($customFieldNames as $index => $name) {
    // logEntry("Processing custom field: $name");
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
