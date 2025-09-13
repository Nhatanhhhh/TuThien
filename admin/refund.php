<?php
require_once '../config.php';

// Hàm xử lý hoàn tiền
function processRefund($pdo, $trip_id)
{
    try {
        $pdo->beginTransaction();

        // Lấy thông tin chuyến đi để kiểm tra
        $stmt = $pdo->prepare("SELECT is_cancelled, refund_status FROM trips WHERE id = ?");
        $stmt->execute([$trip_id]);
        $trip = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$trip || $trip['is_cancelled'] != 1) {
            throw new Exception("Chuyến đi không ở trạng thái bị hủy.");
        }

        if ($trip['refund_status'] === 'completed') {
            throw new Exception("Hoàn tiền cho chuyến đi này đã được xử lý trước đó.");
        }

        // Lấy danh sách vé cần hoàn tiền
        $stmt = $pdo->prepare("
            SELECT t.*, u.id as user_id 
            FROM tickets t 
            JOIN users u ON t.user_id = u.id 
            WHERE t.trip_id = ? 
            AND t.status = 'confirmed' 
            AND (t.refund_status IS NULL OR t.refund_status = 'pending')
        ");
        $stmt->execute([$trip_id]);
        $tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($tickets)) {
            // Nếu không có vé để hoàn tiền, cập nhật trạng thái hoàn tiền của chuyến đi
            $stmt = $pdo->prepare("UPDATE trips SET refund_status = 'completed' WHERE id = ?");
            $stmt->execute([$trip_id]);
            $pdo->commit();
            return true;
        }

        foreach ($tickets as $ticket) {
            // Cập nhật trạng thái vé
            $stmt = $pdo->prepare("
                UPDATE tickets 
                SET status = 'cancelled', 
                    refund_status = 'completed',
                    refunded_at = NOW() 
                WHERE id = ?
            ");
            $stmt->execute([$ticket['id']]);

            // Hoàn tiền vào tài khoản người dùng
            $stmt = $pdo->prepare("
                UPDATE user_funds 
                SET balance = balance + ?, 
                    updated_at = NOW()
                WHERE user_id = ?
            ");
            $stmt->execute([$ticket['amount'], $ticket['user_id']]);

            // Ghi log giao dịch hoàn tiền
            $stmt = $pdo->prepare("
                INSERT INTO transactions 
                (user_id, trip_id, amount, type, payment_method, status, message, created_at, updated_at) 
                VALUES (?, ?, ?, 'refund', 'system', 'completed', 'Hoàn tiền do chuyến đi bị hủy', NOW(), NOW())
            ");
            $stmt->execute([
                $ticket['user_id'],
                $trip_id,
                $ticket['amount']
            ]);
        }

        // Cập nhật trạng thái hoàn tiền của chuyến đi
        $stmt = $pdo->prepare("UPDATE trips SET refund_status = 'completed' WHERE id = ?");
        $stmt->execute([$trip_id]);

        $pdo->commit();
        return true;
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Error processing refund for trip $trip_id: " . $e->getMessage());
        return false;
    }
}

// Xử lý hoàn tiền cho các chuyến đi bị hủy (chỉ chạy nếu được gọi trực tiếp)
if (basename(__FILE__) == basename($_SERVER['PHP_SELF'])) {
    try {
        $stmt = $pdo->prepare("
            SELECT id 
            FROM trips 
            WHERE is_cancelled = 1 
            AND refund_status = 'pending'
        ");
        $stmt->execute();
        $cancelledTrips = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($cancelledTrips as $trip) {
            if (processRefund($pdo, $trip['id'])) {
                error_log("Refund processed successfully for trip ID: " . $trip['id']);
            } else {
                error_log("Failed to process refund for trip ID: " . $trip['id']);
            }
        }
    } catch (Exception $e) {
        error_log("Error in refund script: " . $e->getMessage());
    }
}
?>