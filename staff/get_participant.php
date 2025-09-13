<?php
session_start();
header('Content-Type: application/json');

error_log('=== START get_participant.php ===');
error_log('Session user_id: ' . ($_SESSION['user_id'] ?? 'not set'));
error_log('Session role: ' . ($_SESSION['role'] ?? 'not set'));

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'staff') {
    error_log('Access denied: User not staff or not logged in');
    echo json_encode(['success' => false, 'message' => 'Không có quyền truy cập']);
    exit;
}

require_once '../config.php';

if (!isset($_GET['id'])) {
    error_log('Missing participant ID');
    echo json_encode(['success' => false, 'message' => 'ID tham gia không được cung cấp']);
    exit;
}

$participant_id = $_GET['id'];

try {
    error_log('Fetching participant with ID: ' . $participant_id);
    $stmt = $pdo->prepare("
        SELECT 
            p.*,
            u.username,
            u.fullname,
            t.name AS trip_name,
            t.is_cancelled,
            t.refund_status
        FROM trip_participants p
        LEFT JOIN users u ON p.user_id = u.id
        LEFT JOIN trips t ON p.trip_id = t.id
        WHERE p.id = ? AND t.is_deleted = 0
    ");

    $stmt->execute([$participant_id]);
    $participant = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($participant) {
        error_log('Participant found: ' . json_encode($participant));
        echo json_encode(['success' => true, 'participant' => $participant]);
    } else {
        error_log('Participant not found for ID: ' . $participant_id);
        echo json_encode(['success' => false, 'message' => 'Không tìm thấy thông tin tham gia']);
    }
} catch (PDOException $e) {
    error_log('Database error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Lỗi truy vấn: ' . $e->getMessage()]);
}

error_log('=== END get_participant.php ===');
?>