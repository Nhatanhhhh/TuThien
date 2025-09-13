<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Không có quyền truy cập']);
    exit;
}

require_once '../config.php';

try {
    $id = $_GET['id'] ?? null;
    if (!$id) {
        echo json_encode(['success' => false, 'message' => 'ID không hợp lệ']);
        exit;
    }

    $stmt = $pdo->prepare("
        SELECT tp.*, u.username, u.fullname, t.name AS trip_name 
        FROM trip_participants tp 
        LEFT JOIN users u ON tp.user_id = u.id 
        LEFT JOIN trips t ON tp.trip_id = t.id 
        WHERE tp.id = ? AND t.is_deleted = 0 AND t.is_active = 1
    ");
    $stmt->execute([$id]);
    $participant = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($participant) {
        echo json_encode(['success' => true, 'participant' => $participant]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Không tìm thấy thông tin tham gia']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Lỗi truy vấn: ' . $e->getMessage()]);
}
?>