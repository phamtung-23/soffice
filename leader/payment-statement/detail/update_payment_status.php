<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'leader') {
  echo json_encode(['success' => false, 'message' => 'Unauthorized']);
  exit();
}

header('Content-Type: application/json');

// Get data from POST request
$data = json_decode(file_get_contents("php://input"), true);
$instructionNo = $data['instruction_no'] ?? null;
$status = $data['approval_status'] ?? null;
$message = $data['message'] ?? null;

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
  file_put_contents($filePath, json_encode($jsonData, JSON_PRETTY_PRINT));
  echo json_encode(['success' => true, 'message' => 'Status updated successfully']);
} else {
  echo json_encode(['success' => false, 'message' => 'Approval entry not found or already updated']);
}

?>
