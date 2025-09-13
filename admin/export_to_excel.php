<?php
require_once '../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Chart\Chart;
use PhpOffice\PhpSpreadsheet\Chart\DataSeries;
use PhpOffice\PhpSpreadsheet\Chart\DataSeriesValues;
use PhpOffice\PhpSpreadsheet\Chart\Legend;
use PhpOffice\PhpSpreadsheet\Chart\PlotArea;
use PhpOffice\PhpSpreadsheet\Chart\Title;

session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

require_once '../config.php';

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

$sheet->setCellValue('A1', 'Tên Chuyến Đi');
$sheet->setCellValue('B1', 'Số Vé Tham Gia');
$sheet->setCellValue('C1', 'Trạng Thái');
$sheet->setCellValue('D1', 'Thời Gian Hủy');
$sheet->setCellValue('E1', 'Lý Do Hủy');
$sheet->setCellValue('F1', 'Hoàn Tiền');
$sheet->setCellValue('G1', 'Số Tiền Hoàn');
$sheet->setCellValue('H1', 'Người Nhận Hoàn Tiền');

$stmt = $pdo->prepare("
    SELECT t.id, t.name, t.is_active, t.is_cancelled, t.cancelled_at, t.cancellation_reason, t.refund_status,
           (SELECT COUNT(*) FROM trip_participants tp WHERE tp.trip_id = t.id) as participant_count,
           (SELECT COUNT(*) FROM tickets ti WHERE ti.trip_id = t.id AND ti.status = 'confirmed') as ticket_count,
           (SELECT COALESCE(SUM(tr.amount), 0) FROM transactions tr 
            WHERE tr.trip_id = t.id AND tr.type = 'refund' AND tr.status = 'completed') as refunded_amount,
           (SELECT COALESCE(SUM(ti.amount), 0) FROM tickets ti 
            WHERE ti.trip_id = t.id AND ti.status = 'confirmed') as total_amount
    FROM trips t
");
$stmt->execute();
$trips = $stmt->fetchAll(PDO::FETCH_ASSOC);

$row = 2;
foreach ($trips as $trip) {
    $stmt = $pdo->prepare("
        SELECT DISTINCT u.username
        FROM transactions tr
        JOIN users u ON tr.user_id = u.id
        WHERE tr.trip_id = ? AND tr.type = 'refund' AND tr.status = 'completed'
    ");
    $stmt->execute([$trip['id']]);
    $recipients = implode(', ', $stmt->fetchAll(PDO::FETCH_COLUMN));

    $status = $trip['is_cancelled'] ? 'Đã hủy' : ($trip['is_active'] ? 'Đang hoạt động' : 'Không hoạt động');
    $refundStatus = $trip['refund_status'] ?? 'Chưa hoàn tiền';
    $refundAmount = $trip['refunded_amount'] ? number_format($trip['refunded_amount'], 0, ',', '.') . ' VNĐ' : '0 VNĐ';
    $cancelledAt = $trip['cancelled_at'] ?? 'Chưa hủy';
    $cancellationReason = $trip['cancellation_reason'] ?? 'Không có';

    $sheet->setCellValue('A' . $row, $trip['name']);
    $sheet->setCellValue('B' . $row, $trip['ticket_count'] ?? 0);
    $sheet->setCellValue('C' . $row, $status);
    $sheet->setCellValue('D' . $row, $cancelledAt);
    $sheet->setCellValue('E' . $row, $cancellationReason);
    $sheet->setCellValue('F' . $row, $refundStatus);
    $sheet->setCellValue('G' . $row, $refundAmount);
    $sheet->setCellValue('H' . $row, $recipients);
    $row++;
}

// Thêm biểu đồ cột
$labels = new DataSeriesValues('String', 'Worksheet!$A$2:$A$' . ($row - 1), null, count($trips));
$categories = new DataSeriesValues('String', 'Worksheet!$A$1', null, 1);
$values = new DataSeriesValues('Number', 'Worksheet!$B$2:$B$' . ($row - 1), null, count($trips));

$series = new DataSeries(
    DataSeries::TYPE_BARCHART,
    DataSeries::GROUPING_CLUSTERED,
    range(0, count($trips) - 1),
    [$labels],
    [$categories],
    [$values]
);

$plotArea = new PlotArea(null, [$series]);
$legend = new Legend(Legend::POSITION_RIGHT, null, false);
$title = new Title('Biểu đồ Số Vé Tham Gia');
$chart = new Chart('chart1', $title, $legend, $plotArea);

$chart->setTopLeftPosition('A' . ($row + 2));
$chart->setBottomRightPosition('H' . ($row + 12));
$sheet->addChart($chart);

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="thong_ke_chuyen_di.xlsx"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->setIncludeCharts(true);
$writer->save('php://output');
?>