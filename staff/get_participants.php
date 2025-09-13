<?php
session_start();
header('Content-Type: application/json');

error_log('=== START get_participants.php ===');
error_log('Session user_id: ' . ($_SESSION['user_id'] ?? 'not set'));
error_log('Session role: ' . ($_SESSION['role'] ?? 'not set'));

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'staff') {
    error_log('Access denied: User not staff or not logged in');
    echo json_encode(['success' => false, 'message' => 'Không có quyền truy cập']);
    exit;
}

require_once '../config.php';

try {
    error_log('Connecting to database...');
    $stmt = $pdo->query("
        SELECT 
            p.*,
            u.username,
            u.fullname,
            t.name AS trip_name,
            t.is_cancelled,
            t.refund_status
        FROM trip_participants p
        LEFT JOIN users u ON p.user_id = u.id
        LEFT JOIN trips t ON p.trip_id = t.id
        WHERE t.is_deleted = 0
        ORDER BY p.id DESC
    ");
    $participants = $stmt->fetchAll(PDO::FETCH_ASSOC);

    error_log('Fetched participants count: ' . count($participants));
    error_log('Sample participant data: ' . json_encode($participants[0] ?? 'No participants'));

    echo json_encode(['success' => true, 'participants' => $participants]);
} catch (PDOException $e) {
    error_log('Database error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Lỗi truy vấn: ' . $e->getMessage()]);
}

error_log('=== END get_participants.php ===');
?>