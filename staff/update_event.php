<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'staff') {
    echo json_encode(['success' => false, 'message' => 'Không có quyền truy cập']);
    exit;
}

require_once '../config.php';

if (
    !isset($_POST['event_id']) || !isset($_POST['name']) || !isset($_POST['date']) ||
    !isset($_POST['time']) || !isset($_POST['location']) || !isset($_POST['description'])
) {
    echo json_encode(['success' => false, 'message' => 'Tất cả các trường là bắt buộc']);
    exit;
}

$event_id = $_POST['event_id'];
$name = $_POST['name'];
$date = $_POST['date'];
$time = $_POST['time'];
$location = $_POST['location'];
$description = $_POST['description'];
$image_url = $_POST['image'] ?? '';

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
        echo json_encode(['success' => false, 'message' => 'Lỗi khi tải ảnh lên']);
        exit;
    }
}

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("
        UPDATE trips 
        SET name = ?, date = ?, time = ?, location = ?, description = ?, image = ? 
        WHERE id = ? AND is_active = 1 AND is_cancelled = 0
    ");
    $image_to_save = $image_path ?? ($image_url ?: null);
    $stmt->execute([$name, $date, $time, $location, $description, $image_to_save, $event_id]);

    if ($stmt->rowCount() > 0) {
        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'Cập nhật sự kiện thành công']);
    } else {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Không tìm thấy sự kiện hoặc sự kiện không hoạt động']);
    }
} catch (PDOException $e) {
    $pdo->rollBack();
    error_log('Database error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Lỗi cơ sở dữ liệu: ' . $e->getMessage()]);
}
?>