<?php
session_start();
require_once 'config.php';
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng Nhập - HopeLink</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="styles/header.css">
    <link rel="stylesheet" href="styles/login.css">
</head>

<body>
    <section class="auth-section">
        <div class="auth-container">
            <div id="auth-message" class="error" style="display: none;"></div>
            <form id="login-form">
                <h3><i class="fas fa-sign-in-alt"></i> Đăng Nhập</h3>
                <div class="form-group">
                    <label for="username">Tên đăng nhập</label>
                    <i class="fas fa-user"></i>
                    <input type="text" id="username" name="username" placeholder="Nhập tên đăng nhập" required>
                </div>
                <div class="form-group">
                    <label for="password">Mật khẩu</label>
                    <i class="fas fa-lock"></i>
                    <input type="password" id="password" name="password" placeholder="Nhập mật khẩu" required>
                </div>
                <button type="submit" class="auth-btn">Đăng Nhập</button>
                <div class="forgot-password">
                    <a href="forgot_password.php">Quên mật khẩu?</a>
                </div>
            </form>
            <div class="auth-link">
                <p>Chưa có tài khoản? <a href="register.php">Đăng ký ngay</a></p>
            </div>
        </div>
    </section>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const loginForm = document.getElementById('login-form');
            const authMessageDiv = document.getElementById('auth-message');

            loginForm.addEventListener('submit', async (e) => {
                e.preventDefault();
                authMessageDiv.style.display = 'none';

                const formData = new FormData(loginForm);

                try {
                    const response = await fetch('login_process.php', {
                        method: 'POST',
                        body: formData
                    });
                    const result = await response.json();

                    authMessageDiv.style.display = 'block';
                    authMessageDiv.textContent = result.message || 'Không có thông báo';
                    authMessageDiv.className = result.success ? 'success' : 'error';

                    if (result.success) {
                        await new Promise(resolve => setTimeout(resolve, 500)); 
                        window.location.href = result.redirect || 'index.php';
                    }
                } catch (error) {
                    console.error('Login error:', error);
                    authMessageDiv.style.display = 'block';
                    authMessageDiv.textContent = 'Có lỗi xảy ra, vui lòng thử lại sau.';
                    authMessageDiv.className = 'error';
                }
            });
        });
    </script>
</body>

</html>