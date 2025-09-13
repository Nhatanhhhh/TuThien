<?php
session_start();
error_log('Session in manage_events.php: user_id=' . ($_SESSION['user_id'] ?? 'not set') . ', role=' . ($_SESSION['role'] ?? 'not set'));
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
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
    <title>Quản lý Sự kiện - HopeLink</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../styles/header.css">
    <link rel="stylesheet" href="../styles/manage_events.css">
    <link rel="stylesheet" href="../styles/update_events.css">
    <link rel="stylesheet" href="../styles/create_event.css">
</head>

<body>
    <div class="sidebar">
        <h2>Admin Panel</h2>
        <div class="nav-item" onclick="window.location.href='dashboard.php'">
            <i class="fas fa-users"></i> Quản lý tài khoản
        </div>
        <div class="nav-item active" onclick="window.location.href='manage_events.php'">
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
            <h1>Quản lý Sự kiện</h1>
            <div class="search-bar">
                <input type="text" id="search-input" placeholder="Tìm kiếm sự kiện...">
                <button class="btn btn-primary" onclick="searchEvents()">Tìm kiếm</button>
                <button class="btn btn-primary" onclick="showCreateModal()">Thêm sự kiện</button>
            </div>
        </div>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Tên</th>
                        <th>Mô tả</th>
                        <th>Ngày</th>
                        <th>Giờ</th>
                        <th>Địa điểm</th>
                        <th>Hình ảnh</th>
                        <th>Trạng thái</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody id="events-table-body">
                    <!-- Dữ liệu sự kiện sẽ được thêm vào đây -->
                </tbody>
            </table>
        </div>
    </div>
    <div id="edit-modal" class="update-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-edit"></i> Chỉnh sửa thông tin sự kiện</h2>
                <button type="button" class="close-btn" onclick="closeModal()"><i class="fas fa-times"></i></button>
            </div>
            <div id="edit-alert" class="alert"></div>
            <form id="edit-form">
                <input type="hidden" id="edit-event-id">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="edit-name"><i class="fas fa-heading"></i> Tên sự kiện</label>
                        <input type="text" id="edit-name" required placeholder="Nhập tên sự kiện">
                    </div>
                    <div class="form-group">
                        <label for="edit-date"><i class="fas fa-calendar"></i> Ngày</label>
                        <input type="date" id="edit-date" required>
                    </div>
                    <div class="form-group">
                        <label for="edit-time"><i class="fas fa-clock"></i> Giờ</label>
                        <input type="time" id="edit-time" required>
                    </div>
                    <div class="form-group">
                        <label for="edit-location"><i class="fas fa-map-marker-alt"></i> Địa điểm</label>
                        <input type="text" id="edit-location" required placeholder="Nhập địa điểm">
                    </div>
                    <div class="form-group full-width">
                        <label for="edit-description"><i class="fas fa-align-left"></i> Mô tả</label>
                        <textarea id="edit-description" required
                            placeholder="Nhập mô tả chi tiết về sự kiện"></textarea>
                    </div>
                    <div class="form-group">
                        <label for="edit-is-active"><i class="fas fa-toggle-on"></i> Trạng thái</label>
                        <select id="edit-is-active" required>
                            <option value="1">Hiển thị</option>
                            <option value="0">Ẩn</option>
                        </select>
                    </div>
                    <div class="form-group full-width">
                        <label><i class="fas fa-image"></i> Hình ảnh</label>
                        <div class="image-upload-container">
                            <div class="image-upload-group">
                                <input type="text" id="edit-image"
                                    placeholder="Nhập URL hình ảnh (ví dụ: https://example.com/image.jpg)">
                                <div class="upload-buttons">
                                    <input type="file" id="edit-image-upload" accept="image/*" hidden>
                                    <label for="edit-image-upload" class="btn btn-secondary">
                                        <i class="fas fa-upload"></i> Chọn ảnh
                                    </label>
                                </div>
                            </div>
                            <div class="image-preview-container">
                                <img id="edit-image-preview" class="preview-image" alt="Xem trước hình ảnh">
                                <div class="preview-placeholder">
                                    <i class="fas fa-image"></i>
                                    <span>Xem trước hình ảnh</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="form-buttons">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Lưu thay đổi
                    </button>
                    <button type="button" class="btn btn-danger" onclick="closeModal()">
                        <i class="fas fa-times"></i> Hủy
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal tạo sự kiện mới -->
    <div id="create-modal" class="update-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-plus"></i> Thêm sự kiện mới</h2>
                <button type="button" class="close-btn" onclick="closeCreateModal()"><i
                        class="fas fa-times"></i></button>
            </div>
            <div id="create-alert" class="alert"></div>
            <form id="create-form">
                <div class="form-grid">
                    <div class="form-group">
                        <label for="create-name"><i class="fas fa-heading"></i> Tên sự kiện</label>
                        <input type="text" id="create-name" required placeholder="Nhập tên sự kiện">
                    </div>
                    <div class="form-group">
                        <label for="create-date"><i class="fas fa-calendar"></i> Ngày</label>
                        <input type="date" id="create-date" required>
                    </div>
                    <div class="form-group">
                        <label for="create-time"><i class="fas fa-clock"></i> Giờ</label>
                        <input type="time" id="create-time" required>
                    </div>
                    <div class="form-group">
                        <label for="create-location"><i class="fas fa-map-marker-alt"></i> Địa điểm</label>
                        <input type="text" id="create-location" required placeholder="Nhập địa điểm">
                    </div>
                    <div class="form-group full-width">
                        <label for="create-description"><i class="fas fa-align-left"></i> Mô tả</label>
                        <textarea id="create-description" required
                            placeholder="Nhập mô tả chi tiết về sự kiện"></textarea>
                    </div>
                    <div class="form-group">
                        <label for="create-is-active"><i class="fas fa-toggle-on"></i> Trạng thái</label>
                        <select id="create-is-active" required>
                            <option value="1">Hiển thị</option>
                            <option value="0">Ẩn</option>
                        </select>
                    </div>
                    <div class="form-group full-width">
                        <label><i class="fas fa-image"></i> Hình ảnh</label>
                        <div class="image-upload-container">
                            <div class="image-upload-group">
                                <input type="text" id="create-image"
                                    placeholder="Nhập URL hình ảnh (ví dụ: https://example.com/image.jpg)">
                                <div class="upload-buttons">
                                    <input type="file" id="create-image-upload" accept="image/*" hidden>
                                    <label for="create-image-upload" class="btn btn-secondary">
                                        <i class="fas fa-upload"></i> Chọn ảnh
                                    </label>
                                </div>
                            </div>
                            <div class="image-preview-container">
                                <img id="create-image-preview" class="preview-image" alt="Xem trước hình ảnh">
                                <div class="preview-placeholder">
                                    <i class="fas fa-image"></i>
                                    <span>Xem trước hình ảnh</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="form-buttons">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Tạo mới
                    </button>
                    <button type="button" class="btn btn-danger" onclick="closeCreateModal()">
                        <i class="fas fa-times"></i> Hủy
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal hủy chuyến đi -->
    <div id="cancel-modal" class="modal">
        <div class="modal-content">
            <h2>Hủy chuyến đi</h2>
            <div id="cancel-alert" class="alert"></div>
            <form id="cancel-form">
                <input type="hidden" id="cancel-event-id">
                <div class="form-group full-width">
                    <label for="cancel-reason">Lý do hủy chuyến đi</label>
                    <textarea id="cancel-reason" required
                        placeholder="Ví dụ: Do thời tiết xấu, chuyến đi bị hủy."></textarea>
                </div>
                <div class="form-buttons">
                    <button type="submit" class="btn btn-danger">Xác nhận hủy</button>
                    <button type="button" class="btn btn-secondary" onclick="closeCancelModal()">Đóng</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const csrfToken = '<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>';
        function loadEvents() {
            console.log('Bắt đầu tải sự kiện...');
            fetch('/TuThien/admin/get_events.php')
                .then(response => {
                    console.log('Phản hồi từ get_events.php:', response);
                    if (!response.ok) {
                        throw new Error('Lỗi mạng: ' + response.status + ' ' + response.statusText);
                    }
                    return response.json();
                })
                .then(data => {
                    console.log('Dữ liệu JSON nhận được:', data);
                    const tbody = document.getElementById('events-table-body');
                    if (!tbody) {
                        console.error('Không tìm thấy phần tử tbody với id "events-table-body"');
                        return;
                    }
                    tbody.innerHTML = '';
                    if (!data || !data.success) {
                        console.error('Lỗi từ server:', data?.message || 'Không có dữ liệu');
                        tbody.innerHTML = '<tr><td colspan="9">Lỗi: ' + (data?.message || 'Không có dữ liệu') + '</td></tr>';
                        return;
                    }
                    if (!data.events || data.events.length === 0) {
                        console.log('Không có sự kiện nào để hiển thị');
                        tbody.innerHTML = '<tr><td colspan="9">Không có sự kiện nào.</td></tr>';
                        return;
                    }
                    console.log('Số lượng sự kiện:', data.events.length);
                    data.events.forEach((event, index) => {
                        console.log(`Hiển thị sự kiện ${index + 1}:`, event);
                        const imageElement = event.image ? `<img src="${event.image}" alt="${event.name}" style="width: 50px; height: auto;">` : '';
                        const statusText = event.is_deleted == 1 ? 'Đã xóa' : (event.is_cancelled == 1 ? 'Đã hủy' : (event.is_active == 1 ? 'Hiển thị' : 'Ẩn'));
                        const cancelButton = event.is_cancelled == 0 && event.is_deleted == 0 ? `<button class="btn btn-warning" onclick="cancelEvent(${event.id})">Hủy chuyến</button>` : '';
                        const restoreButton = (event.is_deleted == 1 || event.is_cancelled == 1) ? `<button class="btn btn-success" onclick="restoreEvent(${event.id})">Khôi phục</button>` : '';
                        const row = `
                    <tr>
                        <td>${event.id}</td>
                        <td>${event.name}</td>
                        <td>${event.description.substring(0, 50)}${event.description.length > 50 ? '...' : ''}</td>
                        <td>${event.date}</td>
                        <td>${event.time}</td>
                        <td>${event.location}</td>
                        <td>${imageElement}</td>
                        <td>${statusText}</td>
                        <td>
                            <button class="btn btn-primary" onclick="editEvent(${event.id})">Sửa</button>
                            <!-- <button class="btn btn-danger" onclick="deleteEvent(${event.id})" ${event.is_deleted == 1 ? 'disabled' : ''}>Xóa</button> -->
                            ${cancelButton}
                            ${restoreButton}
                        </td>
                    </tr>
                `;
                        tbody.innerHTML += row;
                    });
                })
                .catch(error => {
                    console.error('Lỗi khi tải sự kiện:', error);
                    const tbody = document.getElementById('events-table-body');
                    if (tbody) {
                        tbody.innerHTML = '<tr><td colspan="9">Lỗi tải dữ liệu: ' + error.message + '</td></tr>';
                    }
                })
                .finally(() => {
                    console.log('Kết thúc tải sự kiện');
                });
        }

        function restoreEvent(eventId) {
            if (confirm('Bạn có chắc chắn muốn khôi phục sự kiện này?')) {
                fetch('/TuThien/admin/restore_event.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ event_id: eventId })
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            loadEvents();
                        } else {
                            alert(data.message);
                        }
                    })
                    .catch(error => console.error('Error:', error));
            }
        }

        function searchEvents() {
            const searchTerm = document.getElementById('search-input').value.toLowerCase();
            const rows = document.getElementById('events-table-body').getElementsByTagName('tr');
            for (let row of rows) {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchTerm) ? '' : 'none';
            }
        }

        function editEvent(eventId) {
            fetch(`/TuThien/admin/get_event.php?id=${eventId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const event = data.event;
                        document.getElementById('edit-event-id').value = event.id;
                        document.getElementById('edit-name').value = event.name;
                        document.getElementById('edit-description').value = event.description;
                        document.getElementById('edit-date').value = event.date;
                        document.getElementById('edit-time').value = event.time;
                        document.getElementById('edit-location').value = event.location;
                        const editImageInput = document.getElementById('edit-image');
                        editImageInput.value = event.image || '';
                        updateImagePreview('edit-image', 'edit-image-preview', 'edit-image-upload');
                        document.getElementById('edit-is-active').value = event.is_active;
                        document.getElementById('edit-modal').style.display = 'block';
                    }
                })
                .catch(error => console.error('Error:', error));
        }

        function cancelEvent(eventId) {
            document.getElementById('cancel-event-id').value = eventId;
            document.getElementById('cancel-reason').value = '';
            document.getElementById('cancel-alert').style.display = 'none';
            document.getElementById('cancel-modal').style.display = 'block';
        }

        function closeModal() {
            document.getElementById('edit-modal').style.display = 'none';
            const preview = document.getElementById('edit-image-preview');
            const placeholder = preview.parentElement.querySelector('.preview-placeholder');
            preview.style.display = 'none';
            placeholder.style.display = 'flex';
            document.getElementById('edit-image-upload').value = '';
            document.getElementById('edit-image').value = '';
        }

        function closeCancelModal() {
            document.getElementById('cancel-modal').style.display = 'none';
            document.getElementById('cancel-alert').style.display = 'none';
        }

        function deleteEvent(eventId) {
            if (confirm('Bạn có chắc chắn muốn xóa sự kiện này? Nó sẽ được đánh dấu là đã xóa.')) {
                fetch('/TuThien/admin/delete_event.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ event_id: eventId })
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            loadEvents();
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
            formData.append('event_id', document.getElementById('edit-event-id').value);
            formData.append('name', document.getElementById('edit-name').value);
            formData.append('description', document.getElementById('edit-description').value);
            formData.append('date', document.getElementById('edit-date').value);
            formData.append('time', document.getElementById('edit-time').value);
            formData.append('location', document.getElementById('edit-location').value);
            const imageFile = document.getElementById('edit-image-upload').files[0];
            if (imageFile) {
                formData.append('image_file', imageFile);
            } else {
                formData.append('image', document.getElementById('edit-image').value);
            }
            formData.append('is_active', document.getElementById('edit-is-active').value);
            fetch('/TuThien/admin/update_event.php', {
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
                            loadEvents();
                        }, 1000);
                    }
                })
                .catch(error => console.error('Error:', error));
        });

        document.getElementById('cancel-form').addEventListener('submit', function (e) {
            e.preventDefault();
            const eventId = document.getElementById('cancel-event-id').value;
            const reason = document.getElementById('cancel-reason').value;

            fetch('/TuThien/admin/cancel_event.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    event_id: eventId,
                    cancellation_reason: reason,
                    csrf_token: csrfToken // Use the dynamic token
                })
            })
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! Status: ${response.status}`);
                    }
                    return response.text();
                })
                .then(text => {
                    console.log('Phản hồi thô từ server:', text);
                    try {
                        const data = JSON.parse(text);
                        const alert = document.getElementById('cancel-alert');
                        alert.textContent = data.message;
                        alert.className = `alert alert-${data.success ? 'success' : 'error'}`;
                        alert.style.display = 'block';
                        if (data.success) {
                            setTimeout(() => {
                                closeCancelModal();
                                loadEvents();
                            }, 1000);
                        }
                    } catch (error) {
                        throw new Error('Phản hồi không phải JSON hợp lệ: ' + error.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    const alert = document.getElementById('cancel-alert');
                    alert.textContent = 'Lỗi khi hủy chuyến đi: ' + error.message;
                    alert.className = 'alert alert-error';
                    alert.style.display = 'block';
                });
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

        function showSection(section) {
            console.log(`Show section: ${section}`);
        }

        document.addEventListener('DOMContentLoaded', loadEvents);

        function showCreateModal() {
            document.getElementById('create-modal').style.display = 'block';
            const preview = document.getElementById('create-image-preview');
            const placeholder = preview.parentElement.querySelector('.preview-placeholder');
            preview.style.display = 'none';
            placeholder.style.display = 'flex';
            document.getElementById('create-image').value = '';
            document.getElementById('create-image-upload').value = '';
        }

        function closeCreateModal() {
            document.getElementById('create-modal').style.display = 'none';
            document.getElementById('create-alert').style.display = 'none';
            const preview = document.getElementById('create-image-preview');
            const placeholder = preview.parentElement.querySelector('.preview-placeholder');
            preview.style.display = 'none';
            placeholder.style.display = 'flex';
            document.getElementById('create-image-upload').value = '';
        }

        document.getElementById('create-form').addEventListener('submit', function (e) {
            e.preventDefault();
            const formData = new FormData();
            formData.append('name', document.getElementById('create-name').value);
            formData.append('description', document.getElementById('create-description').value);
            formData.append('date', document.getElementById('create-date').value);
            formData.append('time', document.getElementById('create-time').value);
            formData.append('location', document.getElementById('create-location').value);
            const imageFile = document.getElementById('create-image-upload').files[0];
            if (imageFile) {
                formData.append('image_file', imageFile);
            } else {
                formData.append('image', document.getElementById('create-image').value);
            }
            formData.append('is_active', document.getElementById('create-is-active').value);

            fetch('/TuThien/admin/create_event.php', {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    const alert = document.getElementById('create-alert');
                    alert.textContent = data.message;
                    alert.className = `alert alert-${data.success ? 'success' : 'error'}`;
                    alert.style.display = 'block';
                    if (data.success) {
                        setTimeout(() => {
                            closeCreateModal();
                            loadEvents();
                        }, 1000);
                    }
                })
                .catch(error => console.error('Lỗi khi tạo sự kiện:', error));
        });

        // Cập nhật hàm updateImagePreview
        function updateImagePreview(inputId, previewId, fileInputId) {
            const urlInput = document.getElementById(inputId);
            const preview = document.getElementById(previewId);
            const fileInput = fileInputId ? document.getElementById(fileInputId) : null;
            const placeholder = preview.parentElement.querySelector('.preview-placeholder');

            // Reset trạng thái ban đầu
            preview.style.display = 'none';
            placeholder.style.display = 'flex';
            preview.src = ''; // Xóa nguồn hình ảnh để tránh hiển thị hình cũ

            // Ưu tiên file upload nếu có
            if (fileInput && fileInput.files && fileInput.files[0]) {
                const file = fileInput.files[0];
                const reader = new FileReader();
                reader.onload = function (e) {
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                    placeholder.style.display = 'none';
                    urlInput.value = ''; // Xóa URL khi chọn file
                };
                reader.onerror = function () {
                    console.error('Lỗi khi đọc file hình ảnh');
                    preview.style.display = 'none';
                    placeholder.style.display = 'flex';
                };
                reader.readAsDataURL(file);
            } else {
                const url = urlInput.value.trim();
                if (url) {
                    const isValidUrl = /^(ftp|http|https):\/\/[^ "]+$/.test(url);
                    if (isValidUrl) {
                        // Tạo một hình ảnh tạm để kiểm tra xem URL có tải được không
                        const tempImage = new Image();
                        tempImage.src = url;
                        tempImage.onload = function () {
                            preview.src = url;
                            preview.style.display = 'block';
                            placeholder.style.display = 'none';
                        };
                        tempImage.onerror = function () {
                            console.warn('Không thể tải hình ảnh từ URL:', url);
                            preview.style.display = 'none';
                            placeholder.style.display = 'flex';
                        };
                    } else {
                        console.warn('URL không hợp lệ:', url);
                        preview.style.display = 'none';
                        placeholder.style.display = 'flex';
                    }
                } else {
                    preview.style.display = 'none';
                    placeholder.style.display = 'flex';
                }
            }
        }

        // Thêm sự kiện thay đổi input để xem trước hình ảnh
        ['edit-image', 'create-image'].forEach(inputId => {
            const input = document.getElementById(inputId);
            input.addEventListener('input', () => {
                updateImagePreview(inputId, `${inputId}-preview`, `${inputId}-upload`);
            });
        });

        // Thêm sự kiện cho file upload
        ['edit-image-upload', 'create-image-upload'].forEach(inputId => {
            const input = document.getElementById(inputId);
            input.addEventListener('change', () => {
                const baseId = inputId.replace('-upload', '');
                updateImagePreview(`${baseId}`, `${baseId}-preview`, inputId);
            });
        });
    </script>
</body>

</html>