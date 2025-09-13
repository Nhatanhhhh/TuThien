<?php
session_start();

// Kiểm tra đăng nhập và quyền admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Không có quyền truy cập']);
    exit;
}

?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý - HopeLink</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../styles/header.css">
    <link rel="stylesheet" href="../styles/dashboard.css">
    <style>
        .modal {
            display: none;
            position: fixed;
            z-index: 1;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
        }

        .modal-content {
            background-color: #fff;
            margin: 5% auto;
            padding: 20px;
            border-radius: 5px;
            width: 50%;
            max-width: 500px;
            max-height: 80vh;
            /* Giới hạn chiều cao modal */
            overflow-y: auto;
            /* Cho phép cuộn nội dung nếu dài */
            position: relative;
            display: flex;
            flex-direction: column;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        .btn {
            padding: 8px 16px;
            margin-right: 10px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        .btn-primary {
            background-color: #4a90e2;
            color: white;
        }

        .btn-danger {
            background-color: #e74c3c;
            color: white;
        }

        .btn-success {
            background-color: #2ecc71;
            color: white;
        }
    </style>
</head>

<body>
    <div class="sidebar">
        <h2>Admin Panel</h2>
        <div class="nav-item active" onclick="window.location.href='dashboard.php'">
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
        <!-- Chuyển từ modal sang trang mới -->
        <div class="nav-item" onclick="window.location.href='add_staff_page.php'">
            <i class="fas fa-user-plus"></i> Thêm Nhân Viên
        </div>
        <div class="nav-item" onclick="logout()">
            <i class="fas fa-sign-out-alt"></i> Đăng xuất
        </div>
    </div>
    <div class="main-content">
        <div class="header">
            <h1>Quản lý tài khoản</h1>
            <div class="search-bar">
                <input type="text" id="search-input" placeholder="Tìm kiếm...">
                <button class="btn btn-primary" onclick="searchUsers()">Tìm kiếm</button>
            </div>
        </div>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Tên đăng nhập</th>
                        <th>Email</th>
                        <th>Số điện thoại</th>
                        <th>Giới tính</th>
                        <th>Ngày sinh</th>
                        <th>Trạng thái</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody id="users-table-body">
                    <!-- Dữ liệu người dùng sẽ được thêm vào đây -->
                </tbody>
            </table>
        </div>
    </div>
    <div id="edit-modal" class="modal">
        <div class="modal-content">
            <h2>Chỉnh sửa thông tin người dùng</h2>
            <div id="edit-alert" class="alert"></div>
            <form id="edit-form">
                <input type="hidden" id="edit-user-id">
                <div class="form-group">
                    <label for="edit-username">Tên đăng nhập</label>
                    <input type="text" id="edit-username" readonly>
                </div>
                <div class="form-group">
                    <label for="edit-email">Email</label>
                    <input type="email" id="edit-email" required>
                </div>
                <div class="form-group">
                    <label for="edit-phone">Số điện thoại</label>
                    <input type="tel" id="edit-phone" required>
                </div>
                <div class="form-group">
                    <label for="edit-gender">Giới tính</label>
                    <select id="edit-gender" required>
                        <option value="male">Nam</option>
                        <option value="female">Nữ</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="edit-birthdate">Ngày sinh</label>
                    <input type="date" id="edit-birthdate" required>
                </div>
                <div class="form-group">
                    <label for="edit-is-locked">Trạng thái khóa</label>
                    <select id="edit-is-locked">
                        <option value="0">Hoạt động</option>
                        <option value="1">Khóa</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="edit-locked-until">Thời gian mở khóa</label>
                    <input type="datetime-local" id="edit-locked-until">
                </div>
                <div class="form-group" id="edit-password-group" style="display:none;">
                    <label for="edit-password">Mật khẩu mới (chỉ cho staff)</label>
                    <input type="password" id="edit-password" name="edit-password" placeholder="Để trống nếu không đổi">
                </div>
                <div style="display: flex; gap: 10px; margin-top: 20px;">
                    <button type="submit" class="btn btn-primary">Lưu thay đổi</button>
                    <button type="button" class="btn btn-danger" onclick="closeModal('edit-modal')">Hủy</button>
                </div>
            </form>
        </div>
    </div>
    <div id="lock-modal" class="modal">
        <div class="modal-content">
            <h2>Khóa tài khoản</h2>
            <div id="lock-alert" class="alert"></div>
            <form id="lock-form">
                <input type="hidden" id="lock-user-id">
                <div class="form-group">
                    <label for="lock-duration">Thời gian khóa</label>
                    <select id="lock-duration" name="lock-duration" onchange="toggleCustomDuration()">
                        <option value="permanent">Vĩnh viễn</option>
                        <option value="custom">Tùy chỉnh</option>
                    </select>
                </div>
                <div class="form-group" id="custom-duration" style="display: none;">
                    <label for="lock-hours">Số giờ khóa</label>
                    <input type="number" id="lock-hours" name="lock-hours" min="1" placeholder="Nhập số giờ">
                </div>
                <button type="submit" class="btn btn-primary">Khóa</button>
                <button type="button" class="btn btn-danger" onclick="closeModal('lock-modal')">Hủy</button>
            </form>
        </div>
    </div>
    <script>
        function loadUsers() {
            fetch('get_users.php')
                .then(response => response.json())
                .then(data => {
                    const tbody = document.getElementById('users-table-body');
                    tbody.innerHTML = '';
                    if (data.success && data.users.length > 0) {
                        data.users.forEach(user => {
                            let status = 'Hoạt động';
                            let isLocked = user.is_locked;
                            if (isLocked) {
                                const lockedUntil = user.locked_until ? new Date(user.locked_until) : null;
                                status = lockedUntil ? `Đã khóa (Mở vào ${lockedUntil.toLocaleString('vi-VN')})` : 'Đã khóa vĩnh viễn';
                            }

                            tbody.innerHTML += `
                                <tr>
                                    <td>${user.id}</td>
                                    <td>${user.username}</td>
                                    <td>${user.email}</td>
                                    <td>${user.phone}</td>
                                    <td>${user.gender === 'male' ? 'Nam' : 'Nữ'}</td>
                                    <td>${user.birthdate}</td>
                                    <td>${status}</td>
                                    <td>
                                        <button class="btn btn-primary" onclick="editUser(${user.id})">Sửa</button>
                                        ${isLocked ?
                                    `<button class="btn btn-success" onclick="unlockUser(${user.id})">Mở khóa</button>` :
                                    `<button class="btn btn-danger" onclick="showLockModal(${user.id})">Khóa</button>`}
                                    </td>
                                </tr>
                            `;
                        });
                    } else {
                        tbody.innerHTML = '<tr><td colspan="8">Không tìm thấy người dùng.</td></tr>';
                    }
                })
                .catch(error => {
                    console.error('Error loading users:', error);
                    document.getElementById('users-table-body').innerHTML = '<tr><td colspan="8">Lỗi khi tải dữ liệu.</td></tr>';
                });
        }

        function searchUsers() {
            const searchTerm = document.getElementById('search-input').value.toLowerCase();
            const rows = document.getElementById('users-table-body').getElementsByTagName('tr');
            for (let row of rows) {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchTerm) ? '' : 'none';
            }
        }

        function editUser(userId) {
            fetch(`get_user.php?id=${userId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const user = data.user;
                        document.getElementById('edit-user-id').value = user.id;
                        document.getElementById('edit-username').value = user.username;
                        document.getElementById('edit-email').value = user.email || '';
                        document.getElementById('edit-phone').value = user.phone || '';
                        document.getElementById('edit-gender').value = user.gender || 'male';
                        document.getElementById('edit-birthdate').value = user.birthdate || '';
                        document.getElementById('edit-is-locked').value = user.is_locked || 0;
                        document.getElementById('edit-locked-until').value = user.locked_until ? new Date(user.locked_until).toISOString().slice(0, 16) : '';
                        if (user.role === 'staff') {
                            document.getElementById('edit-password-group').style.display = '';
                        } else {
                            document.getElementById('edit-password-group').style.display = 'none';
                        }
                        document.getElementById('edit-modal').style.display = 'block';
                    } else {
                        console.error('Failed to fetch user:', data.message);
                    }
                })
                .catch(error => console.error('Error fetching user:', error));
        }

        function showLockModal(userId) {
            document.getElementById('lock-user-id').value = userId;
            document.getElementById('lock-modal').style.display = 'block';
        }

        function toggleCustomDuration() {
            const duration = document.getElementById('lock-duration').value;
            document.getElementById('custom-duration').style.display = duration === 'custom' ? 'block' : 'none';
        }

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
            if (modalId === 'lock-modal') {
                document.getElementById('lock-duration').value = 'permanent';
                document.getElementById('lock-hours').value = '';
                document.getElementById('custom-duration').style.display = 'none';
            }
        }

        function lockUser(userId, duration, hours) {
            const data = { user_id: userId };
            if (duration === 'custom' && hours) {
                data.hours = parseInt(hours);
            }

            fetch('lock_user.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            })
                .then(response => response.json())
                .then(data => {
                    const alert = document.getElementById('lock-alert');
                    alert.textContent = data.message;
                    alert.className = `alert alert-${data.success ? 'success' : 'error'}`;
                    alert.style.display = 'block';
                    if (data.success) {
                        setTimeout(() => {
                            closeModal('lock-modal');
                            loadUsers();
                        }, 1000);
                    }
                })
                .catch(error => console.error('Error locking user:', error));
        }

        function unlockUser(userId) {
            if (confirm('Bạn có chắc chắn muốn mở khóa người dùng này?')) {
                fetch('unlock_user.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ user_id: userId })
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            loadUsers();
                        } else {
                            alert(data.message);
                        }
                    })
                    .catch(error => console.error('Error unlocking user:', error));
            }
        }

        document.getElementById('lock-form').addEventListener('submit', function (e) {
            e.preventDefault();
            const userId = document.getElementById('lock-user-id').value;
            const duration = document.getElementById('lock-duration').value;
            const hours = document.getElementById('lock-hours').value;
            if (duration === 'custom' && (!hours || hours <= 0)) {
                const alert = document.getElementById('lock-alert');
                alert.textContent = 'Vui lòng nhập số giờ hợp lệ';
                alert.className = 'alert alert-error';
                alert.style.display = 'block';
                return;
            }
            lockUser(userId, duration, hours);
        });

        document.getElementById('edit-form').addEventListener('submit', function (e) {
            e.preventDefault();

            const userId = document.getElementById('edit-user-id').value;
            const userRole = document.getElementById('edit-password-group').style.display === 'none' ? 'user' : 'staff';
            const password = document.getElementById('edit-password').value;

            // Nếu là staff và có password, chỉ gửi password
            if (userRole === 'staff' && password) {
                const formData = new FormData();
                formData.append('edit-user-id', userId);
                formData.append('edit-password', password);

                fetch('update_user.php', {
                    method: 'POST',
                    body: formData
                })
                    .then(response => response.json())
                    .then(data => {
                        const alert = document.getElementById('edit-alert');
                        alert.textContent = data.message;
                        alert.className = `alert alert-${data.success ? 'success' : 'error'}`;
                        alert.style.display = 'block';
                        if (data.success) {
                            setTimeout(() => {
                                closeModal('edit-modal');
                                loadUsers();
                            }, 1000);
                        }
                    })
                    .catch(error => console.error('Error updating user:', error));
            } else {
                // Gửi toàn bộ form nếu không phải staff hoặc không có password
                const formData = new FormData(this);
                fetch('update_user.php', {
                    method: 'POST',
                    body: formData
                })
                    .then(response => response.json())
                    .then(data => {
                        const alert = document.getElementById('edit-alert');
                        alert.textContent = data.message;
                        alert.className = `alert alert-${data.success ? 'success' : 'error'}`;
                        alert.style.display = 'block';
                        if (data.success) {
                            setTimeout(() => {
                                closeModal('edit-modal');
                                loadUsers();
                            }, 1000);
                        }
                    })
                    .catch(error => console.error('Error updating user:', error));
            }
        });

        function logout() {
            const formData = new FormData();
            formData.append('csrf_token', '<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>');
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

        function showSection(section) {
            console.log(`Show section: ${section}`);
        }

        document.addEventListener('DOMContentLoaded', loadUsers);

        // Đóng modal khi click ra ngoài modal-content
        document.querySelectorAll('.modal').forEach(function (modal) {
            modal.addEventListener('mousedown', function (e) {
                if (e.target === modal) {
                    modal.style.display = 'none';
                }
            });
        });
    </script>
</body>

</html>