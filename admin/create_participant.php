<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Không có quyền truy cập']);
    exit;
}

require_once '../config.php';

try {
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $user_id = $_POST['user_id'] ?? null;
    $trip_id = $_POST['trip_id'] ?? null;

    if (empty($name) || empty($email) || empty($phone) || !$trip_id) {
        echo json_encode(['success' => false, 'message' => 'Vui lòng điền đầy đủ thông tin']);
        exit;
    }

    $stmt = $pdo->prepare("INSERT INTO trip_participants (name, email, phone, user_id, trip_id) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$name, $email, $phone, $user_id ?: null, $trip_id]);

    echo json_encode(['success' => true, 'message' => 'Thêm tham gia thành công']);
} catch (PDOException $e) {
    error_log('Lỗi khi tạo tham gia: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Lỗi khi tạo tham gia: ' . $e->getMessage()]);
}
?>