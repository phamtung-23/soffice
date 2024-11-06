<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'sale') {
  echo json_encode(['success' => false, 'message' => 'Unauthorized']);
  exit();
}

header('Content-Type: application/json');

  // Get data from POST request
  $requestData = json_decode(file_get_contents("php://input"), true);
  $data = $requestData['data'] ?? null;

  // Check if data is provided
  if (empty($data)) {
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

  // Update the data for the matching instruction number
  $updated = false;
  foreach ($jsonData as &$entry) {
    if ($entry['instruction_no'] == $data['instruction_no']) {
      $entry['delivery_address'] = $data['delivery_address'];
      $entry['delivery_time'] = $data['delivery_time'];
      $entry['delivery_pct'] = $data['delivery_pct'];
      $entry['trucking'] = $data['trucking'];
      $entry['trunkingVat'] = $data['trunkingVat'];
      $entry['trunkingIncl'] = isset($data['trunkingIncl']) ? $data['trunkingIncl'] : "";
      $entry['trunkingExcl'] = isset($data['trunkingExcl']) ? $data['trunkingExcl'] : "";
      $entry['stuffing'] = $data['stuffing'];
      $entry['stuffingVat'] = $data['stuffingVat'];
      $entry['stuffingIncl'] = isset($data['stuffingIncl']) ? $data['stuffingIncl'] : "";
      $entry['stuffingExcl'] = isset($data['stuffingExcl']) ? $data['stuffingExcl'] : "";
      $entry['liftOnOff'] = $data['liftOnOff'];
      $entry['liftOnOffVat'] = $data['liftOnOffVat'];
      $entry['liftOnOffIncl'] = isset($data['liftOnOffIncl']) ? $data['liftOnOffIncl'] : "";
      $entry['liftOnOffExcl'] = isset($data['liftOnOffExcl']) ? $data['liftOnOffExcl'] : "";
      $entry['chiHo'] = $data['chiHo'];
      $entry['chiHoVat'] = $data['chiHoVat'];
      $entry['chiHoIncl'] = isset($data['chiHoIncl']) ? $data['chiHoIncl'] : "";
      $entry['chiHoExcl'] = isset($data['chiHoExcl']) ? $data['chiHoExcl'] : "";
      foreach ($entry['approval'] as &$approval) {
        if ($approval['role'] === 'sale' && $approval['email'] === $_SESSION['user_id']) {
          $approval['status'] = 'approved';
          $approval['time'] = date("Y-m-d H:i:s"); // Update with current timestamp
          break;
        }
      }
      $updated = true;
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
