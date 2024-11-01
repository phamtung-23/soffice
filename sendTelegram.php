<?php
// sendTelegram.php

// Hàm gửi tin nhắn đến Telegram
function sendTelegramMessage($message, $chatId) {
    $telegramBotToken = '6200192705:AAEiGy7e2hhCeF7LNwhO2gtmRil24pgIA8g'; // Thay đổi thành token của bot Telegram của bạn
    
    $url = "https://api.telegram.org/bot$telegramBotToken/sendMessage";
    $data = [
        'chat_id' => $chatId,  // Sử dụng sale_phone làm chat_id
        'text' => $message,
        'parse_mode' => 'HTML' // Chọn định dạng nếu cần
    ];

    // Gửi yêu cầu HTTP POST
    file_get_contents($url . '?' . http_build_query($data));
}

// Lấy thông tin từ yêu cầu POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $requestData = json_decode(file_get_contents('php://input'), true);
    
    // Lấy message và sale_phone từ request
    $message = $requestData['message'];
    $chatId = $requestData['id_telegram']; // Sử dụng sale_phone làm chat_id
    
    // Kiểm tra nếu có chatId
    if ($chatId) {
        // Gửi tin nhắn với chatId là sale_phone
        sendTelegramMessage($message, $chatId);
        echo json_encode(['status' => 'success', 'message' => 'Message sent successfully']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'chat_id (id_telegram) is missing']);
    }
}
?>
