<?php

function saveDataToJson($data, $directory, $fileName)
{
  // Ensure the directory exists
  if (!is_dir($directory)) {
    mkdir($directory, 0777, true);
  }

  $filePath = $directory . '/' . $fileName . '.json';

  // Save data to JSON file
  if (file_put_contents($filePath, json_encode($data, JSON_PRETTY_PRINT))) {
    return ['status' => 'success', 'data' => $data];
  } else {
    return ['status' => 'fail'];
  }
}

function updateDataToJson($data, $directory, $fileName)
{
  // Ensure the directory exists
  if (!is_dir($directory)) {
    mkdir($directory, 0777, true);
  }

  $filePath = $directory . '/' . $fileName . '.json';

  // Load existing data or initialize a new object
  $existingData = [];
  if (file_exists($filePath)) {
    $fileContent = file_get_contents($filePath);
    $existingData = json_decode($fileContent, true);
  }

  // Merge existing data with new data
  $updatedData = array_merge($existingData, $data);

  // Save data to JSON file
  if (file_put_contents($filePath, json_encode($updatedData, JSON_PRETTY_PRINT))) {
    return ['status' => 'success', 'data' => $updatedData];
  } else {
    return ['status' => 'fail'];
  }
}

function getDataFromJson($filePath)
{
  // Check if the file exists
  if (!file_exists($filePath)) {
    return ['status' => 'fail', 'message' => 'File not found'];
  }

  // Read the file content
  $jsonContent = file_get_contents($filePath);

  // Decode the JSON data
  $data = json_decode($jsonContent, true);

  // Check for JSON decoding errors
  if (json_last_error() !== JSON_ERROR_NONE) {
    return ['status' => 'fail', 'message' => 'Error decoding JSON'];
  }

  return ['status' => 'success', 'data' => $data];
}

function translate($key, $language)
{
  if ($language === 'en') {
    return $key;
  }
  // Load the language file
  $filePath = '../translations/' . $language . '.php';
  $translations = include $filePath;

  // Check if the key exists in the translations
  if (array_key_exists($key, $translations)) {
    return $translations[$key];
  } else {
    return $key;
  }
}

function saveEmailData($filePath, $email, $stationName)
{
  // Parse station type and ID
  $stationType = substr($stationName, 0, 3); // 'BAT'
  $stationId = $stationName; // 'BAT0001'

  // Load existing data or initialize a new object
  $data = [];
  if (file_exists($filePath)) {
    $fileContent = file_get_contents($filePath);
    $data = json_decode($fileContent, true);
  }

  // Update data
  if (!isset($data[$email])) {
    $data[$email] = [];
  }

  // Check if the station type exists then update the number and list, otherwise create a new entry with default values (number = 0, list = [])
  if (!isset($data[$email][$stationType])) {
    $data[$email][$stationType]['number'] = 1;
    $data[$email][$stationType]['list'] = [$stationId];
  } else {
    // check if the station ID already exists in the list
    if (!in_array($stationId, $data[$email][$stationType]['list'])) {
      $data[$email][$stationType]['number']++;
      $data[$email][$stationType]['list'][] = $stationId;
    }
  }

  // Save data back to the file
  file_put_contents($filePath, json_encode($data, JSON_PRETTY_PRINT));
}

function getDataByStationName($stationName)
{
  $directory = '../database/site/dataSubmit';
  $filePath = $directory . '/' . $stationName . '.json';

  // check if the file exists
  if (!file_exists($filePath)) {
    return ['status' => 'fail', 'message' => 'File not found'];
  }

  // read the file content
  $jsonContent = file_get_contents($filePath);

  // decode the JSON data
  $data = json_decode($jsonContent, true);

  // check for JSON decoding errors
  if (json_last_error() !== JSON_ERROR_NONE) {
    return ['status' => 'fail', 'message' => 'Error decoding JSON'];
  }

  return ['status' => 'success', 'data' => $data];
}



function getApprovalStatus($approval)
{
  $levelName = [
    'bod_pro_gis' => 'BoD GIS Province',
    'office_gis' => 'Head ELE GIS',
    'office_vtc' => 'Head ELE VTC',
  ];
  // Check the approval status
  $updateTime = '';
  foreach ($approval as $item) {
    // check if update time is valid
    if ($item['updateTime'] !== '') {
      $updateTime = $item['updateTime'];
    }
    if ($item['status'] === 'rejected') {
      return ['status' => 'Rejected', 'role' => $levelName[$item['role']], 'updateTime' => $updateTime];
    }
    if ($item['status'] === 'pending') {
      return ['status' => 'Pending', 'role' => $levelName[$item['role']], 'updateTime' => $updateTime];
    }
  }

  return ['status' => 'Approved', 'role' => $levelName[$item['role']], 'updateTime' => $updateTime];
}

// get the approval status by role
function getApprovalStatusByRole($approval, $role)
{
  $levelName = [
    'bod_pro_gis' => 'BoD GIS Province',
    'office_gis' => 'Head ELE GIS',
    'office_vtc' => 'Head ELE VTC',
  ];
  // Check the approval status
  foreach ($approval as $item) {
    if ($item['role'] === $role) {
      return ['status' => $item['status'], 'role' => $levelName[$item['role']], 'updateTime' => $item['updateTime']];
    }
  }
}

// get information of the user by email
function getUserInfo($email)
{
  $filePath = '../../../database/users.json';

  // check if the file exists
  if (!file_exists($filePath)) {
    return ['status' => 'fail', 'message' => 'File not found'];
  }

  // read the file content
  $jsonContent = file_get_contents($filePath);

  // decode the JSON data
  $data = json_decode($jsonContent, true);

  // check for JSON decoding errors
  if (json_last_error() !== JSON_ERROR_NONE) {
    return ['status' => 'fail', 'message' => 'Error decoding JSON'];
  }

  // find the user by email
  foreach ($data as $item) {
    if ($item['email'] === $email) {
      return ['status' => 'success', 'data' => $item];
    }
  }
}

// get information of the user by user id
function getUserInfoById($userId, $filePath)
{
  // check if the file exists
  if (!file_exists($filePath)) {
    return ['status' => 'fail', 'message' => 'File not found'];
  }

  // read the file content
  $jsonContent = file_get_contents($filePath);

  // decode the JSON data
  $data = json_decode($jsonContent, true);

  // check for JSON decoding errors
  if (json_last_error() !== JSON_ERROR_NONE) {
    return ['status' => 'fail', 'message' => 'Error decoding JSON'];
  }

  // find the user by user id
  foreach ($data as $item) {
    if ($item['id'] === $userId) {
      return ['status' => 'success', 'data' => $item];
    }
  }
}

// update user by user id
function updateUserInfoById($userId, $data, $filePath)
{
  // check if the file exists
  if (!file_exists($filePath)) {
    return ['status' => 'fail', 'message' => 'File not found'];
  }

  // read the file content
  $jsonContent = file_get_contents($filePath);

  // decode the JSON data
  $users = json_decode($jsonContent, true);

  // check for JSON decoding errors
  if (json_last_error() !== JSON_ERROR_NONE) {
    return ['status' => 'fail', 'message' => 'Error decoding JSON'];
  }

  // find the user by user id
  foreach ($users as $index => $item) {
    if ($item['id'] === $userId) {
      // update the user info
      $users[$index] = array_merge($item, $data);
      break;
    }
  }

  // save the updated data
  if (file_put_contents($filePath, json_encode($users, JSON_PRETTY_PRINT))) {
    return ['status' => 'success', 'data' => $data];
  } else {
    return ['status' => 'fail'];
  }
}

// get all data on file json in a directory
function getAllDataFiles($directory)
{
  // check if the directory exists
  if (!is_dir($directory)) {
    return ['status' => 'fail', 'message' => 'Directory not found'];
  }

  // get all files in the directory
  $files = scandir($directory);

  // filter out the current directory and parent directory
  $files = array_diff($files, ['.', '..']);

  // read the content of each file
  $data = [];
  foreach ($files as $file) {
    $filePath = $directory . '/' . $file;
    $jsonContent = file_get_contents($filePath);
    $data[] = json_decode($jsonContent, true);
  }

  return ['status' => 'success', 'data' => $data];
}


function updateJsonData($filePath, $instructionNo, $newData) {
  // Read the JSON file
  $json = file_get_contents($filePath);
  $data = json_decode($json, true);

  // Find the entry with the matching instruction_no
  foreach ($data as &$entry) {
      if ($entry['instruction_no'] == $instructionNo) {
          // Update the necessary fields
          foreach ($newData as $key => $value) {
              $entry[$key] = $value;
          }
          break;
      }
  }

  // Save the updated JSON back to the file
  file_put_contents($filePath, json_encode($data, JSON_PRETTY_PRINT));
}
