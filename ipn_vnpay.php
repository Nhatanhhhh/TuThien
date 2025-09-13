<?php
require_once 'config.php';

header('Content-Type: application/json');

$vnpay_config = [
    'vnp_TmnCode' => '3WLODVHG',
    'vnp_HashSecret' => '7TDMA6CAC28HZEX16VSGXDWJX0N1QTVR',
    'vnp_Url' => 'https://sandbox.vnpayment.vn/paymentv2/vpcpay.html',
    'vnp_ReturnUrl' => 'http://localhost:85/TuThien/verify_vnpay.php'
];

$vnp_SecureHash = $_GET['vnp_SecureHash'] ?? '';
$inputData = array();
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
    $hashData .= $key . "=" . $value . "&";
}
$hashData = rtrim($hashData, "&");

$secureHash = hash_hmac('sha512', $hashData, $vnpay_config['vnp_HashSecret']);
$orderId = $inputData['vnp_TxnRef'] ?? '';
$amount = ($inputData['vnp_Amount'] ?? 0) / 100;

if (empty($orderId)) {
    echo json_encode(['RspCode' => '01', 'Message' => 'Missing order ID']);
    exit;
}

if ($secureHash !== $vnp_SecureHash) {
    file_put_contents('vnpay_ipn.log', 'Hash mismatch: Expected ' . $secureHash . ', Got ' . $vnp_SecureHash . PHP_EOL, FILE_APPEND);
    echo json_encode(['RspCode' => '97', 'Message' => 'Invalid signature']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT user_id, amount, status FROM transactions WHERE order_id = ?");
    $stmt->execute([$orderId]);
    $transaction = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$transaction) {
        echo json_encode(['RspCode' => '01', 'Message' => 'Order not found']);
        exit;
    }

    if ($transaction['status'] !== 'pending') {
        echo json_encode(['RspCode' => '02', 'Message' => 'Order already confirmed']);
        exit;
    }

    if ($transaction['amount'] != $amount) {
        echo json_encode(['RspCode' => '04', 'Message' => 'Invalid amount']);
        exit;
    }

    if ($inputData['vnp_ResponseCode'] == '00' && $inputData['vnp_TransactionStatus'] == '00') {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("UPDATE user_funds SET balance = balance + ? WHERE user_id = ?");
        $stmt->execute([$amount, $transaction['user_id']]);

        $stmt = $pdo->prepare("UPDATE transactions SET status = 'completed' WHERE order_id = ?");
        $stmt->execute([$orderId]);

        $pdo->commit();
        echo json_encode(['RspCode' => '00', 'Message' => 'Confirm Success']);
    } else {
        $stmt = $pdo->prepare("UPDATE transactions SET status = 'failed' WHERE order_id = ?");
        $stmt->execute([$orderId]);
        echo json_encode(['RspCode' => '00', 'Message' => 'Confirm Success']);
    }
} catch (Exception $e) {
    echo json_encode(['RspCode' => '99', 'Message' => 'Unknown error: ' . $e->getMessage()]);
}
?>