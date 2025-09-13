<?php
session_start();
error_log('Session in manage_participants.php: user_id=' . ($_SESSION['user_id'] ?? 'not set') . ', role=' . ($_SESSION['role'] ?? 'not set'));
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'staff') {
    header('Location: ../login.php');
    exit;
}
// Đảm bảo CSRF token tồn tại
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý Chuyến bay - HopeLink</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../styles/header.css">
    <link rel="stylesheet" href="../styles/manage_participants_staff.css">
</head>

<body>
    <div class="sidebar">
        <div class="logo">
            <a href="../index.php"
                style="display: flex; align-items: center; gap: 10px; text-decoration: none; color: white;">
                <img src="../images/hopelink-logo.svg" alt="HopeLink Logo">
                <h1>HopeLink</h1>
            </a>
        </div>
        <div class="nav-item" onclick="window.location.href='dashboard.php'">
            <i class="fas fa-tachometer-alt"></i> Dashboard
        </div>
        <div class="nav-item" onclick="window.location.href='manage_events.php'">
            <i class="fas fa-calendar-alt"></i> Quản lý sự kiện
        </div>
        <div class="nav-item active" onclick="window.location.href='manage_participants.php'">
            <i class="fas fa-users"></i> Quản lý người tham gia
        </div>
        <div class="nav-item" onclick="logout()">
            <i class="fas fa-sign-out-alt"></i> Đăng xuất
        </div>
    </div>
    <div class="main-content">
        <div class="header">
            <h1>Quản lý Người tham gia</h1>
            <div class="search-bar">
                <input type="text" id="search-input" placeholder="Tìm kiếm người tham gia...">
                <button class="btn btn-primary" onclick="searchParticipants()">Tìm kiếm</button>
            </div>
        </div>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Email</th>
                        <th>Số điện thoại</th>
                        <th>Họ tên</th>
                        <th>Sự kiện</th>
                        <th>Trạng thái</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody id="participants-table-body">
                    <!-- Dữ liệu người tham gia sẽ được thêm vào đây -->
                </tbody>
            </table>
        </div>
    </div>

    <div id="edit-modal" class="modal">
        <div class="modal-content">
            <h2>Chỉnh sửa thông tin người tham gia</h2>
            <div id="edit-alert" class="alert"></div>
            <form id="edit-form">
                <input type="hidden" id="edit-participant-id">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="edit-email">Email</label>
                        <input type="email" id="edit-email" required>
                    </div>
                    <div class="form-group">
                        <label for="edit-phone">Số điện thoại</label>
                        <input type="tel" id="edit-phone" required>
                    </div>
                    <div class="form-group">
                        <label for="edit-fullname">Họ tên</label>
                        <input type="text" id="edit-fullname" required>
                    </div>
                    <div class="form-group">
                        <label for="edit-user-id">Người dùng</label>
                        <select id="edit-user-id" required></select>
                    </div>
                </div>
                <div class="form-buttons">
                    <button type="submit" class="btn btn-primary">Lưu thay đổi</button>
                    <button type="button" class="btn btn-danger" onclick="closeModal()">Hủy</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        let users = [];
        let trips = [];

        function loadParticipants() {
            console.log('Bắt đầu tải danh sách người tham gia...');
            fetch('/TuThien/staff/get_participants.php')
                .then(response => {
                    console.log('Phản hồi từ get_participants.php:', response);
                    if (!response.ok) {
                        throw new Error('Lỗi mạng: ' + response.status + ' ' + response.statusText);
                    }
                    return response.json();
                })
                .then(data => {
                    console.log('Dữ liệu JSON nhận được:', data);
                    const tbody = document.getElementById('participants-table-body');
                    if (!tbody) {
                        console.error('Không tìm thấy phần tử tbody với id "participants-table-body"');
                        return;
                    }
                    tbody.innerHTML = '';
                    if (!data || !data.success) {
                        console.error('Lỗi từ server:', data?.message || 'Không có dữ liệu');
                        tbody.innerHTML = '<tr><td colspan="8">Lỗi: ' + (data?.message || 'Không có dữ liệu') + '</td></tr>';
                        return;
                    }
                    if (!data.participants || data.participants.length === 0) {
                        console.log('Không có người tham gia nào để hiển thị');
                        tbody.innerHTML = '<tr><td colspan="8">Không có dữ liệu người tham gia.</td></tr>';
                        return;
                    }
                    console.log('Số lượng người tham gia:', data.participants.length);
                    data.participants.forEach((participant, index) => {
                        console.log(`Hiển thị người tham gia ${index + 1}:`, participant);
                        const isCancelled = participant.is_cancelled == 1;
                        const refundStatus = participant.refund_status || 'Chưa hoàn tiền';
                        const statusText = isCancelled ?
                            `Đã hủy (${refundStatus === 'completed' ? 'Đã hoàn tiền' : 'Chưa hoàn tiền'})` :
                            'Đang hoạt động';
                        const row = `
                            <tr>
                                <td>${participant.id}</td>
                                <td>${participant.email}</td>
                                <td>${participant.phone}</td>
                                <td>${participant.fullname || 'N/A'}</td>
                                <td>${participant.trip_name || 'N/A'}</td>
                                <td>${statusText}</td>
                                <td>
                                    <button class="btn btn-primary" onclick="editParticipant(${participant.id})" ${isCancelled ? 'disabled' : ''}>Sửa</button>
                                </td>
                            </tr>
                        `;
                        tbody.innerHTML += row;
                    });
                })
                .catch(error => {
                    console.error('Lỗi khi tải danh sách người tham gia:', error);
                    const tbody = document.getElementById('participants-table-body');
                    if (tbody) {
                        tbody.innerHTML = '<tr><td colspan="8">Lỗi tải dữ liệu: ' + error.message + '</td></tr>';
                    }
                })
                .finally(() => {
                    console.log('Kết thúc tải danh sách người tham gia');
                });
        }

        function searchParticipants() {
            const searchTerm = document.getElementById('search-input').value.toLowerCase();
            const rows = document.getElementById('participants-table-body').getElementsByTagName('tr');
            for (let row of rows) {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchTerm) ? '' : 'none';
            }
        }

        function loadUsersAndTrips() {
            // Load danh sách người dùng
            fetch('/TuThien/staff/get_users.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        users = data.users;
                        populateSelect('edit-user-id', users, 'username', 'id');
                    }
                })
                .catch(error => console.error('Error loading users:', error));

            // Load danh sách sự kiện
            fetch('/TuThien/staff/get_events.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        trips = data.events.filter(trip => trip.is_active == 1 && trip.is_deleted == 0);
                        populateSelect('edit-trip-id', trips, 'name', 'id');
                    } else {
                        console.error('Error loading events:', data?.message || 'No data');
                    }
                })
                .catch(error => console.error('Error loading events:', error));
        }

        function populateSelect(selectId, data, labelKey = 'username', valueKey = 'id') {
            const select = document.getElementById(selectId);
            select.innerHTML = '';
            data.forEach(item => {
                const option = document.createElement('option');
                option.value = item[valueKey];
                option.textContent = item[labelKey] || 'N/A';
                select.appendChild(option);
            });
        }

        function editParticipant(participantId) {
            fetch(`/TuThien/staff/get_participant.php?id=${participantId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const participant = data.participant;
                        console.log('Editing participant:', participant); // Debug
                        document.getElementById('edit-participant-id').value = participant.id;
                        document.getElementById('edit-email').value = participant.email || '';
                        document.getElementById('edit-phone').value = participant.phone || '';
                        document.getElementById('edit-fullname').value = participant.fullname || '';
                        document.getElementById('edit-user-id').value = participant.user_id || '';
                        document.getElementById('edit-modal').style.display = 'block';
                    }
                })
                .catch(error => console.error('Error:', error));
        }

        function closeModal() {
            document.getElementById('edit-modal').style.display = 'none';
            document.getElementById('edit-alert').style.display = 'none';
        }

        function deleteParticipant(participantId) {
            if (confirm('Bạn có chắc chắn muốn xóa thông tin người tham gia này?')) {
                fetch('/TuThien/staff/delete_participant.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ participant_id: participantId })
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            loadParticipants();
                        } else {
                            alert(data.message);
                        }
                    })
                    .catch(error => console.error('Error:', error));
            }
        }

        document.getElementById('edit-form').addEventListener('submit', function (e) {
            e.preventDefault();
            const formData = new FormData();
            formData.append('participant_id', document.getElementById('edit-participant-id').value);
            formData.append('email', document.getElementById('edit-email').value);
            formData.append('phone', document.getElementById('edit-phone').value);
            formData.append('fullname', document.getElementById('edit-fullname').value);
            formData.append('user_id', document.getElementById('edit-user-id').value);
            formData.append('name', document.getElementById('edit-fullname').value);
            fetch('/TuThien/staff/update_participant.php', {
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
                            closeModal();
                            loadParticipants();
                        }, 1000);
                    }
                })
                .catch(error => console.error('Error:', error));
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
                    console.error('Error:', error);
                    alert('Đã xảy ra lỗi khi đăng xuất.');
                });
        }

        document.addEventListener('DOMContentLoaded', () => {
            loadUsersAndTrips();
            loadParticipants();
        });
    </script>
</body>

</html>