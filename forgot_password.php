<?php
session_start();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quên Mật Khẩu - HopeLink</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="styles/header.css">
    <link rel="stylesheet" href="styles/forgot_password.css">
</head>
<body>
    <section class="auth-section">
        <div class="auth-container">
            <div id="message" class="error" style="display: none;"></div>
            
            <!-- Bước 1: Nhập email -->
            <div id="step1" class="step active">
                <h3><i class="fas fa-envelope"></i> Quên mật khẩu</h3>
                <div class="form-group">
                    <label for="email">Email</label>
                    <i class="fas fa-envelope"></i>
                    <input type="email" id="email" name="email" placeholder="Nhập email của bạn" required>
                </div>
                <button type="button" class="auth-btn" id="send-otp-btn">Gửi mã xác thực</button>
            </div>

            <!-- Bước 2: Nhập OTP -->
            <div id="step2" class="step">
                <h3><i class="fas fa-key"></i> Xác thực mã OTP</h3>
                <div class="form-group">
                    <label for="otp">Mã xác thực</label>
                    <i class="fas fa-key"></i>
                    <input type="text" id="otp" name="otp" placeholder="Nhập mã xác thực" required maxlength="6">
                </div>
                <button type="button" class="auth-btn" id="verify-otp-btn">Xác thực</button>
            </div>

            <!-- Bước 3: Đặt lại mật khẩu -->
            <div id="step3" class="step">
                <h3><i class="fas fa-lock"></i> Đặt lại mật khẩu</h3>
                <div class="form-group">
                    <label for="new-password">Mật khẩu mới</label>
                    <i class="fas fa-lock"></i>
                    <input type="password" id="new-password" name="new_password" placeholder="Nhập mật khẩu mới" required>
                </div>
                <div class="form-group">
                    <label for="confirm-password">Xác nhận mật khẩu</label>
                    <i class="fas fa-lock"></i>
                    <input type="password" id="confirm-password" name="confirm_password" placeholder="Nhập lại mật khẩu" required>
                </div>
                <button type="button" class="auth-btn" id="reset-btn">Đặt lại mật khẩu</button>
            </div>

            <div class="auth-link">
                <p>Đã nhớ mật khẩu? <a href="login.php">Đăng nhập</a></p>
            </div>
        </div>
    </section>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const messageDiv = document.getElementById('message');
            const step1 = document.getElementById('step1');
            const step2 = document.getElementById('step2');
            const step3 = document.getElementById('step3');
            const sendOtpBtn = document.getElementById('send-otp-btn');
            const verifyOtpBtn = document.getElementById('verify-otp-btn');
            const resetBtn = document.getElementById('reset-btn');

            function showMessage(message, isError = true) {
                messageDiv.textContent = message;
                messageDiv.className = isError ? 'error' : 'success';
                messageDiv.style.display = 'block';
            }

            function showStep(stepNumber) {
                [step1, step2, step3].forEach(step => step.classList.remove('active'));
                document.getElementById(`step${stepNumber}`).classList.add('active');
            }

            // Gửi mã OTP
            sendOtpBtn.addEventListener('click', async () => {
                const email = document.getElementById('email').value;
                // Validate email format
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!email || !emailRegex.test(email)) {
                    showMessage('Vui lòng nhập đúng định dạng email');
                    return;
                }

                const formData = new FormData();
                formData.append('forgot_password', '1');
                formData.append('email', email);

                try {
                    const response = await fetch('config.php', {
                        method: 'POST',
                        body: formData
                    });
                    
                    if (!response.ok) {
                        const text = await response.text();
                        console.error('Server response:', text);
                        showMessage(`Lỗi server: ${response.status} ${response.statusText}`);
                        return;
                    }
                    
                    let result;
                    try {
                        result = await response.json();
                    } catch (e) {
                        const text = await response.text();
                        console.error('JSON Parse Error:', e, 'Response:', text);
                        showMessage('Có lỗi xảy ra khi xử lý phản hồi từ server');
                        return;
                    }

                    showMessage(result.message, !result.success);
                    if (result.success) {
                        setTimeout(() => showStep(2), 1000);
                    }
                } catch (error) {
                    console.error('Fetch Error:', error);
                    showMessage('Có lỗi xảy ra. Vui lòng thử lại sau');
                }
            });

            // Xác thực OTP
            verifyOtpBtn.addEventListener('click', async () => {
                const email = document.getElementById('email').value;
                const otp = document.getElementById('otp').value;
                
                if (!otp) {
                    showMessage('Vui lòng nhập mã xác thực');
                    return;
                }

                const formData = new FormData();
                formData.append('verify_otp', '1');
                formData.append('email', email);
                formData.append('otp', otp);

                try {
                    const response = await fetch('config.php', {
                        method: 'POST',
                        body: formData
                    });
                    
                    if (!response.ok) {
                        const text = await response.text();
                        console.error('Server response:', text);
                        showMessage(`Lỗi server: ${response.status} ${response.statusText}`);
                        return;
                    }
                    
                    let result;
                    try {
                        result = await response.json();
                    } catch (e) {
                        const text = await response.text();
                        console.error('JSON Parse Error:', e, 'Response:', text);
                        showMessage('Có lỗi xảy ra khi xử lý phản hồi từ server');
                        return;
                    }

                    showMessage(result.message, !result.success);
                    if (result.success) {
                        setTimeout(() => showStep(3), 1000);
                    }
                } catch (error) {
                    console.error('Fetch Error:', error);
                    showMessage('Có lỗi xảy ra. Vui lòng thử lại sau');
                }
            });

            // Đặt lại mật khẩu
            resetBtn.addEventListener('click', async () => {
                const email = document.getElementById('email').value;
                const otp = document.getElementById('otp').value;
                const newPassword = document.getElementById('new-password').value;
                const confirmPassword = document.getElementById('confirm-password').value;
                
                if (!newPassword || !confirmPassword) {
                    showMessage('Vui lòng nhập đầy đủ mật khẩu mới');
                    return;
                }
                
                if (newPassword !== confirmPassword) {
                    showMessage('Mật khẩu xác nhận không khớp');
                    return;
                }

                const formData = new FormData();
                formData.append('reset_password', '1');
                formData.append('email', email);
                formData.append('otp', otp);
                formData.append('new_password', newPassword);

                try {
                    const response = await fetch('config.php', {
                        method: 'POST',
                        body: formData
                    });
                    
                    if (!response.ok) {
                        const text = await response.text();
                        console.error('Server response:', text);
                        showMessage(`Lỗi server: ${response.status} ${response.statusText}`);
                        return;
                    }
                    
                    let result;
                    try {
                        result = await response.json();
                    } catch (e) {
                        const text = await response.text();
                        console.error('JSON Parse Error:', e, 'Response:', text);
                        showMessage('Có lỗi xảy ra khi xử lý phản hồi từ server');
                        return;
                    }

                    showMessage(result.message, !result.success);
                    if (result.success) {
                        setTimeout(() => {
                            window.location.href = 'login.php';
                        }, 2000);
                    }
                } catch (error) {
                    console.error('Fetch Error:', error);
                    showMessage('Có lỗi xảy ra. Vui lòng thử lại sau');
                }
            });
        });
    </script>
</body>
</html> 