<?php
session_start();

// Kiểm tra đăng nhập và quyền admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

// Tính ngày tối đa cho nhân viên trên 18 tuổi (ngày hiện tại là 17/05/2025)
$maxBirthdate = date('Y-m-d', strtotime('-18 years')); // 17/05/2007
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thêm Nhân Viên - HopeLink</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../styles/header.css">
    <link rel="stylesheet" href="../styles/dashboard.css">
    <style>
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 1000;
            display: none;
        }
        .spinner {
            border: 5px solid #f3f3f3;
            border-top: 5px solid #3498db;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            animation: spin 1s linear infinite;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        .form-container {
            max-width: 600px;
            margin: 40px auto;
            padding: 20px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: 500; }
        .form-group input, .form-group select {
            width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box;
        }
        .btn { padding: 8px 16px; border: none; border-radius: 4px; cursor: pointer; font-size: 14px; }
        .btn-primary { background-color: #4a90e2; color: white; }
        .btn-danger { background-color: #e74c3c; color: white; }
        .alert {
            display: block !important;
            z-index: 9999;
            font-size: 16px;
        }
        .alert-success { background-color: #e8f8e8; color: #2ecc71; }
        .alert-error { background-color: #fdecea; color: #e74c3c; }
        .error-message { color: #e74c3c; font-size: 12px; margin-top: 5px; display: none; }
    </style>
</head>

<body>
    <div class="sidebar">
        <h2>Admin Panel</h2>
        <div class="nav-item" onclick="window.location.href='dashboard.php'">
            <i class="fas fa-users"></i> Quản lý tài khoản
        </div>
        <div class="nav-item" onclick="window.location.href='manage_events.php'">
            <i class="fas fa-calendar-alt"></i> Quản lý sự kiện
        </div>
        <div class="nav-item" onclick="window.location.href='manage_participants.php'">
            <i class="fas fa-plane"></i> Quản lý chuyến bay
        </div>
        <div class="nav-item" onclick="window.location.href='stats.php'">
            <i class="fas fa-chart-bar"></i> Thống kê
        </div>
        <div class="nav-item active" onclick="window.location.href='add_staff_page.php'">
            <i class="fas fa-user-plus"></i> Thêm Nhân Viên
        </div>
        <div class="nav-item" onclick="logout()">
            <i class="fas fa-sign-out-alt"></i> Đăng xuất
        </div>
    </div>
    <div class="main-content">
        <div class="header">
            <h1>Thêm Nhân Viên Mới</h1>
        </div>
        <div class="loading-overlay" id="loadingOverlay">
            <div class="spinner"></div>
        </div>
        <div class="form-container">
            <?php
            // Hiển thị thông báo nếu có
            if (isset($_SESSION['alert_message_add_staff'])): ?>
                <div class="alert alert-<?php echo htmlspecialchars($_SESSION['alert_type_add_staff']); ?>">
                    <?php echo htmlspecialchars($_SESSION['alert_message_add_staff']); ?>
                </div>
                <?php
                // Xóa session sau khi hiển thị
                unset($_SESSION['alert_message_add_staff']);
                unset($_SESSION['alert_type_add_staff']);
            endif;
            ?>
            <form id="add-staff-form">
                <div class="form-group">
                    <label for="staff-username">Tên đăng nhập</label>
                    <input type="text" id="staff-username" name="username" placeholder="Ví dụ: staff123" required>
                </div>
                <div class="form-group">
                    <label for="staff-password">Mật khẩu</label>
                    <input type="password" id="staff-password" name="password" required>
                </div>
                <div class="form-group">
                    <label for="staff-email">Email</label>
                    <input type="email" id="staff-email" name="email" placeholder="Ví dụ: staff123@hopelink.org"
                        required>
                </div>
                <div class="form-group">
                    <label for="staff-phone">Số điện thoại</label>
                    <input type="tel" id="staff-phone" name="phone" placeholder="Ví dụ: 0123456789">
                </div>
                <div class="form-group">
                    <label for="staff-gender">Giới tính</label>
                    <select id="staff-gender" name="gender" required>
                        <option value="male">Nam</option>
                        <option value="female">Nữ</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="staff-birthdate">Ngày sinh (phải trên 18 tuổi)</label>
                    <input type="date" id="staff-birthdate" name="birthdate" max="<?php echo $maxBirthdate; ?>"
                        required>
                    <div id="birthdate-error" class="error-message">Nhân viên phải trên 18 tuổi.</div>
                </div>
                <div class="form-group">
                    <label for="staff-fullname">Họ và tên</label>
                    <input type="text" id="staff-fullname" name="fullname" placeholder="Ví dụ: Nguyễn Văn A" required>
                </div>
                <button type="submit" class="btn btn-primary" id="submitBtn">Thêm Nhân Viên</button>
                <button type="button" class="btn btn-danger" onclick="window.location.href='dashboard.php'">Hủy</button>
            </form>
        </div>
    </div>
    <script>
        document.getElementById('add-staff-form').addEventListener('submit', function(e) {
            e.preventDefault();
            console.log('Form submitted');
            
            document.getElementById('loadingOverlay').style.display = 'flex';
            document.getElementById('submitBtn').disabled = true;
            
            const formData = new FormData(this);
            console.log('Form data:', Object.fromEntries(formData));
            
            fetch('add_staff.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                console.log('Server response:', data);
                if (data.success) {
                    showAlert(data.message, 'success');
                    this.reset();
                } else {
                    showAlert(data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error details:', error);
                showAlert('Đã xảy ra lỗi khi kết nối với server', 'error');
            })
            .finally(() => {
                document.getElementById('loadingOverlay').style.display = 'none';
                document.getElementById('submitBtn').disabled = false;
            });
        });
        function showAlert(message, type) {
            console.log('Showing alert:', { message, type });
            // Xóa alert cũ nếu có
            const oldAlert = document.querySelector('.form-container .alert');
            if (oldAlert) oldAlert.remove();

            const alertDiv = document.createElement('div');
            alertDiv.className = `alert alert-${type}`;
            alertDiv.textContent = message;

            // Chèn alert ngay trước form
            const form = document.getElementById('add-staff-form');
            form.parentNode.insertBefore(alertDiv, form);

            setTimeout(() => { alertDiv.remove(); }, 5000);
        }
        document.getElementById('staff-birthdate').addEventListener('change', function() {
            const birthdate = new Date(this.value);
            const today = new Date();
            let age = today.getFullYear() - birthdate.getFullYear();
            const monthDiff = today.getMonth() - birthdate.getMonth();
            if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthdate.getDate())) {
                age--;
            }
            const errorElement = document.getElementById('birthdate-error');
            if (age < 18) {
                errorElement.style.display = 'block';
            } else {
                errorElement.style.display = 'none';
            }
        });
        function logout() {
            const formData = new FormData();
            formData.append('csrf_token', '<?php echo isset($_SESSION['csrf_token']) ? htmlspecialchars($_SESSION['csrf_token']) : ''; ?>');
            fetch('../logout.php', {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        window.location.href = data.redirect;
                    } else {
                        alert(data.message || 'Đăng xuất thất bại. Vui lòng thử lại.');
                    }
                })
                .catch(error => {
                    console.error('Error logging out:', error);
                    alert('Đã xảy ra lỗi khi đăng xuất.');
                });
        }
    </script>
</body>

</html>