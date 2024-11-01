<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Lấy dữ liệu từ yêu cầu POST
    $data = json_decode(file_get_contents('php://input'), true);

    if ($data) {
        // Thêm thông tin thời gian gửi request
        $data['date_time'] = date('Y-m-d H:i:s');

        // Đọc dữ liệu hiện tại từ request.json
        $file = 'request.json';
        if (file_exists($file)) {
            $existingData = json_decode(file_get_contents($file), true);
            if ($existingData === null) {
                // Nếu có lỗi khi giải mã JSON
                echo json_encode(['success' => false, 'message' => 'Failed to read existing data.']);
                http_response_code(500);
                exit();
            }
        } else {
            $existingData = [];
        }

        // Tìm ID lớn nhất hiện có để tạo ID mới
        $newId = getNextId($existingData);
        $data['id'] = $newId; // Gán ID mới cho dữ liệu

        // Thêm dữ liệu mới vào mảng
        $existingData[] = $data;

        // Ghi dữ liệu vào file request.json
        if (file_put_contents($file, json_encode($existingData, JSON_PRETTY_PRINT))) {
            // Trả về phản hồi thành công
            echo json_encode(['success' => true]);
            http_response_code(200);
        } else {
            // Lỗi khi ghi dữ liệu vào file
            echo json_encode(['success' => false, 'message' => 'Failed to write data to file.']);
            http_response_code(500);
        }
    } else {
        // Dữ liệu không hợp lệ
        echo json_encode(['success' => false, 'message' => 'Invalid data.']);
        http_response_code(400);
    }
} else {
    // Chỉ chấp nhận phương thức POST
    echo json_encode(['success' => false, 'message' => 'Only POST requests are allowed.']);
    http_response_code(405);
}

// Hàm để lấy ID tiếp theo
function getNextId($data) {
    if (empty($data)) return 1; // Nếu không có mục nào, bắt đầu từ 1
    $maxId = max(array_column($data, 'id')); // Tìm ID lớn nhất
    return $maxId + 1; // Trả về ID tiếp theo
}
?>
