<?php
session_start();

// Check if the user is an admin; otherwise, redirect
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'director') {
    echo "<script>alert('Bạn không có quyền truy cập vào trang này!'); window.location.href = 'index.php';</script>";
    exit();
}

// Read users from users.json
$usersFile = '../database/users.json';
if (file_exists($usersFile)) {
    $jsonData = file_get_contents($usersFile);
    $users = json_decode($jsonData, true);
} else {
    $users = [];
}

// Count accounts by role
$roleCounts = [];
foreach ($users as $user) {
    $role = $user['role'];
    if (!isset($roleCounts[$role])) {
        $roleCounts[$role] = 0;
    }
    $roleCounts[$role]++;
}

// Handle role and name update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email'], $_POST['role'], $_POST['fullname'])) {
    $userEmailToUpdate = $_POST['email'];
    $newRole = $_POST['role'];
    $newName = $_POST['fullname'];

    // Update the user's role and name in the array
    foreach ($users as &$user) {
        if ($user['email'] == $userEmailToUpdate) {
            $user['role'] = $newRole;
            $user['fullname'] = $newName;
            break;
        }
    }

    // Save updated users back to users.json
    file_put_contents($usersFile, json_encode($users, JSON_PRETTY_PRINT));
    echo "<script>alert('Cập nhật thông tin thành công!'); window.location.href = 'admin.php';</script>";
    exit();
}

// Handle user deletion
if (isset($_GET['delete'])) {
    $userEmailToDelete = $_GET['delete'];
    $users = array_filter($users, function ($user) use ($userEmailToDelete) {
        return $user['email'] !== $userEmailToDelete;
    });

    // Save updated users back to users.json
    file_put_contents($usersFile, json_encode($users, JSON_PRETTY_PRINT));
    echo "<script>alert('Xóa tài khoản thành công!'); window.location.href = 'admin.php';</script>";
    exit();
}

// Get the user to edit if any
$editingUser = null;
if (isset($_GET['edit'])) {
    $editingUserEmail = $_GET['edit']; // Get the email to edit
    foreach ($users as $user) {
        if ($user['email'] == $editingUserEmail) {
            $editingUser = $user;
            break;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trang chủ director</title>
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
            overflow-x: auto;
            background-color: white;
        }

        table,
        th,
        td {
            border: 1px solid #ddd;
        }

        th,
        td {
            padding: 8px;
            text-align: left;
            white-space: nowrap;
        }

        th {
            font-size: 1em;
            /* Adjust this value as needed */
            background-color: #f2f2f2;
            padding: 6px;
            text-align: left;
        }


        input[type="text"] {
            width: 100%;
            padding: 8px;
            margin: 10px 0;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        .footer {
            text-align: center;
            margin-top: 40px;
            font-size: 14px;
            color: #888;
        }

        .update-btn,
        .delete-btn {
            cursor: pointer;
            padding: 4px 8px;
            color: white;
            border: none;
            border-radius: 4px;
            text-decoration: none;
        }

        .update-btn {
            background-color: #4CAF50;
        }

        .update-btn:hover {
            background-color: #45a049;
        }

        .delete-btn {
            background-color: #f44336;
        }

        .delete-btn:hover {
            background-color: #d32f2f;
        }

        .role-counts {
            margin-bottom: 20px;
        }

        .action-btn {
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .icon {
            padding: 10px 20px;
        }
        .menu-icon {
            width: 40px;
            height: 40px;
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
            .table-wrapper {
                overflow-x: auto;
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
        }
    </style>
</head>

<body>
    <div class="header">
        <h1>Quản lý Tài khoản</h1>
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

    <div class="content">
        <h2>Thống kê tài khoản theo chức danh</h2>
        <div class="role-counts">
            <?php foreach ($roleCounts as $role => $count): ?>
                <p><?php echo htmlspecialchars($role); ?>: <?php echo htmlspecialchars($count); ?> tài khoản</p>
            <?php endforeach; ?>
        </div>

        <h2>Danh sách tài khoản</h2>
        <table>
            <?php if ($editingUser): ?>
                <h2>Cập nhật thông tin cho <?php echo htmlspecialchars($editingUser['fullname']); ?></h2>
                <form method="POST" action="">
                    <input type="hidden" name="email" value="<?php echo htmlspecialchars($editingUser['email']); ?>">
                    <label for="fullname">Tên:</label>
                    <input type="text" name="fullname" id="fullname" value="<?php echo htmlspecialchars($editingUser['fullname']); ?>" required>

                    <label for="role">Chọn chức danh:</label>
                    <select name="role" id="role" required>
                        <option value="sale" <?php if ($editingUser['role'] == 'sale') echo 'selected'; ?>>Sale</option>
                        <option value="operator" <?php if ($editingUser['role'] == 'operator') echo 'selected'; ?>>Operator</option>
                        <option value="leader" <?php if ($editingUser['role'] == 'leader') echo 'selected'; ?>>Leader</option>
                        <option value="accountant" <?php if ($editingUser['role'] == 'accountant') echo 'selected'; ?>>Accountant</option>
                        <!-- Add more roles as needed -->
                    </select>
                    <button type="submit" class="update-btn">Cập nhật</button>
                </form>
            <?php endif; ?>
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>ID Telegram</th>
                    <th>Role</th>
                    <th>Hình chữ ký</th> <!-- New column for signature -->
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($user['fullname']); ?></td>
                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                        <td><?php echo htmlspecialchars($user['phone']); ?></td>
                        <td><?php echo htmlspecialchars($user['role']); ?></td>
                        <td>
                            <?php
                            // Generate the signature file name
                            $signatureFileName = '../signatures/' . md5($user['email']) . '.jpg';
                            // Check if the signature file exists
                            if (file_exists($signatureFileName)): ?>
                                <img src="<?php echo htmlspecialchars($signatureFileName); ?>" alt="Signature" style="width: 50px; height: auto;">
                            <?php else: ?>
                                Chưa có chữ ký
                            <?php endif; ?>
                        </td>
                        <td class="action-btn">
                            <a href="admin.php?edit=<?php echo urlencode($user['email']); ?>" class="update-btn">Update</a>
                            <a href="admin.php?delete=<?php echo urlencode($user['email']); ?>" class="delete-btn" onclick="return confirm('Bạn có chắc chắn muốn xóa tài khoản này không?');">Delete</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

    </div>
    <script>
        // Toggle the responsive class to show/hide the menu
        function toggleMenu() {
            var menu = document.querySelector('.menu');
            menu.classList.toggle('responsive');
        }
    </script>
</body>

</html>