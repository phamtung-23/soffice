<?php
session_start();

// Kiểm tra nếu người dùng đã đăng nhập, thì tiếp tục trang, nếu không thì chuyển hướng về trang login
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'director') {
  echo "<script>alert('Bạn chưa đăng nhập! Vui lòng đăng nhập lại.'); window.location.href = '../index.php';</script>";
  exit();
}


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

  // Search for the entry with the matching InstructionNo
  foreach ($jsonData as $entry) {
    if ($entry['instruction_no'] == $instructionNo) {
      $data = $entry;
      // user data
      $operatorUserData = null;
      foreach ($jsonDataUser as $user) {
        if ($user['email'] == $entry['operator_email']) {
          $operatorUserData = $user;
          break;
        }
      }

      // get leader data
      $leaderData = null;
      foreach ($jsonDataUser as $user) {
        if ($user['role'] == 'leader' && $user['email'] == $entry['approval'][0]['email']) {
          $leaderData = $user;
          break;
        }
      }

      // get sale data
      $saleUserData = null;
      foreach ($jsonDataUser as $user) {
        if ($user['role'] == 'sale' && $user['email'] == $entry['approval'][1]['email']) {
          $saleUserData = $user;
          break;
        }
      }

      // get leader data
      $directorData = null;
      foreach ($jsonDataUser as $user) {
        if ($user['role'] == 'director' && $user['email'] == $entry['approval'][2]['email']) {
          $directorData = $user;
          break;
        }
      }

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
</head>

<body>
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
            <input type="text" class="form-control" id="soTien" placeholder="" name="soTien" required disabled value="<?= $data['volume'] ?>">
          </div>
        </div>
        <div class="row mb-3 mt-3 ps-4">
          <label for="soTienBangChu" class="col-sm-2 col-form-label">Số tiền bằng chữ:</label>
          <div class="col-sm-10">
            <input type="text" class="form-control" id="soTienBangChu" placeholder="" name="soTienBangChu" required disabled value="<?= $data['volume'] ?>">
          </div>
        </div>
        <div class="row mb-3 mt-3 ps-4">
          <label for="payment_lo" class="col-sm-2 col-form-label">Nội dung thanh toán:</label>
          <div class="col-sm-10">
            <input type="text" class="form-control" id="payment_lo" placeholder="" name="payment_lo" required disabled value="<?php echo $data['shipper']."/".$data['volume']."/".$data['customs_manifest_on']." ".$data['payment_lo'] ?>">
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

      <div>
        <h6>II. PICK UP/DELIVERY INFORMATION:</h6>
        <div class="row mb-3 mt-3 ps-4">
          <label for="delivery_address" class="col-sm-2 col-form-label">Address</label>
          <div class="col-sm-10">
            <input type="text" class="form-control" id="delivery_address" name="delivery_address" required value="<?= $data['delivery_address'] ?>" disabled>
          </div>
        </div>

        <div class="row mb-3 mt-3 ps-4">
          <label for="delivery_time" class="col-sm-2 col-form-label">Time</label>
          <div class="col-sm-4">
            <input type="date" class="form-control" id="delivery_time" placeholder="" name="delivery_time" required value="<?= $data['delivery_time'] ?>" disabled>
          </div>

          <label for="delivery_pct" class="col-sm-2 col-form-label">PCT</label>
          <div class="col-sm-4">
            <input type="text" class="form-control" id="delivery_pct" placeholder="" name="delivery_pct" required value="<?= $data['delivery_pct'] ?>" disabled>
          </div>
        </div>
        <div class="row mb-3 mt-3 ps-4 d-flex align-items-center">
          <label for="trucking" class="col-sm-2 col-form-label">Trucking</label>
          <div class="col-sm-3">
            <input type="text" class="form-control" id="trucking" placeholder="" name="trucking" required value="<?= $data['trucking'] ?>" disabled>
          </div>
          <label for="trunkingVat" class="col-sm-1 col-form-label">V.A.T</label>
          <div class="col-sm-2">
            <div class="input-group">
              <input type="text" class="form-control" aria-label="Amount (to the nearest dollar)" name="trunkingVat" required value="<?= $data['trunkingVat'] ?>" disabled>
              <span class="input-group-text">%</span>
            </div>
          </div>
          <div class="form-check col-sm-2 d-flex gap-2 align-items-center">
            <input class="form-check-input" type="checkbox" id="trunkingIncl" name="trunkingIncl" disabled <?= $data['trunkingIncl'] == 'on' ? 'checked' : ''; ?>>
            <label class="form-check-label" for="trunkingIncl">
              INCL
            </label>
          </div>
          <div class="form-check col-sm-2 d-flex gap-2 align-items-center">
            <input class="form-check-input" type="checkbox" id="trunkingExcl" name="trunkingExcl" disabled <?= $data['trunkingExcl'] == 'on' ? 'checked' : ''; ?>>
            <label class="form-check-label" for="trunkingExcl">
              EXCL
            </label>
          </div>
        </div>
        <div class="row mb-3 mt-3 ps-4 d-flex align-items-center">
          <label for="stuffing" class="col-sm-2 col-form-label">Stuffing & customs & Phyto</label>
          <div class="col-sm-3">
            <input type="text" class="form-control" id="stuffing" placeholder="" name="stuffing" required value="<?= $data['stuffing'] ?>" disabled>
          </div>
          <label for="stuffingVat" class="col-sm-1 col-form-label">V.A.T</label>
          <div class="col-sm-2">
            <div class="input-group">
              <input type="text" class="form-control" aria-label="Amount (to the nearest dollar)" name="stuffingVat" required value="<?= $data['stuffingVat'] ?>" disabled>
              <span class="input-group-text">%</span>
            </div>
          </div>
          <div class="form-check col-sm-2 d-flex gap-2 align-items-center">
            <input class="form-check-input" type="checkbox" id="stuffingIncl" name="stuffingIncl" disabled <?= $data['stuffingIncl'] == 'on' ? 'checked' : ''; ?>>
            <label class="form-check-label" for="stuffingIncl">
              INCL
            </label>
          </div>
          <div class="form-check col-sm-2 d-flex gap-2 align-items-center">
            <input class="form-check-input" type="checkbox" id="stuffingExcl" name="stuffingExcl" disabled <?= $data['stuffingExcl'] == 'on' ? 'checked' : ''; ?>>
            <label class="form-check-label" for="stuffingExcl">
              EXCL
            </label>
          </div>
        </div>
        <div class="row mb-3 mt-3 ps-4 d-flex align-items-center">
          <label for="liftOnOff" class="col-sm-2 col-form-label">Lift on/off</label>
          <div class="col-sm-3">
            <input type="text" class="form-control" id="liftOnOff" placeholder="" name="liftOnOff" required value="<?= $data['liftOnOff'] ?>" disabled>
          </div>
          <label for="liftOnOffVat" class="col-sm-1 col-form-label">V.A.T</label>
          <div class="col-sm-2">
            <div class="input-group">
              <input type="text" class="form-control" aria-label="Amount (to the nearest dollar)" name="liftOnOffVat" required value="<?= $data['liftOnOffVat'] ?>" disabled>
              <span class="input-group-text">%</span>
            </div>
          </div>
          <div class="form-check col-sm-2 d-flex gap-2 align-items-center">
            <input class="form-check-input" type="checkbox" id="liftOnOffIncl" name="liftOnOffIncl" disabled <?= $data['liftOnOffIncl'] == 'on' ? 'checked' : ''; ?>>
            <label class="form-check-label" for="liftOnOffIncl">
              INCL
            </label>
          </div>
          <div class="form-check col-sm-2 d-flex gap-2 align-items-center">
            <input class="form-check-input" type="checkbox" id="liftOnOffExcl" name="liftOnOffExcl" disabled <?= $data['liftOnOffExcl'] == 'on' ? 'checked' : ''; ?>>
            <label class="form-check-label" for="liftOnOffExcl">
              EXCL
            </label>
          </div>
        </div>
        <div class="row mb-3 mt-3 ps-4 d-flex align-items-center">
          <label for="chiHo" class="col-sm-2 col-form-label">Chi hộ</label>
          <div class="col-sm-3">
            <input type="text" class="form-control" id="chiHo" placeholder="" name="chiHo" required value="<?= $data['chiHo'] ?>" disabled>
          </div>
          <label for="chiHoVat" class="col-sm-1 col-form-label">V.A.T</label>
          <div class="col-sm-2">
            <div class="input-group">
              <input type="text" class="form-control" aria-label="Amount (to the nearest dollar)" name="chiHoVat" required value="<?= $data['chiHoVat'] ?>" disabled>
              <span class="input-group-text">%</span>
            </div>
          </div>
          <div class="form-check col-sm-2 d-flex gap-2 align-items-center">
            <input class="form-check-input" type="checkbox" id="chiHoIncl" name="chiHoIncl" disabled <?= $data['chiHoIncl'] == 'on' ? 'checked' : ''; ?>>
            <label class="form-check-label" for="chiHoIncl">
              INCL
            </label>
          </div>
          <div class="form-check col-sm-2 d-flex gap-2 align-items-center">
            <input class="form-check-input" type="checkbox" id="chiHoExcl" name="chiHoExcl" disabled <?= $data['chiHoExcl'] == 'on' ? 'checked' : ''; ?>>
            <label class="form-check-label" for="chiHoExcl">
              EXCL
            </label>
          </div>
        </div>
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
            </tr>
            <tr>
              <th>Actual</th>
              <th>Actual</th>
            </tr>
          </thead>
          <tbody class="tableBody">
            <?php
            foreach ($data['expenses'] as $index => $expense) {
            ?>
              <tr>
                <td><?= $index ?></td>
                <td><input type="text" class="form-control" required disabled value="<?= $expense['expense_kind'] ?>"></td>
                <td><input type="number" class="form-control" required disabled value="<?= $expense['expense_amount'] ? $expense['expense_amount'] : null ?>"></td>
                <td><input type="number" class="form-control" required disabled value="<?php if (isset($data['expense_amount1'])) {
                                                                                          echo $data['expense_amount1'];
                                                                                        } else {
                                                                                          echo null;
                                                                                        } ?>"></td>
                <td><input type="text" class="form-control" required disabled value="<?= $expense['expense_payee'] ?>"></td>
                <td><input type="text" class="form-control" disabled value="<?= $expense['expense_doc'] ?>"></td>
              </tr>
            <?php
            }
            ?>

            <!-- Additional rows as needed -->
          <tfoot>
            <tr>
              <td colspan="2" class="text-end">TOTAL</td>
              <td><input type="number" name="total_actual" class="form-control" required value="<?= $data['total_actual'] ?>" disabled></td>
              <td><input type="number" name="total_actual1" class="form-control" required value="<?= $data['total_actual1'] ?>" disabled></td>
              <td>
                RECEIVED BACK ON: <input type="text" class="form-control" name="received_back_on" value="<?= $data['received_back_on'] ?>" disabled>
              </td>
              <td colspan="2">
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
        <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#exampleModal">Từ chối</button>
      </div>
      <div class="d-flex justify-content-end pb-3">
        <button type="submit" class="btn btn-success" onclick="handleApprovePayment('approved')">Phê duyệt</button>
      </div>

    </div>

    <!-- modal từ chối -->
    <div class="modal fade" id="exampleModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h1 class="modal-title fs-5" id="exampleModalLabel">Xác nhận từ chối</h1>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <form>
              <div class="mb-3">
                <label for="message-text" class="col-form-label">Lý do:</label>
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
      handleApprovePayment('rejected', messageText);
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
      return num.replace(/\B(?=(\d{3})+(?!\d))/g, ",");
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
              telegramMessage = `**Yêu cầu đã được Giám đốc phê duyệt!**\n` +
                `ID yêu cầu: ${itemData.instruction_no}\n` +
                `Người đề nghị: ${itemData.operator_name}\n` +
                `Số tiền thanh toán: ${formatNumber((getFirstExpenseAmountWithPayee(itemData, 'OPS')).toString())} VND\n` +
                `Số tiền thanh toán bằng chữ: ${convertNumberToTextVND(getFirstExpenseAmountWithPayee(itemData, 'OPS'))}\n` +
                `Tên khách hàng: ${itemData.shipper}\n` +
                `Số tờ khai: ${itemData.customs_manifest_on}\n` +
                `Người phê duyệt:  ${directorData.fullname} - ${itemData.approval[2].email}\n` +
                `Thời gian phê duyệt: ${itemData.approval[0].time}`;
            } else {
              telegramMessage = `**Yêu cầu đã bị Giám đốc từ chối!**\n` +
                `ID yêu cầu: ${itemData.instruction_no}\n` +
                `Người đề nghị: ${itemData.operator_name}\n` +
                `Số tiền thanh toán: ${formatNumber((getFirstExpenseAmountWithPayee(itemData, 'OPS')).toString())} VND\n` +
                `Số tiền thanh toán bằng chữ: ${convertNumberToTextVND(getFirstExpenseAmountWithPayee(itemData, 'OPS'))}\n` +
                `Tên khách hàng: ${itemData.shipper}\n` +
                `Số tờ khai: ${itemData.customs_manifest_on}\n` +
                `Lý do: **${message}**\n` +
                `Người từ chối:  ${directorData.fullname} - ${itemData.approval[2].email}\n` +
                `Thời gian từ chối: ${itemData.approval[0].time}`;
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
                amount: formatNumber((getFirstExpenseAmountWithPayee(itemData, 'OPS')).toString()),
                amountWords: convertNumberToTextVND(getFirstExpenseAmountWithPayee(itemData, 'OPS')),  
              }); // Gọi hàm fill template+
            }

            alert("Approval status updated successfully!");
            window.location.href = '../../index.php';
          } else {
            alert("Failed to update approval status: " + data.message);
          }
        })
        .catch(error => {
          console.error('Error:', error);
          alert("An error occurred. Please try again.");
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

    document.addEventListener('DOMContentLoaded', function() {
      loadDetail(); // Gọi hàm loadRequests khi trang được tải
    });

    function loadDetail() {
      const soTienDocument = document.getElementById('soTien');
      const soTienBangChu = document.getElementById('soTienBangChu');
      const amount = formatNumber((getFirstExpenseAmountWithPayee(itemData, 'OPS')).toString());
      const amountWords = convertNumberToTextVND(getFirstExpenseAmountWithPayee(itemData, 'OPS'));
      soTienDocument.value = `${amount} VND`;
      soTienBangChu.value = amountWords;
    }
  </script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>