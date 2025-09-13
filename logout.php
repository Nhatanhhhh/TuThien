<?php
session_start();

// Kiểm tra CSRF token để đảm bảo bảo mật
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Yêu cầu không hợp lệ: CSRF token không đúng.']);
    exit;
}

// Ghi log đăng xuất
$userId = $_SESSION['user_id'] ?? 'unknown';
error_log("User ID $userId logged out at " . date('Y-m-d H:i:s'));

// Xóa tất cả dữ liệu session
session_unset();
session_destroy();

// Xóa cookie session nếu có
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// Trả về phản hồi JSON và chuyển hướng
header('Content-Type: application/json');
echo json_encode(['success' => true, 'redirect' => '/TuThien/index.php']);
exit;
?>