<?php
session_start();
require_once 'config.php';

$vnpay_config = [
    'vnp_TmnCode' => '3WLODVHG',
    'vnp_HashSecret' => '7TDMA6CAC28HZEX16VSGXDWJX0N1QTVR',
    'vnp_Url' => 'https://sandbox.vnpayment.vn/paymentv2/vpcpay.html',
    'vnp_ReturnUrl' => 'http://localhost:85/TuThien/verify_vnpay.php'
];

$vnp_SecureHash = $_GET['vnp_SecureHash'] ?? '';
$inputData = [];
foreach ($_GET as $key => $value) {
    if (substr($key, 0, 4) == "vnp_") {
        $inputData[$key] = $value;
    }
}

unset($inputData['vnp_SecureHash']);
unset($inputData['vnp_SecureHashType']);
ksort($inputData);

$hashData = '';
foreach ($inputData as $key => $value) {
    $hashData .= urlencode($key) . "=" . urlencode($value) . "&";
}
$hashData = rtrim($hashData, "&");

$vnpSecureHash = hash_hmac('sha512', $hashData, $vnpay_config['vnp_HashSecret']);

$orderId = $_GET['vnp_TxnRef'] ?? '';
if (empty($orderId)) {
    header('Location: donation.php?error=1&message=' . urlencode('Thiếu mã giao dịch.'));
    exit;
}

if ($vnpSecureHash === $vnp_SecureHash) {
    $vnp_ResponseCode = $_GET['vnp_ResponseCode'] ?? '';
    $amount = ($_GET['vnp_Amount'] ?? 0) / 100;

    // Kiểm tra trạng thái giao dịch
    $stmt = $pdo->prepare("SELECT user_id, status FROM transactions WHERE order_id = ?");
    $stmt->execute([$orderId]);
    $transaction = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$transaction) {
        header('Location: donation.php?error=1&message=' . urlencode('Không tìm thấy giao dịch.'));
        exit;
    }

    if ($transaction['status'] !== 'pending') {
        header('Location: donation.php?error=1&message=' . urlencode('Giao dịch đã được xử lý trước đó.'));
        exit;
    }

    if ($vnp_ResponseCode === '00' && $amount > 0) {
        if (!isset($_SESSION['user_id'])) {
            $stmt = $pdo->prepare("UPDATE transactions SET status = 'failed' WHERE order_id = ?");
            $stmt->execute([$orderId]);
            header('Location: donation.php?error=1&message=' . urlencode('Phiên đăng nhập không hợp lệ.'));
            exit;
        }

        $userId = $_SESSION['user_id'];
        $pdo->beginTransaction();

        try {
            // Kiểm tra xem user_funds đã tồn tại chưa
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM user_funds WHERE user_id = ?");
            $stmt->execute([$userId]);
            $exists = $stmt->fetchColumn();

            if (!$exists) {
                // Nếu chưa tồn tại, tạo mới với balance = 0
                $stmt = $pdo->prepare("INSERT INTO user_funds (user_id, balance) VALUES (?, 0)");
                $stmt->execute([$userId]);
            }

            // Cập nhật balance trong user_funds
            $stmt = $pdo->prepare("UPDATE user_funds SET balance = balance + ? WHERE user_id = ?");
            $stmt->execute([$amount, $userId]);

            // Cập nhật trạng thái giao dịch
            $stmt = $pdo->prepare("UPDATE transactions SET status = 'completed' WHERE order_id = ?");
            $stmt->execute([$orderId]);

            $pdo->commit();
            header('Location: donation.php?success=1&amount=' . $amount . '&message=' . urlencode('Thanh toán thành công.'));
        } catch (PDOException $e) {
            $pdo->rollBack();
            $stmt = $pdo->prepare("UPDATE transactions SET status = 'failed' WHERE order_id = ?");
            $stmt->execute([$orderId]);
            header('Location: donation.php?error=1&message=' . urlencode('Lỗi khi cập nhật quỹ: ' . $e->getMessage()));
        }
    } else {
        $stmt = $pdo->prepare("UPDATE transactions SET status = 'failed' WHERE order_id = ?");
        $stmt->execute([$orderId]);
        $errorMessage = $vnp_ResponseCode ? 'Thanh toán thất bại. Mã lỗi: ' . $vnp_ResponseCode : 'Thanh toán thất bại.';
        header('Location: donation.php?error=1&message=' . urlencode($errorMessage));
    }
} else {
    $stmt = $pdo->prepare("UPDATE transactions SET status = 'failed' WHERE order_id = ?");
    $stmt->execute([$orderId]);
    header('Location: donation.php?error=1&message=' . urlencode('Chữ ký bảo mật không hợp lệ.'));
}
?>