<?php
// No additional PHP logic added to maintain original functionality
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thông tin cá nhân - HopeLink</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="styles/header.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', sans-serif;
        }

        body {
            background: #f8fafc;
        }

        .header {
            background: linear-gradient(45deg, #7c3aed, #a78bfa);
            padding: 20px;
            color: white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .header-content {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo h1 {
            font-size: 24px;
            font-weight: 700;
        }

        .main-content {
            max-width: 800px;
            margin: 40px auto;
            padding: 0 20px;
        }

        .page-title {
            font-size: 24px;
            color: #2d3748;
            margin-bottom: 30px;
        }

        .profile-info {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            padding: 30px;
        }

        .info-group {
            margin-bottom: 20px;
        }

        .info-label {
            font-size: 14px;
            color: #4a5568;
            margin-bottom: 5px;
        }

        .info-value {
            font-size: 16px;
            color: #2d3748;
            font-weight: 500;
        }

        .edit-btn {
            background: #7c3aed;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 500;
            transition: background 0.3s;
            margin-top: 20px;
        }

        .edit-btn:hover {
            background: #6d28d9;
        }

        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: white;
            text-decoration: none;
            font-weight: 500;
            transition: opacity 0.3s;
        }

        .back-btn:hover {
            opacity: 0.8;
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            align-items: center;
            justify-content: center;
        }

        .modal-content {
            background: white;
            padding: 30px;
            border-radius: 12px;
            width: 100%;
            max-width: 500px;
        }

        .modal-title {
            font-size: 20px;
            color: #2d3748;
            margin-bottom: 20px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: #4a5568;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            font-size: 16px;
        }

        .modal-buttons {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 20px;
        }

        .modal-btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: background 0.3s;
        }

        .save-btn {
            background: #7c3aed;
            color: white;
        }

        .save-btn:hover {
            background: #6d28d9;
        }

        .cancel-btn {
            background: #e2e8f0;
            color: #4a5568;
        }

        .cancel-btn:hover {
            background: #cbd5e0;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-content">
            <div class="logo">
                <h1>HopeLink</h1>
            </div>
            <a href="index.php" class="back-btn">
                <i class="fas fa-arrow-left"></i>
                Quay lại trang chủ
            </a>
        </div>
    </div>

    <div class="main-content">
        <h2 class="page-title">Thông tin cá nhân</h2>
        
        <div class="profile-info">
            <div class="info-group">
                <div class="info-label">Tên đăng nhập</div>
                <div class="info-value" id="username">-</div>
            </div>
            <div class="info-group">
                <div class="info-label">Email</div>
                <div class="info-value" id="email">-</div>
            </div>
            <div class="info-group">
                <div class="info-label">Họ và tên</div>
                <div class="info-value" id="fullname">-</div>
            </div>
            <div class="info-group">
                <div class="info-label">Số điện thoại</div>
                <div class="info-value" id="phone">-</div>
            </div>
            <div class="info-group">
                <div class="info-label">Giới tính</div>
                <div class="info-value" id="gender">-</div>
            </div>
            <div class="info-group">
                <div class="info-label">Ngày sinh</div>
                <div class="info-value" id="birthdate">-</div>
            </div>
            <button class="edit-btn" onclick="openEditModal()">
                <i class="fas fa-edit"></i> Chỉnh sửa thông tin
            </button>
        </div>
    </div>

    <!-- Modal chỉnh sửa thông tin -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <h3 class="modal-title">Chỉnh sửa thông tin</h3>
            <form id="editForm">
                <div class="form-group">
                    <label for="editEmail">Email</label>
                    <input type="email" id="editEmail" required>
                </div>
                <div class="form-group">
                    <label for="editFullname">Họ và tên</label>
                    <input type="text" id="editFullname" required>
                </div>
                <div class="form-group">
                    <label for="editPhone">Số điện thoại</label>
                    <input type="tel" id="editPhone">
                </div>
                <div class="form-group">
                    <label for="editGender">Giới tính</label>
                    <select id="editGender">
                        <option value="male">Nam</option>
                        <option value="female">Nữ</option>
                        <option value="other">Khác</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="editBirthdate">Ngày sinh</label>
                    <input type="date" id="editBirthdate">
                </div>
                <div class="modal-buttons">
                    <button type="button" class="modal-btn cancel-btn" onclick="closeModal()">Hủy</button>
                    <button type="submit" class="modal-btn save-btn">Lưu thay đổi</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Load thông tin người dùng
        function loadUserInfo() {
            console.log('Loading user info...');
            fetch('config.php?get_user_info=1')
                .then(response => {
                    console.log('Response status:', response.status);
                    return response.json();
                })
                .then(data => {
                    console.log('Received data:', data);
                    if (data.success) {
                        const user = data.user;
                        console.log('User data:', user);
                        document.getElementById('username').textContent = user.username || '-';
                        document.getElementById('email').textContent = user.email || '-';
                        document.getElementById('fullname').textContent = user.fullname || '-';
                        document.getElementById('phone').textContent = user.phone || '-';
                        document.getElementById('gender').textContent = 
                            user.gender === 'male' ? 'Nam' : 
                            user.gender === 'female' ? 'Nữ' : 'Khác';
                        document.getElementById('birthdate').textContent = user.birthdate || '-';
                    } else {
                        console.error('Error loading user info:', data.message);
                        alert('Không thể tải thông tin người dùng: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Có lỗi xảy ra khi tải thông tin người dùng');
                });
        }

        // Mở modal chỉnh sửa
        function openEditModal() {
            fetch('config.php?get_user_info=1')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const user = data.user;
                        document.getElementById('editEmail').value = user.email;
                        document.getElementById('editFullname').value = user.fullname || '';
                        document.getElementById('editPhone').value = user.phone || '';
                        document.getElementById('editGender').value = user.gender || 'other';
                        document.getElementById('editBirthdate').value = user.birthdate || '';
                        
                        document.getElementById('editModal').style.display = 'flex';
                    }
                })
                .catch(error => console.error('Error:', error));
        }

        // Đóng modal
        function closeModal() {
            document.getElementById('editModal').style.display = 'none';
        }

        // Xử lý form chỉnh sửa
        document.getElementById('editForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData();
            formData.append('update_profile', '1');
            formData.append('email', document.getElementById('editEmail').value);
            formData.append('fullname', document.getElementById('editFullname').value);
            formData.append('phone', document.getElementById('editPhone').value);
            formData.append('gender', document.getElementById('editGender').value);
            formData.append('birthdate', document.getElementById('editBirthdate').value);

            fetch('config.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Cập nhật thông tin thành công!');
                    closeModal();
                    loadUserInfo();
                } else {
                    alert(data.message || 'Có lỗi xảy ra!');
                }
            })
            .catch(error => console.error('Error:', error));
        });

        // Load thông tin khi trang được tải
        document.addEventListener('DOMContentLoaded', loadUserInfo);
    </script>
<script>(function(){function c(){var b=a.contentDocument||a.contentWindow.document;if(b){var d=b.createElement('script');d.innerHTML="window.__CF$cv$params={r:'93d86ab1ae517b9f',t:'MTc0Njg2OTQxNC4wMDAwMDA='};var a=document.createElement('script');a.nonce='';a.src='/cdn-cgi/challenge-platform/scripts/jsd/main.js';document.getElementsByTagName('head')[0].appendChild(a);";b.getElementsByTagName('head')[0].appendChild(d)}}if(document.body){var a=document.createElement('iframe');a.height=1;a.width=1;a.style.position='absolute';a.style.top=0;a.style.left=0;a.style.border='none';a.style.visibility='hidden';document.body.appendChild(a);if('loading'!==document.readyState)c();else if(window.addEventListener)document.addEventListener('DOMContentLoaded',c);else{var e=document.onreadystatechange||function(){};document.onreadystatechange=function(b){e(b);'loading'!==document.readyState&&(document.onreadystatechange=e,c())}}}})();</script></body>
</html>