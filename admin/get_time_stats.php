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

    // Thống kê theo năm
    $stmt = $pdo->prepare("
        SELECT YEAR(t.created_at) as year, 
               COUNT(t.id) as trip_count,
               COALESCE(SUM(ti.amount), 0) as total_amount
        FROM trips t
        LEFT JOIN tickets ti ON t.id = ti.trip_id AND ti.status = 'confirmed'
        GROUP BY YEAR(t.created_at)
        ORDER BY year DESC
    ");
    $stmt->execute();
    $yearly = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Thống kê theo quý
    $stmt = $pdo->prepare("
        SELECT YEAR(t.created_at) as year, 
               QUARTER(t.created_at) as quarter, 
               COUNT(t.id) as trip_count,
               COALESCE(SUM(ti.amount), 0) as total_amount
        FROM trips t
        LEFT JOIN tickets ti ON t.id = ti.trip_id AND ti.status = 'confirmed'
        GROUP BY YEAR(t.created_at), QUARTER(t.created_at)
        ORDER BY year DESC, quarter DESC
    ");
    $stmt->execute();
    $quarterly = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Thống kê theo tháng
    $stmt = $pdo->prepare("
        SELECT YEAR(t.created_at) as year, 
               MONTH(t.created_at) as month, 
               COUNT(t.id) as trip_count,
               COALESCE(SUM(ti.amount), 0) as total_amount
        FROM trips t
        LEFT JOIN tickets ti ON t.id = ti.trip_id AND ti.status = 'confirmed'
        GROUP BY YEAR(t.created_at), MONTH(t.created_at)
        ORDER BY year DESC, month DESC
    ");
    $stmt->execute();
    $monthly = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'yearly' => $yearly,
        'quarterly' => $quarterly,
        'monthly' => $monthly
    ]);
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()]);
}
?>