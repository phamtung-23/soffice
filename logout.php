<?php
session_start();

// Hủy tất cả session
session_unset();
session_destroy();

// Chuyển hướng về trang đăng nhập
echo "<script>alert('Bạn đã đăng xuất thành công.'); window.location.href = 'index.php';</script>";
exit();
?>
