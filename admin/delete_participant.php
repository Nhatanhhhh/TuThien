<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Không có quyền truy cập']);
    exit;
}

require_once '../config.php';

try {
    $participant_id = $_POST['participant_id'] ?? null;
    if (!$participant_id) {
        echo json_encode(['success' => false, 'message' => 'ID không hợp lệ']);
        exit;
    }

    $stmt = $pdo->prepare("DELETE FROM trip_participants WHERE id = ?");
    $stmt->execute([$participant_id]);

    echo json_encode(['success' => true, 'message' => 'Xóa tham gia thành công']);
} catch (PDOException $e) {
    error_log('Lỗi khi xóa tham gia: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Lỗi khi xóa tham gia: ' . $e->getMessage()]);
}
?>