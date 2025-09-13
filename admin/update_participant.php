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
    $fullname = $_POST['fullname'] ?? '';
    $email = $_POST['email'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $user_id = $_POST['user_id'] ?? null;
    $trip_id = $_POST['trip_id'] ?? null;

    if (!$participant_id || empty($fullname) || empty($email) || empty($phone) || !$trip_id) {
        echo json_encode(['success' => false, 'message' => 'Vui lòng điền đầy đủ thông tin']);
        exit;
    }

    $stmt = $pdo->prepare("UPDATE trip_participants SET name = ?, email = ?, phone = ?, user_id = ?, trip_id = ? WHERE id = ?");
    $stmt->execute([$fullname, $email, $phone, $user_id ?: null, $trip_id, $participant_id]);

    echo json_encode(['success' => true, 'message' => 'Cập nhật tham gia thành công']);
} catch (PDOException $e) {
    error_log('Lỗi khi cập nhật tham gia: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Lỗi khi cập nhật tham gia: ' . $e->getMessage()]);
}
?>