<?php
session_start();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng Ký - HopeLink</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;700&display=swap" rel="stylesheet">
    <!-- Thêm Flatpickr CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="styles/header.css">
    <style>
        .auth-section {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(rgba(0, 0, 0, 0.4), rgba(0, 0, 0, 0.4)), url('https://images.unsplash.com/photo-1505455184862-554165e5f6ba?ixlib=rb-4.0.3&auto=format&fit=crop&w=1920&q=80') no-repeat center center/cover;
            padding: 20px;
        }

        .auth-container {
            max-width: 450px;
            width: 100%;
            background: rgba(255, 255, 255, 0.95);
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.08);
            transition: transform 0.3s ease;
        }

        .auth-container:hover {
            transform: translateY(-5px);
        }

        .auth-container h3 {
            font-size: 28px;
            font-weight: 700;
            color: #2d3748;
            margin-bottom: 20px;
            text-align: center;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .form-group {
            margin-bottom: 20px;
            position: relative;
        }

        .form-group label {
            font-size: 14px;
            font-weight: 500;
            color: #2d3748;
            display: block;
            margin-bottom: 8px;
        }

        .form-group input[type="text"],
        .form-group input[type="password"],
        .form-group input[type="email"],
        .form-group input[type="tel"] {
            width: 100%;
            height: 44px;
            padding: 10px 10px 10px 40px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            font-size: 16px;
            line-height: 24px;
            transition: border-color 0.3s ease;
        }

        .form-group input:focus {
            border-color: #5a67d8;
            outline: none;
        }

        .form-group i {
            position: absolute;
            top: 36px;
            left: 12px;
            color: #6b7280;
            font-size: 18px;
            line-height: 24px;
            cursor: pointer;
        }

        .form-group.radio-group {
            display: flex;
            align-items: center;
            gap: 20px;
            margin-bottom: 20px;
        }

        .form-group.radio-group label {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            font-size: 14px;
            font-weight: 500;
            color: #2d3748;
            cursor: pointer;
            margin-bottom: 0;
        }

        .form-group.radio-group input[type="radio"] {
            width: 18px;
            height: 18px;
            margin: 0;
            vertical-align: middle;
        }

        .auth-btn {
            background: linear-gradient(45deg, #5a67d8, #b794f4);
            color: #fff;
            padding: 14px;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            width: 100%;
            text-align: center;
            transition: transform 0.3s ease, box-shadow 0.3s ease, background 0.3s ease;
        }

        .auth-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 12px rgba(90, 103, 216, 0.3);
            background: #eddbff;
            color: #5a67d8;
        }

        .error, .success {
            text-align: center;
            margin-bottom: 15px;
            padding: 10px;
            border-radius: 8px;
            font-size: 14px;
        }

        .error {
            background: #fee2e2;
            color: #dc2626;
        }

        .success {
            background: #d1fae5;
            color: #10b981;
        }

        .auth-link {
            text-align: center;
            margin-top: 20px;
            font-size: 14px;
        }

        .auth-link a {
            color: #5a67d8;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
        }

        .auth-link a:hover {
            color: #b794f4;
        }

        footer {
            background: #2d3748;
            color: #edf2f7;
            padding: 60px 20px 20px;
        }

        .footer-content {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 40px;
        }

        .footer-section h3 {
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 15px;
        }

        .footer-section p,
        .footer-section a {
            font-size: 14px;
            color: #edf2f7;
            margin-bottom: 10px;
            text-decoration: none;
            font-weight: 400;
        }

        .footer-section a:hover {
            color: #b794f4;
        }

        .footer-bottom {
            text-align: center;
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #4b5563;
        }

        .footer-bottom p {
            font-size: 14px;
            color: #edf2f7;
            font-weight: 400;
        }

        /* CSS cho Flatpickr wrapper */
        .flatpickr-wrapper {
            position: relative;
        }

        .flatpickr-wrapper input[type="text"] {
            width: 100%;
            height: 44px;
            padding: 10px 10px 10px 40px; /* Đồng bộ padding với các input khác */
            border: 1px solid #d1d5db;
            border-radius: 8px;
            font-size: 16px;
            line-height: 24px;
            transition: border-color 0.3s ease;
        }

        .flatpickr-wrapper input:focus {
            border-color: #5a67d8;
            outline: none;
        }

        @media (max-width: 768px) {
            .auth-container {
                max-width: 90%;
                padding: 30px;
            }

            .form-group.radio  {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }

            .form-group.radio-group label {
                width: 100%;
            }
        }

        @media (max-width: 480px) {
            .auth-container h3 {
                font-size: 24px;
            }

            .form-group input {
                font-size: 14px;
                height: 40px;
            }

            .form-group i {
                top: 34px;
                font-size: 16px;
            }
        }
    </style>
</head>
<body>
    <section class="auth-section">
        <div class="auth-container">
            <h3><i class="fas fa-user-plus"></i> Đăng Ký</h3>
            <div id="auth-message" class="error" style="display: none;"></div>
            <form id="register-form">
                <div class="form-group">
                    <label for="username">Tên đăng nhập</label>
                    <i class="fas fa-user"></i>
                    <input type="text" id="username" name="username" placeholder="Nhập tên đăng nhập" required>
                </div>
                <div class="form-group">
                    <label for="email">Email</label>
                    <i class="fas fa-envelope"></i>
                    <input type="email" id="email" name="email" placeholder="Nhập email" required>
                </div>
                <div class="form-group">
                    <label for="phone">Số điện thoại</label>
                    <i class="fas fa-phone"></i>
                    <input type="tel" id="phone" name="phone" placeholder="Nhập số điện thoại" pattern="[0-9]{10,11}" title="Số điện thoại phải có 10-11 chữ số">
                </div>
                <div class="form-group">
                    <label for="password">Mật khẩu</label>
                    <i class="fas fa-lock"></i>
                    <input type="password" id="password" name="password" placeholder="Nhập mật khẩu" required>
                </div>
                <div class="form-group">
                    <label for="confirm_password">Xác nhận mật khẩu</label>
                    <i class="fas fa-lock"></i>
                    <input type="password" id="confirm_password" name="confirm_password" placeholder="Nhập lại mật khẩu" required>
                </div>
                <div class="form-group radio-group">
                    <label>Giới tính</label>
                    <label><input type="radio" name="gender" value="male" required> Nam</label>
                    <label><input type="radio" name="gender" value="female"> Nữ</label>
                </div>
                <div class="form-group flatpickr-wrapper">
                    <label for="birthdate">Ngày sinh (DD/MM/YYYY)</label>
                    <i class="fas fa-calendar-alt" data-toggle></i>
                    <input type="text" id="birthdate" name="birthdate" placeholder="DD/MM/YYYY" data-input required>
                </div>
                <button type="submit" class="auth-btn">Đăng Ký</button>
            </form>
            <div class="auth-link">
                <p>Đã có tài khoản? <a href="login.php">Đăng nhập ngay</a></p>
            </div>
        </div>
    </section>

    <!-- Thêm Flatpickr JS -->
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // Khởi tạo Flatpickr cho input ngày sinh
            flatpickr(".flatpickr-wrapper", {
                wrap: true, // Bọc input và icon để icon có thể kích hoạt lịch
                dateFormat: "d/m/Y", // Định dạng hiển thị DD/MM/YYYY
                allowInput: true, // Cho phép nhập tay
                locale: {
                    firstDayOfWeek: 1 // Bắt đầu tuần từ thứ Hai
                },
                onChange: function(selectedDates, dateStr, instance) {
                    // Cập nhật giá trị input khi chọn ngày
                    instance.element.querySelector('[data-input]').value = dateStr;
                }
            });

            // Xử lý submit form đăng ký
            document.getElementById('register-form').addEventListener('submit', async (e) => {
                e.preventDefault();
                const password = document.getElementById('password').value;
                const confirmPassword = document.getElementById('confirm_password').value;
                const phone = document.getElementById('phone').value;
                const birthdate = document.getElementById('birthdate').value;
                const messageDiv = document.getElementById('auth-message');

                // Kiểm tra xác nhận mật khẩu
                if (password !== confirmPassword) {
                    messageDiv.style.display = 'block';
                    messageDiv.textContent = 'Mật khẩu xác nhận không khớp.';
                    messageDiv.className = 'error';
                    return;
                }

                // Kiểm tra số điện thoại
                const phonePattern = /^[0-9]{10,11}$/;
                if (phone && !phonePattern.test(phone)) {
                    messageDiv.style.display = 'block';
                    messageDiv.textContent = 'Số điện thoại phải có 10-11 chữ số.';
                    messageDiv.className = 'error';
                    return;
                }

                // Kiểm tra định dạng ngày sinh
                const birthdatePattern = /^(0[1-9]|[12][0-9]|3[01])\/(0[1-9]|1[0-2])\/[0-9]{4}$/;
                if (!birthdatePattern.test(birthdate)) {
                    messageDiv.style.display = 'block';
                    messageDiv.textContent = 'Ngày sinh phải có định dạng DD/MM/YYYY.';
                    messageDiv.className = 'error';
                    return;
                }

                // Chuyển đổi DD/MM/YYYY thành YYYY-MM-DD
                const [day, month, year] = birthdate.split('/');
                const formattedBirthdate = `${year}-${month}-${day}`;

                const formData = new FormData(e.target);
                formData.append('register', true);
                formData.set('birthdate', formattedBirthdate); // Ghi đè birthdate với định dạng cho database

                try {
                    const response = await fetch('config.php', {
                        method: 'POST',
                        body: formData
                    });
                    const result = await response.json();

                    messageDiv.style.display = 'block';
                    messageDiv.textContent = result.message;
                    messageDiv.className = result.success ? 'success' : 'error';

                    if (result.success) {
                        setTimeout(() => {
                            window.location.href = 'login.php';
                        }, 1000);
                    }
                } catch (error) {
                    messageDiv.style.display = 'block';
                    messageDiv.textContent = 'Có lỗi xảy ra, vui lòng thử lại sau.';
                    messageDiv.className = 'error';
                    console.error('Registration error:', error);
                }
            });
        });
    </script>
</body>
</html>