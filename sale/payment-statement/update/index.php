<?php
session_start();

// Kiểm tra nếu người dùng đã đăng nhập, thì tiếp tục trang, nếu không thì chuyển hướng về trang login
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'sale') {
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
    <form id="form-update" class="needs-validation" novalidate>
      <div class="d-flex flex-column justify-content-center align-items-center">
        <h3 class="fw-bold">PHIẾU ĐỀ NGHỊ THANH TOÁN</h3>
        <!-- <h5 class="mb-4">INLAND SERVICE INTERNAL INSTRUCTION</h5> -->
        <div class="row mb-3 w-50 d-none">
          <label for="instruction_no" class="col-sm-5 col-form-label pr-0 text-end">Instruction No: </label>
          <div class="col-sm-7">
            <input type="text" class="form-control" id="instruction_no" name="instruction_no" value="<?= $data['instruction_no'] ?>">
          </div>
        </div>
      </div>

      <!-- I. SALES INFORMATION -->
      <div>
        <h6>I. SALES INFORMATION:</h6>
        <div class="row mb-3 mt-3 ps-4">
          <label for="shipper" class="col-sm-2 col-form-label">Shipper</label>
          <div class="col-sm-10">
            <input type="text" class="form-control" id="shipper" placeholder="" disabled value="<?= $data['shipper'] ?>">
          </div>
        </div>
        <div class="row mb-3 mt-3 ps-4">
          <label for="billTo" class="col-sm-2 col-form-label">Bill To</label>
          <div class="col-sm-10">
            <input type="text" class="form-control" id="billTo" placeholder="" disabled value="<?= $data['billTo'] ?>">
          </div>
        </div>
        <div class="row mb-3 mt-3 ps-4">
          <label for="volume" class="col-sm-2 col-form-label">Volume</label>
          <div class="col-sm-10">
            <input type="text" class="form-control" id="volume" placeholder="" disabled value="<?= $data['volume'] ?>">
          </div>
        </div>
        <div class="row mb-3 mt-3 ps-4">
          <label for="payment_lo" class="col-sm-2 col-form-label">Lô</label>
          <div class="col-sm-10">
            <input type="text" class="form-control" id="payment_lo" placeholder="" disabled value="<?= $data['payment_lo'] ?>">
          </div>
        </div>
        <div class="row mb-3 mt-3 ps-4">
          <label for="leader" class="col-sm-2 col-form-label">Approve by</label>
          <div class="col-sm-10">
            <div class="dropdown">
              <select class="form-select" aria-label="Default select example" disabled>
                <option value='<?= $data['approval'][0]['email'] ?>'><?= $data['approval'][0]['email'] ?></option>
              </select>
            </div>
          </div>
        </div>
        <div class="row mb-3 mt-3 ps-4">
          <label for="sale" class="col-sm-2 col-form-label">Sale man</label>
          <div class="col-sm-10">
            <div class="dropdown">
              <select class="form-select" aria-label="Default select example" disabled>
                <option value='<?= $data['approval'][1]['email'] ?>'><?= $data['approval'][1]['email'] ?></option>
              </select>
            </div>
          </div>
        </div>
      </div>

      <!-- II. PICK UP/DELIVERY INFORMATION: -->
      <?php if ($userRole === 'sale') {
      ?>
        <div>
          <h6>II. PICK UP/DELIVERY INFORMATION:</h6>
          <div class="row mb-3 mt-3 ps-4">
            <label for="delivery_address" class="col-sm-2 col-form-label">Address</label>
            <div class="col-sm-10">
              <input type="text" class="form-control" id="delivery_address" placeholder="" name="delivery_address" required>
            </div>
          </div>

          <div class="row mb-3 mt-3 ps-4">
            <label for="delivery_time" class="col-sm-2 col-form-label">Time</label>
            <div class="col-sm-4">
              <input type="date" class="form-control" id="delivery_time" placeholder="" name="delivery_time" required>
            </div>

            <label for="delivery_pct" class="col-sm-2 col-form-label">PCT</label>
            <div class="col-sm-4">
              <input type="text" class="form-control" id="delivery_pct" placeholder="" name="delivery_pct" required>
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
            <label for="StuffingVat" class="col-sm-1 col-form-label">V.A.T</label>
            <div class="col-sm-2">
              <div class="input-group">
                <input type="text" class="form-control" aria-label="Amount (to the nearest dollar)" name="StuffingVat" required>
                <span class="input-group-text">%</span>
              </div>
            </div>
            <div class="form-check col-sm-2 d-flex gap-2 align-items-center">
              <input class="form-check-input" type="checkbox" id="StuffingIncl" name="StuffingIncl">
              <label class="form-check-label" for="StuffingIncl">
                INCL
              </label>
            </div>
            <div class="form-check col-sm-2 d-flex gap-2 align-items-center">
              <input class="form-check-input" type="checkbox" id="StuffingExcl" name="StuffingExcl">
              <label class="form-check-label" for="StuffingExcl">
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

      <div>
        <h6>III. OPERATION INFORMATION</h6>

        <div class="row mb-3 mt-3 ps-4">
          <label for="operatorName" class="col-sm-2 col-form-label">Operator</label>
          <div class="col-sm-4">
            <input type="text" class="form-control" id="operatorName" disabled value="<?= $data['operator_name'] ?>">
          </div>

          <label for="customs_manifest_on" class="col-sm-2 col-form-label">Customs manifest no</label>
          <div class="col-sm-4">
            <input type="text" class="form-control" id="customs_manifest_on" placeholder="" disabled value="<?= $data['customs_manifest_on'] ?>">
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
                <td><input type="text" class="form-control" disabled value="<?= $expense['expense_kind'] ?>"></td>
                <td><input type="number" class="form-control" disabled value="<?= $expense['expense_amount'] ? $expense['expense_amount'] : null ?>"></td>
                <td><input type="number" class="form-control" disabled value="<?php if (isset($data['expense_amount1'])) {
                                                                                echo $data['expense_amount1'];
                                                                              } else {
                                                                                echo null;
                                                                              } ?>"></td>
                <td><input type="text" class="form-control" disabled value="<?= $expense['expense_payee'] ?>"></td>
                <td><input type="text" class="form-control" disabled value="<?= $expense['expense_doc'] ?>"></td>
              </tr>
            <?php
            }
            ?>

            <!-- Additional rows as needed -->
          <tfoot>
            <tr>
              <td colspan="2" class="text-end">TOTAL</td>
              <td><input type="number" name="total_actual" class="form-control" value="<?= $data['total_actual'] ?>" disabled></td>
              <td><input type="number" name="total_actual1" class="form-control" value="<?= $data['total_actual1'] ?>" disabled></td>
              <td>
                RECEIVED BACK ON: <input type="text" class="form-control" value="<?= $data['received_back_on'] ?>" disabled>
              </td>
              <td colspan="2">
                BY: <input type="text" class="form-control" value="<?= $data['by'] ?>" disabled>
              </td>
            </tr>
          </tfoot>

          </tbody>
        </table>
      </div>

      <!-- Submission Button -->
      <div class="d-flex justify-content-end pb-3">
        <button type="submit" class="btn btn-success">Trình ký</button>
      </div>
    </form>
    <!-- <div class="d-flex align-items-center justify-content-end gap-3">
      <div class="d-flex justify-content-end pb-3">
        <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#exampleModal">Từ chối</button>
      </div>
    </div> -->
  </div>
  <script src="./index.js"></script>
  <script>
    const itemData = <?= json_encode($data) ?>;
    const operatorUserData = <?= json_encode($operatorUserData) ?>;
    const leaderData = <?= json_encode($leaderData) ?>;
    let updateForm = document.getElementById("form-update");
    updateForm.addEventListener("submit", (e) => {
      e.preventDefault();

      // get data from form
      const formData = new FormData(updateForm);
      const data = Object.fromEntries(formData.entries());

      // handle submit
      const updateData = {
        data: data
      };

      // Send data to the server using fetch
      fetch('update_payment_delivery.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json'
          },
          body: JSON.stringify(updateData)
        })
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            alert("Delivery data updated successfully!");
            window.location.href = '../../index.php';
          } else {
            alert("Failed to update approval status: " + data.message);
          }
        })
        .catch(error => {
          console.error('Error:', error);
          alert("An error occurred. Please try again.");
        });
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
  </script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>