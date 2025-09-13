<?php
header('Content-Type: application/json');
session_start();

// Kiểm tra quyền admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Không có quyền truy cập']);
    exit;
}

require_once '../config.php';

try {
    $pdo->beginTransaction();

    // Lấy danh sách tất cả chuyến đi với thông tin chi tiết
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

    // Lấy danh sách người nhận hoàn tiền cho mỗi chuyến đi (loại bỏ trùng lặp)
    foreach ($trips as &$trip) {
        $stmt = $pdo->prepare("
            SELECT DISTINCT u.username
            FROM transactions tr
            JOIN users u ON tr.user_id = u.id
            WHERE tr.trip_id = ? AND tr.type = 'refund' AND tr.status = 'completed'
        ");
        $stmt->execute([$trip['id']]);
        $recipients = $stmt->fetchAll(PDO::FETCH_COLUMN);
        $trip['refunded_users'] = $recipients;
    }

    $pdo->commit();

    echo json_encode(['success' => true, 'trips' => $trips]);
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()]);
}
?>