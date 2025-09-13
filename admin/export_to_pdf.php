<?php
ini_set('memory_limit', '1024M');
gc_enable();

ob_start();
require_once '../vendor/autoload.php';

use TCPDF;

session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

require_once '../config.php';

$pdo->exec("SET NAMES 'utf8mb4'");

class PDF extends TCPDF
{
    public $widths = [];
    public $aligns = [];
    private $chartData = [];

    function __construct()
    {
        parent::__construct('L', PDF_UNIT, 'A4', true, 'UTF-8', false);
        
        // Tối ưu hóa để tiết kiệm bộ nhớ
        $this->setJPEGQuality(50);
        $this->setFontSubsetting(true);
        $this->setImageScale(PDF_IMAGE_SCALE_RATIO);
        $this->setCompression(true);
        
        // Set document information
        $this->SetCreator(PDF_CREATOR);
        $this->SetAuthor('HopeLink');
        $this->SetTitle('Thống Kê Chuyến Đi');

        // Set margins
        $this->SetMargins(10, 10, 10);
        $this->SetHeaderMargin(10);
        $this->SetFooterMargin(10);
        $this->SetAutoPageBreak(TRUE, 20);
        $this->SetFont('dejavusans', '', 9);
    }
    
    function __destruct() {
        $this->_destroy(true);
        gc_collect_cycles();
    }

    function Footer()
    {
        $this->SetY(-15);
        $this->SetFont('dejavusans', 'I', 8);
        $this->Cell(0, 10, 'Trang ' . $this->getAliasNumPage() . '/' . $this->getAliasNbPages(), 0, 0, 'C');
    }

    function ChapterTitle($title)
    {
        $this->SetFont('dejavusans', 'B', 12);
        $this->Cell(0, 6, $title, 0, 1, 'C');
        $this->Ln(8);
    }

    function getSafeHeight()
    {
        return $this->PageBreakTrigger - $this->GetY() - 20;
    }

    function PieChart($labels, $data, $title, $maxDiameter, $maxHeight)
    {
        $total = array_sum($data);
        if ($total == 0)
            return $this->GetY();

        $diameter = min($maxDiameter, $maxHeight - 40);
        $radius = $diameter / 2;
        $x = ($this->getPageWidth() - $diameter) / 2;
        $y = $this->GetY() + 30;

        $colors = [
            [255, 99, 132],
            [54, 162, 235],
            [255, 206, 86],
            [75, 192, 192],
            [153, 102, 255],
            [255, 159, 64],
            [201, 203, 207]
        ];

        // Title
        $this->SetFont('dejavusans', 'B', 12);
        $this->Cell(0, 10, $title, 0, 1, 'C');
        $this->Ln(5);

        // Draw pie chart
        $startAngle = 0;
        $legendItems = [];
        for ($i = 0; $i < count($data); $i++) {
            $percentage = $data[$i] / $total;
            $angle = $percentage * 360;
            $this->SetFillColor($colors[$i % count($colors)][0], $colors[$i % count($colors)][1], $colors[$i % count($colors)][2]);
            $this->SetDrawColor(255, 255, 255);
            $this->PieSector($x + $radius, $y + $radius, $radius, $startAngle, $startAngle + $angle, 'FD', true, 90);
            // Không dùng sanitizeLabel cho legend để giữ nguyên tiếng Việt
            $legendItems[] = [
                'text' => mb_strimwidth($labels[$i], 0, 40, '...') . ' (' . number_format($percentage * 100, 1) . '%)',
                'color' => $colors[$i % count($colors)]
            ];
            $startAngle += $angle;
        }

        // Draw legend as a table (color box + text) on the left
        $this->SetFont('dejavusans', '', 8);
        $margins = $this->getMargins();
        $legendX = $margins['left'];
        $legendY = $y + $diameter + 10;

        foreach ($legendItems as $item) {
            $this->SetFillColor($item['color'][0], $item['color'][1], $item['color'][2]);
            $this->Rect($legendX, $legendY, 5, 5, 'F');
            $this->SetXY($legendX + 8, $legendY);
            $this->SetFont('dejavusans', '', 10); // Đảm bảo font unicode, size lớn hơn cho dễ đọc
            $this->MultiCell(0, 8, $item['text'], 0, 'L');
            $legendY += 12;
        }
        // Trả về vị trí Y sau khi vẽ xong
        return $legendY;
    }

    function wrapLabel($text, $maxLen)
    {
        if (strlen($text) > $maxLen) {
            $words = explode(' ', $text);
            $lines = [''];
            $currentLine = 0;
            foreach ($words as $word) {
                if (strlen($lines[$currentLine] . ' ' . $word) <= $maxLen) {
                    $lines[$currentLine] .= ($lines[$currentLine] ? ' ' : '') . $word;
                } else {
                    $lines[] = $word;
                    $currentLine++;
                }
            }
            return implode("\n", $lines);
        }
        return $text;
    }

    function sanitizeLabel($text)
    {
        $text = preg_replace("/[\n\r]/", ' ', $text);
        $text = preg_replace("/[^a-zA-Z0-9\s\(\)ĐđÀÁÂÃÈÉÊÌÍÒÓÔÕÙÚĂăĐđĨĩŨũƠơƯưẠạẢảẤấẦầẨẩẪẫẬậẮắẰằẲẳẴẵẶặẸẹẺẻẼẽẾếỀềỂểỄễỆệỈỉỊịỌọỎỏỐốỒồỔổỖỗỘộỚớỜờỞởỠỡỢợỤụỦủỨứỪừỬửỮữỰựỲỳỴỵỶỷỸỹ]/u", '', $text);
        $text = trim($text);
        return $text;
    }

    // Thêm hàm vẽ table dữ liệu
    function DrawTripTable($trips, $startY = null)
    {
        $this->SetFont('dejavusans', 'B', 10);
        $margins = $this->getMargins();
        $tableWidth = $this->getPageWidth() - $margins['left'] - $margins['right'];
        $colWidths = [$tableWidth * 0.40, $tableWidth * 0.25, $tableWidth * 0.15, $tableWidth * 0.20];
        $headers = ['Tên chuyến đi', 'Tổng số tiền (VNĐ)', 'Số vé', 'Số người tham gia'];
        if ($startY !== null) $this->SetY($startY + 5);
        // Table header với màu nền
        $this->SetFillColor(54, 162, 235); // Xanh dương
        $this->SetTextColor(255,255,255);
        foreach ($headers as $i => $header) {
            $this->Cell($colWidths[$i], 8, $header, 1, 0, 'C', true);
        }
        $this->Ln();
        $this->SetFont('dejavusans', '', 9);
        $this->SetTextColor(0,0,0);
        // Table rows với màu nền xen kẽ
        $fill = false;
        $rowCount = 0;
        foreach ($trips as $trip) {
            // Nếu không đủ chỗ cho 1 dòng mới thì sang trang mới và vẽ lại header
            if ($this->GetY() + 10 > ($this->getPageHeight() - $margins['bottom'])) {
                $this->AddPage();
                $this->SetFont('dejavusans', 'B', 10);
                $this->SetFillColor(54, 162, 235);
                $this->SetTextColor(255,255,255);
                foreach ($headers as $i => $header) {
                    $this->Cell($colWidths[$i], 8, $header, 1, 0, 'C', true);
                }
                $this->Ln();
                $this->SetFont('dejavusans', '', 9);
                $this->SetTextColor(0,0,0);
            }
            if ($fill) {
                $this->SetFillColor(230, 240, 255); // Màu nền nhạt
            } else {
                $this->SetFillColor(255,255,255); // Màu trắng
            }
            $this->Cell($colWidths[0], 8, $this->sanitizeLabel($trip['name']), 1, 0, 'L', true);
            $this->Cell($colWidths[1], 8, number_format($trip['total_amount']), 1, 0, 'R', true);
            $this->Cell($colWidths[2], 8, $trip['ticket_count'], 1, 0, 'C', true);
            $this->Cell($colWidths[3], 8, $trip['participant_count'], 1, 0, 'C', true);
            $this->Ln();
            $fill = !$fill;
            $rowCount++;
        }
        // Vẽ khung ngoài cho table chỉ khi có dữ liệu
        if ($rowCount > 0) {
            $tableHeight = $rowCount * 8 + 8; // 8 là chiều cao mỗi dòng, 8 cho header
            $this->SetDrawColor(54, 162, 235);
            $this->Rect($margins['left'], ($startY !== null ? $startY + 5 : $this->GetY() - $tableHeight), $tableWidth, $tableHeight, 'D');
        }
    }
}

// Lấy dữ liệu từ database với giới hạn nếu cần
$stmt = $pdo->prepare("SELECT t.id, t.name, t.is_active, t.is_cancelled, t.cancelled_at, t.cancellation_reason, t.refund_status,
       (SELECT COUNT(*) FROM trip_participants tp WHERE tp.trip_id = t.id) as participant_count,
       (SELECT COUNT(*) FROM tickets ti WHERE ti.trip_id = t.id AND ti.status = 'confirmed') as ticket_count,
       (SELECT COALESCE(SUM(tr.amount), 0) FROM transactions tr 
        WHERE tr.trip_id = t.id AND tr.type = 'refund' AND tr.status = 'completed') as refunded_amount,
       (SELECT COALESCE(SUM(ti.amount), 0) FROM tickets ti 
        WHERE ti.trip_id = t.id AND ti.status = 'confirmed') as total_amount
FROM trips t
ORDER BY t.id DESC
LIMIT 50");

$stmt->execute();
$trips = $stmt->fetchAll(PDO::FETCH_ASSOC);
unset($stmt);

// Tạo và xuất PDF
$pdf = new PDF();
$pdf->AddPage();
$pdf->ChapterTitle('Biểu đồ tổng số tiền theo chuyến đi');

// Chuẩn bị dữ liệu cho biểu đồ tròn
$labels = array_column($trips, 'name');
$values = array_column($trips, 'total_amount');

// Vẽ biểu đồ tròn
$margins = $pdf->getMargins();
$pageWidth = $pdf->getPageWidth() - $margins['left'] - $margins['right'];
// Thu nhỏ biểu đồ còn 60% chiều rộng trang
$pieYEnd = $pdf->PieChart(
    $labels,
    $values,
    'Tổng số tiền (VNĐ)',
    min($pageWidth * 0.25, $pageWidth - 40),
    $pdf->getSafeHeight()
);
// Vẽ table ngay dưới biểu đồ
$pdf->DrawTripTable($trips, $pieYEnd);

// Xuất file an toàn
ob_end_clean();
$tempFile = tempnam(sys_get_temp_dir(), 'pdf');
$pdf->Output($tempFile, 'F');

header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="thong_ke_chuyen_di.pdf"');
readfile($tempFile);
unlink($tempFile);
exit;
?>