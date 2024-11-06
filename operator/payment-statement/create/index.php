<?php
session_start();

// Kiểm tra nếu người dùng đã đăng nhập, thì tiếp tục trang, nếu không thì chuyển hướng về trang login
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'operator') {
  echo "<script>alert('Bạn chưa đăng nhập! Vui lòng đăng nhập lại.'); window.location.href = '../../../index.php';</script>";
  exit();
}


// Lấy tên đầy đủ từ session
$fullName = $_SESSION['full_name'];
$email = $_SESSION['user_id'];
$userRole = $_SESSION['role'];

// Đọc dữ liệu từ file users.json
$userFile = '../../../database/users.json';
if (!file_exists($userFile)) {
  die("File users.json không tồn tại.");
}

$usersData = file_get_contents($userFile);
$users = json_decode($usersData, true);

// Lọc danh sách người dùng có vai trò là "leader"
$leaders = array_filter($users, function ($user) {
  return $user['role'] === 'leader';
});
// Lọc danh sách người dùng có vai trò là "sale"
$sales = array_filter($users, function ($user) {
  return $user['role'] === 'sale';
});


// Get the current year
$currentYear = date('Y');

// Define the path to the JSON file for the current year
$filePath = "../../../database/payment_$currentYear.json";
$filePathUser = '../../../database/users.json';

if (file_exists($filePathUser)) {
  $jsonUserData = json_decode(file_get_contents($filePathUser), true);
  if (!empty($jsonUserData)) {
    // get leader data
    $directorData = null;
    foreach ($jsonUserData as $user) {
      if ($user['role'] == 'director') {
        $directorData = $user;
        break;
      }
    }
  }
} else {
  $jsonUserData = [];
}


// Initialize existing data array
$existingData = [];
if (file_exists($filePath)) {
  $existingData = json_decode(file_get_contents($filePath), true) ?? [];
}

// Find the last instruction number and increment it
$lastInstructionNo = 0;
if (!empty($existingData)) {
  // Extract the last instructionNo by finding the highest value in existing records
  $lastInstructionNo = (int) max(array_column($existingData, 'instruction_no'));
}
$newInstructionNo = $lastInstructionNo + 1; // Set the new instruction number

// Check if form was submitted via POST
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
  // Initialize an array to store submitted data
  $data = ['instruction_no' => $newInstructionNo]; // Add new instruction number to data
  $data['approval'] = [];

  // Initialize the expenses array
  $data['expenses'] = [];

  // Collect expense information in the required format
  if (isset($_POST['expense_kind']) && isset($_POST['expense_amount']) && isset($_POST['so_hoa_don']) && isset($_POST['expense_payee']) && isset($_POST['expense_doc'])) {
    for ($i = 0; $i < count($_POST['expense_kind']); $i++) {
      $expenseAmount = $_POST['expense_amount'][$i];
      // Remove commas and convert to float
      $expenseAmount = (float)str_replace(',', '', $expenseAmount);

      $expense = [
        'expense_kind' => $_POST['expense_kind'][$i],
        'expense_amount' => $expenseAmount,  // Ensure it's stored as a number
        'so_hoa_don' => $_POST['so_hoa_don'][$i],
        'expense_payee' => $_POST['expense_payee'][$i],
        'expense_doc' => $_POST['expense_doc'][$i]
      ];
      $data['expenses'][] = $expense;  // Add each expense entry to the 'expenses' array
    }
  }

  // Iterate through each submitted data field and store it in the $data array
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

  // add fullName and email to data
  $data['operator_name'] = $fullName;
  $data['operator_email'] = $email;
  $data['approval'][] = [
    'role' => 'director',
    'email' => $directorData['email'],
    'status' => 'pending',
    'time' => '',
    'comment' => ''
  ];
  $data['total_actual'] = (float)str_replace(',', '', $data['total_actual']);
  $data['created_at'] = date('Y-m-d H:i:s');

  

  // Append the new data to the existing data array
  $existingData[] = $data;

  // Save the updated data array back to the JSON file
  file_put_contents($filePath, json_encode($existingData, JSON_PRETTY_PRINT));

  // Display an alert and redirect to a new page
  echo "<script>
            alert('Data submitted successfully!');
            window.location.href = '../../index.php';
          </script>";
  exit();
} else {
  echo "<script>console.log('No Data submitted');</script>";
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Form</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://unpkg.com/@phosphor-icons/web"></script>
</head>

<body>
  <div class="container mt-5">
    <form method="post" class="needs-validation" novalidate>
      <div class="d-flex flex-column justify-content-center align-items-center">
        <h3 class="fw-bold">UNI-GLOBAL</h3>
        <h5 class="mb-4">INLAND SERVICE INTERNAL INSTRUCTION</h5>
        <!-- <div class="row mb-3 w-50">
          <label for="instructionNo" class="col-sm-5 col-form-label pr-0 text-end">Instruction No: </label>
          <div class="col-sm-7">
            <input type="text" class="form-control" id="instructionNo" name="instructionNo" required>
          </div>
        </div> -->
      </div>

      <!-- I. SALES INFORMATION -->
      <?php if ($userRole === 'operator') {
      ?>
        <div>
          <h6>I. SALES INFORMATION:</h6>
          <div class="row mb-3 mt-3 ps-4">
            <label for="shipper" class="col-sm-2 col-form-label">Shipper</label>
            <div class="col-sm-10">
              <input type="text" class="form-control" id="shipper" placeholder="Ex: Nguyen Van A" name="shipper" required>
            </div>
          </div>
          <div class="row mb-3 mt-3 ps-4">
            <label for="billTo" class="col-sm-2 col-form-label">Bill To</label>
            <div class="col-sm-10">
              <input type="text" class="form-control" id="billTo" placeholder="Ex: 1x40" name="billTo" required>
            </div>
          </div>
          <div class="row mb-3 mt-3 ps-4">
            <label for="volume" class="col-sm-2 col-form-label">Volume</label>
            <div class="col-sm-10">
              <input type="text" class="form-control" id="volume" placeholder="Ex: xxxx-SOC" name="volume" required>
            </div>
          </div>
          <div class="row mb-3 mt-3 ps-4">
            <label for="payment_lo" class="col-sm-2 col-form-label">Lô</label>
            <div class="col-sm-10">
              <input type="text" class="form-control" id="payment_lo" placeholder="Ex: 1222" name="payment_lo" required>
            </div>
          </div>
          <div class="row mb-3 mt-3 ps-4">
            <label for="loai_hinh" class="col-sm-2 col-form-label">Chọn loại hình</label>
            <div class="col-sm-10">
              <div class="dropdown">
                <select class="form-select" aria-label="Default select example" name="loai_hinh" required>
                  <option value="">Chọn loại hình</option>
                  <option value="nhap">Nhập</option>
                  <option value="xuat">Xuất</option>
                </select>
              </div>
            </div>
          </div>
          <div class="row mb-3 mt-3 ps-4">
            <label for="leader" class="col-sm-2 col-form-label">Approve by</label>
            <div class="col-sm-10">
              <div class="dropdown">
                <select class="form-select" aria-label="Default select example" name="leader" required>
                  <?php
                  foreach ($leaders as $leader) {
                    echo "<option value='$leader[email]'>$leader[fullname] - $leader[email]</option>";
                  }
                  ?>
                </select>
              </div>
            </div>
          </div>
          <div class="row mb-3 mt-3 ps-4">
            <label for="sale" class="col-sm-2 col-form-label">Sale man</label>
            <div class="col-sm-10">
              <div class="dropdown">
                <select class="form-select" aria-label="Default select example" name="sale" required>
                  <?php
                  foreach ($sales as $sale) {
                    echo "<option value='$sale[email]'>$sale[fullname] - $sale[email]</option>";
                  }
                  ?>
                </select>
              </div>
            </div>
          </div>
        </div>
      <?php } ?>

      <!-- II. PICK UP/DELIVERY INFORMATION: -->
      <?php if ($userRole === 'sale') {
      ?>
        <div>
          <h6>II. PICK UP/DELIVERY INFORMATION:</h6>
          <div class="row mb-3 mt-3 ps-4">
            <label for="address" class="col-sm-2 col-form-label">Address</label>
            <div class="col-sm-10">
              <input type="text" class="form-control" id="address" placeholder="" name="address" required>
            </div>
          </div>

          <div class="row mb-3 mt-3 ps-4">
            <label for="time" class="col-sm-2 col-form-label">Time</label>
            <div class="col-sm-4">
              <input type="date" class="form-control" id="time" placeholder="" name="time" required>
            </div>

            <label for="volume" class="col-sm-2 col-form-label">PCT</label>
            <div class="col-sm-4">
              <input type="text" class="form-control" id="volume" placeholder="" name="volume" required>
            </div>
          </div>
          <div class="row mb-3 mt-3 ps-4 d-flex align-items-center">
            <label for="trucking" class="col-sm-2 col-form-label">Trucking</label>
            <div class="col-sm-3">
              <input type="text" class="form-control" id="trucking" placeholder="" name="trucking" required>
            </div>
            <label for="trunkingVat" class="col-sm-1 col-form-label">V.A.T</label>
            <div class="col-sm-2">
              <div class="input-group">
                <input type="text" class="form-control" aria-label="Amount (to the nearest dollar)" name="trunkingVat" required>
                <span class="input-group-text">%</span>
              </div>
            </div>
            <div class="form-check col-sm-2 d-flex gap-2 align-items-center">
              <input class="form-check-input" type="checkbox" id="trunkingIncl" name="trunkingIncl" value="">
              <label class="form-check-label" for="trunkingIncl">
                INCL
              </label>
            </div>
            <div class="form-check col-sm-2 d-flex gap-2 align-items-center">
              <input class="form-check-input" type="checkbox" id="trunkingExcl" name="trunkingExcl">
              <label class="form-check-label" for="trunkingExcl">
                EXCL
              </label>
            </div>
          </div>
          <div class="row mb-3 mt-3 ps-4 d-flex align-items-center">
            <label for="stuffing" class="col-sm-2 col-form-label">Stuffing & customs & Phyto</label>
            <div class="col-sm-3">
              <input type="text" class="form-control" id="stuffing" placeholder="" name="stuffing" required>
            </div>
            <label for="stuffingVat" class="col-sm-1 col-form-label">V.A.T</label>
            <div class="col-sm-2">
              <div class="input-group">
                <input type="text" class="form-control" aria-label="Amount (to the nearest dollar)" name="stuffingVat" required>
                <span class="input-group-text">%</span>
              </div>
            </div>
            <div class="form-check col-sm-2 d-flex gap-2 align-items-center">
              <input class="form-check-input" type="checkbox" id="stuffingIncl" name="stuffingIncl">
              <label class="form-check-label" for="stuffingIncl">
                INCL
              </label>
            </div>
            <div class="form-check col-sm-2 d-flex gap-2 align-items-center">
              <input class="form-check-input" type="checkbox" id="stuffingExcl" name="stuffingExcl">
              <label class="form-check-label" for="stuffingExcl">
                EXCL
              </label>
            </div>
          </div>
          <div class="row mb-3 mt-3 ps-4 d-flex align-items-center">
            <label for="liftOnOff" class="col-sm-2 col-form-label">Lift on/off</label>
            <div class="col-sm-3">
              <input type="text" class="form-control" id="liftOnOff" placeholder="" name="liftOnOff" required>
            </div>
            <label for="liftOnOffVat" class="col-sm-1 col-form-label">V.A.T</label>
            <div class="col-sm-2">
              <div class="input-group">
                <input type="text" class="form-control" aria-label="Amount (to the nearest dollar)" name="liftOnOffVat" required>
                <span class="input-group-text">%</span>
              </div>
            </div>
            <div class="form-check col-sm-2 d-flex gap-2 align-items-center">
              <input class="form-check-input" type="checkbox" id="liftOnOffIncl" name="liftOnOffIncl">
              <label class="form-check-label" for="liftOnOffIncl">
                INCL
              </label>
            </div>
            <div class="form-check col-sm-2 d-flex gap-2 align-items-center">
              <input class="form-check-input" type="checkbox" id="liftOnOffExcl" name="liftOnOffExcl">
              <label class="form-check-label" for="liftOnOffExcl">
                EXCL
              </label>
            </div>
          </div>
          <div class="row mb-3 mt-3 ps-4 d-flex align-items-center">
            <label for="chiHo" class="col-sm-2 col-form-label">Chi hộ</label>
            <div class="col-sm-3">
              <input type="text" class="form-control" id="chiHo" placeholder="" name="chiHo" required>
            </div>
            <label for="chiHoVat" class="col-sm-1 col-form-label">V.A.T</label>
            <div class="col-sm-2">
              <div class="input-group">
                <input type="text" class="form-control" aria-label="Amount (to the nearest dollar)" name="chiHoVat" required>
                <span class="input-group-text">%</span>
              </div>
            </div>
            <div class="form-check col-sm-2 d-flex gap-2 align-items-center">
              <input class="form-check-input" type="checkbox" id="chiHoIncl" name="chiHoIncl">
              <label class="form-check-label" for="chiHoIncl">
                INCL
              </label>
            </div>
            <div class="form-check col-sm-2 d-flex gap-2 align-items-center">
              <input class="form-check-input" type="checkbox" id="chiHoExcl" name="chiHoExcl">
              <label class="form-check-label" for="chiHoExcl">
                EXCL
              </label>
            </div>
          </div>
        </div>

      <?php } ?>
      <!-- III. OPERATION INFORMATION -->
      <?php if ($userRole === 'operator') {
      ?>
        <div>
          <h6>III. OPERATION INFORMATION</h6>

          <div class="row mb-3 mt-3 ps-4">
            <label for="operatorName" class="col-sm-2 col-form-label">Operator</label>
            <div class="col-sm-4">
              <input type="text" class="form-control" id="operatorName" value="<?= $fullName ?>" name="operatorName" required disabled>
            </div>

            <label for="customs_manifest_on" class="col-sm-2 col-form-label">Customs manifest no</label>
            <div class="col-sm-4">
              <input type="text" class="form-control" id="customs_manifest_on" placeholder="Ex: 12345678" name="customs_manifest_on" required>
            </div>
          </div>

          <!-- Expense Table Section -->
          <table class="table table-bordered mb-4">
            <thead>
              <tr>
                <th scope="col" rowspan="2" class="align-middle">No</th>
                <th scope="col" rowspan="2" class="align-middle">Kind of Expense</th>
                <th scope="col" colspan="2">Amount</th>
                <th scope="col" rowspan="2" class="align-middle">Payee</th>
                <th scope="col" rowspan="2" class="align-middle">Doc. No.</th>
                <th scope="col" rowspan="2" class="align-middle">Action</th>
              </tr>
              <tr>
                <th>Actual</th>
                <th>Số hóa đơn</th>
              </tr>
            </thead>
            <tbody class="tableBody">
              <tr>
                <td>1</td>
                <td><input type="text" name="expense_kind[]" class="form-control" required></td>
                <td><input type="text" name="expense_amount[]" class="form-control" required oninput="toggleExpenseFields(this)"></td>
                <td><input type="text" name="so_hoa_don[]" class="form-control"></td>
                <td><input type="text" name="expense_payee[]" class="form-control" required></td>
                <td><input type="text" name="expense_doc[]" class="form-control"></td>
                <td class="align-middle"><button onclick="deleteRow(this)"><i class="ph ph-trash"></i></button></td>
              </tr>

              <!-- Additional rows as needed -->
            <tfoot>
              <tr>
                <td colspan="7" class="text-center">
                  <button type="button" class="btn btn-secondary w-100" onclick="addRow()">Add Row</button>
                </td>
              </tr>
              <tr>
                <td colspan="2" class="text-end">TOTAL</td>
                <td><input type="text" id="total_actual" name="total_actual" class="form-control" required oninput="updateAmountText(this)"></td>
                <td></td>
                <td>
                  RECEIVED BACK ON: <input type="text" class="form-control" name="received_back_on">
                </td>
                <td colspan="2">
                  BY: <input type="text" class="form-control" name="by">
                </td>
              </tr>
            </tfoot>

            </tbody>
          </table>
        </div>
      <?php } ?>
      <!-- Submission Button -->
      <div class="w-100 d-flex justify-content-end pb-3">
        <button type="submit" class="btn btn-primary">Submit</button>
      </div>
    </form>
  </div>
  <script src="./index.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>