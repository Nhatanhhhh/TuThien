<?php
session_start();
require_once '../config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'staff') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

try {
    $db = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Lấy tham số lọc thời gian
    $timeFilter = $_GET['time_filter'] ?? 'month';
    $dateFormat = match($timeFilter) {
        'day' => '%Y-%m-%d',
        'year' => '%Y',
        default => '%Y-%m'
    };
    $interval = match($timeFilter) {
        'day' => 'INTERVAL 30 DAY',
        'year' => 'INTERVAL 5 YEAR',
        default => 'INTERVAL 6 MONTH'
    };

    // Lấy số chuyến đi đang diễn ra
    $stmt = $db->query("SELECT COUNT(*) FROM trips WHERE is_active = 1 AND is_cancelled = 0 AND date >= CURDATE()");
    $active_events = $stmt->fetchColumn();

    // Lấy tổng số người tham gia
    $stmt = $db->query("SELECT COUNT(DISTINCT user_id) FROM trip_participants");
    $total_participants = $stmt->fetchColumn();

    // Lấy tổng số vé đã bán
    $stmt = $db->query("SELECT COUNT(*) FROM tickets WHERE status = 'confirmed'");
    $total_tickets = $stmt->fetchColumn();

    // Lấy tổng số tiền từ các giao dịch thành công
    $stmt = $db->query("SELECT SUM(amount) FROM transactions WHERE status = 'completed' AND type = 'deposit'");
    $total_donations = $stmt->fetchColumn() ?: 0;

    // Lấy dữ liệu cho biểu đồ vé bán
    $stmt = $db->query("
        SELECT DATE_FORMAT(created_at, '$dateFormat') as time_period, COUNT(*) as ticket_count 
        FROM tickets 
        WHERE status = 'confirmed'
        AND created_at >= DATE_SUB(CURDATE(), $interval)
        GROUP BY DATE_FORMAT(created_at, '$dateFormat')
        ORDER BY time_period ASC
    ");
    $ticket_stats = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Lấy dữ liệu cho biểu đồ quyên góp
    $stmt = $db->query("
        SELECT DATE_FORMAT(created_at, '$dateFormat') as time_period, SUM(amount) as total_amount
        FROM transactions 
        WHERE status = 'completed' 
        AND type = 'deposit'
        AND created_at >= DATE_SUB(CURDATE(), $interval)
        GROUP BY DATE_FORMAT(created_at, '$dateFormat')
        ORDER BY time_period ASC
    ");
    $donation_stats = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'active_events' => $active_events,
        'total_participants' => $total_participants,
        'total_tickets' => $total_tickets,
        'total_donations' => $total_donations,
        'ticket_stats' => $ticket_stats,
        'donation_stats' => $donation_stats,
        'time_filter' => $timeFilter
    ]);

} catch (PDOException $e) {
    error_log("Database error in get_dashboard_stats: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?> 