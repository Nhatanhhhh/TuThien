<?php
session_start();
header('Content-Type: application/json');
require_once '../config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Không có quyền truy cập']);
    exit;
}

$event_id = $_POST['event_id'] ?? null;
$name = $_POST['name'] ?? '';
$description = $_POST['description'] ?? '';
$date = $_POST['date'] ?? '';
$time = $_POST['time'] ?? '';
$location = $_POST['location'] ?? '';
$is_active = $_POST['is_active'] ?? '0';
$image_url = $_POST['image'] ?? '';

if (!$event_id || !$name || !$description || !$date || !$time || !$location) {
    echo json_encode(['success' => false, 'message' => 'Vui lòng điền đầy đủ thông tin']);
    exit;
}

// Xử lý file upload
$image_path = null;
if (isset($_FILES['image_file']) && $_FILES['image_file']['error'] === UPLOAD_ERR_OK) {
    $upload_dir = '../images/';
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    $file_name = uniqid() . '_' . basename($_FILES['image_file']['name']);
    $file_path = $upload_dir . $file_name;
    if (move_uploaded_file($_FILES['image_file']['tmp_name'], $file_path)) {
        $image_path = '/TuThien/images/' . $file_name;
    } else {
        echo json_encode(['success' => false, 'message' => 'Lỗi khi tải lên hình ảnh']);
        exit;
    }
}

try {
    $stmt = $pdo->prepare("UPDATE trips SET name = ?, description = ?, date = ?, time = ?, location = ?, image = ?, is_active = ? WHERE id = ?");
    $image_to_save = $image_path ?? ($image_url ?: null);
    $stmt->execute([$name, $description, $date, $time, $location, $image_to_save, $is_active, $event_id]);
    echo json_encode(['success' => true, 'message' => 'Cập nhật sự kiện thành công']);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Lỗi truy vấn: ' . $e->getMessage()]);
}
?>