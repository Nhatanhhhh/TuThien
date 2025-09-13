<?php
session_start();
header('Content-Type: application/json');
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', 'C:/laragon/www/TuThien/logs/error.log');

require_once '../vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require_once '../config.php';
require_once 'refund.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'staff') {
    error_log('Access denied: No staff session');
    echo json_encode(['success' => false, 'message' => 'Không có quyền truy cập.']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$event_id = $input['event_id'] ?? null;
$cancellation_reason = $input['cancellation_reason'] ?? '';

if (!$event_id || empty($cancellation_reason)) {
    echo json_encode(['success' => false, 'message' => 'Thiếu thông tin bắt buộc.']);
    exit;
}

try {
    $pdo->beginTransaction();

    // Kiểm tra trạng thái chuyến đi
    $stmt = $pdo->prepare("SELECT is_cancelled FROM trips WHERE id = ? AND is_active = 1");
    $stmt->execute([$event_id]);
    $trip = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$trip) {
        throw new Exception("Không tìm thấy chuyến đi hoặc đã bị hủy.");
    }

    if ($trip['is_cancelled'] == 1) {
        throw new Exception("Chuyến đi đã bị hủy trước đó.");
    }

    // Cập nhật trạng thái chuyến đi
    $stmt = $pdo->prepare("
        UPDATE trips 
        SET is_cancelled = 1, 
            is_active = 0,
            cancellation_reason = ?, 
            cancelled_at = NOW(),
            refund_status = 'pending'
        WHERE id = ?
    ");
    $stmt->execute([$cancellation_reason, $event_id]);

    // Cập nhật refund_status của vé
    $stmt = $pdo->prepare("
        UPDATE tickets 
        SET refund_status = 'pending'
        WHERE trip_id = ? AND status = 'confirmed'
    ");
    $stmt->execute([$event_id]);

    $pdo->commit();

    // Xử lý hoàn tiền
    $refund_success = processRefund($pdo, $event_id);
    if ($refund_success) {
        // Lấy thông tin chuyến đi
        $stmt = $pdo->prepare("SELECT name FROM trips WHERE id = ?");
        $stmt->execute([$event_id]);
        $trip = $stmt->fetch(PDO::FETCH_ASSOC);
        $trip_name = $trip['name'];

        // Lấy danh sách người dùng đã quyên góp
        $stmt = $pdo->prepare("
            SELECT u.email, u.fullname, SUM(t.amount) as total_refunded
            FROM tickets t
            JOIN users u ON t.user_id = u.id
            WHERE t.trip_id = ? 
            AND t.refund_status = 'completed'
            GROUP BY u.id, u.email, u.fullname
        ");
        $stmt->execute([$event_id]);
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (!empty($users)) {
            try {
                $mail = new PHPMailer(true);
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username = 'renboy122333444@gmail.com';
                $mail->Password = 'sujk zghj eigy mquh';
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port = 587;
                $mail->CharSet = 'UTF-8';
                $mail->setFrom('renboy122333444@gmail.com', 'HopeLink System');

                foreach ($users as $user) {
                    $mail->addBCC($user['email'], $user['fullname']);
                }

                $mail->isHTML(true);
                $mail->Subject = "Thông Báo Hoàn Tiền - Chuyến Đi '$trip_name'";
                $mail->Body = "
                    <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                        <h2 style='color: #5a67d8;'>Thông Báo Hoàn Tiền - HopeLink</h2>
                        <p>Kính gửi Quý khách,</p>
                        <p>Chuyến đi <strong>'$trip_name'</strong> đã bị hủy với lý do: $cancellation_reason.</p>
                        <p>Số tiền bạn đã quyên góp đã được hoàn lại vào tài khoản của bạn.</p>
                        <p>Vui lòng kiểm tra tài khoản HopeLink để biết thêm chi tiết.</p>
                        <p>Cảm ơn bạn đã tham gia cùng HopeLink!</p>
                        <hr style='border: 1px solid #e2e8f0; margin: 20px 0;'>
                        <p style='color: #718096; font-size: 12px;'>Email này được gửi tự động, vui lòng không trả lời.</p>
                    </div>
                ";

                if ($mail->send()) {
                    error_log("Refund email sent to " . count($users) . " recipients for trip $event_id");
                } else {
                    error_log("Failed to send refund email for trip $event_id: " . $mail->ErrorInfo);
                }
            } catch (Exception $e) {
                error_log("PHPMailer error: " . $e->getMessage());
            }
        }

        echo json_encode(['success' => true, 'message' => 'Chuyến đi đã được hủy và hoàn tiền đã được xử lý. Email thông báo đã được gửi.']);
    } else {
        echo json_encode(['success' => true, 'message' => 'Hủy chuyến đi thành công nhưng không thể xử lý hoàn tiền.']);
    }
} catch (Exception $e) {
    $pdo->rollBack();
    error_log('Lỗi khi hủy chuyến đi: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Lỗi khi hủy chuyến đi: ' . $e->getMessage()]);
}
?>