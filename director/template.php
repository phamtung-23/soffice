<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

require 'vendor/autoload.php'; // Đảm bảo đường dẫn tới mPDF chính xác


// Include PHPMailer's classes
require 'mailer/src/Exception.php';
require 'mailer/src/PHPMailer.php';
require 'mailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;



$idFile ='../database/id.json';  // Đảm bảo đường dẫn tới file id.json là đúng
$currentYear = date("Y");

 // Đọc dữ liệu từ file id.json
 $jsonData = file_get_contents($idFile);
 $data = json_decode($jsonData, true);
 $newId = $data[$currentYear]["id"] + 1;
 $data[$currentYear]["id"] = $newId;

// Cập nhật lại file id.json với giá trị ID mới
file_put_contents($idFile, json_encode($data));



// Đọc dữ liệu JSON từ yêu cầu POST
$request = json_decode(file_get_contents('php://input'), true);

// Nếu không nhận được dữ liệu, hiển thị thông báo lỗi
if (!$request) {
    echo json_encode(['success' => false, 'message' => 'Không có dữ liệu yêu cầu.']);
    exit;
}

// Lấy ngày hiện tại
$date = date('d/m/Y'); // Format ngày: Ngày/Tháng/Năm

// Điền thông tin vào template
$proposer = $request['full_name'] ?? 'Chưa có thông tin';
$department = $request['department'] ?? 'Giao nhận';
$advance_amount = $request['advance_amount'];
$advance_amountFormatted = number_format($advance_amount, 0, '.', ','); // Định dạng số tiền
$approvedAmount = $request['approved_amount'] ?? '0';
$approvedAmountFormatted = number_format($approvedAmount, 0, '.', ','); // Định dạng số tiền
$approvedAmountWords = $request['approved_amount_words'] ?? '';
$customerName = $request['customer_name'] ?? 'Chưa có';
$declarationNumber = $request['declaration_number'] ?? 'Chưa có';
$quantity = $request['quantity'] ?? 'Chưa có';
$unit = $request['unit'] ?? 'Chưa có';
$lotNumber = $request['lot_number'] ?? 'Chưa có';
$id = $request['id'];
$operator_email=$request['operator_email'];
$leader_email=$request['leader_email'];
$director_email=$request['director_email'];
$type_item=$request['type_item'];
$advance_description=$request['advance_description'];





// Tạo tên file bằng hàm MD5 từ email của người dùng
$operator_email_md5 = md5($operator_email);
$leader_email_md5 = md5($leader_email);
$director_email_md5 = md5($director_email);
$operator_signature_path = "/soffice/signatures/" . $operator_email_md5 . ".jpg";
$leader_signature_path = "/soffice/signatures/" . $leader_email_md5 . ".jpg";
$director_signature_path = "/soffice/signatures/" . $director_email_md5 . ".jpg";



// Tạo nội dung HTML cho template
$htmlContent = "
<!DOCTYPE html>
<html lang='vi'>
<head>
    <meta charset='UTF-8'>
    <title>Phiếu Đề Nghị Tạm Ứng</title>
    <style>
        body {
            font-family: 'Times New Roman', Times, serif;
            width: 80%;
            margin: auto;
        }
        .header {
            width: 100%;
            margin-bottom: 20px;
        }
        .header-table {
            width: 100%;
            border-collapse: collapse;
        }
        .header-left, .header-right {
            vertical-align: top;
            text-align: left;
            width: 50%;
            text-align: center;
        }
        .header-left h5, .header-right h4 {
            margin: 0;
            line-height: 1.2;
        }
        .header-left p, .header-right p {
            margin: 0;
            line-height: 1.2;
        }
        .form-section {
            margin: 20px 0;
        }
        .form-group {
            margin: 10px 0;
            display: flex;
            justify-content: space-between;
        }
        .form-group label {
            width: 30%;
        }
        .form-group input, .form-group textarea {
            width: 65%;
            padding: 8px;
            font-size: 16px;
        }
        .signature {
            text-align: center;
            margin-top: 50px;
        }
        .signature table {
            margin: 0 auto;
        }
        .signature td {
            text-align: center;
            vertical-align: top;
        }
    </style>
</head>
<body>          
    <div class='header'>
        <table class='header-table'>
            <tr>
                <td class='header-left'>
                    <h5>CÔNG TY CP GIAO NHẬN THẾ GIỚI TOÀN CẦU</h5>
                    <p>Lầu 3, Cao ốc Đinh Lễ, số 1 Đinh Lễ,</p>
                    <p>Phường 13, Quận 4, TP.HCM</p>
                </td>
                <td class='header-right'>
                    <h4>CỘNG HÒA XÃ HỘI CHỦ NGHĨA VIỆT NAM</h4>
                    <p>Độc Lập - Tự Do - Hạnh Phúc</p>
                    <hr style='width: 50%; margin: 0 auto; border: 1px solid black;'>
                </td>
            </tr>
        </table>
    </div>

    <h2 style='text-align: center;'>PHIẾU ĐỀ NGHỊ TẠM ỨNG</h2>
   <div class='info' style='text-align: center; margin: 20px 0;'>
    <p>Số: $newId . Ngày: $date</p>
</div>

    <div class='form-section'>
        <div class='form-group'>
            <label for='proposer'>Người đề nghị:</label>
            <span>$proposer</span>
        </div>
        <div class='form-group'>
            <label for='department'>Thuộc bộ phận:</label>
            <span>$department</span>
        </div>
        <div class='form-group'>
            <label for='payment-content'>Nội dung tạm ứng:</label>
             <span> $approvedAmountFormatted VNĐ.</span>
            <span> ($approvedAmountWords)</span>
        </div>
    </div>

    <h3>Chi tiết nội dung</h3>
    <table border='1' cellspacing='0' cellpadding='8' style='width: 100%; border-collapse: collapse; text-align: center;'>
        <thead>
            <tr>
                <th>Tên Khách hàng</th>
                <th>Số Bill/Booking</th>
                <th>Số lượng (Container)</th>
                <th>Đơn vị (feet)</th>
                <th>Loại hình</th>
                <th>Nội dung xin tạm ứng</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>$customerName</td>
                <td>$lotNumber</td>
                <td>$quantity</td>
                <td>$unit</td>
                <td>$type_item</td>
                <td>$advance_description</td>
            </tr>
        </tbody>
    </table>

    <div class='signature'>
        <table>
            <tr>
                <td>
                    <p>Người đề nghị</p>
                </td>
                <td>
                    <p>Người duyệt</p>
                </td>
              
            </tr>
            <tr>
            <td>
            <img src='$operator_signature_path' alt='Chữ ký Người đề nghị' style='width: 150px; height: auto;'>
            </td>
        <td>
            <img src='$leader_signature_path' alt='Chữ ký Người duyệt' style='width: 150px; height: auto;'>
        </td>
               
            </tr>
            <br>
            <tr>
                <td colspan='3'>
                    <p>Giám đốc</p>
                </td>
            </tr>
            <tr>
                <td colspan='3'>
                   <img src='$director_signature_path' alt='Chữ ký Giám đốc' style='width: 150px; height: auto;'>
            </td>
                </td>
            </tr>
        </table>
    </div>

</body>
</html>
";

// Tạo thư mục lưu PDF nếu chưa tồn tại
//$pdfDir = __DIR__ . '/pdfs/';
$pdfDir = '../database/pdfs/';
if (!is_dir($pdfDir)) {
    mkdir($pdfDir, 0777, true);
}

try {
    // Khởi tạo mPDF và lưu file PDF
    $mpdf = new \Mpdf\Mpdf();
    $mpdf->WriteHTML($htmlContent);
    $month = date('m'); // Lấy tháng hiện tại
    $year = date('Y');  // Lấy năm hiện tại
    $pdfFileName = 'Phieu de nghi tam ung_id_' . $newId . '_time_' . $month . '_' . $year . '.pdf';
    $pdfPath = $pdfDir . $pdfFileName;

    $mpdf->Output($pdfPath, 'F'); // Lưu file PDF


    // Gửi email với file PDF
    sendEmailWithAttachment($pdfPath, $pdfFileName, $sale_email);
   

    // Trả về đường dẫn file PDF
    echo json_encode([
        'success' => true,
        'pdfUrl' => 'database/pdfs/' . $pdfFileName
    ]);
} catch (\Mpdf\MpdfException $e) {
    // Xử lý lỗi mPDF
    echo json_encode([
        'success' => false,
        'message' => 'Lỗi khi tạo PDF: ' . $e->getMessage()
    ]);
}





function sendEmailWithAttachment($filePath, $fileName, $sale_email) {
    $mail = new PHPMailer();
    try {
        //$mail->SMTPDebug = 2; 
        //$mail->Debugoutput = 'html';
        // Cấu hình server SMTP
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com'; // Thay bằng SMTP host của bạn
        $mail->SMTPAuth = true;
        $mail->Username = 'nguyenlonggm2021@gmail.com'; // Thay bằng email của bạn
        $mail->Password = 'hnuozppidlbfkmlm'; // Thay bằng mật khẩu email của bạn
        $mail->SMTPSecure = 'tls'; // Có thể thử với SSL nếu cần
        $mail->Port = 587; // Sử dụng cổng TLS 587

        // Người gửi và người nhận
        $mail->setFrom('vip@cloud.info.vn', 'Voffice');
        $mail->addAddress($sale_email); // Địa chỉ người nhận
        $mail->CharSet = 'UTF-8';
        $mail->Encoding = 'base64';
        $mail->Subject = '=?UTF-8?B?' . base64_encode($mail->Subject) . '?=';
        // Tiêu đề và nội dung email
        $mail->Subject = 'Đề nghị tạm ứng';
        $mail->Body = 'Xin vui lòng xem file đính kèm.';
        $mail->addAttachment($filePath, $fileName); // Đính kèm tệp

        // Gửi email
        if (!$mail->send()) {
            echo "Không thể gửi email. Lỗi: {$mail->ErrorInfo}";
        } else {
            echo "Email đã được gửi thành công!";
        }
    } catch (Exception $e) {
        echo "Không thể gửi email. Lỗi: {$mail->ErrorInfo}";
    }
}


?>
