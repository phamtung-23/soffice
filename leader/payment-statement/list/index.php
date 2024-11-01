<?php
session_start();

// Kiểm tra nếu người dùng đã đăng nhập, thì tiếp tục trang, nếu không thì chuyển hướng về trang login
if (!isset($_SESSION['user_id'])) {
  echo "<script>alert('Bạn chưa đăng nhập! Vui lòng đăng nhập lại.'); window.location.href = '../../../index.php';</script>";
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
foreach (glob('../../../database/payment_*.json') as $filename) {
  // Lấy năm từ tên tệp
  if (preg_match('/payment_(\d{4})\.json/', basename($filename), $matches)) {
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

    function handleShowDetail(instructionNo) {
      const year = getSelectedYear();
      window.location.href = `../detail?instruction_no=${instructionNo}&year=${year}`;
    }

    function getFirstExpenseAmountWithPayee(item, payee) {
      if (item.expenses && item.expenses.length > 0) { // Check if expenses exist and are non-empty
        const expense = item.expenses.find(exp => exp.expense_payee === payee);
        if (expense) {
          return expense.expense_amount;
        }
      }
      return ''; // Return null if no matching expense is found
    }
    // Tải yêu cầu dựa trên năm đã chọn
    function loadRequests() {
      const year = getSelectedYear();
      // const operatorFilter = document.getElementById('operator-filter').value.toLowerCase();
      // const customerFilter = document.getElementById('customer-filter').value.toLowerCase();
      // const dateFilter = document.getElementById('date-filter').value;

      fetch(`../../../database/payment_${year}.json`, {
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
            return request.approval[0].status === 'pending' && request.approval[0].email === userEmail;
          });

          if (validRequests.length === 0) {
            const noRequestsRow = document.createElement('tr');
            noRequestsRow.innerHTML = '<td colspan="12">Không có yêu cầu nào để hiển thị.</td>';
            tableBody.appendChild(noRequestsRow);
          } else {
            validRequests.forEach(request => {
              const row = document.createElement('tr');
              row.setAttribute('id', 'request-row-' + request.id);
              console.log(request);
              const cells = [
                request.instruction_no,
                request.operator_name,
                request.shipper,
                request.customs_manifest_on,
                getFirstExpenseAmountWithPayee(request, 'OPS'),
                request.approval[0].status,
                request.approval[1].status,
                request.approval[2].status,
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

              const actionsCell = document.createElement('td');
              actionsCell.innerHTML = `
                  <button style="background-color: #808080;
                    color: white;
                    border: none;
                    border-radius: 5px;
                    padding: 5px 10px;
                    cursor: pointer;" 
                  onclick="handleShowDetail(${request.instruction_no})">Xem chi tiết</button>
              `;
              row.appendChild(actionsCell);

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
    <a href="../../../<?php echo $userRole; ?>">Home</a>
    <?php
    if ($userRole == 'operator') {
      echo '<a href="../../../operator/request.php">Tạo phiếu xin tạm ứng</a>';
    }
    ?>
    <a href="">Danh sách phiếu tạm ứng đã duyệt</a>
    <a href="../../../update_signature.php">Cập nhật hình chữ ký</a>
    <a href="../../../logout.php" class="logout">Đăng xuất</a>
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
    <!-- <div class="filters">
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
    </div> -->

    <!-- Requests table -->
    <div id="requests">
      <h2>Danh sách các phiếu thanh toán</h2>
      <table>
        <thead>
          <tr>
            <th>ID</th>
            <th>Họ tên Operator</th>
            <th>Tên Khách hàng</th>
            <th>Số tờ khai</th>
            <th>Số tiền</th>
            <th>Leader</th>
            <th>Sale</th>
            <th>Director</th>
            <th>Action</th>
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