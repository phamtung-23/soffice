<?php
// Đặt tiêu đề cho trình duyệt biết đây là JSON
header('Content-Type: application/json');

// Đọc dữ liệu JSON từ yêu cầu POST
$requestData = json_decode(file_get_contents('php://input'), true);

// Kiểm tra nếu JSON hợp lệ
if (json_last_error() !== JSON_ERROR_NONE) {
    echo json_encode(['success' => false, 'message' => 'Invalid JSON data', 'error' => json_last_error_msg()]);
    exit;
}

// Lấy `data` và `year` từ mảng `$requestData`
$data = isset($requestData['data']) ? $requestData['data'] : null;
$year = isset($requestData['year']) ? (int)$requestData['year'] : date('Y');

// Kiểm tra nếu dữ liệu `data` và trường `id` tồn tại
if (!$data || !isset($data['id'])) {
    echo json_encode(['success' => false, 'message' => 'Missing ID or data']);
    exit;
}



// Đường dẫn tới file JSON của năm tương ứng
$requestFile = "database/request_$year.json";

// Kiểm tra nếu file JSON tồn tại
if (file_exists($requestFile)) {
    // Mở file ở chế độ đọc-ghi
    $fileHandle = fopen($requestFile, 'c+');
    if ($fileHandle) {
        // Khoá file để tránh ghi đè từ các tiến trình khác
        if (flock($fileHandle, LOCK_EX)) {
            // Đọc nội dung file
            $fileContent = fread($fileHandle, filesize($requestFile));
            $requests = json_decode($fileContent, true);

            // Kiểm tra nếu `requests` là một mảng hợp lệ
            if (is_array($requests)) {
                $found = false;

                // Duyệt qua từng request và cập nhật request có id tương ứng
                foreach ($requests as $key => $request) {
                    if (isset($request['id']) && $request['id'] === $data['id']) {
                        // Cập nhật thông tin cho request đã tìm thấy
                        $requests[$key] = array_merge($request, $data);
                        $found = true;
                        break;
                    }
                }

                if (!$found) {
                    echo json_encode(['success' => false, 'message' => 'Request ID not found']);
                } else {
                    // Đưa con trỏ file về đầu và xóa nội dung cũ
                    ftruncate($fileHandle, 0);
                    rewind($fileHandle);

                    // Ghi lại dữ liệu vào file JSON
                    if (fwrite($fileHandle, json_encode($requests, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))) {
                        echo json_encode(['success' => true, 'message' => 'Request updated successfully']);
                    } else {
                        echo json_encode(['success' => false, 'message' => 'Failed to write data to file']);
                    }
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to read requests array']);
            }
            
            // Mở khóa file sau khi ghi xong
            flock($fileHandle, LOCK_UN);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to lock file']);
        }

        // Đóng file sau khi hoàn tất
        fclose($fileHandle);
    } else {
        echo json_encode(['success' => false, 'message' => 'Unable to open file']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'File not found']);
}
?>
