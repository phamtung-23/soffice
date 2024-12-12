<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'operator') {
  echo json_encode(['success' => false, 'error' => 'Unauthorized']);
  exit();
}

include('../../../helper/payment.php');
include('../../../helper/general.php');

header('Content-Type: application/json');

// Get session data
$fullName = $_SESSION['full_name'];
$email = $_SESSION['user_id'];
$userRole = $_SESSION['role'];

// Paths to JSON files
$userFile = '../../../database/users.json';
$idFile = '../../../database/id_payment.json';
$currentYear = date("Y");
$filePath = "../../../database/payment_$currentYear.json";

// Validate file existence and read data
if (!file_exists($userFile) || !file_exists($idFile)) {
  echo json_encode(['success' => false, 'error' => 'Required data files are missing.']);
  exit();
}

// Read and increment ID
$jsonDataIdPayment = file_get_contents($idFile);
$dataIdPayment = json_decode($jsonDataIdPayment, true);
$newIdPayment = $dataIdPayment[$currentYear]["id"] + 1;
$dataIdPayment[$currentYear]["id"] = $newIdPayment;
file_put_contents($idFile, json_encode($dataIdPayment));

// Read user data and filter roles
$usersData = file_get_contents($userFile);
$users = json_decode($usersData, true);
$leaders = array_filter($users, fn($user) => $user['role'] === 'leader');
$sales = array_filter($users, fn($user) => $user['role'] === 'sale');
$directorData = current(array_filter($users, fn($user) => $user['role'] == 'director'));

// Initialize existing data array
$existingData = file_exists($filePath) ? json_decode(file_get_contents($filePath), true) : [];
$lastInstructionNo = !empty($existingData) ? (int) max(array_column($existingData, 'instruction_no')) : 0;
$newInstructionNo = $lastInstructionNo + 1;

// Check if form was submitted via POST
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
  $errors = [];
  $data = ['instruction_no' => $newInstructionNo, 'approval' => [], 'expenses' => []];
  $targetDir = '../../../database/payment/uploads/';

  // Create uploads directory if not exists
  if (!is_dir($targetDir)) {
    mkdir($targetDir, 0777, true);
  }

  // Collect expense information
  if (isset($_POST['expense_kind'], $_POST['expense_amount'], $_POST['so_hoa_don'], $_POST['expense_payee'], $_POST['expense_doc'])) {
    for ($i = 0; $i < count($_POST['expense_kind']); $i++) {
      $expenseAmount = (float)str_replace('.', '', $_POST['expense_amount'][$i]);
      $soHoaDon = $_POST['so_hoa_don'][$i];
      $uploadedFiles = $_FILES['expense_file']['name'][$i] == [""] ? [] : $_FILES['expense_file']['name'][$i];
      $uploadedFilesTmp = $_FILES['expense_file']['tmp_name'][$i] ?? [];
      $expenseFiles = [];

      // Check conditional file upload requirement
      if (!empty($soHoaDon) && empty($uploadedFiles)) {
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
            $uniqueFileName = uniqid() . "_" . $formattedFileName . "." . $fileExtension;
            $targetFilePath = $targetDir . $uniqueFileName;

            if (move_uploaded_file($uploadedFilesTmp[$fileIndex], $targetFilePath)) {
              $expenseFiles[] = $uniqueFileName;
            } else {
              $errors[] = "Failed to upload file: {$fileName} for row " . ($i + 1);
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

      $data['expenses'][] = $expense;
    }
  }

  $fieldIgnore = ['expense_kind', 'expense_amount', 'so_hoa_don', 'expense_payee', 'expense_doc', 'customFieldName', 'customField', 'customVat', 'customContSet', 'customIncl', 'customExcl'];
  // Additional fields
  foreach ($_POST as $key => $value) {
    if ($key == "leader" || $key == "sale") {

      $infoUserRes = getUserInfo($value);
      $infoUserData = $infoUserRes['data'];
      // check user role is director
      if ($infoUserData['role'] === 'director') {
        $data['approval'][0]['status'] = 'approved';
        $data['approval'][0]['time'] = date('Y-m-d H:i:s');

        $data['approval'][] = [
          'role' => $key,
          'email' => $value,
          'status' => 'approved',
          'time' => date('Y-m-d H:i:s'),
          'comment' => ''
        ];
      }

      $data['approval'][] = [
        'role' => $key,
        'email' => $value,
        'status' => 'pending',
        'time' => '',
        'comment' => ''
      ];
    } elseif (!in_array($key, $fieldIgnore)) {
      $data[$key] = is_array($value) ? $value : trim($value);
    }
  }

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

  // Save to entry
  $data['payment'] = $customData;


  // Append session and meta data
  $data['operator_name'] = $fullName;
  $data['operator_email'] = $email;
  $data['approval'][] = ['role' => 'director', 'email' => '', 'status' => 'pending', 'time' => '', 'comment' => ''];
  $data['approval'][] = ['role' => 'accountant', 'email' => '', 'status' => 'pending', 'time' => '', 'comment' => ''];
  $data['total_actual'] = (float)str_replace(',', '', $data['total_actual'] ?? '0');
  $data['created_at'] = date('Y-m-d H:i:s');
  $data['id'] = $newIdPayment;

  // Check for errors and handle accordingly
  if (!empty($errors)) {
    echo json_encode(['success' => false, 'error' => implode("\n", $errors)]);
  } else {
    $existingData[] = $data;
    file_put_contents($filePath, json_encode($existingData, JSON_PRETTY_PRINT));
    echo json_encode(['success' => true, 'message' => 'Data submitted successfully!']);
  }
}
