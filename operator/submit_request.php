<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Lấy dữ liệu từ yêu cầu POST
    $data = json_decode(file_get_contents('php://input'), true);

    if ($data) {
        // Thêm thông tin thời gian gửi request
        $data['date_time'] = date('Y-m-d H:i:s');

        // Xác định tên file JSON theo năm
        $year = date('Y'); // Hoặc có thể lấy từ dữ liệu `$data` nếu có
        $file = "../database/request_$year.json"; // File sẽ có dạng request_2024.json
         // Kiểm tra và tạo file nếu chưa tồn tại
        if (!file_exists($file)) {
            // Tạo file mới
            touch($file); // Tạo file mới
            chmod($file, 0600); // Thiết lập quyền 600
        }
        // Mở file ở chế độ đọc và ghi
        $fp = fopen($file, 'c+'); // 'c+' tạo file nếu chưa tồn tại và mở để đọc/ghi

        if (flock($fp, LOCK_EX)) { // Đặt khóa độc quyền để tránh xung đột
            // Đọc dữ liệu hiện tại từ file JSON
            $fileContents = file_get_contents($file);
            $existingData = $fileContents ? json_decode($fileContents, true) : [];
            if ($existingData === null) {
                echo json_encode(['success' => false, 'message' => 'Failed to read existing data.']);
                http_response_code(500);
                flock($fp, LOCK_UN); // Giải phóng khóa
                fclose($fp);
                exit();
            }

            // Tìm ID lớn nhất hiện có để tạo ID mới
            $newId = getNextId($existingData);
            $data['id'] = $newId; // Gán ID mới cho dữ liệu

            // Thêm dữ liệu mới vào mảng
            $existingData[] = $data;

            // Ghi dữ liệu vào file JSON theo năm
            if (ftruncate($fp, 0) && fwrite($fp, json_encode($existingData, JSON_PRETTY_PRINT))) {
                echo json_encode(['success' => true]);
                http_response_code(200);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to write data to file.']);
                http_response_code(500);
            }
            
            // Giải phóng khóa và đóng file
            flock($fp, LOCK_UN);
        } else {
            echo json_encode(['success' => false, 'message' => 'Could not lock the file.']);
            http_response_code(500);
        }
        
        fclose($fp);
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid data.']);
        http_response_code(400);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Only POST requests are allowed.']);
    http_response_code(405);
}

// Hàm để lấy ID tiếp theo
function getNextId($data) {
    if (empty($data)) return 1;
    $maxId = max(array_column($data, 'id'));
    return $maxId + 1;
}
?>
