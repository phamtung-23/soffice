<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

require '../../vendor/autoload.php'; // Đảm bảo đường dẫn tới mPDF chính xác
include('../../../helper/payment.php');
include('../../../helper/general.php');

// Include PHPMailer's classes
require '../../mailer/src/Exception.php';
require '../../mailer/src/PHPMailer.php';
require '../../mailer/src/SMTP.php';
require '../../../library/google_api/vendor/autoload.php'; // Đảm bảo đường dẫn đúng

function uploadFileToGoogleDrive($filePath, $fileName, $folderId)
{
  $client = new Google_Client();
  $client->setAuthConfig('gdcredentials.json'); // Đường dẫn tới file credential
  $client->addScope(Google_Service_Drive::DRIVE_FILE);

  $service = new Google_Service_Drive($client);

  $fileMetadata = new Google_Service_Drive_DriveFile([
    'name' => $fileName,
    'parents' => [$folderId]
  ]);

  $content = file_get_contents($filePath);

  try {
    $file = $service->files->create($fileMetadata, [
      'data' => $content,
      'mimeType' => mime_content_type($filePath),
      'uploadType' => 'multipart'
    ]);
    return "https://drive.google.com/file/d/" . $file->id . "/view";
  } catch (Exception $e) {
    return null;
  }
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;



$idFile = '../../../database/id.json';  // Đảm bảo đường dẫn tới file id.json là đúng
$currentYear = date("Y");

$filePathUser = '../../../database/users.json';

$jsonDataUser = json_decode(file_get_contents($filePathUser), true);

if (!$jsonDataUser) {
  echo json_encode(['success' => false, 'message' => 'Không thể tải dữ liệu người dùng.']);
  exit;
}

//  // Đọc dữ liệu từ file id.json
//  $jsonData = file_get_contents($idFile);
//  $data = json_decode($jsonData, true);
//  $newId = $data[$currentYear]["id"] + 1;
//  $data[$currentYear]["id"] = $newId;

// // Cập nhật lại file id.json với giá trị ID mới
// file_put_contents($idFile, json_encode($data));



// Đọc dữ liệu JSON từ yêu cầu POST
$request = json_decode(file_get_contents('php://input'), true);

// Nếu không nhận được dữ liệu, hiển thị thông báo lỗi
if (!$request) {
  echo json_encode(['success' => false, 'message' => 'Không có dữ liệu yêu cầu.']);
  exit;
}

// sale user data
$saleUserData = null;
foreach ($jsonDataUser as $user) {
  if ($request['approval'][1]['email'] === $user['email'] && $user['role'] == 'sale') {
    $saleUserData = $user;
    break;
  }
}
// get leader data
$leaderData = null;
foreach ($jsonDataUser as $user) {
  if ($user['role'] == 'leader' && $user['email'] == $request['approval'][0]['email']) {
    $leaderData = $user;
    break;
  }
}

// Lấy ngày hiện tại
$date = date('d/m/Y'); // Format ngày: Ngày/Tháng/Năm

// Tạo nội dung cho bảng payment
$rowsPaymentTable = "";
foreach ($request['payment'] as $payment) {
  // check incl or excl is checked
  $inclChecked = $payment['incl'] == 'on' ? "<img src='https://static-00.iconduck.com/assets.00/checkbox-checked-icon-256x256-5ht7e55d.png' style='width: 15px; height: auto;'>" : "<img src='https://images.freeimages.com/fic/images/icons/2711/free_icons_for_windows8_metro/512/unchecked_checkbox.png' style='width: 15px; height: auto;'>";

  $exclChecked = $payment['excl'] == 'on' ? "<img src='https://static-00.iconduck.com/assets.00/checkbox-checked-icon-256x256-5ht7e55d.png' style='width: 15px; height: auto;'>" : "<img src='https://images.freeimages.com/fic/images/icons/2711/free_icons_for_windows8_metro/512/unchecked_checkbox.png' style='width: 15px; height: auto;'>";

  $formatNumber = number_format($payment['value'], 0, ",", ".");

  $rowsPaymentTable = $rowsPaymentTable . "
    <table class='line-item-table'>
        <tr>
            <td class='cell-item'>
                <div class='form-group'>
                    <label for='payment-content'>{$payment['name']}:</label>
                    <span>{$formatNumber}</span>
                    <span> {$payment['unit']}</span>
                </div>
            </td>
            <td>
                <div class='form-group'>
                    <label for='payment-content'>V.A.T:</label>
                    <span>{$payment['vat']} %</span>
                </div>
            </td>
            <td>
                <div class='form-group'>
                    <label for='payment-content'>Cont/Set:</label>
                    <span>{$payment['contSet']}</span>
                </div>
            </td>
            <td>
              <div class='form-group'>
                  {$inclChecked}
                  <label class='form-check-label' for='trunkingIncl'>
                  INCL
                  </label>
              </div>
            </td>
            <td>
              <div class='form-group'>
                  {$exclChecked}
                  <label class='form-check-label' for='trunkingExcl'>
                    EXCL
                  </label>
              </div>
            </td>
        </tr>
    </table>
  ";
}


$rowsTable = "";

if (count($request['expenses']) > 0) {
  for ($i = 0; $i < count($request['expenses']); $i++) {
    $item = $request['expenses'][$i];
    $index = $i + 1;
    $amount = (!empty($item['expense_amount']) ? number_format($item['expense_amount'], 0, ",", ".") : "");
    $rowsTable = $rowsTable . "
    <tr>
      <td class='line-item'>{$index}</td>
      <td class='line-item'>{$item['expense_kind']}</td>
      <td class='line-item'>{$amount}</td>
      <td class='line-item'>{$item['so_hoa_don']}</td>
      <td class='line-item'>{$item['expense_payee']}</td>
      <td class='line-item'>{$item['expense_doc']}</td>
    </tr>
    ";
  }
}


// Tạo data cho html
$department = $request['department'] ?? 'Giao nhận';
$formatTotal = (!empty($request['total_actual']) ? number_format($request['total_actual'], 0, ",", ".") : "");



// Tạo tên file bằng hàm MD5 từ email của người dùng
$operator_email_md5 = md5($request['operator_email']);
$leader_email_md5 = md5($request['approval'][0]['email']);
$sale_email_md5 = md5($request['approval'][1]['email']);
$director_email_md5 = md5($request['approval'][2]['email']);

$operator_signature_path = "/soffice/signatures/" . $operator_email_md5 . ".jpg";
$leader_signature_path = "/soffice/signatures/" . $leader_email_md5 . ".jpg";
$sale_signature_path = "/soffice/signatures/" . $sale_email_md5 . ".jpg";
$director_signature_path = "/soffice/signatures/" . $director_email_md5 . ".jpg";



// Tạo nội dung HTML cho template
$htmlContent = "
<!DOCTYPE html>
<html lang='vi'>
<head>
    <meta charset='UTF-8'>
    <title>Phiếu Đề Nghị Thanh Toán</title>
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
        .line-item-table {
            width: 100%;
            border-collapse: collapse;
            margin-left: 20px;
            margin-bottom: 10px;
        }
        .cell-item {
            width: 300px;
        }
        .line-item {
            padding: 8px;
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
            margin: 20px 0 10px 0;
        }
        .form-group {
            margin: 10px 0;
            margin-left: 20px;
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
        .page-break {
          page-break-after: always;
        }
    </style>
    <script src='https://unpkg.com/@phosphor-icons/web'></script>
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

  <h2 style='text-align: center;'>PHIẾU ĐỀ NGHỊ THANH TOÁN</h2>
  <div class='info' style='text-align: center; margin: 20px 0;'>
    <p>Số: {$request['instruction_no']} . Ngày: $date</p>
  </div>

   <div class='form-section'>
        <div class='form-group'>
            <label for='proposer'>Người đề nghị:</label>
            <span>{$request['operator_name']}</span>
        </div>
        <div class='form-group'>
            <label for='department'>Thuộc bộ phận:</label>
            <span>$department</span>
        </div>
        <div class='form-group'>
            <label for='payment-content'>Số tiền:</label>
            <span> {$request['amount']} đồng.</span>
            <label for='payment-content'> Bằng chữ:</label>
            <span> {$request['amountWords']}</span>
        </div>
        <div class='form-group'>
            <label for='payment-content'>Nội dung thanh toán: </label>
            <span>{$request['shipper']}/{$request['volume']}/{$request['customs_manifest_on']} LO {$request['payment_lo']}</span>
        </div>
    </div>

    <table class='line-item-table'>
      <tr>
        <td style='with: 25%; text-align: center;'>
          <p>Người đề nghị</p>
        </td>
        <td style='with: 25%; text-align: center;'>
          <p>Người duyệt</p>
        </td>
        <td style='with: 25%; text-align: center;'>
          <p>Kế toán</p>
        </td>
        <td style='with: 25%; text-align: center;'>
          <p>Giám đốc</p>
        </td>
      </tr>
      <tr>
        <td style='with: 25%; text-align: center;'>
          <img src='$operator_signature_path' alt='Chữ ký Người đề nghị' style='width: 150px; height: auto;'>
        </td>
        <td style='with: 25%; text-align: center;'>
          <img src='$sale_signature_path' alt='Chữ ký Người duyệt' style='width: 150px; height: auto;'>
        </td>
        <td style='with: 25%; text-align: center;'></td>
        <td style='with: 25%; text-align: center;'>
          <img src='$director_signature_path' alt='Chữ ký Người duyệt' style='width: 150px; height: auto;'>
        </td>
      </tr>
    </table>

  <div class='page-break'></div> <!-- Page break here -->


  <h3 style='text-align: center;'>UNI-GLOBAL</h3>
  <h5 style='text-align: center;'>INLAND SERVICE INTERNAL INSTRUCTION</h5>
  <div class='form-section'>
    <div class='form-group' style='text-align: center;'>
        <label for='proposer'>Instruction No:</label>
        <span>{$request['instruction_no']}</span>
    </div>
  </div>

  <div class='form-section'>
      <h4>I. SALES INFORMATION:</h4>
      <div class='form-group'>
          <label for='proposer'>Shipper:</label>
          <span>{$request['shipper']}</span>
      </div>
      <div class='form-group'>
          <label for='department'>Bill To:</label>
          <span>{$request['billTo']}</span>
      </div>
      <div class='form-group'>
          <label for='payment-content'>Volume:</label>
          <span>{$request['volume']}</span>
      </div>
      <div class='form-group'>
          <label for='payment-content'>Lô:</label>
          <span>{$request['payment_lo']}</span>
      </div>
  </div>
  <div class='form-section'>
      <h4>II. PICK UP/DELIVERY INFORMATION:</h4>
      <div class='form-group'>
          <label for='proposer'>Address:</label>
          <span>{$request['delivery_address']}</span>
      </div>

      <table class='line-item-table'>
          <tr>
              <td>
                <div class='form-group'>
                    <label for='department'>Time:</label>
                    <span>{$request['delivery_time']}</span>
                </div>
              </td>
              <td>
                <div class='form-group'>
                    <label for='payment-content'>PCT:</label>
                    <span>{$request['delivery_pct']}</span>
                </div>
              </td>
          </tr>
      </table>
      {$rowsPaymentTable}
  </div>

  <div class='form-section'>
      <h4>III. OPERATION INFORMATION</h4>
      <table class='line-item-table'>
          <tr>
              <td>
                <div class='form-group'>
                    <label for='proposer'>Operator:</label>
                    <span>{$request['operator_name']}</span>
                </div>
              </td>
              <td>
                <div class='form-group'>
                    <label for='department'>Customs manifest no:</label>
                    <span>{$request['customs_manifest_on']}</span>
                </div>
              </td>
          </tr>
      </table>

      <h4>EXPENSE</h4>
      <table border='1' cellspacing='0' class='line-item-table'>
        <thead>
          <tr>
            <th rowspan='2'>No</th>
            <th rowspan='2'>Kind of expense</th>
            <th colspan='2'>Amount</th>
            <th rowspan='2'>Payee</th>
            <th rowspan='2'>Doc.No</th>
          </tr>
          <tr>
            <th>Actual</th>
            <th>Số hóa đơn</th>
          </tr>
        </thead>
        <tbody>
          {$rowsTable}
          <tfoot>
            <tr>
              <td colspan='2'>TOTAL</td>
              <td><span>{$formatTotal}</span></td>
              <td></td>
              <td>
                RECEIVED BACK ON: <span>{$request['received_back_on']}</span>
              </td>
              <td colspan='2'>
                BY: <span>{$request['by']}</span>
              </td>
            </tr>
          </tfoot>
        </tbody>
      </table>

      <h4>DOCUMENTS REVERT:</h4>
      <table class='line-item-table'>
        <tr>
          <td>
            <div class='form-group'>
                <label for='proposer'>Salesman:</label>
                <span>{$saleUserData['fullname']}</span>
            </div>
          </td>
          <td>
            <div class='form-group'>
                <label for='department'>Date:</label>
                <span>{$request['approval'][0]['time']}</span>
            </div>
          </td>
          <td>
            <div class='form-group'>
                <label for='proposer'>Approved by:</label>
                <span>{$leaderData['fullname']}</span>
            </div>
          </td>
        </tr>
      </table>
  </div>
</body>
</html>
";

// Tạo thư mục lưu PDF nếu chưa tồn tại
$pdfDir = '../../../../../private_data/soffice_database/payment/exports/';
if (!is_dir($pdfDir)) {
  mkdir($pdfDir, 0777, true);
}

try {
  // Khởi tạo mPDF và lưu file PDF
  $mpdf = new \Mpdf\Mpdf([
    'margin_top' => 10,
    'margin_bottom' => 10,
    'margin_left' => 10,
    'margin_right' => 10,
  ]);
  // Optional: force page breaks if you need
  $mpdf->SetAutoPageBreak(true, 10);
  $mpdf->WriteHTML($htmlContent);

  $pdfFileName = $request['file_path'];
  $pdfPath = $pdfDir . $pdfFileName;

  $mpdf->Output($pdfPath, 'F'); // Lưu file PDF


  // upload google drive
  $folderId = '175l19YFsHesJmn5yKVO8XV-H5RZp8ron';
  $linkImg = uploadFileToGoogleDrive($pdfPath, $pdfFileName, $folderId);
  if ($linkImg) {
    unlink($pdfPath);
  }
  // update database file path by instruction_no
  $request['file_path'] = $linkImg;
  $directoryData = '../../../../../private_data/soffice_database/payment/data/' . $currentYear;
  $res = updateDataToJson($request, $directoryData, 'payment_' . $request['instruction_no']);

  // Trả về đường dẫn file PDF
  echo json_encode([
    'success' => true,
    'pdfUrl' => $linkImg
  ]);
} catch (\Mpdf\MpdfException $e) {
  // Xử lý lỗi mPDF
  echo json_encode([
    'success' => false,
    'message' => 'Lỗi khi tạo PDF: ' . $e->getMessage()
  ]);
}