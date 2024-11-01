<?php
// Link đến file PHP bạn muốn kiểm tra
$url = 'https://cloud.info.vn/soffice/director/template.php';

// Tạo dữ liệu JSON giả lập để gửi
$data = [
    'full_name' => 'Nguyễn Văn B',
    'department' => 'Phòng Kế Toán',
    'advance_amount' => 1000000,
    'approved_amount' => 1000000,
    'approved_amount_words' => 'Một triệu đồng',
    'customer_name' => 'Công ty ABC',
    'declaration_number' => '54321',
    'quantity' => '5',
    'unit' => '40',
    'lot_number' => '9876',
    'id' => 2,
    'operator_email' => 'operator@example.com',
    'leader_email' => 'leader@example.com',
    'director_email' => 'director@example.com',
    'type_item' => 'Loại hàng hóa',
    'advance_description' => 'Mô tả tạm ứng'
];

// Mã hóa dữ liệu thành JSON
$jsonData = json_encode($data);

// Khởi tạo cURL để gọi đến file PHP trực tiếp
$ch = curl_init($url);

// Cấu hình cURL
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Content-Length: ' . strlen($jsonData)
]);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);

// Thực hiện yêu cầu và lưu kết quả trả về
$response = curl_exec($ch);

// Kiểm tra lỗi cURL nếu có
if ($response === false) {
    echo 'Lỗi cURL: ' . curl_error($ch);
} else {
    // In kết quả trả về
    echo 'Kết quả xử lý: ' . $response;
}

// Đóng cURL
curl_close($ch);
?>
