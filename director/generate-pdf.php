<?php
// Bật hiển thị lỗi (chỉ trong môi trường phát triển)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require 'vendor/autoload.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    // Kiểm tra lỗi JSON
    if (json_last_error() !== JSON_ERROR_NONE) {
        echo json_encode(['success' => false, 'message' => 'Invalid JSON input']);
        exit;
    }

    if (isset($data['htmlContent'])) {
        $htmlContent = $data['htmlContent'];

        try {
            $mpdf = new \Mpdf\Mpdf();
            $mpdf->WriteHTML($htmlContent);

            $fileName = 'request_' . time() . '.pdf';
            $filePath = __DIR__ . '/pdfs/' . $fileName;

            $mpdf->Output($filePath, \Mpdf\Output\Destination::FILE);

            echo json_encode(['success' => true, 'pdfUrl' => '/pdfs/' . $fileName]);
        } catch (\Mpdf\MpdfException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'No HTML content found']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>
