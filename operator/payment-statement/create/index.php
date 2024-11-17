<?php
session_start();

// Check if user is logged in; otherwise, redirect to login page
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'operator') {
  echo "<script>alert('Bạn chưa đăng nhập! Vui lòng đăng nhập lại.'); window.location.href = '../../../index.php';</script>";
  exit();
}

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
if (!file_exists($userFile)) {
  die("File users.json không tồn tại.");
}
if (!file_exists($idFile)) {
  die("File id_payment.json không tồn tại.");
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

?>


<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Form</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://unpkg.com/@phosphor-icons/web"></script>
  <style>
    /* Basic styles for layout */
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      font-family: Arial, sans-serif;
      background-color: #f4f4f4;
    }

    .header {
      background-color: #4CAF50;
      color: white;
      padding: 10px 20px;
      text-align: center;
    }


    .menu {
      background-color: #333;
      overflow: hidden;
      display: flex;
      align-items: center;
      justify-content: space-between;
    }

    .icon {
      padding: 10px 20px;
    }

    .menu-icon {
      width: 40px;
      height: 40px;
    }

    .menu a {
      float: left;
      display: block;
      color: white;
      text-align: center;
      padding: 14px 20px;
      text-decoration: none;
      font-size: 17px;
    }

    .menu a:hover {
      background-color: #575757;
    }

    .menu a.logout {
      float: right;
      background-color: #f44336;
    }

    .menu a.logout:hover {
      background-color: #d32f2f;
    }

    /* Hamburger icon (hidden by default) */
    .hamburger {
      display: none;
      float: right;
      font-size: 28px;
      cursor: pointer;
      color: white;
      padding: 10px 20px;
    }

    /* Basic responsive adjustments */
    @media (max-width: 950px) {

      /* Header and menu adjustments */
      .header {
        padding: 20px;
        font-size: 1.5em;
      }

      .header h1 {
        font-size: 1.2em;
      }

      .menu {
        background-color: #333;
        overflow: hidden;
        display: block;
      }

      .menu a {
        float: none;
        display: block;
        text-align: left;
        padding: 10px;
      }

      .menu a.logout {
        float: none;
        background-color: #f44336;
        text-align: center;
      }

      .menu a {
        display: none;
        /* Hide menu links */
      }

      .menu a.logout {
        display: none;
      }

      .hamburger {
        display: block;
        /* Show hamburger icon */
      }

      .menu.responsive a {
        float: none;
        /* Make links stack vertically */
        display: block;
        text-align: left;
      }

      .menu.responsive .logout {
        float: none;
      }
    }

    @media (max-width: 480px) {

      /* Smaller screens (mobile) */
      .header h1 {
        font-size: 1.2em;
      }

      .menu {
        background-color: #333;
        overflow: hidden;
        display: block;
      }

      .menu a {
        font-size: 0.9em;
      }

      .menu a {
        display: none;
        /* Hide menu links */
      }

      .menu a.logout {
        display: none;
      }

      .hamburger {
        display: block;
        /* Show hamburger icon */
      }

      .menu.responsive a {
        float: none;
        /* Make links stack vertically */
        display: block;
        text-align: left;
      }

      .menu.responsive .logout {
        float: none;
      }
    }
  </style>
</head>

<body>
  <div class="header">
    <h1>Operator Dashboard</h1>
  </div>

  <div class="menu">
    <span class="hamburger" onclick="toggleMenu()">&#9776;</span>
    <div class='icon'>
      <img src="../../../images/uniIcon.png" alt="Home Icon" class="menu-icon">
    </div>
    <a href="../../index.php">Home</a>
    <a href="../../request.php">Tạo phiếu xin tạm ứng</a>
    <a href="../../payment-statement/create">Tạo phiếu thanh toán</a>
    <a href="../../../update_signature.php">Cập nhật hình chữ ký</a>
    <a href="../../../update_idtelegram.php">Cập nhật ID Telegram</a>
    <a href="../../../logout.php" class="logout">Đăng xuất</a>
  </div>
  <div class="container mt-5">
    <form id="expenseForm" class="needs-validation" novalidate enctype="multipart/form-data">
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
                <th scope="col" rowspan="2" class="align-middle">Upload file</th>
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
                <td><input class="form-control" type="file" id="formFile" name="expense_file[]"></td>
                <td class="align-middle">
                  <button onclick="deleteRow(this)"><i class="ph ph-trash"></i></button>
                </td>
              </tr>

              <!-- Additional rows as needed -->
            <tfoot>
              <tr>
                <td colspan="8" class="text-center">
                  <button type="button" class="btn btn-secondary w-100" onclick="addRow()">Add Row</button>
                </td>
              </tr>
              <tr>
                <td colspan="2" class="text-end"></td>
                <td>
                  <!-- <input type="text" id="total_actual" name="total_actual" class="form-control" required oninput="updateAmountText(this)"> -->
                </td>
                <td></td>
                <td>
                  RECEIVED BACK ON: <input type="text" class="form-control" name="received_back_on">
                </td>
                <td colspan="3">
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
        <button type="submit" class="btn btn-success" id="submitButton">Submit</button>
      </div>
    </form>
  </div>
  <script src="./index.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>