<?php
session_start();

// Kiểm tra đăng nhập và quyền admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once '../config.php';

// Chỉ trả về JSON khi là AJAX request
header('Content-Type: application/json');

$username = trim($_POST['username'] ?? '');
$password = trim($_POST['password'] ?? '');
$email = trim($_POST['email'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$gender = $_POST['gender'] ?? '';
$birthdate = $_POST['birthdate'] ?? '';
$fullname = trim($_POST['fullname'] ?? '');
$role = 'staff';

// Validate input
if (empty($username) || empty($password) || empty($email) || empty($gender) || empty($birthdate) || empty($fullname)) {
    echo json_encode(['success' => false, 'message' => 'Vui lòng nhập đầy đủ thông tin bắt buộc']);
    exit;
}

try {
    $pdo->beginTransaction();

    // Kiểm tra username tồn tại
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->execute([$username]);
    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => false, 'message' => 'Tên đăng nhập đã tồn tại']);
        exit;
    }

    // Kiểm tra email tồn tại
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => false, 'message' => 'Email đã tồn tại']);
        exit;
    }

    // Kiểm tra phone tồn tại (nếu có)
    if (!empty($phone)) {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE phone = ?");
        $stmt->execute([$phone]);
        if ($stmt->rowCount() > 0) {
            echo json_encode(['success' => false, 'message' => 'Số điện thoại đã tồn tại']);
            exit;
        }
    }

    // Mã hóa mật khẩu
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // Thêm nhân viên mới
    $stmt = $pdo->prepare("INSERT INTO users (username, password, email, phone, gender, birthdate, fullname, role) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$username, $hashedPassword, $email, $phone ?: null, $gender, $birthdate, $fullname, $role]);

    // Tạo quỹ cho nhân viên mới
    $userId = $pdo->lastInsertId();
    $stmt = $pdo->prepare("INSERT INTO user_funds (user_id, balance) VALUES (?, 0.00)");
    $stmt->execute([$userId]);

    $pdo->commit();

    echo json_encode(['success' => true, 'message' => 'Thêm nhân viên thành công']);
} catch (PDOException $e) {
    $pdo->rollBack();
    error_log("Error adding staff: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Lỗi hệ thống: ' . $e->getMessage()]);
}