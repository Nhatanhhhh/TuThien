<?php
session_start();
require_once '../config.php';

header('Content-Type: application/json');

// Kiểm tra quyền admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Không có quyền truy cập']);
    exit;
}

// Nhận dữ liệu từ FormData
$user_id = $_POST['edit-user-id'] ?? null;
$email = $_POST['edit-email'] ?? null;
$phone = $_POST['edit-phone'] ?? null;
$gender = $_POST['edit-gender'] ?? null;
$birthdate = $_POST['edit-birthdate'] ?? null;
$is_locked = $_POST['edit-is-locked'] ?? 0;
$locked_until = $_POST['edit-locked-until'] ?? null;
$password = $_POST['edit-password'] ?? null;

// Kiểm tra user_id bắt buộc
if (!$user_id) {
    echo json_encode(['success' => false, 'message' => 'Thiếu ID người dùng']);
    exit;
}

// Lấy thông tin user hiện tại
$stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    echo json_encode(['success' => false, 'message' => 'Người dùng không tồn tại']);
    exit;
}

// Nếu là staff và có password mới
if ($user['role'] === 'staff' && $password && strlen($password) > 0) {
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    try {
        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->execute([$hashedPassword, $user_id]);

        echo json_encode(['success' => true, 'message' => 'Cập nhật mật khẩu thành công']);
        exit;
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Lỗi khi cập nhật mật khẩu: ' . $e->getMessage()]);
        exit;
    }
}

// Nếu không phải staff hoặc không có password, kiểm tra các trường khác
if (!$email || !$phone || !$gender || !$birthdate) {
    echo json_encode(['success' => false, 'message' => 'Thiếu thông tin bắt buộc']);
    exit;
}

// Chuẩn hóa locked_until (nếu không có giá trị thì đặt thành NULL)
$locked_until = $locked_until ? date('Y-m-d H:i:s', strtotime($locked_until)) : null;

try {
    $stmt = $pdo->prepare("UPDATE users SET 
        email = ?, 
        phone = ?, 
        gender = ?, 
        birthdate = ?, 
        is_locked = ?, 
        locked_until = ? 
        WHERE id = ?");

    $stmt->execute([$email, $phone, $gender, $birthdate, $is_locked, $locked_until, $user_id]);

    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true, 'message' => 'Cập nhật người dùng thành công']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Không tìm thấy người dùng hoặc không có thay đổi']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()]);
}
?>