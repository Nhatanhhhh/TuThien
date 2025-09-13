<?php
session_start();
require_once 'config.php';

header('Content-Type: application/json');

$username = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';

if (empty($username) || empty($password)) {
    echo json_encode(['success' => false, 'message' => 'Vui lòng nhập đầy đủ thông tin']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT id, username, password, role, is_locked, locked_until, email FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {
        if ($user['is_locked']) {
            $locked_until = $user['locked_until'] ? new DateTime($user['locked_until']) : null;
            $now = new DateTime();

            if ($locked_until && $locked_until <= $now) {
                $stmt = $pdo->prepare("UPDATE users SET is_locked = 0, locked_until = NULL WHERE id = ?");
                $stmt->execute([$user['id']]);
                $user['is_locked'] = 0;
            }

            if ($user['is_locked']) {
                $message = $locked_until
                    ? "Tài khoản của bạn bị khóa đến " . $locked_until->format('d/m/Y H:i:s')
                    : "Tài khoản của bạn bị khóa vĩnh viễn";
                echo json_encode(['success' => false, 'message' => $message]);
                exit;
            }
        }

        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['email'] = $user['email'] ?? '';
        $_SESSION['role'] = $user['role'];
        echo json_encode(['success' => true, 'message' => 'Đăng nhập thành công', 'redirect' => $redirect]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Tên đăng nhập hoặc mật khẩu không đúng']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()]);
}
?>