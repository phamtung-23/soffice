<?php
session_start();

// Kiểm tra nếu người dùng đã đăng nhập, thì tiếp tục trang, nếu không thì chuyển hướng về trang login
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'operator') {
  echo "<script>alert('Bạn chưa đăng nhập! Vui lòng đăng nhập lại.'); window.location.href = '../index.php';</script>";
  exit();
}


// Lấy tên đầy đủ từ session
$fullName = $_SESSION['full_name'];
$email = $_SESSION['user_id'];
$userRole = $_SESSION['role'];

// Define the path to the JSON file
$filePath = '../../../database/payment.json';

// Get InstructionNo from URL
$instructionNo = isset($_GET['InstructionNo']) ? $_GET['InstructionNo'] : null;

$data = null;

if ($instructionNo !== null) {
  // Load and decode JSON data
  $jsonData = json_decode(file_get_contents($filePath), true);

  // Search for the entry with the matching InstructionNo
  foreach ($jsonData as $entry) {
      if ($entry['instructionNo'] == $instructionNo && $entry['operatorEmail'] == $email) {
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
  <div class="container mt-5">
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
      <?php if ($userRole === 'operator') {
      ?>
        <div>
          <!-- <h6>I. SALES INFORMATION:</h6> -->
          <div class="row mb-3 mt-3 ps-4">
            <label for="nguoiDeNghi" class="col-sm-3 col-form-label">Người đề nghị:</label>
            <div class="col-sm-7">
              <input type="text" class="form-control" id="nguoiDeNghi" placeholder="" name="nguoiDeNghi" required>
            </div>
          </div>
          <div class="row mb-3 mt-3 ps-4">
            <label for="thuocBoPhan" class="col-sm-3 col-form-label">Thuộc bộ phận:</label>
            <div class="col-sm-7">
              <input type="text" class="form-control" id="thuocBoPhan" placeholder="" name="thuocBoPhan" required>
            </div>
          </div>
          <div class="row mb-3 mt-3 ps-4 d-flex align-items-center">
            <label for="soTien" class="col-sm-3 col-form-label">Số tiền:</label>
            <div class="col-sm-7">
              <span class=""><?=$data['totalActual']?></span>
            </div>
          </div>
          <div class="row mb-3 mt-3 ps-4 d-flex align-items-center">
            <label for="soTien" class="col-sm-3 col-form-label">Bằng chữ:</label>
            <div class="col-sm-7">
              <input type="text" class="form-control" id="advance-amount-words" placeholder="" disabled>
            </div>
          </div>
          <div class="row mb-3 mt-3 ps-4 d-flex align-items-center">
            <label for="soTien" class="col-sm-3 col-form-label">Nội dung thanh toán:</label>
            <div class="col-sm-7">
              <span class="fw-bold fs-5"><?php echo $data['customsManifestOn']."/".$data['volume']?>/LO 580</span>
            </div>
          </div>
        </div>
      <?php } ?>

      <!-- Submission Button -->
      <div class="w-100 d-flex justify-content-end pb-3">
        <button type="submit" class="btn btn-primary">Submit</button>
      </div>
    </form>
  </div>
  <script src="./index.js"></script>
  <script>
    window.addEventListener('load', updateAmountText(<?=$data['totalActual']?> || 0));
  </script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>