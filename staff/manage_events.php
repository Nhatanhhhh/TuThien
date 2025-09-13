<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'staff') {
    header('Location: ../login.php');
    exit;
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
    <link rel="stylesheet" href="../styles/manage_events_staff.css">
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
        <div class="nav-item active" onclick="window.location.href='manage_events.php'">
            <i class="fas fa-calendar-alt"></i> Quản lý sự kiện
        </div>
        <div class="nav-item" onclick="window.location.href='manage_participants.php'">
            <i class="fas fa-users"></i> Quản lý người tham gia
        </div>
        <div class="nav-item" onclick="logout()">
            <i class="fas fa-sign-out-alt"></i> Đăng xuất
        </div>
    </div>

    <div class="main-content">
        <div class="header">
            <h2>Quản lý Sự kiện</h2>
            <div class="search-bar">
                <input type="text" id="search-input" placeholder="Tìm kiếm sự kiện...">
                <button class="btn btn-primary" onclick="searchEvents()">Tìm kiếm</button>
            </div>
        </div>

        <div id="alert" class="alert"></div>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Tên sự kiện</th>
                        <th>Mô tả</th>
                        <th>Hình ảnh</th>
                        <th>Ngày</th>
                        <th>Thời gian</th>
                        <th>Địa điểm</th>
                        <th>Trạng thái</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody id="events-table-body"></tbody>
            </table>
        </div>
    </div>

    <div id="edit-modal" class="modal">
        <div class="modal-content">
            <h2>Chỉnh sửa thông tin sự kiện</h2>
            <div id="edit-alert" class="alert"></div>
            <form id="edit-form">
                <input type="hidden" id="edit-event-id">
                <div class="form-group">
                    <label for="edit-name">Tên sự kiện</label>
                    <input type="text" id="edit-name" required>
                </div>
                <div class="form-group">
                    <label for="edit-date">Ngày</label>
                    <input type="date" id="edit-date" required>
                </div>
                <div class="form-group">
                    <label for="edit-time">Thời gian</label>
                    <input type="time" id="edit-time" required>
                </div>
                <div class="form-group">
                    <label for="edit-location">Địa điểm</label>
                    <input type="text" id="edit-location" required>
                </div>
                <div class="form-group">
                    <label for="edit-description">Mô tả</label>
                    <textarea id="edit-description" rows="4" required></textarea>
                </div>
                <div class="form-group">
                    <label>Hình ảnh</label>
                    <div class="image-upload-container">
                        <div class="image-upload-group">
                            <input type="text" id="edit-image" placeholder="Nhập URL hình ảnh">
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
                <div class="form-buttons">
                    <button type="submit" class="btn btn-primary">Lưu thay đổi</button>
                    <button type="button" class="btn btn-secondary" onclick="closeModal()">Hủy</button>
                </div>
            </form>
        </div>
    </div>

    <div id="cancel-modal" class="modal">
        <div class="modal-content">
            <h2>Hủy chuyến đi</h2>
            <div id="cancel-alert" class="alert"></div>
            <form id="cancel-form">
                <input type="hidden" id="cancel-event-id">
                <div class="form-group">
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
        function loadEvents() {
            fetch('get_events.php')
                .then(response => response.json())
                .then(data => {
                    const tbody = document.getElementById('events-table-body');
                    tbody.innerHTML = '';
                    if (data.success && data.events) {
                        data.events.forEach(event => {
                            const imageElement = event.image ? `<img src="${event.image}" alt="${event.name}">` : 'Không có';
                            const row = `
                                <tr>
                                    <td>${event.id}</td>
                                    <td>${event.name}</td>
                                    <td>${event.description.substring(0, 50)}${event.description.length > 50 ? '...' : ''}</td>
                                    <td>${imageElement}</td>
                                    <td>${event.date}</td>
                                    <td>${event.time}</td>
                                    <td>${event.location}</td>
                                    <td>${event.is_active ? 'Đang hoạt động' : 'Đã hủy'}</td>
                                    <td>
                                        <button class="btn btn-primary" onclick="editEvent(${event.id})">Sửa</button>
                                        <button class="btn btn-danger" onclick="cancelEvent(${event.id})" ${!event.is_active ? 'disabled' : ''}>Hủy</button>
                                    </td>
                                </tr>
                            `;
                            tbody.innerHTML += row;
                        });
                    } else {
                        tbody.innerHTML = '<tr><td colspan="9">Không có sự kiện nào.</td></tr>';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    tbody.innerHTML = '<tr><td colspan="9">Lỗi tải dữ liệu.</td></tr>';
                });
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
            fetch(`get_event.php?id=${eventId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const event = data.event;
                        document.getElementById('edit-event-id').value = event.id;
                        document.getElementById('edit-name').value = event.name;
                        document.getElementById('edit-description').value = event.description;
                        document.getElementById('edit-image').value = event.image || '';
                        document.getElementById('edit-date').value = event.date;
                        document.getElementById('edit-time').value = event.time;
                        document.getElementById('edit-location').value = event.location;
                        updateImagePreview('edit-image', 'edit-image-preview', 'edit-image-upload');
                        document.getElementById('edit-modal').style.display = 'block';
                    }
                })
                .catch(error => console.error('Error:', error));
        }

        function closeModal() {
            document.getElementById('edit-modal').style.display = 'none';
            document.getElementById('edit-alert').style.display = 'none';
            const preview = document.getElementById('edit-image-preview');
            preview.style.display = 'none';
            preview.parentElement.querySelector('.preview-placeholder').style.display = 'flex';
            document.getElementById('edit-image-upload').value = '';
        }

        function cancelEvent(eventId) {
            document.getElementById('cancel-event-id').value = eventId;
            document.getElementById('cancel-reason').value = '';
            document.getElementById('cancel-alert').style.display = 'none';
            document.getElementById('cancel-modal').style.display = 'block';
        }

        function closeCancelModal() {
            document.getElementById('cancel-modal').style.display = 'none';
            document.getElementById('cancel-alert').style.display = 'none';
        }

        function updateImagePreview(inputId, previewId, fileInputId) {
            const urlInput = document.getElementById(inputId);
            const preview = document.getElementById(previewId);
            const fileInput = document.getElementById(fileInputId);
            const placeholder = preview.parentElement.querySelector('.preview-placeholder');

            preview.style.display = 'none';
            placeholder.style.display = 'flex';
            preview.src = '';

            if (fileInput.files && fileInput.files[0]) {
                const reader = new FileReader();
                reader.onload = function (e) {
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                    placeholder.style.display = 'none';
                    urlInput.value = '';
                };
                reader.readAsDataURL(fileInput.files[0]);
            } else if (urlInput.value.trim()) {
                const url = urlInput.value.trim();
                if (/^(ftp|http|https):\/\/[^ "]+$/.test(url)) {
                    const tempImage = new Image();
                    tempImage.src = url;
                    tempImage.onload = function () {
                        preview.src = url;
                        preview.style.display = 'block';
                        placeholder.style.display = 'none';
                    };
                    tempImage.onerror = function () {
                        preview.style.display = 'none';
                        placeholder.style.display = 'flex';
                    };
                }
            }
        }

        document.getElementById('edit-image').addEventListener('input', () => {
            updateImagePreview('edit-image', 'edit-image-preview', 'edit-image-upload');
        });

        document.getElementById('edit-image-upload').addEventListener('change', () => {
            updateImagePreview('edit-image', 'edit-image-preview', 'edit-image-upload');
        });

        document.getElementById('cancel-form').addEventListener('submit', function (e) {
            e.preventDefault();
            const eventId = document.getElementById('cancel-event-id').value;
            const reason = document.getElementById('cancel-reason').value;

            fetch('cancel_event.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ event_id: eventId, cancellation_reason: reason })
            })
                .then(response => response.json())
                .then(data => {
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
                })
                .catch(error => console.error('Error:', error));
        });

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

            fetch('update_event.php', {
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

        function logout() {
            fetch('../logout.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'csrf_token=' + encodeURIComponent('<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>')
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        window.location.href = data.redirect;
                    } else {
                        alert(data.message || 'Đăng xuất thất bại.');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Lỗi khi đăng xuất.');
                });
        }

        document.addEventListener('DOMContentLoaded', loadEvents);

        // Thêm hàm để đóng modal khi click bên ngoài
        function closeModalOnOutsideClick(event) {
            const modal = document.getElementById('edit-modal');
            const cancelModal = document.getElementById('cancel-modal');
            
            if (event.target === modal) {
                closeModal();
            }
            if (event.target === cancelModal) {
                closeCancelModal();
            }
        }

        // Thêm event listener cho modal
        document.addEventListener('DOMContentLoaded', function() {
            const editModal = document.getElementById('edit-modal');
            const cancelModal = document.getElementById('cancel-modal');
            
            editModal.addEventListener('click', closeModalOnOutsideClick);
            cancelModal.addEventListener('click', closeModalOnOutsideClick);
        });
    </script>
</body>

</html>