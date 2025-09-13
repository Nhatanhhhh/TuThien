<?php
session_start();
header('Content-Type: application/json');

error_log('=== START update_participant.php ===');
error_log('Session user_id: ' . ($_SESSION['user_id'] ?? 'not set'));
error_log('Session role: ' . ($_SESSION['role'] ?? 'not set'));

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'staff') {
    error_log('Access denied: User not staff or not logged in');
    echo json_encode(['success' => false, 'message' => 'Không có quyền truy cập']);
    exit;
}

require_once '../config.php';

try {
    $participant_id = $_POST['participant_id'] ?? null;
    $name = $_POST['name'] ?? '';
    $fullname = $_POST['fullname'] ?? '';
    $email = $_POST['email'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $user_id = $_POST['user_id'] ?? null;

    error_log('Received data: participant_id=' . $participant_id . ', name=' . $name . ', fullname=' . $fullname . ', email=' . $email . ', phone=' . $phone . ', user_id=' . $user_id);

    if (!$participant_id || empty($name) || empty($fullname) || empty($email) || empty($phone)) {
        error_log('Missing required fields');
        echo json_encode(['success' => false, 'message' => 'Vui lòng điền đầy đủ thông tin']);
        exit;
    }

    $pdo->beginTransaction();

    $stmt = $pdo->prepare('SELECT trip_id FROM trip_participants WHERE id = ?');
    $stmt->execute([$participant_id]);
    $trip_id = $stmt->fetchColumn();

    $stmt = $pdo->prepare("
        UPDATE trip_participants 
        SET name = ?, email = ?, phone = ?, user_id = ?
        WHERE id = ?
    ");

    $result = $stmt->execute([$fullname, $email, $phone, $user_id ?: null, $participant_id]);

    if ($result) {
        $pdo->commit();
        error_log('Participant updated successfully for ID: ' . $participant_id);
        echo json_encode(['success' => true, 'message' => 'Cập nhật tham gia thành công']);
    } else {
        $pdo->rollBack();
        error_log('Failed to update participant for ID: ' . $participant_id);
        echo json_encode(['success' => false, 'message' => 'Không thể cập nhật thông tin tham gia']);
    }
} catch (PDOException $e) {
    $pdo->rollBack();
    error_log('Database error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Lỗi truy vấn: ' . $e->getMessage()]);
}

error_log('=== END update_participant.php ===');
?>