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
$hours = isset($input['hours']) ? (int) $input['hours'] : null;

if (!$user_id) {
    echo json_encode(['success' => false, 'message' => 'Thiếu ID người dùng']);
    exit;
}

try {
    $locked_until = null;
    if ($hours) {
        $locked_until = date('Y-m-d H:i:s', strtotime("+$hours hours"));
    }

    // Thêm debug log
    error_log("Locking user $user_id until $locked_until");

    $stmt = $pdo->prepare("UPDATE users SET is_locked = 1, locked_until = ? WHERE id = ?");
    $result = $stmt->execute([$locked_until, $user_id]);

    // Thêm debug log
    error_log("Update result: " . ($result ? 'success' : 'fail'));
    error_log("Rows affected: " . $stmt->rowCount());

    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true, 'message' => 'Khóa người dùng thành công']);
    } else {
        // Kiểm tra xem user có tồn tại không
        $check = $pdo->prepare("SELECT id FROM users WHERE id = ?");
        $check->execute([$user_id]);
        if (!$check->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Người dùng không tồn tại']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Không có thay đổi nào được thực hiện']);
        }
    }
} catch (PDOException $e) {
    error_log('PDO Error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Lỗi database: ' . $e->getMessage()]);
}
?>