<?php
session_start();

// Kiểm tra nếu người dùng đã đăng nhập, thì tiếp tục trang, nếu không thì chuyển hướng về trang login
if (!isset($_SESSION['user_id'])) {
  echo "<script>alert('Bạn chưa đăng nhập! Vui lòng đăng nhập lại.'); window.location.href = '../../index.php';</script>";
  exit();
}


// Lấy tên đầy đủ từ session
$fullName = $_SESSION['full_name'];
$userEmail = $_SESSION['user_id'];
$userRole = $_SESSION['role'];
// Lấy năm hiện tại
$currentYear = date("Y");
// Đọc các năm từ tệp JSON trong thư mục
$years = [];
foreach (glob('../../database/request_*.json') as $filename) {
  // Lấy năm từ tên tệp
  if (preg_match('/request_(\d{4})\.json/', basename($filename), $matches)) {
    $years[] = $matches[1]; // Thêm năm vào mảng
  }
}

// Xóa trùng lặp và sắp xếp các năm
$years = array_unique($years);
sort($years);


?>


<!DOCTYPE html>
<html lang="vi">

<head>
  <meta charset="UTF-8">
  <title>Accountant Dashboard</title>
  <link rel="stylesheet" href="styles.css">
  <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.2/html2pdf.bundle.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/0.4.1/html2canvas.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/crypto-js/4.1.1/crypto-js.min.js"></script>
  <script>
    let userEmail = ('<?php echo $userEmail; ?>');
    let userRole = ('<?php echo $userRole; ?>');
    let currentRequest = null;
    // Hàm để lấy năm từ dropdown
    function getSelectedYear() {
      const yearSelect = document.getElementById('year-select');
      return yearSelect.value;
    }
    // Tải yêu cầu dựa trên năm đã chọn
    function loadRequests() {
      const year = getSelectedYear();
      const operatorFilter = document.getElementById('operator-filter').value.toLowerCase();
      const customerFilter = document.getElementById('customer-filter').value.toLowerCase();
      const dateFilter = document.getElementById('date-filter').value;

      fetch(`../../database/request_${year}.json`, {
          cache: "no-store"
        })
        .then(response => {
          if (!response.ok) {
            throw new Error(`HTTP error! Status: ${response.status}`);
          }
          return response.json();
        })
        .then(data => {
          const tableBody = document.getElementById('requests-table-body');
          tableBody.innerHTML = '';

          const validRequests = data.filter(request => {
            return request.payment_status === 'Chi tiền' &&
              (!operatorFilter || request.full_name.toLowerCase().includes(operatorFilter)) &&
              (!customerFilter || request.customer_name.toLowerCase().includes(customerFilter)) &&
              (!dateFilter || request.payment_time.includes(dateFilter)) && (userRole == 'operator' ? !userEmail || request.operator_email === userEmail : true);
          });

          if (validRequests.length === 0) {
            const noRequestsRow = document.createElement('tr');
            noRequestsRow.innerHTML = '<td colspan="12">Không có yêu cầu nào để hiển thị.</td>';
            tableBody.appendChild(noRequestsRow);
          } else {
            validRequests.forEach(request => {
              const row = document.createElement('tr');
              row.setAttribute('id', 'request-row-' + request.id);

              const cells = [
                request.id,
                request.full_name,
                request.customer_name,
                request.lot_number,
                request.payment_time,
                request.approved_filename ? `<a href="../../director/pdfs/${request.approved_filename}" target="_blank">Xem Phiếu</a>` : ''
              ];

              cells.forEach(cell => {
                const cellElement = document.createElement('td');
                if (String(cell).includes('<a')) {
                  cellElement.innerHTML = cell;
                } else {
                  cellElement.textContent = cell;
                }
                row.appendChild(cellElement);
              });

              tableBody.appendChild(row);
            });
          }

          document.getElementById('requests').style.display = 'block';
        })
        .catch(error => {
          console.error('Error loading requests:', error.message);
        });
    }

    function updateYear() {
      loadRequests(); // Tải lại yêu cầu khi năm được chọn
    }

    document.addEventListener('DOMContentLoaded', function() {
      loadRequests(); // Gọi hàm loadRequests khi trang được tải
    });
  </script>
</head>

<body>
  <div class="header">
    <h1>Trang chủ</h1>
  </div>
  <div class="menu">
    <a href="../../<?php echo $userRole;?>">Home</a>
    <?php
      if ($userRole == 'operator') {
        echo '<a href="../../operator/request.php">Tạo phiếu xin tạm ứng</a>';
      }
    ?>
    <a href="">Danh sách phiếu tạm ứng đã duyệt</a>
    <a href="../../update_signature.php">Cập nhật hình chữ ký</a>
    <a href="../../logout.php" class="logout">Đăng xuất</a>
  </div>
  <div class="container">
    <div class="welcome-message">
      <p>Xin chào, <?php echo $fullName; ?>!</p>
    </div>

    <!-- Thêm Dropdown chọn năm -->
    <div class="form-group">
      <label for="year-select">Chọn năm:</label>
      <select id="year-select" onchange="updateYear()">
        <?php
        // Tạo các tùy chọn cho năm từ mảng $years
        foreach ($years as $year) {
          echo "<option value=\"$year\" " . ($year == $currentYear ? 'selected' : '') . ">$year</option>";
        }
        ?>
      </select>
    </div>

    <!-- Filter Fields -->
    <div class="filters">
      <div class="filter-name-container">
        <label for="operator-filter">Họ tên Operator:</label>
        <input type="text" id="operator-filter" oninput="loadRequests()">

        <label for="customer-filter">Tên Khách hàng:</label>
        <input type="text" id="customer-filter" oninput="loadRequests()">
      </div>

      <div class="filter-date-container">
        <label for="date-filter">Ngày duyệt:</label>
        <input type="date" id="date-filter" oninput="loadRequests()">
      </div>
    </div>

    <!-- Requests table -->
    <div id="requests">
      <h2>Danh sách các yêu cầu tạm ứng đã chi tiền</h2>
      <table>
        <thead>
          <tr>
            <th>ID</th>
            <th>Họ tên Operator</th>
            <th>Tên Khách hàng</th>
            <th>Số thứ tự lô</th>
            <th>Ngày duyệt</th>
            <th>Phiếu tạm ứng</th>
          </tr>
        </thead>
        <tbody id="requests-table-body"></tbody>
      </table>
    </div>

  </div>
  <div class="footer">
    <p>© 2024 Phần mềm soffice phát triển bởi Hienlm 0988838487</p>
  </div>
</body>

</html>