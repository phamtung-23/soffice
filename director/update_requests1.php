<?php
// Đặt tiêu đề cho trình duyệt biết đây là JSON
header('Content-Type: application/json');

// Đọc dữ liệu JSON từ yêu cầu POST
$data = json_decode(file_get_contents('php://input'), true);

// Lấy năm từ query string
$year = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');

// Đường dẫn tới file request.json theo năm
$requestFile = "../database/request_$year.json";

// Kiểm tra dữ liệu có hợp lệ hay không
if (json_last_error() !== JSON_ERROR_NONE) {
    echo json_encode(['success' => false, 'message' => 'Invalid JSON data', 'error' => json_last_error_msg()]);
    exit;
}

// Kiểm tra nếu trường ID tồn tại
if (!isset($data['id'])) {
    echo json_encode(['success' => false, 'message' => 'Missing ID in data']);
    exit;
}

// Kiểm tra nếu file request.json tồn tại
if (file_exists($requestFile)) {
    // Đọc nội dung file
    $requests = json_decode(file_get_contents($requestFile), true);

    // Kiểm tra nếu requests đã được đọc thành công và là mảng hợp lệ
    if (is_array($requests)) {
        $found = false;

        // Duyệt qua từng request và cập nhật request có id tương ứng
        foreach ($requests as $key => $request) {
            if (isset($request['id']) && $request['id'] === $data['id']) {
                // Cập nhật thông tin cho request đã tìm thấy
                $requests[$key] = array_merge($request, $data); // Ghi đè đúng phần tử
                $found = true;
                break;
            }
        }

        if (!$found) {
            // Nếu không tìm thấy id, thêm request mới vào mảng
            $requests[] = $data;
        }

        // Ghi lại dữ liệu vào file request.json
        if (file_put_contents($requestFile, json_encode($requests, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))) {
            echo json_encode(['success' => true, 'message' => 'Request updated successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to write data to file']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to read requests array']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'File not found']);
}

?>
