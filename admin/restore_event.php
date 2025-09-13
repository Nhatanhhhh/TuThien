<?php
header('Content-Type: application/json');
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Không có quyền truy cập']);
    exit;
}

require_once '../config.php';

$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['event_id']) || !is_numeric($input['event_id'])) {
    echo json_encode(['success' => false, 'message' => 'ID sự kiện không hợp lệ']);
    exit;
}

try {
    $pdo->beginTransaction();

    // Kiểm tra trạng thái chuyến đi trước khi khôi phục
    $stmt = $pdo->prepare("SELECT is_cancelled, refund_status FROM trips WHERE id = ?");
    $stmt->execute([$input['event_id']]);
    $trip = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$trip) {
        throw new Exception("Không tìm thấy chuyến đi.");
    }

    if ($trip['is_cancelled'] != 1) {
        throw new Exception("Chuyến đi không ở trạng thái bị hủy để khôi phục.");
    }

    // Reset trạng thái chuyến đi
    $stmt = $pdo->prepare("
        UPDATE trips 
        SET is_deleted = 0, 
            is_cancelled = 0, 
            is_active = 1, 
            cancellation_reason = NULL, 
            cancelled_at = NULL, 
            refund_status = NULL 
        WHERE id = ?
    ");
    $stmt->execute([$input['event_id']]);

    if ($stmt->rowCount() > 0) {
        // Reset trạng thái vé và xóa các vé cũ
        $stmt = $pdo->prepare("
            UPDATE tickets 
            SET status = 'cancelled', 
                refund_status = 'completed', 
                refunded_at = NOW() 
            WHERE trip_id = ?
        ");
        $stmt->execute([$input['event_id']]);

        // Xóa các bản ghi trong trip_participants
        $stmt = $pdo->prepare("
            DELETE FROM trip_participants 
            WHERE trip_id = ?
        ");
        $stmt->execute([$input['event_id']]);

        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'Khôi phục sự kiện thành công. Số tiền và số vé đã được reset về 0.']);
    } else {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Không tìm thấy sự kiện']);
    }
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()]);
}
?>