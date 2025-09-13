<?php
session_start();
require_once '../config.php';

header('Content-Type: application/json');

// Kiểm tra quyền admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Không có quyền truy cập']);
    exit;
}

// Nhận dữ liệu từ request
$input = json_decode(file_get_contents('php://input'), true);
$user_id = $input['user_id'] ?? null;

if (!$user_id) {
    echo json_encode(['success' => false, 'message' => 'Thiếu ID người dùng']);
    exit;
}

try {
    $stmt = $pdo->prepare("UPDATE users SET is_locked = 0, locked_until = NULL WHERE id = ?");
    $stmt->execute([$user_id]);

    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true, 'message' => 'Mở khóa người dùng thành công']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Người dùng không tồn tại']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()]);
}
?>