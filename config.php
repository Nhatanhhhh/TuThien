<?php
// Enable output buffering
ob_start();

// Error reporting configuration
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', 'php_errors.log');

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Custom error handler
function handleError($errno, $errstr, $errfile, $errline)
{
    error_log("Error [$errno] $errstr on line $errline in file $errfile");
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Có lỗi xảy ra. Vui lòng thử lại sau']);
        exit;
    }
    return false;
}

// Register error handler
set_error_handler('handleError');

// Database connection
try {
    $host = 'localhost';
    $dbname = 'tuthien';
    $user = 'root';
    $pass = '123';

    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    error_log("Database connection successful at " . date('Y-m-d H:i:s'));
} catch (PDOException $e) {
    error_log("Database connection error at " . date('Y-m-d H:i:s') . ": " . $e->getMessage());
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Có lỗi xảy ra. Vui lòng thử lại sau']);
        exit;
    }
}

// PHPMailer configuration (already present)
require_once 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

function sendOTPEmail($email, $otp)
{
    try {
        error_log("Attempting to send OTP email to: " . $email . " at " . date('Y-m-d H:i:s'));

        $mail = new PHPMailer(true);

        // SMTP configuration
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'renboy122333444@gmail.com';
        $mail->Password = 'sujk zghj eigy mquh';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        $mail->CharSet = 'UTF-8';

        // Sender and recipient
        $mail->setFrom('renboy122333444@gmail.com', 'HopeLink System');
        $mail->addAddress($email);

        // Email content
        $mail->isHTML(true);
        $mail->Subject = 'Mã xác thực đặt lại mật khẩu - HopeLink';
        $mail->Body = "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                <h2 style='color: #5a67d8;'>Đặt lại mật khẩu HopeLink</h2>
                <p>Xin chào,</p>
                <p>Chúng tôi nhận được yêu cầu đặt lại mật khẩu cho tài khoản của bạn.</p>
                <p>Mã xác thực của bạn là: <strong style='font-size: 20px; color: #5a67d8;'>{$otp}</strong></p>
                <p>Mã này sẽ hết hạn sau 5 phút.</p>
                <p>Nếu bạn không yêu cầu đặt lại mật khẩu, vui lòng bỏ qua email này.</p>
                <hr style='border: 1px solid #e2e8f0; margin: 20px 0;'>
                <p style='color: #718096; font-size: 12px;'>Email này được gửi tự động, vui lòng không trả lời.</p>
            </div>
        ";

        $mail->send();
        error_log("OTP email sent successfully to: " . $email . " at " . date('Y-m-d H:i:s'));
        return true;
    } catch (Exception $e) {
        error_log("PHPMailer error at " . date('Y-m-d H:i:s') . ": " . $e->getMessage());
        return false;
    }
}

// Handle forgot password request
if (isset($_POST['forgot_password'])) {
    error_log("Starting forgot_password handler at " . date('Y-m-d H:i:s'));

    // Clear output buffers
    while (ob_get_level()) {
        ob_end_clean();
    }

    // Set JSON header
    if (!headers_sent()) {
        header('Content-Type: application/json');
    }

    try {
        error_log("Checking PDO connection");
        if (!isset($pdo)) {
            throw new Exception('Database connection not available');
        }

        $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
        error_log("Received email: " . $email);

        if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            error_log("Invalid email format: " . $email);
            echo json_encode(['success' => false, 'message' => 'Email không hợp lệ']);
            exit;
        }

        // Kiểm tra email có tồn tại trong hệ thống không
        error_log("Checking if email exists in database: " . $email);
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user) {
            error_log("Email not found in database: " . $email);
            echo json_encode(['success' => false, 'message' => 'Email không tồn tại trong hệ thống']);
            exit;
        }

        error_log("Generating OTP for user ID: " . $user['id']);
        $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        error_log("Storing OTP in session");
        $_SESSION['reset_otp'] = [
            'otp' => $otp,
            'email' => $email,
            'user_id' => $user['id'],
            'expires' => time() + 300 // 5 minutes
        ];

        error_log("Sending OTP email");
        if (sendOTPEmail($email, $otp)) {
            echo json_encode(['success' => true, 'message' => 'Mã xác thực đã được gửi đến email của bạn']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Không thể gửi email. Vui lòng thử lại sau']);
        }
    } catch (PDOException $e) {
        error_log("Database error in forgot_password at " . date('Y-m-d H:i:s') . ": " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Có lỗi xảy ra. Vui lòng thử lại sau']);
    } catch (Exception $e) {
        error_log("General error in forgot_password at " . date('Y-m-d H:i:s') . ": " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Có lỗi xảy ra. Vui lòng thử lại sau']);
    }
    exit;
}

// Cấu hình VNPay (Đây là chỗ để config VNPay)
$vnpay_config = [
    'vnp_TmnCode' => '3WLODVHG', // Thay bằng mã TmnCode của bạn từ VNPay
    'vnp_HashSecret' => '7TDMA6CAC28HZEX16VSGXDWJX0N1QTVR', // Thay bằng Hash Secret của bạn từ VNPay
    'vnp_Url' => 'https://sandbox.vnpayment.vn/paymentv2/vpcpay.html', // URL sandbox của VNPay (dùng để test)
    'vnp_ReturnUrl' => 'http://localhost:85/TuThien/verify_vnpay.php' // URL để VNPay gửi kết quả thanh toán về (thay bằng domain của bạn)
];

// Hàm tạo URL thanh toán VNPay
function generateVnpayPaymentUrl($amount, $orderId, $orderInfo, $vnpay_config)
{
    date_default_timezone_set('Asia/Ho_Chi_Minh');
    $vnp_TxnRef = $orderId;
    $vnp_OrderInfo = $orderInfo;
    $vnp_OrderType = 'other';
    $vnp_Amount = $amount * 100;
    $vnp_Locale = 'vn';
    $vnp_IpAddr = $_SERVER['REMOTE_ADDR'];
    $vnp_CreateDate = date('YmdHis');
    $vnp_ExpireDate = date('YmdHis', strtotime('+15 minutes'));

    $inputData = [
        "vnp_Version" => "2.1.0",
        "vnp_TmnCode" => $vnpay_config['vnp_TmnCode'],
        "vnp_Amount" => $vnp_Amount,
        "vnp_Command" => "pay",
        "vnp_CreateDate" => $vnp_CreateDate,
        "vnp_CurrCode" => "VND",
        "vnp_IpAddr" => $vnp_IpAddr,
        "vnp_Locale" => $vnp_Locale,
        "vnp_OrderInfo" => $vnp_OrderInfo,
        "vnp_OrderType" => $vnp_OrderType,
        "vnp_ReturnUrl" => $vnpay_config['vnp_ReturnUrl'],
        "vnp_TxnRef" => $vnp_TxnRef,
        "vnp_ExpireDate" => $vnp_ExpireDate
    ];

    ksort($inputData);
    $query = "";
    $i = 0;
    $hashdata = "";
    foreach ($inputData as $key => $value) {
        if ($i == 1) {
            $hashdata .= '&' . urlencode($key) . "=" . urlencode($value);
        } else {
            $hashdata .= urlencode($key) . "=" . urlencode($value);
            $i = 1;
        }
        $query .= urlencode($key) . "=" . urlencode($value) . '&';
    }

    $vnp_Url = $vnpay_config['vnp_Url'] . "?" . $query;
    if (isset($vnpay_config['vnp_HashSecret'])) {
        $vnpSecureHash = hash_hmac('sha512', $hashdata, $vnpay_config['vnp_HashSecret']);
        $vnp_Url .= 'vnp_SecureHash=' . $vnpSecureHash;
    }

    return $vnp_Url;
}

// Xử lý mã hóa mật khẩu hiện có
if (isset($_GET['hash_passwords']) && $_GET['hash_passwords'] === '1') {
    // Kiểm tra bảo mật (có thể thay bằng kiểm tra session admin)
    $secret_key = $_GET['secret_key'] ?? '';
    if ($secret_key !== 'your_secret_key_123') { // Thay bằng khóa bí mật của bạn
        echo json_encode(['success' => false, 'message' => 'Truy cập bị từ chối: Khóa bí mật không đúng.']);
        exit;
    }

    try {
        // Lấy tất cả người dùng
        $stmt = $pdo->query('SELECT id, username, password FROM users');
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $updated_users = [];

        foreach ($users as $user) {
            // Kiểm tra nếu mật khẩu chưa được mã hóa
            if (strpos($user['password'], '$2y$') !== 0) {
                // Mã hóa mật khẩu
                $hashedPassword = password_hash($user['password'], PASSWORD_DEFAULT);

                // Cập nhật mật khẩu trong cơ sở dữ liệu
                $updateStmt = $pdo->prepare('UPDATE users SET password = ? WHERE id = ?');
                $updateStmt->execute([$hashedPassword, $user['id']]);
                $updated_users[] = $user['username'];
            }
        }

        if (empty($updated_users)) {
            echo json_encode(['success' => true, 'message' => 'Không có mật khẩu nào cần mã hóa.']);
        } else {
            echo json_encode([
                'success' => true,
                'message' => 'Đã mã hóa mật khẩu cho các người dùng: ' . implode(', ', $updated_users)
            ]);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Lỗi khi mã hóa mật khẩu: ' . $e->getMessage()]);
    }
    exit;
}

// Xử lý đăng xuất
if (isset($_GET['logout']) && $_GET['logout'] == 1) {
    session_start();
    if (!isset($_GET['csrf_token']) || $_GET['csrf_token'] !== $_SESSION['csrf_token']) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Yêu cầu không hợp lệ: CSRF token không đúng.']);
        exit;
    }
    $userId = $_SESSION['user_id'] ?? 'unknown';
    error_log("User ID $userId logged out at " . date('Y-m-d H:i:s'));
    session_unset();
    session_destroy();
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
    header('Content-Type: application/json');
    echo json_encode(['success' => true]);
    exit;
}

// Xử lý đăng nhập
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    if (empty($username) || empty($password)) {
        echo json_encode(['success' => false, 'message' => 'Vui lòng điền đầy đủ thông tin.']);
        exit;
    }

    try {
        $stmt = $pdo->prepare("SELECT id, username, password, role, email FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['email'] = $user['email'] ?? '';

            echo json_encode([
                'success' => true,
                'message' => 'Đăng nhập thành công',
                'redirect' => 'index.php'
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Tên đăng nhập hoặc mật khẩu không đúng.']);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()]);
    }
    exit;
}

// Xử lý đăng ký
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $gender = $_POST['gender'];
    $birthdate = $_POST['birthdate'];

    if (empty($username) || empty($password) || empty($email) || empty($gender) || empty($birthdate)) {
        echo json_encode(['success' => false, 'message' => 'Vui lòng nhập đầy đủ thông tin bắt buộc']);
        exit;
    }

    try {
        // Bắt đầu giao dịch
        $pdo->beginTransaction();

        // Kiểm tra username, email và phone đã tồn tại chưa
        $sql = "SELECT id FROM users WHERE username = ? OR email = ?";
        $params = [$username, $email];
        if (!empty($phone)) {
            $sql .= " OR phone = ?";
            $params[] = $phone;
        }
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        if ($stmt->rowCount() > 0) {
            $message = 'Tên đăng nhập hoặc email';
            if (!empty($phone)) {
                $message .= ' hoặc số điện thoại';
            }
            $message .= ' đã tồn tại';
            echo json_encode(['success' => false, 'message' => $message]);
            $pdo->rollBack();
            exit;
        }

        // Mã hóa mật khẩu
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        // Thêm người dùng mới
        $stmt = $pdo->prepare("INSERT INTO users (username, password, email, phone, gender, birthdate, role) VALUES (?, ?, ?, ?, ?, ?, 'user')");
        $stmt->execute([$username, $hashedPassword, $email, $phone ?: null, $gender, $birthdate]);

        // Lấy ID của người dùng vừa tạo
        $userId = $pdo->lastInsertId();

        // Tạo quỹ cho người dùng mới
        $stmt = $pdo->prepare("INSERT INTO user_funds (user_id, balance) VALUES (?, 0.00)");
        $stmt->execute([$userId]);

        // Hoàn tất giao dịch
        $pdo->commit();

        echo json_encode(['success' => true, 'message' => 'Đăng ký thành công']);
    } catch (PDOException $e) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()]);
    }
    exit;
}
// Xử lý nạp tiền (quyên góp) vào quỹ
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['deposit'])) {
    if (!isset($_SESSION['user_id'])) {
        error_log("Deposit attempt without login");
        echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập.']);
        exit;
    }

    $amount = floatval($_POST['amount']);
    if ($amount < 10000) {
        error_log("Invalid deposit amount: " . $amount);
        echo json_encode(['success' => false, 'message' => 'Số tiền nạp phải ≥ 10.000 VND.']);
        exit;
    }

    $userId = $_SESSION['user_id'];
    $orderId = "DEPOSIT_" . time() . "_" . rand(1000, 9999);

    try {
        error_log("Creating deposit transaction - User ID: $userId, Amount: $amount, Order ID: $orderId");

        // Lưu giao dịch vào database
        $stmt = $pdo->prepare("INSERT INTO transactions (user_id, trip_id, amount, type, payment_method, order_id, status) 
                              VALUES (?, NULL, ?, 'deposit', 'vnpay', ?, 'pending')");
        $stmt->execute([$userId, $amount, $orderId]);

        // Tạo URL thanh toán VNPay
        $vnpayUrl = generateVnpayPaymentUrl($amount, $orderId, "Nạp tiền vào quỹ", $vnpay_config);
        error_log("Generated VNPay URL for order: $orderId");

        echo json_encode([
            'success' => true,
            'paymentUrl' => $vnpayUrl,
            'orderId' => $orderId,
            'formattedAmount' => number_format($amount, 0, ',', '.')
        ]);
    } catch (PDOException $e) {
        error_log("Database error during deposit: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => 'Lỗi khi tạo giao dịch: ' . $e->getMessage()
        ]);
    }
    exit;
}

// Xử lý đặt vé cho chuyến đi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['book_ticket'])) {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập.']);
        exit;
    }

    $tripId = intval($_POST['trip_id']);
    $amount = floatval($_POST['amount']);
    if ($amount < 10000) {
        echo json_encode(['success' => false, 'message' => 'Giá vé phải ≥ 10.000 VND.']);
        exit;
    }

    $userId = $_SESSION['user_id'];
    try {
        $pdo->beginTransaction();

        // Kiểm tra số dư quỹ
        $stmt = $pdo->prepare("SELECT balance FROM user_funds WHERE user_id = ? FOR UPDATE");
        $stmt->execute([$userId]);
        $fund = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$fund || $fund['balance'] < $amount) {
            $pdo->rollBack();
            echo json_encode(['success' => false, 'message' => 'Quỹ không đủ. Vui lòng nạp thêm.', 'required' => $amount]);
            exit;
        }

        // Trừ tiền từ quỹ
        $newBalance = $fund['balance'] - $amount;
        $pdo->prepare("UPDATE user_funds SET balance = ? WHERE user_id = ?")->execute([$newBalance, $userId]);

        // Ghi nhận vé
        $stmt = $pdo->prepare("INSERT INTO tickets (user_id, trip_id, amount, status) VALUES (?, ?, ?, 'confirmed')");
        $stmt->execute([$userId, $tripId, $amount]);
        $ticketId = $pdo->lastInsertId();

        // Ghi nhận người tham gia (nếu cần thông tin chi tiết)
        $stmt = $pdo->prepare("SELECT username, email, phone FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        $stmt = $pdo->prepare("INSERT INTO trip_participants (ticket_id, user_id, trip_id, name, email, phone) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$ticketId, $userId, $tripId, $user['username'], $user['email'], $user['phone'] ?: '']);

        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'Đặt vé thành công!', 'balance' => $newBalance]);
    } catch (PDOException $e) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Lỗi khi đặt vé: ' . $e->getMessage()]);
    }
    exit;
}

// Xử lý kiểm tra username cho quên mật khẩu
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['check_username'])) {
    $username = trim($_POST['username']);

    if (empty($username)) {
        echo json_encode(['success' => false, 'message' => 'Vui lòng nhập tên đăng nhập.']);
        exit;
    }

    try {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->rowCount() > 0) {
            echo json_encode(['success' => true, 'message' => 'Tài khoản tồn tại. Vui lòng nhập mật khẩu mới.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Tài khoản không tồn tại.']);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()]);
    }
    exit;
}

// Xác thực OTP
if (isset($_POST['verify_otp'])) {
    // Xóa mọi output buffer
    while (ob_get_level()) {
        ob_end_clean();
    }

    // Đảm bảo không có output nào trước header
    if (!headers_sent()) {
        header('Content-Type: application/json');
    }

    try {
        if (!isset($pdo)) {
            throw new Exception('Database connection not available');
        }

        $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
        $otp = filter_input(INPUT_POST, 'otp', FILTER_SANITIZE_STRING);

        if (!$email || !$otp) {
            echo json_encode(['success' => false, 'message' => 'Dữ liệu không hợp lệ']);
            exit;
        }

        // Kiểm tra OTP trong session
        if (
            !isset($_SESSION['reset_otp']) ||
            $_SESSION['reset_otp']['email'] !== $email ||
            $_SESSION['reset_otp']['otp'] !== $otp ||
            time() > $_SESSION['reset_otp']['expires']
        ) {
            echo json_encode(['success' => false, 'message' => 'Mã xác thực không hợp lệ hoặc đã hết hạn']);
            exit;
        }

        echo json_encode(['success' => true, 'message' => 'Mã xác thực hợp lệ']);
    } catch (Exception $e) {
        error_log("Error in verify_otp: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Có lỗi xảy ra. Vui lòng thử lại sau']);
    }
    exit;
}

// Đặt lại mật khẩu
if (isset($_POST['reset_password'])) {
    // Xóa mọi output buffer
    while (ob_get_level()) {
        ob_end_clean();
    }

    // Đảm bảo không có output nào trước header
    if (!headers_sent()) {
        header('Content-Type: application/json');
    }

    try {
        if (!isset($pdo)) {
            throw new Exception('Database connection not available');
        }

        $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
        $otp = filter_input(INPUT_POST, 'otp', FILTER_SANITIZE_STRING);
        $new_password = $_POST['new_password'];

        if (!$email || !$otp || !$new_password) {
            echo json_encode(['success' => false, 'message' => 'Dữ liệu không hợp lệ']);
            exit;
        }

        // Kiểm tra OTP trong session
        if (
            !isset($_SESSION['reset_otp']) ||
            $_SESSION['reset_otp']['email'] !== $email ||
            $_SESSION['reset_otp']['otp'] !== $otp ||
            time() > $_SESSION['reset_otp']['expires']
        ) {
            echo json_encode(['success' => false, 'message' => 'Mã xác thực không hợp lệ hoặc đã hết hạn']);
            exit;
        }

        // Cập nhật mật khẩu
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->execute([$hashed_password, $_SESSION['reset_otp']['user_id']]);

        // Xóa OTP khỏi session
        unset($_SESSION['reset_otp']);

        echo json_encode(['success' => true, 'message' => 'Mật khẩu đã được cập nhật thành công']);
    } catch (PDOException $e) {
        error_log("Database error in reset_password: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Có lỗi xảy ra. Vui lòng thử lại sau']);
    } catch (Exception $e) {
        error_log("General error in reset_password: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Có lỗi xảy ra. Vui lòng thử lại sau']);
    }
    exit;
}

// Xử lý cập nhật thông tin tài khoản
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập để thực hiện chức năng này.']);
        exit;
    }

    $userId = $_SESSION['user_id'];
    $email = trim($_POST['email']);
    $fullname = trim($_POST['fullname']);
    $phone = trim($_POST['phone']);
    $gender = $_POST['gender'];
    $birthdate = $_POST['birthdate'];

    try {
        // Kiểm tra email đã tồn tại chưa (trừ user hiện tại)
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $stmt->execute([$email, $userId]);
        if ($stmt->rowCount() > 0) {
            echo json_encode(['success' => false, 'message' => 'Email đã được sử dụng bởi tài khoản khác.']);
            exit;
        }

        // Cập nhật thông tin
        $stmt = $pdo->prepare("UPDATE users SET email = ?, fullname = ?, phone = ?, gender = ?, birthdate = ? WHERE id = ?");
        $stmt->execute([$email, $fullname, $phone, $gender, $birthdate, $userId]);

        echo json_encode(['success' => true, 'message' => 'Cập nhật thông tin thành công!']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Lỗi khi cập nhật: ' . $e->getMessage()]);
    }
    exit;
}

// Xử lý đổi mật khẩu
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập để thực hiện chức năng này.']);
        exit;
    }

    $userId = $_SESSION['user_id'];
    $currentPassword = trim($_POST['current_password']);
    $newPassword = trim($_POST['new_password']);
    $confirmPassword = trim($_POST['confirm_password']);

    if ($newPassword !== $confirmPassword) {
        echo json_encode(['success' => false, 'message' => 'Mật khẩu mới không khớp.']);
        exit;
    }

    try {
        // Kiểm tra mật khẩu hiện tại
        $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!password_verify($currentPassword, $user['password'])) {
            echo json_encode(['success' => false, 'message' => 'Mật khẩu hiện tại không đúng.']);
            exit;
        }

        // Mã hóa mật khẩu mới
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

        // Cập nhật mật khẩu mới
        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->execute([$hashedPassword, $userId]);

        echo json_encode(['success' => true, 'message' => 'Đổi mật khẩu thành công!']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Lỗi khi đổi mật khẩu: ' . $e->getMessage()]);
    }
    exit;
}

// Trong phần xử lý get_user_info của config.php
if (isset($_GET['get_user_info'])) {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'message' => 'Chưa đăng nhập']);
        exit;
    }

    try {
        $stmt = $pdo->prepare("SELECT u.id, u.username, u.email, u.fullname, u.phone, u.gender, u.birthdate, u.role, f.balance 
                               FROM users u 
                               LEFT JOIN user_funds f ON u.id = f.user_id 
                               WHERE u.id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            echo json_encode([
                'success' => true,
                'user' => $user
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Không tìm thấy thông tin người dùng'
            ]);
        }
    } catch (PDOException $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Lỗi: ' . $e->getMessage()
        ]);
    }
    exit;
}

// Kiểm tra phiên
if (isset($_GET['check_session'])) {
    if (isset($_SESSION['username'])) {
        echo json_encode([
            'success' => true,
            'logged_in' => true,
            'username' => $_SESSION['username'],
            'role' => $_SESSION['role'] ?? 'user',
            'email' => $_SESSION['email'] ?? ''
        ]);
    } else {
        echo json_encode(['success' => true, 'logged_in' => false]);
    }
    exit;
}

// Default handler for requests without specific actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['logout']) && $_POST['logout'] == 1) {
    session_start();
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Yêu cầu không hợp lệ: CSRF token không đúng.']);
        exit;
    }
    session_unset();
    session_destroy();
    header('Content-Type: application/json');
    echo json_encode(['success' => true]);
    exit;
}

// Xử lý yêu cầu get_user_info
if (isset($_GET['get_user_info']) && $_SESSION['user_id']) {
    header('Content-Type: application/json');
    try {
        $stmt = $pdo->prepare("SELECT balance FROM user_funds WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'user' => $user]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Lỗi truy vấn: ' . $e->getMessage()]);
    }
    exit;
}

// Only output JSON for invalid requests if it's an AJAX/API call
if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
    echo json_encode(['success' => false, 'message' => 'Yêu cầu không hợp lệ.']);
    exit;
}
?>