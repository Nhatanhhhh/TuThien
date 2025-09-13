<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Enable error logging
ini_set('log_errors', 1);
ini_set('error_log', 'php_errors.log');

require_once 'config.php';

header('Content-Type: application/json');

// Debug session
error_log("Session data: " . print_r($_SESSION, true));
error_log("POST data: " . print_r($_POST, true));
error_log("GET data: " . print_r($_GET, true));

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    error_log("No user session found. Session contents: " . print_r($_SESSION, true));
    echo json_encode([
        'error' => 'Not logged in',
        'details' => 'No user session found. Please login again.',
        'session_data' => $_SESSION
    ]);
    exit;
}

try {
    // Get user information
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    
    // Debug user data
    error_log("User data: " . print_r($user, true));
    
    if (!$user) {
        error_log("User not found with ID: " . $_SESSION['user_id']);
        echo json_encode([
            'error' => 'User not found',
            'details' => 'No user found with ID: ' . $_SESSION['user_id'],
            'session_data' => $_SESSION
        ]);
        exit;
    }
    
    error_log("User found: " . print_r($user, true));
    
    // Initialize statistics
    $stats = [
        'total_trips' => 0,
        'total_donations' => 0,
        'total_hours' => 0
    ];
    
    // Get total trips
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM trip_registrations WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $stats['total_trips'] = $stmt->fetchColumn();
    error_log("Total trips: " . $stats['total_trips']);
    
    // Get total volunteer hours
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(duration), 0) FROM trip_registrations WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $stats['total_hours'] = $stmt->fetchColumn();
    error_log("Total hours: " . $stats['total_hours']);
    
    // Get total donations
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) FROM donations WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $stats['total_donations'] = $stmt->fetchColumn();
    error_log("Total donations: " . $stats['total_donations']);
    
    // Get recent activities
    $recent_activities = [];
    
    // Get recent trips
    $stmt = $pdo->prepare("
        SELECT 'trip' as type, t.name as title, tr.created_at 
        FROM trip_registrations tr
        JOIN trips t ON tr.trip_id = t.id
        WHERE tr.user_id = ?
        ORDER BY tr.created_at DESC 
        LIMIT 3
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $recent_activities = array_merge($recent_activities, $stmt->fetchAll());
    error_log("Recent trips loaded: " . count($recent_activities));
    
    // Get recent donations
    $stmt = $pdo->prepare("
        SELECT 'donation' as type, 
               CONCAT('Quyên góp: ', FORMAT(amount, 0), ' VNĐ') as title, 
               created_at 
        FROM donations 
        WHERE user_id = ?
        ORDER BY created_at DESC 
        LIMIT 2
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $recent_activities = array_merge($recent_activities, $stmt->fetchAll());
    error_log("Recent donations loaded: " . count($recent_activities));
    
    // Sort activities by date
    usort($recent_activities, function($a, $b) {
        return strtotime($b['created_at']) - strtotime($a['created_at']);
    });
    
    // Limit to 5 most recent activities
    $recent_activities = array_slice($recent_activities, 0, 5);
    
    // Format the response data
    $response = [
        'success' => true,
        'user' => [
            'id' => $user['id'],
            'username' => $user['username'],
            'email' => $user['email'],
            'fullname' => $user['fullname'],
            'phone' => $user['phone'] ?? '',
            'address' => $user['address'] ?? '',
            'role' => $user['role'] ?? 'user'
        ],
        'stats' => $stats,
        'recent_activities' => $recent_activities,
        'session_data' => $_SESSION
    ];
    
    // Debug response
    error_log("User data from database: " . print_r($user, true));
    error_log("Response data: " . print_r($response, true));
    
    echo json_encode($response);
    
} catch(PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    echo json_encode([
        'error' => 'Có lỗi xảy ra khi tải thông tin người dùng',
        'details' => $e->getMessage(),
        'session_data' => $_SESSION
    ]);
} catch(Exception $e) {
    error_log("General error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    echo json_encode([
        'error' => 'Có lỗi xảy ra khi tải thông tin người dùng',
        'details' => $e->getMessage(),
        'session_data' => $_SESSION
    ]);
}
?> 