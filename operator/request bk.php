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

// Đọc dữ liệu từ file users.json
$userFile = '../database/users.json';
if (!file_exists($userFile)) {
    die("File users.json không tồn tại.");
}

$usersData = file_get_contents($userFile);
$users = json_decode($usersData, true);

// Lọc danh sách người dùng có vai trò là "leader"
$leaders = array_filter($users, function($user) {
    return $user['role'] === 'leader';
});

?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Advance Payment Request</title>
    <style>
        /* Existing styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
       body {
    font-family: Arial, sans-serif;
    background-color: #f4f4f4;
    display: flex;
    justify-content: center;
    align-items: center;
    height: 100vh;
    padding: 0 20px; /* Thêm khoảng trống để tránh sát mép màn hình */
}

#content {
    background-color: ffff;
    padding: 40px;
    border-radius: 10px;
    box-shadow: 0px 0px 15px rgba(0, 0, 0, 0.1);
    width: 80%; /* Chiếm 70% màn hình chiều rộng */
    max-width: 800px; /* Đặt giới hạn chiều rộng tối đa */
    min-width: 300px; /* Đặt giới hạn chiều rộng tối thiểu cho màn hình nhỏ */
}
        h2 {
            text-align: center;
            margin-bottom: 30px;
            font-size: 24px;
            color: #333;
        }

        .form-group {
    display: flex;
    align-items: center; /* Căn giữa dọc */
    margin-bottom: 15px;
}

label {
    width: 200px; /* Đặt chiều rộng cho label */
    font-size: 16px;
    color: #555;
}

input[type="text"],
input[type="number"],
select {
    padding: 10px;
    font-size: 16px;
    border: 1px solid #ccc;
    border-radius: 5px;
    width: 100%;
    flex-grow: 1; /* Để input hoặc select chiếm hết không gian còn lại */
}

        button {
            padding: 10px;
            font-size: 18px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            width: 100%;
        }

        .submit-button {
            background-color: #4CAF50;
            color: white;
            margin-top: 10px;
        }

        .reset-button {
            background-color: #f44336;
            color: white;
            margin-top: 10px;
        }

        .logout-button {
            background-color: #555;
            color: white;
            margin-top: 10px;
            cursor: pointer;
            width: 100%;
            text-align: center;
            display: block;
        }

        .form-group input[readonly] {
            background-color: #f9f9f9;
        }

       .menu {
    background-color: #333;
    overflow: hidden;
    text-align: center;
    padding: 10px 0;
    border-radius: 10px;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    margin-bottom: 20px;
    width: 100%; /* Đảm bảo menu chiếm toàn bộ chiều rộng của phần tử cha */
}

.menu a {
    display: inline-block;
    color: white;
    text-align: center;
    padding: 14px 20px;
    text-decoration: none;
    font-size: 16px;
    margin: 0 10px;
    transition: background-color 0.3s ease, color 0.3s ease;
}

.menu a:hover {
    background-color: #575757;
    color: #f1f1f1;
    border-radius: 5px;
}

.menu .logout {
    background-color: #f44336;
    padding: 14px 30px;
    border-radius: 5px;
}

.menu .logout:hover {
    background-color: #d32f2f;
}
.footer {
            text-align: center;
            margin-top: 40px;
            font-size: 14px;
            color: #888;
        }
 </style>

    <script>
        const fullName = '<?php echo $fullName; ?>';

        function loadEmployeeForm() {
            if (fullName) {
                document.getElementById('employee-full-name').value = fullName;
            } else {
                alert('Không tìm thấy tên nhân viên trong session.');
            }
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

      async function submitRequest() {
    const fullName = document.getElementById('employee-full-name').value.trim();
    const customerName = document.getElementById('customer-name').value.trim();
    const type_item = document.getElementById('type_item').value.trim();
    const quantity = document.getElementById('quantity').value.trim();
    const unit = document.getElementById('unit').value.trim();
    const lotNumber = document.getElementById('lot-number').value.trim();
    const advanceAmount = document.getElementById('advance-amount').value.replace(/\./g, '').trim();
    const advanceAmountWords = document.getElementById('advance-amount-words').value.trim();
    const manager = document.getElementById('manager').value;
    const advancedescription = document.getElementById('advance-description').value.trim();

    if (!fullName || !customerName || !type_item || !quantity || !unit || !lotNumber || !advanceAmount || !advanceAmountWords || !advancedescription) {
        alert("Vui lòng điền đầy đủ thông tin vào tất cả các trường.");
        return;
    }

    if (isNaN(quantity) || Number(quantity) <= 0) {
        alert("Vui lòng nhập số lượng hợp lệ.");
        return;
    }

    if (isNaN(advanceAmount) || Number(advanceAmount) <= 0) {
        alert("Vui lòng nhập số tiền tạm ứng hợp lệ.");
        return;
    }

    const dateTime = new Date().toLocaleString();
    const email = '<?php echo $email; ?>';

    try {
        const response = await fetch('submit_request.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                full_name: fullName,
                customer_name: customerName,
                operator_email: email,
                type_item: type_item,
                quantity: quantity,
                unit: unit,
                lot_number: lotNumber,
                advance_amount: advanceAmount,
                advance_amount_words: advanceAmountWords,
                leader_email: manager,
                advance_description: advancedescription,
                date_time: dateTime
            })
        });

        const data = await response.json();
        
        if (data.success) {
            const leader_phone = await getPhoneByEmail(manager); // Retrieve the operator's phone using email

            const telegramMessage = `Đề nghị tạm ứng mới chờ phê duyệt: \n` +
                
                `Người đề nghị: ${fullName}\n` +
                `Số tiền xin tạm ứng: ${advanceAmount} VNĐ\n` +
                `----------------------\n` +
                `Số tiền phê duyệt: ${advanceAmountWords} VNĐ\n` +
                `Nội dung: ${advancedescription}\n` +
                `Tên khách hàng: ${customerName}\n` +
                `Số Bill/Booking: ${lotNumber}\n` +
                `Số lượng: ${quantity}\n` +
                `Đơn vị: ${unit}` ;
                

            await fetch('../sendTelegram.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    message: telegramMessage,
                    id_telegram: leader_phone
                })
            });

            alert('Đơn xin tạm ứng đã được gửi thành công.');
            clearForm();
        } else {
            alert('Lỗi khi gửi đơn xin tạm ứng.');
        }
    } catch (error) {
        console.error('Error:', error);
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
        function clearForm() {
            document.getElementById('customer-name').value = '';
            document.getElementById('quantity').value = '';
            document.getElementById('unit').value = '';
            document.getElementById('lot-number').value = '';
            document.getElementById('advance-amount').value = '';
            document.getElementById('advance-amount-words').value = '';
            document.getElementById('advance-description').value = '';
        }

        window.addEventListener('load', loadEmployeeForm);
    </script>
</head>
<body>
    <div id="content">
    <div class="menu">
        <a href="index.php">Home</a>
        <a href="../update_signature.php">Cập nhật hình chữ ký</a>
        <a href="../logout.php" class="logout">Đăng xuất</a>
    </div>
        <h2>Đơn xin tạm ứng</h2>

        <form method="POST">
            <div class="form-group">
                <label for="employee-full-name">Họ và tên nhân viên Operator:</label>
                <input type="text" id="employee-full-name" readonly>
            </div>

            <div class="form-group">
                <label for="customer-name">Tên khách hàng:</label>
                <input type="text" id="customer-name" placeholder="Tên khách hàng">
            </div>
            <div class="form-group">
                <label for="lot-number">Số Bill/Booking:</label>
                <input type="text" id="lot-number" placeholder="Số Bill/Booking">
            </div>

            <div class="form-group">
                <label for="type_item">Loại hình:</label>
                <select id="type_item">
                    <option value="" disabled selected>Chọn loại hình</option>
                    <option value="Nhập">Nhập</option>
                    <option value="Xuất">Xuất</option>
                </select>
            </div>

            <div class="form-group">
                <label for="quantity">Số lượng (container):</label>
                <input type="number" id="quantity" placeholder="Số lượng">
            </div>

            <div class="form-group">
                <label for="unit">Đơn vị (feet):</label>
                <input type="number" id="unit" placeholder="Đơn vị">
            </div>

            

            <div class="form-group">
                <label for="advance-amount">Số tiền tạm ứng (VNĐ):</label>
                <input type="text" id="advance-amount" placeholder="Số tiền tạm ứng" oninput="updateAmountText()">
            </div>

            <div class="form-group">
                <label for="advance-amount-words">Số tiền bằng chữ:</label>
                <input type="text" id="advance-amount-words" readonly>
            </div>
            <div class="form-group">
                <label for="advance-description">Nội dung xin tạm ứng:</label>
                <input type="text" id="advance-description" placeholder="Nhập nội dung xin tạm ứng">
            </div>


            <div class="form-group">
            <label for="manager">Người quản lý:</label>
            <select id="manager" name="manager">
                <option value="">Chọn người quản lý</option>
                <?php
                // Hiển thị danh sách người quản lý (operators)
                foreach ($leaders as $leader) {
                    echo "<option value='" . $leader['email'] . "'>" . $leader['fullname'] . " (" . $leader['email'] . ")</option>";
                }
                ?>
            </select>
            </div>

            <button type="button" class="submit-button" onclick="submitRequest()">Gửi đơn</button>
            <button type="button" class="reset-button" onclick="clearForm()">Làm lại</button>
        </form>

      
    </div>
  
</body>
</html>
