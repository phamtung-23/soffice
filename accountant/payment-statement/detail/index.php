<?php
session_start();

// Kiểm tra nếu người dùng đã đăng nhập, thì tiếp tục trang, nếu không thì chuyển hướng về trang login
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'accountant') {
  echo "<script>alert('Bạn chưa đăng nhập! Vui lòng đăng nhập lại.'); window.location.href = '../index.php';</script>";
  exit();
}

include('../../../helper/general.php');

// Lấy tên đầy đủ từ session
$fullName = $_SESSION['full_name'];
$email = $_SESSION['user_id'];
$userRole = $_SESSION['role'];



// Get InstructionNo from URL
$instructionNo = isset($_GET['instruction_no']) ? $_GET['instruction_no'] : null;
$year = isset($_GET['year']) ? $_GET['year'] : date('Y');
$data = null;

// Define the path to the JSON file
$filePath = '../../../database/payment_' . $year . '.json';
$filePathUser = '../../../database/users.json';

if ($instructionNo !== null) {
  // Load and decode JSON data
  $jsonData = json_decode(file_get_contents($filePath), true);
  $jsonDataUser = json_decode(file_get_contents($filePathUser), true);

  $filePathPayment = "../../../../../private_data/soffice_database/payment/data/$year/";
  $filePathPaymentID = $filePathPayment . "payment_$instructionNo.json";
  $paymentIdRes = getDataFromJson($filePathPaymentID);
  $paymentId = $paymentIdRes['data'];
  // print_r(json_encode($paymentId));
  $data = $paymentId;

  $operatorUserData = null;
  foreach ($jsonDataUser as $user) {
    if ($user['email'] == $data['operator_email']) {
      $operatorUserData = $user;
      break;
    }
  }

  // get leader data
  $leaderData = null;
  foreach ($jsonDataUser as $user) {
    if ($user['role'] == 'leader' && $user['email'] == $data['approval'][0]['email']) {
      $leaderData = $user;
      break;
    }
  }

  // get sale data
  $saleUserData = null;
  foreach ($jsonDataUser as $user) {
    if ($user['role'] == 'sale' && $user['email'] == $data['approval'][1]['email']) {
      $saleUserData = $user;
      break;
    }
  }

  // get leader data
  $directorData = null;
  foreach ($jsonDataUser as $user) {
    if ($user['role'] == 'director' && $user['email'] == $data['approval'][2]['email']) {
      $directorData = $user;
      break;
    }
  }
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
    <h1>Accountant Dashboard</h1>
  </div>

  <div class="menu">
    <span class="hamburger" onclick="toggleMenu()">&#9776;</span>
    <div class='icon'>
      <img src="../../../images/uniIcon.png" alt="Home Icon" class="menu-icon">
    </div>
    <a href="../../index.php">Home</a>
    <a href="../../all_request.php">Quản lý phiếu tạm ứng</a>
    <a href="../../all_payment.php">Quản lý phiếu thanh toán</a>
    <a href="../../finance.php">Quản lý tài chính</a>
    <a href="../../../update_signature.php">Cập nhật hình chữ ký</a>
    <a href="../../../update_idtelegram.php">Cập nhật ID Telegram</a>
    <a href="../../../logout.php" class="logout">Đăng xuất</a>
  </div>
  <div class="container mt-5 mb-5">
    <form method="post" class="needs-validation" novalidate>
      <div class="d-flex flex-column justify-content-center align-items-center">
        <h3 class="fw-bold">PHIẾU ĐỀ NGHỊ THANH TOÁN</h3>
        <!-- <h5 class="mb-4">INLAND SERVICE INTERNAL INSTRUCTION</h5> -->
        <!-- <div class="row mb-3 w-50">
          <label for="instructionNo" class="col-sm-5 col-form-label pr-0 text-end">Instruction No: </label>
          <div class="col-sm-7">
            <input type="text" class="form-control" id="instructionNo" name="instructionNo" required>
          </div>
        </div> -->
      </div>
      <div>
        <div class="row mb-3 mt-3 ps-4">
          <label for="NguoiDeNghi" class="col-sm-2 col-form-label">Người đề nghị:</label>
          <div class="col-sm-10">
            <input type="text" class="form-control" id="NguoiDeNghi" placeholder="" name="NguoiDeNghi" required disabled value="<?= $data['shipper'] ?>">
          </div>
        </div>
        <div class="row mb-3 mt-3 ps-4">
          <label for="thuocBoPhan" class="col-sm-2 col-form-label">Thuộc bộ phận:</label>
          <div class="col-sm-10">
            <input type="text" class="form-control" id="thuocBoPhan" placeholder="" name="thuocBoPhan" required disabled value="GN">
          </div>
        </div>
        <div class="row mb-3 mt-3 ps-4">
          <label for="soTien" class="col-sm-2 col-form-label">Số tiền:</label>
          <div class="col-sm-10">
            <input type="text" class="form-control" id="soTien" name="soTien" required disabled value="<?= $data['amount'] ?>">
          </div>
        </div>
        <div class="row mb-3 mt-3 ps-4">
          <label for="soTienBangChu" class="col-sm-2 col-form-label">Số tiền bằng chữ:</label>
          <div class="col-sm-10">
            <input type="text" class="form-control" id="soTienBangChu" placeholder="" name="soTienBangChu" required disabled>
          </div>
        </div>
        <div class="row mb-3 mt-3 ps-4">
          <label for="payment_lo" class="col-sm-2 col-form-label">Nội dung thanh toán:</label>
          <div class="col-sm-10">
            <input type="text" class="form-control" id="payment_lo" placeholder="" name="payment_lo" required disabled value="<?php echo $data['shipper'] . "/" . $data['volume'] . "/" . $data['customs_manifest_on'] . " LO " . $data['payment_lo'] ?>">
          </div>
        </div>
      </div>

      <!-- I. SALES INFORMATION -->
      <div>
        <h6>I. SALES INFORMATION:</h6>
        <div class="row mb-3 mt-3 ps-4">
          <label for="shipper" class="col-sm-2 col-form-label">Shipper</label>
          <div class="col-sm-10">
            <input type="text" class="form-control" id="shipper" placeholder="" name="shipper" required disabled value="<?= $data['shipper'] ?>">
          </div>
        </div>
        <div class="row mb-3 mt-3 ps-4">
          <label for="billTo" class="col-sm-2 col-form-label">Bill To</label>
          <div class="col-sm-10">
            <input type="text" class="form-control" id="billTo" placeholder="" name="billTo" required disabled value="<?= $data['billTo'] ?>">
          </div>
        </div>
        <div class="row mb-3 mt-3 ps-4">
          <label for="volume" class="col-sm-2 col-form-label">Volume</label>
          <div class="col-sm-10">
            <input type="text" class="form-control" id="volume" placeholder="" name="volume" required disabled value="<?= $data['volume'] ?>">
          </div>
        </div>
        <div class="row mb-3 mt-3 ps-4">
          <label for="payment_lo" class="col-sm-2 col-form-label">Lô</label>
          <div class="col-sm-10">
            <input type="text" class="form-control" id="payment_lo" placeholder="" name="payment_lo" required disabled value="<?= $data['payment_lo'] ?>">
          </div>
        </div>
        <div class="row mb-3 mt-3 ps-4">
          <label for="leader" class="col-sm-2 col-form-label">Approve by</label>
          <div class="col-sm-10">
            <div class="dropdown">
              <select class="form-select" aria-label="Default select example" name="leader" required disabled>
                <option value='<?= $data['approval'][0]['email'] ?>'><?= $data['approval'][0]['email'] ?></option>
              </select>
            </div>
          </div>
        </div>
        <div class="row mb-3 mt-3 ps-4">
          <label for="sale" class="col-sm-2 col-form-label">Sale man</label>
          <div class="col-sm-10">
            <div class="dropdown">
              <select class="form-select" aria-label="Default select example" name="sale" required disabled>
                <option value='<?= $data['approval'][1]['email'] ?>'><?= $data['approval'][1]['email'] ?></option>
              </select>
            </div>
          </div>
        </div>
      </div>

      <!-- II. PAYMENT INFORMATION -->
      <div id="payment-info-container">
        <h6>II. PICK UP/DELIVERY INFORMATION:</h6>
        <div class="row mb-3 mt-3 ps-4">
          <label for="delivery_address" class="col-sm-2 col-form-label">Address</label>
          <div class="col-sm-10">
            <input type="text" class="form-control" id="delivery_address" placeholder="Ex: Đường abc, quận x, tp.HCM" name="delivery_address" value="<?php echo $data['delivery_address'] ?? '' ?>" required disabled>
          </div>
        </div>

        <div class="row mb-3 mt-3 ps-4">
          <label for="delivery_time" class="col-sm-2 col-form-label">Time</label>
          <div class="col-sm-4">
            <input type="date" class="form-control" id="delivery_time" placeholder="" name="delivery_time" value="<?php echo $data['delivery_time'] ?? '' ?>" required disabled>
          </div>

          <label for="delivery_pct" class="col-sm-2 col-form-label">PCT</label>
          <div class="col-sm-4">
            <input type="text" class="form-control" id="delivery_pct" placeholder="Ex: abc" name="delivery_pct" required value="<?php echo $data['delivery_pct'] ?? '' ?>" disabled>
          </div>
        </div>

        <?php
        foreach ($data['payment'] as $customField) {
        ?>
          <div class="row mb-3 mt-3 ps-4 d-flex align-items-center">
            <div class="col-sm-2 pb-2">
              <input type="text" class="form-control" name="customFieldName[]" placeholder="Ex: Custom Value Name" required value="<?= $customField['name'] ?>" disabled>
            </div>
            <div class="col-sm-2 pb-2">
              <input type="text" class="form-control" name="customField[]" placeholder="Ex: 1.000.000" required value="<?= number_format($customField['value'], 0, ",", ".") ?>" disabled>
            </div>
            <div class="col-sm-1 d-flex pb-2">
              <label for="customUnit" class="col-form-label"></label>
              <div class="input-group">
                <input type="text" class="form-control" name="customUnit[]" placeholder="VND" value="<?= $customField['unit'] ?? '' ?>" disabled>
              </div>
            </div>
            <div class="col-sm-2 d-flex pb-2">
              <label for="customVat" class="col-form-label">V.A.T</label>
              <div class="input-group ps-2">
                <input type="text" class="form-control" name="customVat[]" placeholder="%" required value="<?= $customField['vat'] ?>" disabled>
                <span class="input-group-text">%</span>
              </div>
            </div>
            <div class="form-check col-sm-2 d-flex flex-column gap-2 align-items-start pb-2">
              <select class="form-select" aria-label="Default select example" name="customContSet[]" required disabled>
                <option value="cont" <?= $customField['contSet'] === 'cont' ? 'selected' : '' ?>>Cont</option>
                <option value="set" <?= $customField['contSet'] === 'set' ? 'selected' : '' ?>>Set</option>
              </select>
            </div>
            <div class="form-check col-sm-1 d-flex gap-2 align-items-center pb-2">
              <input class="form-check-input" type="checkbox" name="customIncl[]" <?= $customField['incl'] == 'on' ? 'checked' : '' ?> disabled>
              <label class="form-check-label">INCL</label>
            </div>
            <div class="form-check col-sm-1 d-flex gap-2 align-items-center pb-2">
              <input class="form-check-input" type="checkbox" name="customExcl[]" <?= $customField['excl'] == 'on' ? 'checked' : '' ?> disabled>
              <label class="form-check-label">EXCL</label>
            </div>
            <div class="form-check col-sm-1 d-flex justify-content-end gap-2 align-items-center pb-2">
            </div>
          </div>
        <?php
        }
        ?>
      </div>

      <div>
        <h6>III. OPERATION INFORMATION</h6>

        <div class="row mb-3 mt-3 ps-4">
          <label for="operatorName" class="col-sm-2 col-form-label">Operator</label>
          <div class="col-sm-4">
            <input type="text" class="form-control" id="operatorName" name="operatorName" required disabled value="<?= $data['operator_name'] ?>">
          </div>

          <label for="customs_manifest_on" class="col-sm-2 col-form-label">Customs manifest no</label>
          <div class="col-sm-4">
            <input type="text" class="form-control" id="customs_manifest_on" placeholder="" name="customs_manifest_on" required disabled value="<?= $data['customs_manifest_on'] ?>">
          </div>
        </div>

        <!-- Expense Table Section -->
        <h6>EXPENSE</h6>
        <table class="table table-bordered mb-4">
          <thead>
            <tr>
              <th scope="col" rowspan="2" class="align-middle">No</th>
              <th scope="col" rowspan="2" class="align-middle">Kind of Expense</th>
              <th scope="col" colspan="2">Amount</th>
              <th scope="col" rowspan="2" class="align-middle">Payee</th>
              <th scope="col" rowspan="2" class="align-middle">Doc. No.</th>
              <th scope="col" rowspan="2" class="align-middle">Attachment</th>
            </tr>
            <tr>
              <th>Actual</th>
              <th>Số hóa đơn</th>
            </tr>
          </thead>
          <tbody class="tableBody">
            <?php
            foreach ($data['expenses'] as $index => $expense) {
            ?>
              <tr>
                <td><?= $index ?></td>
                <td><input type="text" class="form-control" required disabled value="<?= $expense['expense_kind'] ?>"></td>
                <td><input type="text" class="form-control" required id="expenses_amount" disabled value="<?= number_format($expense['expense_amount'], 0, ',', '.') ?>"></td>
                <td><input type="text" class="form-control" required disabled value="<?= $expense['so_hoa_don'] ?>"></td>
                <td><input type="text" class="form-control" required disabled value="<?= $expense['expense_payee'] ?>"></td>
                <td><input type="text" class="form-control" disabled value="<?= $expense['expense_doc'] ?>"></td>
                <?php
                if (!empty($expense['expense_files'])) {
                  echo "<td>";
                  foreach ($expense['expense_files'] as $file) {
                    echo "<a href=\"" . $file . "\" target=\"_blank\">Xem hóa đơn</a><br/>";
                  }
                  echo "</tr>";
                } else {
                  echo "<td></td>"; // Empty cell if there's no filename
                }
                ?>
              </tr>
            <?php
            }
            ?>

            <!-- Additional rows as needed -->
          <tfoot>
            <tr>
              <td colspan="2" class="text-end">TOTAL</td>
              <td><input type="text" name="total_actual" id="total_actual" class="form-control" value="<?= $data['total_actual'] ?>" disabled></td>
              <td></td>
              <td>
                RECEIVED BACK ON: <input type="text" class="form-control" name="received_back_on" value="<?= $data['received_back_on'] ?>" disabled>
              </td>
              <td colspan="3">
                BY: <input type="text" class="form-control" name="by" value="<?= $data['by'] ?>" disabled>
              </td>
            </tr>
          </tfoot>

          </tbody>
        </table>


        <h6>DOCUMENTS REVERT/h6>

          <div class="row mb-3 mt-3 ps-4">
            <label for="operatorName" class="col-sm-1 col-form-label">Salesman:</label>
            <div class="col-sm-3">
              <input type="text" class="form-control" id="operatorName" name="operatorName" required disabled value="<?= $leaderData['fullname'] ?>">
            </div>

            <label for="customs_manifest_on" class="col-sm-1 col-form-label">Date:</label>
            <div class="col-sm-2">
              <input type="text" class="form-control" id="customs_manifest_on" placeholder="" name="customs_manifest_on" required disabled value="<?= $data['approval'][0]['time'] ?>">
            </div>

            <label for="customs_manifest_on" class="col-sm-2 col-form-label">Approved by:</label>
            <div class="col-sm-3">
              <input type="text" class="form-control" id="customs_manifest_on" placeholder="" name="customs_manifest_on" required disabled value="<?= $saleUserData['fullname'] ?>">
            </div>
          </div>
      </div>

      <!-- Submission Button -->
    </form>
    <div class="d-flex align-items-center justify-content-end gap-3">
      <div class="d-flex justify-content-end pb-3">
        <button type="button" class="btn btn-success" id="tu_choi_btn" data-bs-toggle="modal" data-bs-target="#exampleModal">Chi Tiền</button>
      </div>
      <!-- <div class="d-flex justify-content-end pb-3">
        <button type="submit" class="btn btn-success" id="phe_duyet_btn" onclick="handleApprovePayment('approved')">Phê duyệt</button>
      </div> -->

    </div>

    <!-- modal từ chối -->
    <div class="modal fade" id="exampleModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h1 class="modal-title fs-5" id="exampleModalLabel">Xác nhận chi tiền</h1>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <form>
              <div class="mb-3">
                <label for="message-text" class="col-form-label">Ghi chú:</label>
                <textarea class="form-control" id="message-text"></textarea>
              </div>
            </form>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="button" class="btn btn-primary" id="rejectSubmitButton">Submit</button>
          </div>
        </div>
      </div>
    </div>
  </div>
  <script src="./index.js"></script>
  <script>
    const itemData = <?= json_encode($data) ?>;
    const operatorUserData = <?= json_encode($operatorUserData) ?>;
    const directorData = <?= json_encode($directorData) ?>;

    // const pheDuyetBtn = document.getElementById('phe_duyet_btn');
    const tuChoiBtn = document.getElementById('tu_choi_btn');

    const expensesAmount = document.getElementById('expenses_amount');
    const expensesAmountValue = expensesAmount.value;
    expensesAmount.value = formatNumber(expensesAmountValue);

    const totalActual = document.getElementById('total_actual');
    const totalActualValue = totalActual.value;
    totalActual.value = formatNumber(totalActualValue);

    const soTienDoc = document.getElementById('soTien');
    const advanceAmount = soTienDoc.value;
    soTienDoc.value = formatNumber(advanceAmount); // Chèn dấu phẩy vào số
    const advanceAmountText = convertNumberToTextVND(advanceAmount);
    document.getElementById('soTienBangChu').value = advanceAmountText;

    // Toggle the responsive class to show/hide the menu
    function toggleMenu() {
      var menu = document.querySelector('.menu');
      menu.classList.toggle('responsive');
    }

    function updateAmountText(currentInput) {
      const advanceAmount = currentInput.value.replace(/\./g, ''); // Loại bỏ dấu phẩy
      // check if not a number
      if (isNaN(advanceAmount)) {
        alert('Vui lòng nhập số');
        currentInput.value = '';
        return;
      }
      currentInput.value = formatNumber(advanceAmount); // Chèn dấu phẩy vào số
      const advanceAmountText = convertNumberToTextVND(advanceAmount);
      document.getElementById('soTienBangChu').value = advanceAmountText;
    }

    const exampleModal = document.getElementById('exampleModal')
    if (exampleModal) {
      exampleModal.addEventListener('show.bs.modal', event => {
        // Button that triggered the modal
        const button = event.relatedTarget
        // Extract info from data-bs-* attributes
      })
    }

    document.getElementById('rejectSubmitButton').addEventListener('click', () => {
      const messageText = document.getElementById('message-text').value;
      handleApprovePayment('approved', messageText);
      // Close the modal
      const modal = bootstrap.Modal.getInstance(document.getElementById('exampleModal'));
      modal.hide();
    });

    function getFirstExpenseAmountWithPayee(item, payee) {
      if (item.expenses && item.expenses.length > 0) { // Check if expenses exist and are non-empty
        const expense = item.expenses.find(exp => exp.expense_payee === payee);
        if (expense) {
          return expense.expense_amount;
        }
      }
      return ''; // Return null if no matching expense is found
    }

    function formatNumber(num) {
      return num.replace(/\B(?=(\d{3})+(?!\d))/g, ".");
    }

    function convertNumberToTextVND(total) {
      try {
        let rs = "";
        let ch = ["không", "một", "hai", "ba", "bốn", "năm", "sáu", "bảy", "tám", "chín"];
        let rch = ["lẻ", "mốt", "", "", "", "lăm"];
        let u = ["", "mươi", "trăm", "ngàn", "", "", "triệu", "", "", "tỷ", "", "", "ngàn", "", "", "triệu"];
        let nstr = total.toString();
        let n = Array.from(nstr).reverse().map(Number);
        let len = n.length;

        for (let i = len - 1; i >= 0; i--) {
          if (i % 3 === 2) {
            if (n[i] === 0 && n[i - 1] === 0 && n[i - 2] === 0) continue;
          } else if (i % 3 === 1) {
            if (n[i] === 0) {
              if (n[i - 1] === 0) continue;
              else {
                rs += " " + rch[n[i]];
                continue;
              }
            }
            if (n[i] === 1) {
              rs += " mười";
              continue;
            }
          } else if (i !== len - 1) {
            if (n[i] === 0) {
              if (i + 2 <= len - 1 && n[i + 2] === 0 && n[i + 1] === 0) continue;
              rs += " " + (i % 3 === 0 ? u[i] : u[i % 3]);
              continue;
            }
            if (n[i] === 1) {
              rs += " " + ((n[i + 1] === 1 || n[i + 1] === 0) ? ch[n[i]] : rch[n[i]]);
              rs += " " + (i % 3 === 0 ? u[i] : u[i % 3]);
              continue;
            }
            if (n[i] === 5) {
              if (n[i + 1] !== 0) {
                rs += " " + rch[n[i]];
                rs += " " + (i % 3 === 0 ? u[i] : u[i % 3]);
                continue;
              }
            }
          }
          rs += (rs === "" ? " " : ", ") + ch[n[i]];
          rs += " " + (i % 3 === 0 ? u[i] : u[i % 3]);
        }

        rs = rs.trim().replace(/lẻ,|mươi,|trăm,|mười,/g, match => match.slice(0, -1));

        if (rs.slice(-1) !== " ") {
          rs += " đồng";
        } else {
          rs += "đồng";
        }

        return rs.charAt(0).toUpperCase() + rs.slice(1);
      } catch (ex) {
        console.error(ex);
        return "";
      }
    }

    function handleApprovePayment(status, message = '') {
      const instructionNo = <?= json_encode($instructionNo) ?>; // Instruction number from PHP
      const updateData = {
        instruction_no: instructionNo,
        approval_status: status,
        message: message
      };

      // disable button
      tuChoiBtn.disabled = true;

      // Send data to the server using fetch
      fetch('update_payment_status.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json'
          },
          body: JSON.stringify(updateData)
        })
        .then(response => response.json())
        .then(async data => {
          if (data.success) {
            // Tạo nội dung tin nhắn để gửi
            let telegramMessage = '';
            if (status === 'approved') {
              telegramMessage = `**Yêu cầu đã được Kế toán phê duyệt!**\n` +
                `ID yêu cầu: ${itemData.instruction_no}\n` +
                `Người đề nghị: ${itemData.operator_name}\n` +
                `Số tiền thanh toán: ${formatNumber((data.data.amount).toString())} VND\n` +
                `Số tiền thanh toán bằng chữ: ${convertNumberToTextVND(data.data.amount)}\n` +
                `Tên khách hàng: ${itemData.shipper}\n` +
                `Số tờ khai: ${itemData.customs_manifest_on}\n` +
                `Người phê duyệt:  <?php echo $fullName; ?> - <?php echo $email; ?>\n` +
                `Thời gian phê duyệt: ${data.data.approval[3].time}`;
            } else {
              telegramMessage = `**Yêu cầu đã bị Kế toán từ chối!**\n` +
                `ID yêu cầu: ${itemData.instruction_no}\n` +
                `Người đề nghị: ${itemData.operator_name}\n` +
                `Số tiền thanh toán: ${formatNumber((data.data.amount).toString())} VND\n` +
                `Số tiền thanh toán bằng chữ: ${convertNumberToTextVND(data.data.amount)}\n` +
                `Tên khách hàng: ${itemData.shipper}\n` +
                `Số tờ khai: ${itemData.customs_manifest_on}\n` +
                `Lý do: **${message}**\n` +
                `Người từ chối:  <?php echo $fullName; ?> - <?php echo $email; ?>\n` +
                `Thời gian từ chối: ${data.data.approval[3].time}`;
            }

            // Gửi tin nhắn đến Telegram
            const res = await fetch('../../../sendTelegram.php', {
              method: 'POST',
              headers: {
                'Content-Type': 'application/json'
              },
              body: JSON.stringify({
                message: telegramMessage,
                id_telegram: operatorUserData.phone // Truyền thêm thông tin operator_phone
              })
            });

            // export pdf
            if (res.status === 200 && data.data) {
              await fetchTemplateAndFill({
                ...data.data,
                amount: formatNumber((data.data.amount).toString()),
                amountWords: convertNumberToTextVND(data.data.amount),
              }); // Gọi hàm fill template+
              await fetchTemplateAndFillForOperator({
                ...data.data,
                amount: formatNumber((data.data.amount).toString()),
                amountWords: convertNumberToTextVND(data.data.amount),
              }); // Gọi hàm fill template+
            }

            alert("Approval status updated successfully!");
            // enable button
            tuChoiBtn.disabled = false;

            window.location.href = '../../index.php';
          } else {
            alert("Failed to update approval status: " + data.message);
            // enable button
            tuChoiBtn.disabled = false;
          }
        })
        .catch(error => {
          console.error('Error:', error);
          alert("An error occurred. Please try again.");
          // enable button
          tuChoiBtn.disabled = false;
        });
    }

    async function fetchTemplateAndFill(request) {
      const pdfUrl = 'export_pdf.php'; // Đường dẫn đến file export_pdf.php
      console.log('Request:', request);

      try {
        // Sử dụng fetch để gửi dữ liệu yêu cầu
        const response = await fetch(pdfUrl, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
          },
          body: JSON.stringify(request)
        });

      } catch (error) {
        console.error('Lỗi khi tạo PDF:', error);
      }
    }
    async function fetchTemplateAndFillForOperator(request) {
      const pdfUrl = 'export_pdf_operator.php'; // Đường dẫn đến file export_pdf.php
      console.log('Request:', request);

      try {
        // Sử dụng fetch để gửi dữ liệu yêu cầu
        const response = await fetch(pdfUrl, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
          },
          body: JSON.stringify(request)
        });

      } catch (error) {
        console.error('Lỗi khi tạo PDF:', error);
      }
    }

    // document.addEventListener('DOMContentLoaded', function() {
    //   loadDetail(); // Gọi hàm loadRequests khi trang được tải
    // });

    // function loadDetail() {
    //   const soTienDocument = document.getElementById('soTien');
    //   const soTienBangChu = document.getElementById('soTienBangChu');
    //   const amount = formatNumber((getFirstExpenseAmountWithPayee(itemData, 'OPS')).toString());
    //   const amountWords = convertNumberToTextVND(getFirstExpenseAmountWithPayee(itemData, 'OPS'));
    //   soTienDocument.value = `${amount} VND`;
    //   soTienBangChu.value = amountWords;
    // }
  </script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>