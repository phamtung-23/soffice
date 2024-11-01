<?php
session_start();

// Kiểm tra nếu người dùng đã đăng nhập, thì tiếp tục trang, nếu không thì chuyển hướng về trang login
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'leader') {
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

if ($instructionNo !== null) {
  // Load and decode JSON data
  $jsonData = json_decode(file_get_contents($filePath), true);

  // Search for the entry with the matching InstructionNo
  foreach ($jsonData as $entry) {
    if ($entry['instruction_no'] == $instructionNo) {
      $data = $entry;
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
      </div>

      <!-- Submission Button -->
    </form>
    <div class="d-flex align-items-center justify-content-end gap-3">
      <div class="d-flex justify-content-end pb-3">
        <button type="submit" class="btn btn-danger" onclick="handleApprovePayment('rejected')">Từ chối</button>
      </div>
      <div class="d-flex justify-content-end pb-3">
        <button type="submit" class="btn btn-success" onclick="handleApprovePayment('approved')">Phê duyệt</button>
      </div>
    </div>
  </div>
  <script src="./index.js"></script>
  <script>
    function handleApprovePayment(status) {
      const instructionNo = <?= json_encode($instructionNo) ?>; // Instruction number from PHP
      const updateData = {
        instruction_no: instructionNo,
        approval_status: status
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
        .then(data => {
          if (data.success) {
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
  </script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>