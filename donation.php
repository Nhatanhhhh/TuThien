<?php
session_start();
$isLoggedIn = isset($_SESSION['user_id']);
$username = $_SESSION['username'] ?? '';
$role = $_SESSION['role'] ?? '';
?>
<!DOCTYPE html>
<html lang="vi">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Nạp tiền vào Quỹ - HopeLink</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="styles/header.css">
  <link rel="stylesheet" href="styles/donation.css">
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
        <?php if (!$isLoggedIn): ?>
          <a href="login.php" class="join-btn" id="login-btn"><i class="fas fa-user-plus"></i> Tham gia ngay</a>
        <?php else: ?>
          <div class="user-info" id="user-info">
            <button class="user-btn">
              <span class="greeting"><i class="fas fa-user"></i> <span
                  id="username"><?php echo htmlspecialchars($username); ?></span></span>
            </button>
            <div class="user-dropdown">
              <div class="user-header">
                <div class="user-avatar" id="user-avatar"><?php echo strtoupper(substr($username, 0, 1)); ?></div>
                <div class="user-details">
                  <div class="user-name" id="dropdown-username"><?php echo htmlspecialchars($username); ?></div>
                  <div class="user-email" id="dropdown-email">
                    <?php echo htmlspecialchars($_SESSION['email'] ?? 'email@example.com'); ?>
                  </div>
                </div>
              </div>
              <!-- Trong phần dropdown menu -->
              <div class="user-stats">
                <div class="stat-item">
                  <span class="stat-value">0</span>
                  <span class="stat-label">Chuyến đi</span>
                </div>
                <div class="stat-item">
                  <span class="stat-value" id="user-balance">$0</span>
                  <span class="stat-label">Quỹ từ thiện</span>
                </div>
                <div class="stat-item">
                  <span class="stat-value">0</span>
                  <span class="stat-label">Giờ tình nguyện</span>
                </div>
              </div>
              <div class="dropdown-divider"></div>
              <a href="donation.php" class="dropdown-item"><i class="fas fa-wallet"></i> Nạp tiền vào quỹ</a>
              <a href="profile.php" class="dropdown-item"><i class="fas fa-user-circle"></i> Thông tin tài khoản</a>
              <a href="donation_history.php" class="dropdown-item"><i class="fas fa-history"></i> Lịch sử chuyến đi</a>
              <div class="dropdown-divider"></div>
              <a href="#" class="dropdown-item"
                onclick="handleLogout(event, '<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>')">
                <i class="fas fa-sign-out-alt"></i> Đăng xuất
              </a>
            </div>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </header>

  <main>
    <div class="deposit-container">
      <h2 class="form-title"><i class="fas fa-wallet" style="font-size: 24px;"></i> Nạp tiền vào Quỹ Từ Thiện</h2>
      <div class="current-balance">Quỹ hiện tại: <span id="current-balance">0 VND</span></div>
      <form id="deposit-form">
        <div class="form-group">
          <label for="deposit-amount">Số Tiền Nạp (Tối thiểu 10.000 VND)</label>
          <input type="number" id="deposit-amount" min="10000" placeholder="Nhập số tiền" required>
        </div>
        <button type="button" id="submit-deposit" class="submit-btn">Thanh Toán Qua VNPay</button>
      </form>
    </div>
  </main>

  <footer>
    <div class="footer-content">
      <div class="footer-section">
        <h3>Về HopeLink</h3>
        <p>HopeLink là nền tảng kết nối tình nguyện viên với các tổ chức từ thiện, lan tỏa yêu thương và xây dựng cộng
          đồng bền vững.</p>
      </div>
      <div class="footer-section">
        <h3>Liên kết nhanh</h3>
        <a href="index.php">Trang chủ</a>
        <a href="index.php#events">Sự kiện</a>
        <a href="charity_travel.php">Chuyến đi từ thiện</a>
        <a href="index.php#lienhe">Liên hệ</a>
      </div>
      <div class="footer-section">
        <h3>Liên hệ</h3>
        <p>Email: <a href="/cdn-cgi/l/email-protection#2744484953464453674f4857424b4e494c09485540">[email protected]</a>
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
    async function checkSession() {
      try {
        const response = await fetch('config.php?check_session=1');
        const result = await response.json();
        const userInfo = document.getElementById('user-info');
        const loginBtn = document.getElementById('login-btn');
        if (result.logged_in) {
          loginBtn.style.display = 'none';
          userInfo.style.display = 'block';
          document.getElementById('username').textContent = result.username;
          document.getElementById('dropdown-username').textContent = result.username;
          document.getElementById('dropdown-email').textContent = result.email || 'email@example.com';
          await updateAllBalances();
        } else {
          loginBtn.style.display = 'inline-flex';
          userInfo.style.display = 'none';
          alert('Vui lòng đăng nhập để nạp tiền vào quỹ.');
          window.location.href = 'login.php';
        }
      } catch (error) {
        console.error('Error checking session:', error);
      }
    }
    function formatCurrency(amount) {
      return new Intl.NumberFormat('vi-VN').format(amount);
    }

    function formatCompactCurrency(amount) {
      if (amount >= 1000000000000) { // nghìn tỷ
        return (amount / 1000000000000).toFixed(1).replace(/\.0$/, '') + 'T';
      } else if (amount >= 1000000000) { // tỷ
        return (amount / 1000000000).toFixed(1).replace(/\.0$/, '') + 'G';
      } else if (amount >= 1000000) { // triệu
        return (amount / 1000000).toFixed(1).replace(/\.0$/, '') + 'M';
      } else if (amount >= 1000) { // nghìn
        return (amount / 1000).toFixed(1).replace(/\.0$/, '') + 'k';
      }
      return amount.toString();
    }

    async function updateAllBalances() {
      try {
        const response = await fetch('config.php?get_user_info=1');
        const result = await response.json();
        if (result.success) {
          const balance = result.user.balance || 0;
          const balanceEl = document.getElementById('current-balance');
          if (balanceEl) balanceEl.textContent = formatCurrency(balance) + ' VND';
          const dropdownBalance = document.getElementById('user-balance');
          if (dropdownBalance) dropdownBalance.textContent = formatCompactCurrency(balance) + ' ₫';
        }
      } catch (e) {
        console.error('Lỗi khi lấy số dư quỹ:', e);
      }
    }
    document.getElementById('deposit-form').addEventListener('submit', async (e) => {
      e.preventDefault();
      handleDeposit();
    });

    document.getElementById('submit-deposit').addEventListener('click', async (e) => {
      e.preventDefault();
      handleDeposit();
    });

    async function handleDeposit() {
      const amount = document.getElementById('deposit-amount').value;
      if (!amount || amount < 10000) {
        alert('Số tiền nạp phải lớn hơn hoặc bằng 10.000 VND.');
        return;
      }
      try {
        const response = await fetch('config.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
          body: `deposit=1&amount=${amount}`
        });
        const result = await response.json();
        if (result.success) {
          window.location.href = result.paymentUrl;
        } else {
          console.error('Payment error:', result.message);
          alert(result.message || 'Lỗi khi nạp tiền. Vui lòng thử lại.');
        }
      } catch (error) {
        console.error('Error depositing:', error);
        alert('Lỗi khi nạp tiền: ' + error.message);
      }
    }
    document.addEventListener('DOMContentLoaded', function () {
      updateAllBalances();
      const userBtn = document.querySelector('.user-btn');
      const userInfo = document.getElementById('user-info');

      if (userBtn) {
        userBtn.addEventListener('click', function () {
          userInfo.classList.toggle('active');
          updateAllBalances();
        });

        // Close dropdown when clicking outside
        document.addEventListener('click', function (event) {
          if (!userInfo.contains(event.target)) {
            userInfo.classList.remove('active');
          }
        });
      }
    });

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
          showNotification('Lỗi', result.message || 'Đăng xuất thất bại. Vui lòng thử lại.', false);
        }
      } catch (error) {
        console.error('Logout error:', error);
        showNotification('Lỗi', 'Đã xảy ra lỗi khi đăng xuất.', false);
      }
    }

    // Cập nhật balance sau khi nạp tiền thành công
    function showSuccessMessage(amount) {
      const modal = document.createElement('div');
      modal.style.position = 'fixed';
      modal.style.top = '0';
      modal.style.left = '0';
      modal.style.width = '100%';
      modal.style.height = '100%';
      modal.style.backgroundColor = 'rgba(0,0,0,0.5)';
      modal.style.display = 'flex';
      modal.style.justifyContent = 'center';
      modal.style.alignItems = 'center';
      modal.style.zIndex = '1000';

      const content = document.createElement('div');
      content.style.backgroundColor = 'white';
      content.style.padding = '20px';
      content.style.borderRadius = '10px';
      content.style.textAlign = 'center';

      content.innerHTML = `
        <h3 style="color: #4CAF50;">Nạp tiền thành công!</h3>
        <p>Bạn đã nạp thành công ${formatCurrency(amount)} VND vào quỹ từ thiện.</p>
        <button onclick="this.parentElement.parentElement.remove()" 
                style="margin-top: 10px; padding: 8px 16px; background: #4CAF50; color: white; border: none; border-radius: 4px;">
            Đóng
        </button>
    `;

      modal.appendChild(content);
      document.body.appendChild(modal);

      // Cập nhật lại số dư
      updateAllBalances();
    }

    // Thêm vào phần script
    document.addEventListener('DOMContentLoaded', function () {
      const urlParams = new URLSearchParams(window.location.search);
      const success = urlParams.get('success');
      const error = urlParams.get('error');
      const message = urlParams.get('message');
      const amount = urlParams.get('amount');

      if (success === '1') {
        showSuccessMessage(amount);
        updateAllBalances();
      } else if (error === '1') {
        showErrorModal(decodeURIComponent(message));
      }
    });

    function showSuccessModal(title, message) {
      const modal = document.createElement('div');
      modal.style.position = 'fixed';
      modal.style.top = '0';
      modal.style.left = '0';
      modal.style.width = '100%';
      modal.style.height = '100%';
      modal.style.backgroundColor = 'rgba(0,0,0,0.5)';
      modal.style.display = 'flex';
      modal.style.justifyContent = 'center';
      modal.style.alignItems = 'center';
      modal.style.zIndex = '1000';

      const content = document.createElement('div');
      content.style.backgroundColor = 'white';
      content.style.padding = '20px';
      content.style.borderRadius = '10px';
      content.style.textAlign = 'center';

      content.innerHTML = `
        <h3 style="color: #4CAF50;">${title}</h3>
        <p>${message}</p>
        <button onclick="this.parentElement.parentElement.remove()" 
                style="margin-top: 10px; padding: 8px 16px; background: #4CAF50; color: white; border: none; border-radius: 4px;">
            Đóng
        </button>
    `;

      modal.appendChild(content);
      document.body.appendChild(modal);
    }

    function showErrorModal(message) {
      const modal = document.createElement('div');
      modal.style.position = 'fixed';
      modal.style.top = '0';
      modal.style.left = '0';
      modal.style.width = '100%';
      modal.style.height = '100%';
      modal.style.backgroundColor = 'rgba(0,0,0,0.5)';
      modal.style.display = 'flex';
      modal.style.justifyContent = 'center';
      modal.style.alignItems = 'center';
      modal.style.zIndex = '1000';

      const content = document.createElement('div');
      content.style.backgroundColor = 'white';
      content.style.padding = '20px';
      content.style.borderRadius = '10px';
      content.style.textAlign = 'center';

      content.innerHTML = `
        <h3 style="color: #f44336;">Lỗi</h3>
        <p>${message}</p>
        <button onclick="this.parentElement.parentElement.remove()" 
                style="margin-top: 10px; padding: 8px 16px; background: #f44336; color: white; border: none; border-radius: 4px;">
            Đóng
        </button>
    `;

      modal.appendChild(content);
      document.body.appendChild(modal);
    }
  </script>
</body>

</html>