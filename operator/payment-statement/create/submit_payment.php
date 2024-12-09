<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'operator') {
  echo json_encode(['success' => false, 'error' => 'Unauthorized']);
  exit();
}

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
      $fileUploaded = isset($_FILES['expense_file']['name'][$i]) && $_FILES['expense_file']['error'][$i] === UPLOAD_ERR_OK;
      $expenseFile = $fileUploaded ? $_FILES['expense_file']['name'][$i] : null;

      // Check conditional file upload requirement
      if (!empty($soHoaDon) && !$fileUploaded) {
        $errors[] = "Vui lòng tải tệp cho hóa đơn số {$soHoaDon}.";
        continue;
      }

      // Store expense data
      $expense = [
        'expense_kind' => $_POST['expense_kind'][$i],
        'expense_amount' => $expenseAmount,
        'so_hoa_don' => $soHoaDon,
        'expense_payee' => $_POST['expense_payee'][$i],
        'expense_doc' => $_POST['expense_doc'][$i],
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

      $data['expenses'][] = $expense;
    }
  }

  // Additional fields
  foreach ($_POST as $key => $value) {
    if ($key == "leader" || $key == "sale") {
      $data['approval'][] = [
        'role' => $key,
        'email' => $value,
        'status' => 'pending',
        'time' => '',
        'comment' => ''
      ];
    } elseif (!in_array($key, ['expense_kind', 'expense_amount', 'so_hoa_don', 'expense_payee', 'expense_doc'])) {
      $data[$key] = is_array($value) ? $value : trim($value);
    }
  }

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
