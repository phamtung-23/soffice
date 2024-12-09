<?php
session_start();

// Kiểm tra nếu người dùng đã đăng nhập, thì tiếp tục trang, nếu không thì chuyển hướng về trang login
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'director') {
    echo "<script>alert('Bạn chưa đăng nhập! Vui lòng đăng nhập lại.'); window.location.href = '../index.php';</script>";
    exit();
}


// Lấy tên đầy đủ từ session
$fullName = $_SESSION['full_name'];
$userEmail = $_SESSION['user_id']; // 
// Lấy năm hiện tại
$currentYear = date("Y");
// Đọc các năm từ tệp JSON trong thư mục
$years = [];
foreach (glob('../database/request_*.json') as $filename) {
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trang Quản lý phiếu tạm ứng chờ chi</title>
    <style>
        /* Basic styles for layout */


        .form-group {
            margin: 15px 0;
        }

        input[type="text"] {
            width: 300px;
            height: 40px;
            font-size: 15px;
            padding: 10px;
            margin: 10px 0;
            border: 1px solid #ccc;
            border-radius: 5px;
        }

        textarea {
            width: 300px;
            height: 100px;
            font-size: 15px;
            padding: 10px;
            margin: 10px 0;
            border: 1px solid #ccc;
            border-radius: 5px;
        }

        button {
            width: 150px;
            height: 50px;
            font-size: 18px;
            padding: 10px;
            cursor: pointer;
        }



        .submit-button {
            margin: 5px 0px;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 10px;
            transition: opacity 0.5s ease; 
        }
        .submit-button2 {
            margin: 5px 0px;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 10px;
            transition: opacity 0.5s ease; 
        }

        .submit-approve {
            width: 100px;
            height: 40px;
            font-size: 1em;
            padding: 5px;
            cursor: pointer;
            margin: 5px 0px;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 10px;
        }

        .reset-reject {
            width: 100px;
            height: 40px;
            font-size: 1em;
            padding: 5px;
            cursor: pointer;
            margin: 5px 0px;
            background-color: #f44336;
            color: white;
            border: none;
            border-radius: 10px;
        }

        .reset-button {
            margin: 5px 0px;
            background-color: #f44336;
            color: white;
            border: none;
            border-radius: 10px;
        }


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
        }

        .menu a.logout {
            float: right;
            background-color: #f44336;
        }

        .menu a.logout:hover {
            background-color: #d32f2f;
        }

        .content {
            background-color: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.1);
            margin-top: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            overflow-x: auto;
        }

        table,
        th,
        td {
            border: 1px solid black;
        }

        th,
        td {
            padding: 8px;
            text-align: left;
            border: 1px solid #ddd;
            font-size: 0.85em;
            min-width: 100px;
            /* Adjust based on content */
            word-wrap: break-word;
            word-break: break-all;
            /* Ensures long words break within cell */
            white-space: normal;
            /* Allows text wrapping */
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

        #requests {
            background-color: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.1);
            margin-top: 20px;
            overflow-x: auto;
            max-width: 100%;
            /* Ensures it doesn't exceed the container's width */
        }

        #requests table {
            width: 100%;
            border-collapse: collapse;
        }

        #requests th,
        #requests td {
            border: 1px solid black;
            padding: 10px;
            text-align: center;
        }

        #requests thead th {
            background-color: #f0f0f0;
            /* Slightly different background for headers */
        }

        #requests tbody tr:nth-child(even) {
            background-color: #f9f9f9;
            /* Alternating row colors */
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

            .submit-approve {
                width: 100%;
                font-size: 1em;
                padding: 5px;
                cursor: pointer;
                margin: 5px 0px;
                background-color: #4CAF50;
                color: white;
                border: none;
                border-radius: 10px;
            }

            .reset-reject {
                width: 100%;
                font-size: 1em;
                padding: 5px;
                cursor: pointer;
                margin: 5px 0px;
                background-color: #f44336;
                color: white;
                border: none;
                border-radius: 10px;
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

            /* Container adjustments */
            .container {
                padding: 10px;
            }

            .welcome-message {
                font-size: 18px;
                text-align: center;
            }

            /* Content adjustments */
            .content {
                padding: 10px;
                margin-top: 15px;
            }

            /* Table adjustments */
            /* .table-wrapper {
                overflow-x: auto;
            } */

            table {
                width: 100%;
                border-collapse: collapse;
                table-layout: fixed;
                /* Forces table columns to fit evenly */
            }

            table,
            th,
            td {
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

            #requests h2{
                font-size: 1.2em;
            }

            .requests-id {
                width: 40px;
            }
            .requests-number {
                width: 45px;
            }

            .requests-feet {
                width: 45px;
            }

            .requests-type {
                width: 60px;
            }

            .requests-money {
                width: 90px;
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

            .welcome-message {
                font-size: 16px;
            }

            /* Table adjustments */
            .table-wrapper {
                overflow-x: auto;
            }

            table {
                width: 100%;
                border-collapse: collapse;
                table-layout: auto;
                /* Forces table columns to fit evenly */
            }

            table,
            th,
            td {
                font-size: 0.9em;
                padding: 6px;
            }

            .content h2 {
                font-size: 1em;
            }

            .footer {
                font-size: 12px;
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

            #requests h2{
                font-size: 1.2em;
            }
        }
    </style>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.2/html2pdf.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/0.4.1/html2canvas.min.js"></script>


    <script src="https://cdnjs.cloudflare.com/ajax/libs/crypto-js/4.1.1/crypto-js.min.js"></script>

    <script>
        let currentRequest = null;
        // Hàm để lấy năm từ dropdown
        function getSelectedYear() {
            const yearSelect = document.getElementById('year-select');
            return yearSelect.value;
        }

        // Toggle the responsive class to show/hide the menu
        function toggleMenu() {
            var menu = document.querySelector('.menu');
            menu.classList.toggle('responsive');
        }

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
        async function getPhoneByEmail(email) {
            try {
                // Tải dữ liệu từ file users.json
                const response = await fetch('../database/users.json', {
                    cache: "no-store"
                });

                // Kiểm tra xem phản hồi có hợp lệ hay không
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }

                // Chuyển đổi phản hồi thành JSON
                const usersData = await response.json();
                const users = Object.values(usersData); // Chuyển đối tượng thành mảng

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

        // Tải yêu cầu dựa trên năm đã chọn
        function loadRequests() {
            const year = getSelectedYear(); // Lấy năm từ dropdown
            const leader_email = "<?php echo $userEmail; ?>";
            fetch(`../database/request_${year}.json`, {
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
                    tableBody.innerHTML = ''; // Xóa nội dung cũ

                    const validRequests = data.filter(request => request.status !== 'Từ chối' && request.status !== 'Phê duyệt' && request.check_status === 'Phê duyệt');

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
                                cellElement.textContent = cell;
                                row.appendChild(cellElement);
                            });

                            const actionsCell = document.createElement('td');
                            actionsCell.innerHTML = `
                                <button class="btn submit-approve" onclick="reviewRequest(${request.id})">Phê duyệt</button>
                                <button class="btn reset-reject" onclick="openRejectModal(${request.id})">Từ chối</button>
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




        function reviewRequest(requestId) {
            currentRequestId = requestId;
            hideOtherRows(requestId); // Ẩn các hàng khác
            const yearselect = document.getElementById('year-select').value; // Lấy năm chọn
            const filePath = `../database/request_${yearselect}.json`;
            // Tìm yêu cầu theo ID cố định
            fetch(filePath, {
                    cache: "no-store"
                })
                .then(response => response.json())
                .then(data => {
                    const request = data.find(req => req.id === currentRequestId);
                    if (request) {
                        // Lấy số tiền từ yêu cầu và định dạng
                        const advanceAmount = request.advance_amount;
                        document.getElementById('money_approve').value = formatNumber(advanceAmount); // Định dạng số với dấu phẩy
                        document.getElementById('advance-amount-words').value = convertNumberToTextVND(advanceAmount); // Chuyển đổi thành chữ
                    }

                    document.getElementById('approve-reason-modal').style.display = 'block';
                    // scroll to approve-reason-modal
                    document.getElementById('approve-reason-modal').scrollIntoView({
                        behavior: 'smooth'
                    });
                })
                .catch(error => {
                    console.error('Error loading requests:', error.message);
                    alert('Failed to load request details.');
                });
        }


        function openRejectModal(requestId) {
            currentRequestId = requestId;
            hideOtherRows(requestId); // Ẩn các hàng khác
            document.getElementById('reject-reason-modal').style.display = 'block';
            // scroll to reject-reason-modal
            document.getElementById('reject-reason-modal').scrollIntoView({
                behavior: 'smooth'
            });
        }

        async function rejectRequest() {
            const reason = document.getElementById('reject-reason').value;
            const yearselect = document.getElementById('year-select').value; // Lấy năm chọn
            const filePath = `../database/request_${yearselect}.json`;
            if (!reason) {
                alert('Vui lòng nhập lý do từ chối!');
                return;
            }

            try {
                const response = await fetch(filePath, {
                    cache: "no-store"
                });
                const data = await response.json();
                const request = data.find(req => req.id === currentRequestId); // Tìm yêu cầu theo ID cố định

                // Cập nhật thông tin yêu cầu
                request.status = 'Từ chối';
                request.reject_reason = reason;
                request.reject_time = new Date().toLocaleString('sv-SE', {
                    timeZone: 'Asia/Ho_Chi_Minh',
                    hour12: false
                }).replace('T', ' ');
                request.rejected_by = "<?php echo $fullName; ?>";
                const advance_amount = request.advance_amount; // Sử dụng thuộc tính của request
                const advance_amountFormatted = advance_amount.toString().replace(/\B(?=(\d{3})+(?!\d))/g, "."); // Định dạng số tiền


                // Ghi lại thông tin đã cập nhật vào file JSON
                const updateSuccess = await updateRequests(request, yearselect); // Gọi hàm updateRequests và chờ kết quả
                const operator_phone = await getPhoneByEmail(request.operator_email);
                //console.log(operator_phone);

                if (updateSuccess) {
                    // Tạo nội dung tin nhắn để gửi
                    const telegramMessage = `**Yêu cầu đã bị Giám đốc từ chối!**\n` +
                        `ID yêu cầu: ${request.id}\n` +
                        `Người đề nghị: ${request.full_name}\n` +
                        `Số tiền xin tạm ứng: ${advance_amountFormatted}\n` +
                        `Số tiền xin tạm ứng bằng chữ: ${request.advance_amount_words}\n` +
                        `Tên khách hàng: ${request.customer_name}\n` +
                        `Số Bill/Booking: ${request.lot_number}\n` +
                        `Người từ chối: ${request.rejected_by}\n` +
                        `Lý do: **${reason}**\n` +
                        `Thời gian từ chối: ${request.reject_time}`;

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


        function cancel() {
            // Tải lại trang mà không lưu bất kỳ thay đổi nào
            location.reload();
        }
        async function updateRequests(data, year) {
            try {
                const response = await fetch('../update_requests.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        data,
                        year
                    }), // Bao gồm cả year trong body
                    cache: 'no-store'
                });

                if (!response.ok) {
                    throw new Error(`Server error: ${response.status}`);
                }

                const result = await response.json();

                if (result.success) {
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
    const advanceAmount = advanceAmountInput.value.replace(/\./g, ''); // Remove any existing periods
    advanceAmountInput.value = formatNumber(advanceAmount); // Insert periods for thousands separator
    const advanceAmountText = convertNumberToTextVND(advanceAmount);
    document.getElementById('advance-amount-words').value = advanceAmountText;
}

function formatNumber(num) {
    return num.replace(/\B(?=(\d{3})+(?!\d))/g, "."); // Replace comma with period
}

        function updateAmountText() {
            const advanceAmountInput = document.getElementById('money_approve');
            let advanceAmount = advanceAmountInput.value.replace(/\./g, ''); // Loại bỏ dấu phẩy

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
          async function getPhoneAccountant() {
            try {
                // Fetch data from users.json without caching
                const response = await fetch('../database/users.json', {
                    cache: "no-store"
                });

                // Check if response is ok
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }

                // Chuyển đổi phản hồi thành JSON
                const usersData = await response.json();
                const users = Object.values(usersData); // Chuyển đối tượng thành mảng

               // Tìm người dùng theo email
                const user = users.find(user => user.role === 'accountant');

                // Kiểm tra xem có người dùng không
                if (user) {
                    //console.log("Số điện thoại của người dùng:", user.phone);
                    return user.phone; // Trả về số điện thoại nếu tìm thấy
                } else {
                    
                    return null; // Trả về null nếu không tìm thấy
                }
            } catch (error) {
                console.error("Đã xảy ra lỗi:", error);
            }
        }
        async function approveRequest() {
            
             const button = document.querySelector(".submit-button2");
            // Nếu nút đã bị vô hiệu hóa, không làm gì thêm
            if (button.disabled) {
                return;
            }

            if (button) {
                button.textContent = "Đang xử lý";
                button.disabled = true; // Vô hiệu hóa nút
                button.style.opacity = "0.5"; // Làm mờ nút
            }
            // Lấy thông tin từ các trường đầu vào
            const approvedAmount = document.getElementById('money_approve').value.replace(/\./g, ''); // Xóa dấu phẩy
            const approvedAmountText = document.getElementById('advance-amount-words').value; // Lấy chữ
            const approvalNote = document.getElementById('approve-note').value.trim(); // Lấy ghi chú phê duyệt
            const directorName = "<?php echo $fullName; ?>";
            const approvalTime = new Date().toLocaleString('sv-SE', {
                timeZone: 'Asia/Ho_Chi_Minh',
                hour12: false
            }).replace('T', ' ');
            const yearselect = document.getElementById('year-select').value; // Lấy năm chọn
            const filePath = `../database/request_${yearselect}.json`;
            // Kiểm tra số tiền được duyệt
            if (!approvedAmount || isNaN(approvedAmount) || approvedAmount <= 0) {
                alert('Vui lòng nhập số tiền hợp lệ để phê duyệt!');
                return;
            }

            // Kiểm tra ghi chú phê duyệt không bị để trống
            if (!approvalNote) {
                alert('Vui lòng nhập ghi chú phê duyệt!');
                return;
            }

            try {
                const response = await fetch(filePath, {
                    cache: "no-store"
                });
                const data = await response.json();
                const request = data.find(req => req.id === currentRequestId); // Tìm yêu cầu theo ID cố định


                // Lấy ID mới từ file id.json
                const idResponse = await fetch('../database/id.json', {
                    cache: "no-store"
                });
                const idData = await idResponse.json();
                const currentYear = new Date().getFullYear();
                const newId = idData[currentYear]["id"] + 1;

                // Cập nhật tên file PDF dựa trên ID mới
                const now = new Date();
                const month = String(now.getMonth() + 1).padStart(2, '0'); // Lấy tháng, thêm số 0 nếu cần
                const year = now.getFullYear(); // Lấy năm
                const pdfFileName = `Phieu de nghi tam ung_id_${newId}_time_${month}_${year}.pdf`;

                // Cập nhật thông tin yêu cầu
                request.status = 'Phê duyệt';
                request.approved_amount = approvedAmount;
                request.approved_amount_words = approvedAmountText;
                request.approval_time = approvalTime;
                request.approved_by = directorName;
                request.approval_note = approvalNote;
                request.director_email = "<?php echo $userEmail; ?>";
                request.approved_filename = pdfFileName;

                // Ghi lại thông tin đã cập nhật vào file JSON
                const updateSuccess = await updateRequests(request, yearselect); // Gọi hàm updateRequests và chờ kết quả

                if (updateSuccess) {
                    //await fetchTemplateAndFill(request); // Gọi hàm fill template+
                    await fetchTemplateAndFill(request); // Gọi hàm fill template+
                    

                    const advance_amount = request.advance_amount; // Sử dụng thuộc tính của request         
                    const advance_amountFormatted = advance_amount.toString().replace(/\B(?=(\d{3})+(?!\d))/g, "."); // Định dạng số tiền
                    const approved_amount = request.approved_amount; // Sử dụng thuộc tính của request
                    const approve_amountFormatted = approved_amount.toString().replace(/\B(?=(\d{3})+(?!\d))/g, "."); // Định dạng số tiền
                    const operator_phone = await getPhoneByEmail(request.operator_email); // Đợi kết quả từ hàm
                    
                    //console.log(operator_phone);
                    const telegramMessage = `Đề nghị tạm ứng đã được Giám đốc phê duyệt: \n` +
                        `Thời gian duyệt: ${request.approval_time}\n` +
                        `Người đề nghị: ${request.full_name}\n` +
                        `Số tiền xin tạm ứng: ${advance_amountFormatted} VNĐ\n` +
                        `----------------------\n` +
                        `Số tiền phê duyệt: ${approve_amountFormatted} VNĐ\n` +
                        `Số tiền bằng chữ: ${request.approved_amount_words}\n` +
                        `Tên khách hàng: ${request.customer_name}\n` +
                        `Số Bill/Booking: ${request.lot_number}\n` +
                        `Số lượng: ${request.quantity}\n` +
                        `Đơn vị: ${request.unit}\n` +
                        `Nội dung: ${request.advance_description}\n` +
                        `Ghi chú GĐ: **${request.approval_note}**` ;
                        


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

                     const accountant_phone = await getPhoneAccountant();
                    

                    const telegramMessage_2 = `Đề nghị tạm ứng đã được Giám đốc phê duyệt: \n` +
                        `Thời gian duyệt: ${request.approval_time}\n` +
                        `Người đề nghị: ${request.full_name}\n` +
                        `Số tiền xin tạm ứng: ${advance_amountFormatted} VNĐ\n` +
                        `----------------------\n` +
                        `Số tiền phê duyệt: ${approve_amountFormatted} VNĐ\n` +
                        `Số tiền bằng chữ: ${request.approved_amount_words}\n` +
                        `Tên khách hàng: ${request.customer_name}\n` +
                        `Số Bill/Booking: ${request.lot_number}\n` +
                        `Số lượng: ${request.quantity}\n` +
                        `Đơn vị: ${request.unit}\n` +
                        `Nội dung: ${request.advance_description}\n` +
                        `Ghi chú GĐ: **${request.approval_note}**` ;


                    await fetch('../sendTelegram.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            message: telegramMessage_2,
                            id_telegram: accountant_phone
                        })
                    });
                    alert('Đã ký thành công và gửi telegram cho các đầu mối!!!');
                    cancel(); // Thực hiện hành động hủy nếu cần
                    


                }
            } catch (error) {
                console.error('Error loading requests:', error);
                alert('Failed to load requests. 1');
            }
        }

        async function fetchTemplateAndFill(request) {
            const pdfUrl = 'template.php'; // Đường dẫn đến file template.php

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
            loadRequests(); // Gọi hàm loadRequests khi trang được tải
        });
    </script>
</head>

<body>

    <div class="header">
        <h1>Quản lý phiếu tạm ứng chờ duyệt</h1>
    </div>
    <div class="menu">
        <span class="hamburger" onclick="toggleMenu()">&#9776;</span>
        <div class='icon'>
            <img src="../images/uniIcon.png" alt="Home Icon" class="menu-icon">
        </div>
        <a href="index.php">Home</a>
        <a href="all_request.php">Quản lý phiếu tạm ứng</a>
        <a href="all_payment.php">Quản lý phiếu thanh toán</a>
        <a href="finance.php">Quản lý tài chính</a>
        <a href="../update_signature.php">Cập nhật hình chữ ký</a>
        <a href="../update_idtelegram.php">Cập nhật ID Telegram</a>
        <a href="admin.php">Quản lý account</a>
        <a href="../logout.php" class="logout">Đăng xuất</a>
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

        <!-- Requests table -->
        <div id="requests" style="display: none;">
            <h2>Danh sách các yêu cầu tạm ứng cần phê duyệt</h2>
            <table>
                <thead>
                    <tr>
                        <th class='requests-id'>ID</th>
                        <th class='requests-operator'>Họ tên Operator</th>
                        <th class='requests-customer'>Tên Khách hàng</th>
                        <th class='requests-bill'>Số Bill/Booking</th>
                        <th class='requests-number'>Số lượng</th>
                        <th class='requests-feet'>Đơn vị (feet)</th>
                        <th class='requests-type'>Loại hình</th>
                        <th class='requests-money'>Số tiền tạm ứng</th>
                        <th class='requests-money-words'>Số tiền tạm ứng (bằng chữ)</th>
                        <th class='requests-content'>Nội dung</th>
                        <th class='requests-time'>Ngày giờ</th>
                        <th style="width: 100px;">Thao tác</th>
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

            <label for="money">Nhập số tiền đồng ý duyệt:</label>
            <input type="text" id="money_approve" oninput="updateAmountText()">
            <br><br>

            <label for="advance-amount-words">Số tiền duyệt (bằng chữ):</label>
            <input type="text" id="advance-amount-words" readonly>
            <br><br>

            <textarea id="approve-note" rows="4" cols="50" placeholder="Nhập nội dung duyệt..."></textarea>
            <br><br>

            <button class="submit-button2" onclick="approveRequest()">Duyệt</button>
            <button class="reset-button" onclick="cancel()">Hủy</button>
        </div>

        <div class="footer">
            <p>© 2024 Phần mềm soffice phát triển bởi Hienlm 0988838487</p>
        </div>

</body>

</html>