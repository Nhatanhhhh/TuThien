<?php
// Ẩn lỗi trên UI, chỉ hiển thị trong môi trường phát triển
if (!defined('ENVIRONMENT')) {
    define('ENVIRONMENT', 'development'); // Đặt 'development' để debug
}
if (ENVIRONMENT === 'production') {
    error_reporting(0);
    ini_set('display_errors', 0);
}

session_start();
require_once 'config.php';

// Kiểm tra role và chuyển hướng nếu không phải là user
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'user') {
    header('Location: index.php');
    exit;
}

$success = ''; // Khởi tạo rỗng
$error = '';   // Khởi tạo rỗng

if (ENVIRONMENT === 'development') {
    echo "<pre>Debug: user_id = " . ($_SESSION['user_id'] ?? 'null') . "</pre>";
}

if (!isset($_SESSION['user_id'])) {
    $error = "Vui lòng đăng nhập để tiếp tục.";
}

if (!isset($_GET['trip_id']) || !is_numeric($_GET['trip_id'])) {
    if (ENVIRONMENT === 'development') {
        echo "<pre>Debug: trip_id not provided or invalid: " . htmlspecialchars($_GET['trip_id'] ?? 'null') . "</pre>";
    }
    $error = "Thông tin chuyến đi không hợp lệ.";
}

$trip_id = isset($_GET['trip_id']) && is_numeric($_GET['trip_id']) ? (int) $_GET['trip_id'] : 0;

// Debug giá trị trip_id
if (ENVIRONMENT === 'development') {
    echo "<pre>Debug: trip_id = $trip_id</pre>";
}

// Lấy thông tin chuyến đi
if ($trip_id > 0) {
    $stmt = $pdo->prepare("SELECT * FROM trips WHERE id = ? AND is_deleted = 0 AND is_active = 1");
    $stmt->execute([$trip_id]);
    $trip = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$trip) {
        if (ENVIRONMENT === 'development') {
            echo "<pre>Debug: trip_id=$trip_id not found or inactive/deleted</pre>";
            $stmt_debug = $pdo->prepare("SELECT id, name, date, is_deleted, is_active FROM trips WHERE id = ?");
            $stmt_debug->execute([$trip_id]);
            $trip_debug = $stmt_debug->fetch(PDO::FETCH_ASSOC);
            echo "<pre>Debug Trip Info: " . print_r($trip_debug, true) . "</pre>";
        }
        $error = "Chuyến đi không tồn tại hoặc đã bị hủy.";
    }
}

// Kiểm tra xem chuyến đi có bị hủy không
if ($trip_id > 0) {
    $stmt = $pdo->prepare("SELECT * FROM trips WHERE id = ? AND is_deleted = 0");
    $stmt->execute([$trip_id]);
    $trip = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$trip) {
        $error = "Chuyến đi không tồn tại hoặc đã bị xóa.";
    } elseif ($trip['is_cancelled']) {
        $error = "Chuyến đi đã bị hủy do: " . htmlspecialchars($trip['cancellation_reason']);
        
        // Kiểm tra xem người dùng đã được hoàn tiền chưa
        $stmt = $pdo->prepare("
            SELECT t.*, tr.refund_status 
            FROM tickets t 
            JOIN trips tr ON t.trip_id = tr.id 
            WHERE t.user_id = ? AND t.trip_id = ? AND t.status = 'confirmed'
        ");
        $stmt->execute([$_SESSION['user_id'], $trip_id]);
        $ticket = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($ticket && $ticket['refund_status'] === 'pending') {
            $success = "Chuyến đi đã bị hủy. Hệ thống sẽ tự động hoàn tiền vào tài khoản của bạn trong vòng 24 giờ.";
        } elseif ($ticket && $ticket['refund_status'] === 'completed') {
            $success = "Chuyến đi đã bị hủy. Tiền đã được hoàn vào tài khoản của bạn.";
        }
    }
}

// Lấy số dư của người dùng
$user_id = $_SESSION['user_id'] ?? null;
if ($user_id) {
    $stmt = $pdo->prepare("SELECT balance FROM user_funds WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $user_funds = $stmt->fetch(PDO::FETCH_ASSOC);
    $balance = $user_funds['balance'] ?? 0;
} else {
    $balance = 0;
    $error = "Vui lòng đăng nhập để tiếp tục.";
}

if (ENVIRONMENT === 'development') {
    echo "<pre>Debug: balance = $balance</pre>";
}

// Lấy thông tin người dùng từ bảng users
if ($user_id) {
    $stmt = $pdo->prepare("SELECT username, fullname, email, phone FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        $error = "Thông tin người dùng không hợp lệ.";
    } else {
        $username = $user['username'] ?? '';
        $user_fullname = $user['fullname'] ?? 'Người dùng';
        $user_email = $user['email'] ?? 'email@example.com';
        $user_phone = $user['phone'] ?? '';
    }
} else {
    $username = '';
    $user_fullname = 'Người dùng';
    $user_email = 'email@example.com';
    $user_phone = '';
}
$_SESSION['username'] = $username;
$_SESSION['fullname'] = $user_fullname;
$_SESSION['email'] = $user_email;
$_SESSION['phone'] = $user_phone;

// Xử lý đặt vé
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['book_ticket'])) {
    $trip_id = isset($_POST['trip_id']) ? (int) $_POST['trip_id'] : 0;
    $amount = isset($_POST['amount']) ? (float) $_POST['amount'] : 0;

    try {
        $pdo->beginTransaction();

        // Kiểm tra số dư
        $stmt = $pdo->prepare("SELECT balance FROM user_funds WHERE user_id = ? FOR UPDATE");
        $stmt->execute([$_SESSION['user_id']]);
        $fund = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$fund) {
            throw new Exception("Không tìm thấy thông tin quỹ của bạn. Vui lòng nạp tiền vào quỹ trước.");
        }

        if ($fund['balance'] < $amount) {
            throw new Exception("Số dư không đủ. Vui lòng nạp thêm " . number_format($amount - $fund['balance'], 0, ',', '.') . " VNĐ vào quỹ.");
        }

        // Trừ tiền từ balance
        $newBalance = $fund['balance'] - $amount;
        $stmt = $pdo->prepare("UPDATE user_funds SET balance = ? WHERE user_id = ?");
        $stmt->execute([$newBalance, $_SESSION['user_id']]);

        // Tạo vé mới
        $stmt = $pdo->prepare("INSERT INTO tickets (user_id, trip_id, amount, status) VALUES (?, ?, ?, 'confirmed')");
        $stmt->execute([$_SESSION['user_id'], $trip_id, $amount]);
        $ticketId = $pdo->lastInsertId();

        // Ghi nhận người tham gia
        $stmt = $pdo->prepare("SELECT fullname, email, phone FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        $stmt = $pdo->prepare("INSERT INTO trip_participants (ticket_id, user_id, trip_id, name, email, phone) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$ticketId, $_SESSION['user_id'], $trip_id, $user['fullname'], $user['email'], $user['phone'] ?: '']);

        $pdo->commit();
        echo json_encode([
            'success' => true, 
            'message' => 'Đặt vé thành công!', 
            'balance' => $newBalance,
            'formatted_balance' => number_format($newBalance, 0, ',', '.') . ' VNĐ'
        ]);
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => empty($error),
        'message' => $error ?: 'Đặt vé thành công!',
        'balance' => isset($newBalance) ? $newBalance : $balance
    ]);
    exit;
}
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chi Tiết Chuyến Đi - HopeLink</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="styles/header.css">
    <link rel="stylesheet" href="styles/trip_details.css">
</head>

<body>
    <header>
        <div class="header-content">
            <div class="logo">
                <img src="images/hopelink-logo.svg" alt="HopeLink Logo">
                <h1>HopeLink</h1>
            </div>
            <nav>
                <a href="index.php"><i class="fas fa-home"></i> Trang chủ</a>
                <div class="dropdown">
                    <a href="#events" class="dropdown-toggle"><i class="fas fa-hands-helping"></i> Hoạt động</a>
                    <div class="dropdown-menu">
                        <a href="charity_travel.php"><i class="fas fa-hands-helping"></i> Chuyến du lịch từ thiện</a>
                    </div>
                </div>
                <a href="#lienhe"><i class="fas fa-envelope"></i> Liên hệ</a>
                <a href="donation_history.php"><i class="fas fa-history"></i> Lịch sử chuyến đi</a>
            </nav>
            <div class="auth-buttons">
                <div class="user-info" id="user-info">
                    <button class="user-btn">
                        <span class="greeting"><i class="fas fa-user"></i> <span
                                id="username"><?php echo htmlspecialchars($username); ?></span></span>
                    </button>
                    <div class="user-dropdown">
                        <div class="user-header">
                            <div class="user-avatar" id="user-avatar">
                                <?php echo strtoupper(substr($username, 0, 1)); ?>
                            </div>
                            <div class="user-details">
                                <div class="user-name" id="dropdown-username">
                                    <?php echo htmlspecialchars($username); ?>
                                </div>
                                <div class="user-email" id="dropdown-email">
                                    <?php echo htmlspecialchars($user_email); ?>
                                </div>
                            </div>
                        </div>
                        <div class="user-stats">
                            <div class="stat-item">
                                <span class="stat-value">0</span>
                                <span class="stat-label">Chuyến đi</span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-value"
                                    id="user-balance"><?php echo number_format($balance, 0, ',', '.') . ' ₫'; ?></span>
                                <span class="stat-label">Quỹ từ thiện</span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-value">0</span>
                                <span class="stat-label">Giờ tình nguyện</span>
                            </div>
                        </div>
                        <div class="dropdown-divider"></div>
                        <a href="donation.php" class="dropdown-item"><i class="fas fa-wallet"></i> Nạp tiền vào quỹ</a>
                        <a href="profile.php" class="dropdown-item"><i class="fas fa-user-circle"></i> Thông tin tài
                            khoản</a>
                        <a href="donation_history.php" class="dropdown-item"><i class="fas fa-history"></i> Lịch sử
                            chuyến đi</a>
                        <div class="dropdown-divider"></div>
                        <a href="#" class="dropdown-item"
                            onclick="handleLogout(event, '<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>')">
                            <i class="fas fa-sign-out-alt"></i> Đăng xuất
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <section id="trip_details" style="padding: 20px 20px;">
        <h2 class="section-title"><i class="fas fa-hands-helping" style="font-size: 24px;"></i> Chi Tiết Chuyến Đi</h2>

        <div class="trip_details">
            <img src="<?php echo htmlspecialchars($trip['image'] ?? 'https://via.placeholder.com/800x300.png?text=' . urlencode($trip['name'] ?? 'Chuyến đi')); ?>"
                alt="<?php echo htmlspecialchars($trip['name'] ?? 'Chuyến đi'); ?>">
            <h3><?php echo htmlspecialchars($trip['name'] ?? 'Chuyến đi không tên'); ?></h3>
            <p><strong>Ngày:</strong> <?php echo htmlspecialchars(date('d/m/Y', strtotime($trip['date'] ?? 'now'))); ?>
            </p>
            <p><strong>Thời gian:</strong> <?php echo htmlspecialchars($trip['time'] ?? 'Chưa xác định'); ?></p>
            <p><strong>Địa điểm:</strong> <?php echo htmlspecialchars($trip['location'] ?? 'Chưa xác định'); ?></p>
            <p><strong>Mô tả:</strong> <?php echo htmlspecialchars($trip['description'] ?? 'Không có mô tả'); ?></p>
        </div>

        <div class="form-container">
            <h3 style="font-size: 24px; font-weight: 600; color: #2d3748; margin-bottom: 20px; text-align: center;">Đặt
                Vé Tham Gia</h3>
            <?php if (!empty($success)): ?>
                <div class="notification success-notification" id="successNotification">
                    <?php echo htmlspecialchars($success); ?>
                </div>
                <script>
                    document.getElementById('successNotification').style.display = 'block';
                    setTimeout(() => {
                        document.getElementById('successNotification').style.display = 'none';
                    }, 5000);
                </script>
            <?php endif; ?>
            <?php if (!empty($error)): ?>
                <div class="notification error-notification" id="errorNotification">
                    <?php echo htmlspecialchars($error); ?>
                </div>
                <script>
                    document.getElementById('errorNotification').style.display = 'block';
                    setTimeout(() => {
                        document.getElementById('errorNotification').style.display = 'none';
                    }, 5000);
                </script>
            <?php endif; ?>
            <form id="bookingForm" method="POST" style="display: flex; flex-direction: column; gap: 20px;">
                <input type="hidden" name="trip_id" value="<?php echo htmlspecialchars($trip_id); ?>">
                <input type="hidden" name="book_ticket" value="1">
                <div class="form-group">
                    <label for="user-name">Họ và Tên</label>
                    <input style="background-color: #f0f0f0;" type="text" id="user-name" value="<?php echo htmlspecialchars($user_fullname); ?>" readonly>
                </div>
                <div class="form-group">
                    <label for="user-email">Email</label>
                    <input style="background-color: #f0f0f0;" type="email" id="user-email" value="<?php echo htmlspecialchars($user_email); ?>" readonly>
                </div>
                <div class="form-group">
                    <label for="user-phone">Số Điện Thoại</label>
                    <input style="background-color: #f0f0f0;" type="tel" id="user-phone" value="<?php echo htmlspecialchars($user_phone); ?>" readonly>
                </div>
                <div class="form-group">
                    <label for="amount">Số Tiền Quyên Góp (VNĐ)</label>
                    <div class="donation-amount">
                        <div class="amount-option" data-amount="100000">100.000đ</div>
                        <div class="amount-option" data-amount="200000">200.000đ</div>
                        <div class="amount-option" data-amount="500000">500.000đ</div>
                        <div class="amount-option custom-amount">
                            <input type="number" id="custom-amount" name="amount" min="10000"
                                placeholder="Nhập số tiền khác (tối thiểu 10,000 VNĐ)" required>
                        </div>
                    </div>
                    <p style="font-size: 14px; color: #4b5563; margin-top: 5px;">Số dư hiện tại:
                        <?php echo number_format($balance, 0, ',', '.') . ' ₫'; ?>
                    </p>
                </div>
                <div class="form-group">
                    <label for="message">Lời Nhắn (Không bắt buộc)</label>
                    <textarea id="message" name="message" rows="4" placeholder="Nhập lời nhắn của bạn..."></textarea>
                </div>
                <div style="display: flex; gap: 15px; justify-content: flex-end;">
                    <a href="charity_travel.php"
                        style="background: #e5e7eb; color: #374151; padding: 12px 24px; border: none; border-radius: 8px; font-weight: 600; text-decoration: none; text-align: center;">Hủy</a>
                    <button type="submit" class="auth-btn">Đặt Vé</button>
                </div>
            </form>
        </div>
    </section>

    <footer style="margin-top: 0px;">
        <div class="footer-content" style="margin-top: 0px;">
            <div class="footer-section">
                <h3>Về HopeLink</h3>
                <p>HopeLink là nền tảng kết nối tình nguyện viên với các tổ chức từ thiện, lan tỏa yêu thương và xây
                    dựng cộng đồng bền vững.</p>
            </div>
            <div class="footer-section">
                <h3>Liên kết nhanh</h3>
                <a href="index.php">Trang chủ</a>
                <a href="index.php#events">Sự kiện</a>
                <a href="charity_travel.php">Chuyến đi từ thiện</a>
                <a href="#lienhe">Liên hệ</a>
            </div>
            <div class="footer-section">
                <h3>Liên hệ</h3>
                <p>Email: <a
                        href="/cdn-cgi/l/email-protection#b99a96978d989a8db99196899c95909792d7968b9e">[email protected]</a>
                </p>
                <p>Hotline: 1800-1234</p>
                <p>Địa chỉ: 123 Đường Từ Thiện, Hà Nội</p>
            </div>
        </div>
        <div class="footer-bottom">
            <p>© 2025 HopeLink. Mọi quyền được bảo lưu.</p>
        </div>
    </footer>

    <script>
        async function handleLogout(event, csrfToken) {
            event.preventDefault();
            try {
                const formData = new FormData();
                formData.append('csrf_token', csrfToken);

                const response = await fetch('logout.php', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();

                if (result.success) {
                    window.location.href = result.redirect;
                } else {
                    alert('Đăng xuất thất bại. Vui lòng thử lại.');
                }
            } catch (error) {
                console.error('Logout error:', error);
                alert('Đã xảy ra lỗi khi đăng xuất.');
            }
        }

        // Xử lý chọn số tiền
        document.querySelectorAll('.amount-option:not(.custom-amount)').forEach(option => {
            option.addEventListener('click', function () {
                document.querySelectorAll('.amount-option').forEach(opt => opt.classList.remove('selected'));
                this.classList.add('selected');
                const customAmountInput = document.getElementById('custom-amount');
                customAmountInput.value = this.dataset.amount;
                console.log('Selected amount:', this.dataset.amount); // Debug
            });
        });

        document.getElementById('custom-amount').addEventListener('input', function () {
            document.querySelectorAll('.amount-option').forEach(opt => opt.classList.remove('selected'));
        });

        const userBtn = document.querySelector('.user-btn');
        const userInfo = document.querySelector('.user-info');
        userBtn.addEventListener('click', () => {
            userInfo.classList.toggle('active');
        });

        document.addEventListener('click', (e) => {
            if (!userInfo.contains(e.target) && !userBtn.contains(e.target)) {
                userInfo.classList.remove('active');
            }
        });

        function showSuccessPopup(message, balance) {
            const popup = document.createElement('div');
            popup.className = 'notification success-notification';
            popup.innerHTML = `
                ${message}
                <p>Số dư mới: ${new Intl.NumberFormat('vi-VN').format(balance)} ₫</p>
            `;
            document.body.appendChild(popup);
            popup.style.display = 'block';

            // Cập nhật số dư hiển thị
            document.getElementById('user-balance').textContent = new Intl.NumberFormat('vi-VN').format(balance) + ' ₫';
            
            // Reset form
            document.getElementById('bookingForm').reset();
            document.querySelectorAll('.amount-option').forEach(opt => opt.classList.remove('selected'));

            // Hiển thị popup trong 3 giây
            setTimeout(() => {
                popup.style.display = 'none';
                popup.remove();
                
                // Reload trang sau khi đóng popup
                window.location.reload();
            }, 3000);
        }

        function showErrorPopup(message) {
            const popup = document.createElement('div');
            popup.className = 'notification error-notification';
            popup.innerHTML = message;
            document.body.appendChild(popup);
            popup.style.display = 'block';
            setTimeout(() => {
                popup.style.display = 'none';
                popup.remove();
            }, 5000);
        }

        // Xử lý submit form bằng AJAX
        document.getElementById('bookingForm').addEventListener('submit', async function (e) {
            e.preventDefault();

            const form = this;
            const formData = new FormData(form);
            const submitButton = form.querySelector('button[type="submit"]');
            
            // Disable nút submit và hiển thị loading
            submitButton.disabled = true;
            submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang xử lý...';

            try {
                const response = await fetch('', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                if (response.ok) {
                    const result = await response.json();
                    console.log('Response:', result);
                    if (result.success) {
                        showSuccessPopup(result.message, result.balance);
                    } else {
                        showErrorPopup(result.message || 'Đã xảy ra lỗi không xác định.');
                    }
                } else {
                    const errorText = await response.text();
                    console.log('Error response:', errorText);
                    showErrorPopup('Lỗi server: ' + errorText);
                }
            } catch (error) {
                console.error('Error:', error);
                showErrorPopup('Lỗi kết nối: ' + error.message);
            } finally {
                // Enable lại nút submit và khôi phục text
                submitButton.disabled = false;
                submitButton.innerHTML = 'Đặt Vé';
            }
        });
    </script>
</body>

</html>