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
    $description = $_POST['description'] ?? '';
    $date = $_POST['date'] ?? '';
    $time = $_POST['time'] ?? '';
    $location = $_POST['location'] ?? '';
    $is_active = $_POST['is_active'] ?? 0;

    if (empty($name) || empty($description) || empty($date) || empty($time) || empty($location)) {
        echo json_encode(['success' => false, 'message' => 'Vui lòng điền đầy đủ thông tin']);
        exit;
    }

    // Xử lý file upload
    $image = null;
    if (isset($_FILES['image_file']) && $_FILES['image_file']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../images/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        $file_name = uniqid() . '_' . basename($_FILES['image_file']['name']);
        $file_path = $upload_dir . $file_name;
        if (move_uploaded_file($_FILES['image_file']['tmp_name'], $file_path)) {
            $image = '/TuThien/images/' . $file_name;
        } else {
            echo json_encode(['success' => false, 'message' => 'Lỗi khi tải lên hình ảnh']);
            exit;
        }
    } else {
        $image = $_POST['image'] ?? null;
    }

    $stmt = $pdo->prepare("INSERT INTO trips (name, description, date, time, location, image, is_active, is_deleted) VALUES (?, ?, ?, ?, ?, ?, ?, 0)");
    $stmt->execute([$name, $description, $date, $time, $location, $image, $is_active]);

    echo json_encode(['success' => true, 'message' => 'Tạo sự kiện thành công']);
} catch (PDOException $e) {
    error_log('Lỗi khi tạo sự kiện: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Lỗi khi tạo sự kiện: ' . $e->getMessage()]);
}
?>