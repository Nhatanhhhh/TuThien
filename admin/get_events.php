<?php
session_start();
header('Content-Type: application/json');

error_log('=== START get_events.php ===');
error_log('Session user_id: ' . ($_SESSION['user_id'] ?? 'not set'));
error_log('Session role: ' . ($_SESSION['role'] ?? 'not set'));

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    error_log('Access denied: User not admin or not logged in');
    echo json_encode(['success' => false, 'message' => 'Không có quyền truy cập']);
    exit;
}

require_once '../config.php';

try {
    error_log('Connecting to database...');
    // Sắp xếp theo id thay vì date
    $stmt = $pdo->query("SELECT * FROM trips ORDER BY id ASC");
    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);

    error_log('Fetched events count: ' . count($events));
    error_log('Sample event data: ' . json_encode($events[0] ?? 'No events'));

    // Trả về dữ liệu JSON
    echo json_encode(['success' => true, 'events' => $events]);
} catch (PDOException $e) {
    error_log('Database error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Lỗi truy vấn: ' . $e->getMessage()]);
}

error_log('=== END get_events.php ===');
?>