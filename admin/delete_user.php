<?php
session_start();
header('Content-Type: application/json');

// Kiểm tra đăng nhập và quyền admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Không có quyền truy cập']);
    exit;
}

// Kiểm tra ID người dùng
if (!isset($_POST['id'])) {
    echo json_encode(['success' => false, 'message' => 'Thiếu ID người dùng']);
    exit;
}

// Kết nối database
$host = 'localhost';
$dbname = 'tuthien';
$user = 'root';
$pass = '123';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Kiểm tra xem người dùng có phải là admin không
    $stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
    $stmt->execute([$_POST['id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user['role'] === 'admin') {
        echo json_encode(['success' => false, 'message' => 'Không thể xóa tài khoản admin']);
        exit;
    }
    
    // Xóa người dùng
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
    $stmt->execute([$_POST['id']]);
    
    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true, 'message' => 'Xóa tài khoản thành công']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Không tìm thấy tài khoản']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()]);
}
?> 