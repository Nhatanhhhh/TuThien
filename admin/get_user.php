<?php
session_start();
header('Content-Type: application/json');

// Kiểm tra đăng nhập và quyền admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Không có quyền truy cập']);
    exit;
}

// Kiểm tra ID người dùng
if (!isset($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'Thiếu ID người dùng']);
    exit;
}

require_once '../config.php';

try {
    $stmt = $pdo->prepare("SELECT id, username, email, phone, gender, birthdate, role, is_locked, locked_until FROM users WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        echo json_encode([
            'success' => true,
            'user' => $user
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Không tìm thấy người dùng'
        ]);
    }
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Lỗi: ' . $e->getMessage()
    ]);
}
?>