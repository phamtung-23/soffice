<?php
session_start();

// Kiểm tra nếu người dùng đã đăng nhập, thì tiếp tục trang, nếu không thì chuyển hướng về trang login
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'leader') {
    echo "<script>alert('Bạn chưa đăng nhập! Vui lòng đăng nhập lại.'); window.location.href = '../index.php';</script>";
    exit();
}


// Lấy tên đầy đủ từ session
$fullName = $_SESSION['full_name'];
$userEmail = $_SESSION['user_id']; //

?>


<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Operator Dashboard</title>
    <style>
        body {
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .form-group {
            margin: 15px 0;
        }

        input[type="text"] {
            width: 300px;
            height: 40px;
            font-size: 18px;
            padding: 5px;
            margin: 10px 0;
        }

        button {
            width: 150px;
            height: 50px;
            font-size: 18px;
            padding: 10px;
            cursor: pointer;
        }

        .submit-button {
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 5px;
        }

        .reset-button {
            background-color: #f44336;
            color: white;
            border: none;
            border-radius: 5px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        table, th, td {
            border: 1px solid black;
        }

        th, td {
            padding: 10px;
            text-align: center;
        }

        #reject-reason-modal {
            display: none;
            margin-top: 20px;
            padding: 20px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }
        #approve-reason-modal {
            display: none;
            margin-top: 20px;
            padding: 20px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }

        .hidden {
            display: none;
        }

        .menu {
            background-color: #333;
            overflow: hidden;
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

        .container {
            padding: 20px;
        }

        .welcome-message {
            font-size: 24px;
            margin-bottom: 20px;
            color: blue;
        }

        .menu a.logout {
            float: right;
            background-color: #f44336;
        }

        .menu a.logout:hover {
            background-color: #d32f2f;
        }
        .footer {
            text-align: center;
            margin-top: 40px;
            font-size: 14px;
            color: #888;
        }

    </style>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.2/html2pdf.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/0.4.1/html2canvas.min.js"></script>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/crypto-js/4.1.1/crypto-js.min.js"></script>

    <script>
        let currentRequest = null;
    function fetchWithRetry(url, options, retries = 3, delay = 1000) {
    return fetch(url, options)
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! Status: ${response.status}`);
            }
            return response.json();
        })
        .catch(error => {
            if (retries > 0) {
                console.error(`Fetch failed, retrying... (${retries} retries left)`);
                return new Promise(resolve => setTimeout(resolve, delay))
                    .then(() => fetchWithRetry(url, options, retries - 1, delay));
            }
            throw error; // Ném lỗi nếu không còn lần thử nào
        });
}



function loadRequests() {
    const leader_email ="<?php echo $userEmail; ?>";
    fetch('../operator/request.json', { cache: "no-store" })
        .then(response => {
            //console.log('Response:', response); // Kiểm tra phản hồi
            if (!response.ok) {
                throw new Error(`HTTP error! Status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            //console.log('Data:', data); // Kiểm tra dữ liệu JSON
            const tableBody = document.getElementById('requests-table-body');
            tableBody.innerHTML = ''; // Xóa nội dung cũ

            // Tạo một mảng để chứa các hàng yêu cầu không bị từ chối
            const validRequests = data.filter(request => request.check_status !== 'Từ chối' && request.check_status !== 'Phê duyệt' && request.status !== 'Phê duyệt' && request.status !== 'Từ chối' && request.leader_email === leader_email);

            // Nếu không có yêu cầu hợp lệ nào, có thể thông báo cho người dùng
            if (validRequests.length === 0) {
                const noRequestsRow = document.createElement('tr');
                noRequestsRow.innerHTML = '<td colspan="12">Không có yêu cầu nào để hiển thị.</td>';
                tableBody.appendChild(noRequestsRow);
            } else {
                // Tạo các hàng mới từ yêu cầu hợp lệ
                validRequests.forEach(request => {
                    const row = document.createElement('tr');
                    row.setAttribute('id', 'request-row-' + request.id);

                    // Thêm các ô dữ liệu
                    const cells = [
                        request.id,
                        request.full_name,
                        request.customer_name,
                        request.lot_number,
                        request.quantity,
                        request.unit,
                        request.type_item,
                        formatNumber(request.advance_amount),
                        request.advance_amount_words,
                        request.advance_description,
                        request.date_time
                    ];
                    
                    cells.forEach(cell => {
                        const cellElement = document.createElement('td');
                        cellElement.textContent = cell; // Sử dụng textContent để tránh lỗi HTML
                        row.appendChild(cellElement);
                    });

                    // Thêm nút xem xét và từ chối
                    const actionsCell = document.createElement('td');
                    actionsCell.innerHTML = `
                        <button onclick="reviewRequest(${request.id})">Phê duyệt</button>
                        <button onclick="openRejectModal(${request.id})">Từ chối</button>
                    `;
                    row.appendChild(actionsCell);

                    tableBody.appendChild(row);
                });
            }

            // Hiển thị bảng yêu cầu
            document.getElementById('requests').style.display = 'block';
        })
        .catch(error => {
            //console.error('Error loading requests:', error.message); // In lỗi chi tiết
            //alert('Không thể tải yêu cầu: ' + error.message);
        });
}



  function reviewRequest(requestId) {
    currentRequestId = requestId;
    hideOtherRows(requestId);  // Ẩn các hàng khác
    document.getElementById('approve-reason-modal').style.display = 'block';

}


    function openRejectModal(requestId) {
        currentRequestId = requestId;
        hideOtherRows(requestId);  // Ẩn các hàng khác
        document.getElementById('reject-reason-modal').style.display = 'block';
    }

  async function rejectRequest() {
    const reason = document.getElementById('reject-reason').value;
    const leader_name = "<?php echo $fullName; ?>";
    if (!reason) {
        alert('Vui lòng nhập lý do từ chối!');
        return;
    }

    try {
        const response = await fetch('../operator/request.json', { cache: "no-store" });
        const data = await response.json();
        const request = data.find(req => req.id === currentRequestId); // Tìm yêu cầu theo ID cố định
        
        // Cập nhật thông tin yêu cầu
        request.check_status = 'Từ chối';
        request.check_reject_reason = reason;
        request.check_reject_time = new Date().toISOString();
        request.check_rejected_by = leader_name;
        const advance_amount = request.advance_amount; // Sử dụng thuộc tính của request
        const advance_amountFormatted = advance_amount.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ","); // Định dạng số tiền
        const operator_phone = await getPhoneByEmail(request.operator_email); // Đợi kết quả từ hàm

        // Ghi lại thông tin đã cập nhật vào file JSON
        const updateSuccess = await updateRequests(request); // Gọi hàm updateRequests và chờ kết quả

        if (updateSuccess) {
            // Tạo nội dung tin nhắn để gửi
            const telegramMessage = `**Yêu cầu đã bị Quản lý từ chối!**\n` +
                                    `ID yêu cầu: ${request.id}\n` +
                                    `Người đề nghị: ${request.full_name}\n`+
                                    `Số tiền xin tạm ứng: ${advance_amountFormatted}\n`+
                                    `Số tiền xin tạm ứng bằng chữ: ${request.advance_amount_words}\n`+
                                    `Tên khách hàng: ${request.customer_name}\n`+
                                    `Số tờ khai: ${request.lot_number}\n`+
                                    `Người từ chối: ${request.check_rejected_by}\n` +
                                    `Lý do: **${reason}**\n` +
                                    `Thời gian từ chối: ${request.check_reject_time}`;
            
            // Gửi tin nhắn đến Telegram
                await fetch('../sendTelegram.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        message: telegramMessage,
                        id_telegram: operator_phone // Truyền thêm thông tin operator_phone
                    })
                });
            alert('Yêu cầu đã bị từ chối thành công!');
            cancel(); // Thực hiện hành động hủy nếu cần
        }
    } catch (error) {
        console.error('Error loading requests:', error);
        alert('Failed to load requests.');
    }
}

async function getPhoneByEmail(email) {
    try {
        // Tải dữ liệu từ file users.json
        const response = await fetch('../database/users.json', { cache: "no-store" });

        // Kiểm tra xem phản hồi có hợp lệ hay không
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }

        // Chuyển đổi phản hồi thành JSON
        const users = await response.json();

        // Tìm người dùng theo email
        const user = users.find(user => user.email === email);

        // Kiểm tra xem có người dùng không
        if (user) {
            //console.log("Số điện thoại của người dùng:", user.phone);
            return user.phone; // Trả về số điện thoại nếu tìm thấy
        } else {
            //console.log("Không tìm thấy người dùng với email:", email);
            return null; // Trả về null nếu không tìm thấy
        }
    } catch (error) {
        console.error("Đã xảy ra lỗi:", error);
    }
}


        function cancel() {
         // Tải lại trang mà không lưu bất kỳ thay đổi nào
          location.reload();
        }
    async function updateRequests(data) {
            try {
                const response = await fetch('update_requests.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(data),
                    cache: 'no-store'
                });

                const result = await response.json(); // Parse JSON từ response

                if (result.success) {
                    //alert('Cập nhật thành công!');
                    loadRequests(); // Tải lại bảng yêu cầu nếu cần
                    return true; // Cập nhật thành công
                } else {
                    alert('Cập nhật thất bại: ' + result.message);
                    return false; // Cập nhật thất bại
                }
            } catch (error) {
                console.error('Error updating requests:', error.message);
                alert('Failed to update requests.');
                return false; // Lỗi khi cập nhật
            }
        }




    function hideOtherRows(requestId) {
        // Ẩn tất cả các hàng ngoại trừ hàng có ID tương ứng
        const rows = document.querySelectorAll('tbody tr');
        rows.forEach(row => {
            if (row.id !== 'request-row-' + requestId) {
                row.classList.add('hidden');
            }
        });
    }

    function showAllRows() {
        // Hiển thị lại tất cả các hàng
        const rows = document.querySelectorAll('tbody tr');
        rows.forEach(row => {
            row.classList.remove('hidden');
        });
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

        function updateAmountText() {
            const advanceAmountInput = document.getElementById('advance-amount');
            const advanceAmount = advanceAmountInput.value.replace(/,/g, ''); // Loại bỏ dấu phẩy
            advanceAmountInput.value = formatNumber(advanceAmount); // Chèn dấu phẩy vào số
            const advanceAmountText = convertNumberToTextVND(advanceAmount);
            document.getElementById('advance-amount-words').value = advanceAmountText;
        }

        function formatNumber(num) {
            return num.replace(/\B(?=(\d{3})+(?!\d))/g, ",");
        }
        function updateAmountText() {
    const advanceAmountInput = document.getElementById('money_approve');
    let advanceAmount = advanceAmountInput.value.replace(/,/g, ''); // Loại bỏ dấu phẩy

    // Kiểm tra nếu giá trị là số và lớn hơn 0
    if (!isNaN(advanceAmount) && advanceAmount > 0) {
        advanceAmountInput.value = formatNumber(advanceAmount); // Chèn dấu phẩy vào số
        const advanceAmountText = convertNumberToTextVND(advanceAmount);
        document.getElementById('advance-amount-words').value = advanceAmountText; // Cập nhật chữ
    } else {
        // Nếu không phải là số hoặc <= 0, xóa ô chữ
        document.getElementById('advance-amount-words').value = '';
    }
}
async function approveRequest() {
    // Lấy thông tin từ các trường đầu vào
   
    const approvalNote = document.getElementById('approve-note').value.trim(); // Lấy ghi chú phê duyệt
    const leader_name = "<?php echo $fullName; ?>";
    const approvalTime = new Date().toISOString();

   
    // Kiểm tra ghi chú phê duyệt không bị để trống
    if (!approvalNote) {
        alert('Vui lòng nhập ghi chú phê duyệt!');
        return;
    }

    try {
        const response = await fetch('../operator/request.json', { cache: "no-store" });
        const data = await response.json();
        const request = data.find(req => req.id === currentRequestId); // Tìm yêu cầu theo ID cố định
        const operator_phone = await getPhoneByEmail(request.operator_email); // Đợi kết quả từ hàm
        

      
        // Cập nhật thông tin yêu cầu
        request.check_status = 'Phê duyệt';
        request.check_approval_time = approvalTime;
        request.check_approved_by = leader_name;
        request.check_approval_note = approvalNote;
        const advance_amount = request.advance_amount; // Sử dụng thuộc tính của request
        const advance_amountFormatted = advance_amount.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ","); // Định dạng số tiền
        // Ghi lại thông tin đã cập nhật vào file JSON
        const updateSuccess = await updateRequests(request); // Gọi hàm updateRequests và chờ kết quả
        
        if (updateSuccess) {
            const telegramMessage = `**Yêu cầu đã được Quản lý duyệt!**\n` +
                                    `ID yêu cầu: ${request.id}\n` +
                                    `Người đề nghị: ${request.full_name}\n`+
                                    `Số tiền xin tạm ứng: ${advance_amountFormatted}\n`+
                                    `Số tiền xin tạm ứng bằng chữ: ${request.advance_amount_words}\n`+
                                    `Tên khách hàng: ${request.customer_name}\n`+
                                    `Số thứ tự lô: ${request.lot_number}\n`+
                                    `Người duyệt: ${request.check_approved_by}\n` +
                                    `Ghi chú: **${approvalNote}**\n` +
                                    `Thời gian duyệt: ${request.check_approval_time}`;
            
            // Gửi tin nhắn đến Telegram
             await fetch('../sendTelegram.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        message: telegramMessage,
                       id_telegram: operator_phone // Truyền thêm thông tin operator
                    })
                });
            alert('Đã phê duyệt thành công!!!');
            cancel(); // Thực hiện hành động hủy nếu cần
        }
    } catch (error) {
        console.error('Error loading requests:', error);
        alert('Failed to load requests.');
    }
}

document.addEventListener('DOMContentLoaded', function() {
    loadRequests(); // Gọi hàm loadRequests khi trang được tải
});

    </script>
</head>
<body>
     <div class="menu">
        <a href="">Home</a>
        <a href="../update_signature.php">Cập nhật hình chữ ký</a>
        <a href="../logout.php" class="logout">Đăng xuất</a>
    </div>
    <div class="container">
    <div class="welcome-message">
            <p>Xin chào, <?php echo $fullName; ?>!</p>
        </div>
    <!-- Requests table -->
    <div id="requests">
        <h2>Danh sách các yêu cầu tạm ứng cần phê duyệt</h2>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Họ tên Operator</th>
                    <th>Tên Khách hàng</th>
                    <th>Số thứ tự lô</th>
                    <th>Số lượng (container)</th>
                    <th>Đơn vị (feet)</th>
                    <th>Loại hình</th>
                    <th>Số tiền tạm ứng</th>
                    <th>Số tiền tạm ứng (bằng chữ)</th>
                    <th>Nội dung xin tạm ứng</th>
                    <th>Ngày giờ trình</th>
                    <th>Thao tác</th>
                </tr>
            </thead>
            <tbody id="requests-table-body"></tbody>
        </table>
    </div>

    <!-- Reject reason modal -->
    <div id="reject-reason-modal">
        <h2>Lý do từ chối</h2>
        <textarea id="reject-reason" rows="4" cols="50" placeholder="Nhập lý do từ chối..."></textarea>
        <br><br>
        <button class="submit-button" onclick="rejectRequest()">Từ chối</button>
        <button class="reset-button" onclick=" cancel()">Hủy</button>
    </div>
  <!-- Approval modal -->
<div id="approve-reason-modal">
    <h2>Nhập nội dung duyệt</h2>
  
    
    <textarea id="approve-note" rows="4" cols="50" placeholder="Nhập nội dung duyệt..."></textarea>
    <br><br>
    
    <button class="submit-button" onclick="approveRequest()">Duyệt</button>
    <button class="reset-button" onclick="cancel()">Hủy</button>
</div>

</div>
 <div class="footer">
        <p>© 2024 Phần mềm soffice phát triển bởi Hienlm 0988838487</p>
    </div>
</body>
</html>
