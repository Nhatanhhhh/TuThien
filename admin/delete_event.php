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
    $stmt = $pdo->prepare("UPDATE trips SET is_deleted = 1 WHERE id = ?");
    $stmt->execute([$input['event_id']]);

    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true, 'message' => 'Xóa sự kiện thành công']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Không tìm thấy sự kiện']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Lỗi truy vấn: ' . $e->getMessage()]);
}
?>