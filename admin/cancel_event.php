<?php
session_start();
header('Content-Type: application/json');

// Tắt hiển thị lỗi và ghi vào log
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', 'C:/laragon/www/TuThien/logs/error.log');

// PHPMailer configuration
require_once '../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Debug: Log the start of the script
error_log('Starting cancel_event.php at ' . date('Y-m-d H:i:s'));

require_once '../config.php';

// Kiểm tra xem file refund.php có tồn tại không trước khi require
if (!file_exists('refund.php')) {
    error_log('File refund.php not found in ' . __DIR__);
    echo json_encode(['success' => false, 'message' => 'Lỗi hệ thống: Không tìm thấy file xử lý hoàn tiền tại ' . __DIR__]);
    exit;
}
require_once 'refund.php';

// Kiểm tra quyền admin và CSRF token
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    error_log('Access denied: No admin session');
    echo json_encode(['success' => false, 'message' => 'Không có quyền truy cập.']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$event_id = $input['event_id'] ?? null;
$cancellation_reason = $input['cancellation_reason'] ?? '';
$csrf_token = $input['csrf_token'] ?? '';

error_log("Received input: event_id=$event_id, cancellation_reason=$cancellation_reason, csrf_token=$csrf_token");

if (!$event_id || empty($cancellation_reason)) {
    echo json_encode(['success' => false, 'message' => 'Thiếu thông tin bắt buộc.']);
    exit;
}

if ($csrf_token !== $_SESSION['csrf_token']) {
    echo json_encode(['success' => false, 'message' => 'CSRF token không hợp lệ.']);
    exit;
}

try {
    $pdo->beginTransaction();

    // Kiểm tra trạng thái chuyến đi
    $stmt = $pdo->prepare("SELECT is_cancelled, refund_status, cancelled_at FROM trips WHERE id = ?");
    $stmt->execute([$event_id]);
    $trip = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$trip) {
        throw new Exception("Không tìm thấy chuyến đi.");
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

    // Cập nhật refund_status của các vé liên quan
    $stmt = $pdo->prepare("
        UPDATE tickets 
        SET refund_status = 'pending'
        WHERE trip_id = ? AND status = 'confirmed'
    ");
    $stmt->execute([$event_id]);

    $pdo->commit();

    // Gọi hàm xử lý hoàn tiền
    $refund_success = processRefund($pdo, $event_id);
    if ($refund_success) {
        // Lấy thông tin chuyến đi
        $stmt = $pdo->prepare("SELECT name FROM trips WHERE id = ?");
        $stmt->execute([$event_id]);
        $trip = $stmt->fetch(PDO::FETCH_ASSOC);
        $trip_name = $trip['name'];

        // Lấy danh sách người dùng đã quyên góp trong lần hủy hiện tại
        // Chỉ lấy vé có created_at sau lần khôi phục gần nhất (nếu có)
        $stmt = $pdo->prepare("
            SELECT u.email, u.fullname, SUM(t.amount) as total_refunded
            FROM tickets t
            JOIN users u ON t.user_id = u.id
            WHERE t.trip_id = ? 
            AND t.refund_status = 'completed'
            AND t.created_at > COALESCE(
                (SELECT cancelled_at FROM trips WHERE id = ? AND cancelled_at IS NOT NULL ORDER BY cancelled_at DESC LIMIT 1, 1),
                '1970-01-01 00:00:00'
            )
            GROUP BY u.id, u.email, u.fullname
        ");
        $stmt->execute([$event_id, $event_id]);
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Gửi email thông báo hàng loạt sử dụng BCC
        if (!empty($users)) {
            try {
                $mail = new PHPMailer(true);

                // SMTP configuration
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username = 'renboy122333444@gmail.com';
                $mail->Password = 'sujk zghj eigy mquh';
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port = 587;
                $mail->CharSet = 'UTF-8';
                $mail->SMTPDebug = SMTP::DEBUG_SERVER;
                $mail->Debugoutput = function ($str, $level) {
                    error_log("PHPMailer Debug [$level]: $str");
                };

                // Sender
                $mail->setFrom('renboy122333444@gmail.com', 'HopeLink System');

                // Thêm tất cả người nhận vào BCC
                foreach ($users as $user) {
                    $mail->addBCC($user['email'], $user['fullname']);
                }

                // Email content
                $mail->isHTML(true);
                $mail->Subject = "Thông Báo Hoàn Tiền - Chuyến Đi '$trip_name'";
                $mail->Body = "
                    <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                        <h2 style='color: #5a67d8;'>Thông Báo Hoàn Tiền - HopeLink</h2>
                        <p>Kính gửi Quý khách,</p>
                        <p>Chuyến đi <strong>'$trip_name'</strong> đã bị hủy với lý do: $cancellation_reason.</p>
                        <p>Số tiền bạn đã quyên góp cho chuyến đi này đã được hoàn lại vào tài khoản của bạn.</p>
                        <p>Để biết thêm chi tiết, vui lòng kiểm tra tài khoản HopeLink của bạn.</p>
                        <p>Cảm ơn bạn đã tham gia cùng HopeLink!</p>
                        <hr style='border: 1px solid #e2e8f0; margin: 20px 0;'>
                        <p style='color: #718096; font-size: 12px;'>Email này được gửi tự động, vui lòng không trả lời.</p>
                    </div>
                ";

                // Gửi email
                if ($mail->send()) {
                    error_log("Refund email sent successfully to " . count($users) . " recipients for trip $event_id at " . date('Y-m-d H:i:s'));
                } else {
                    error_log("Failed to send refund email for trip $event_id: " . $mail->ErrorInfo);
                }
            } catch (Exception $e) {
                error_log("PHPMailer error at " . date('Y-m-d H:i:s') . ": " . $e->getMessage());
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