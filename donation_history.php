<?php
// No additional PHP logic added to maintain original functionality
require_once 'config.php';
session_start();

// Kiểm tra role và chuyển hướng nếu không phải là user
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'user') {
    header('Location: index.php');
    exit;
}

if (isset($_SESSION['user_id'])) {
  $user_id = $_SESSION['user_id'];

  // Truy vấn các vé đã đặt
  $stmt = $pdo->prepare("
      SELECT t.*, tp.name, tp.email, tp.phone, tr.name as trip_name, 
             tr.date as trip_date, tr.time as trip_time, 
             tr.location as trip_location, tr.image as trip_image
      FROM tickets t
      JOIN trip_participants tp ON t.id = tp.ticket_id
      JOIN trips tr ON t.trip_id = tr.id
      WHERE t.user_id = ?
      ORDER BY t.created_at DESC
  ");
  $stmt->execute([$user_id]);
  $tickets = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
$isLoggedIn = isset($_SESSION['user_id']);
$username = $_SESSION['username'] ?? '';
$role = $_SESSION['role'] ?? '';

error_log("Session Debug - isLoggedIn: " . ($isLoggedIn ? 'true' : 'false'));
error_log("Session Debug - username: " . $username);
error_log("Session Debug - role: " . $role);

if (!$isLoggedIn) {
  error_log("User not logged in, dropdown will not render.");
}

?>
<!DOCTYPE html>
<html lang="vi">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Lịch sử chuyến đi - HopeLink</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="styles/header.css">
  <link rel="stylesheet" href="styles/donation_history.css">
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
        <a href="admin/dashboard.php" id="admin-link" style="display: none;"><i class="fas fa-user-shield"></i> Quản lý
          khách hàng</a>
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

  <section id="donation_history" style="padding: 100px 20px;">
    <h2 class="section-title">Lịch sử chuyến đi</h2>
    <div class="history-container">
      <?php if (!empty($tickets)): ?>
        <div class="table-responsive">
          <table class="ticket-table">
            <thead>
              <tr>
                <th>STT</th>
                <th>Tên chuyến đi</th>
                <th>Ngày</th>
                <th>Thời gian</th>
                <th>Địa điểm</th>
                <th>Số tiền</th>
                <th>Trạng thái</th>
                <th>Ngày đặt</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($tickets as $index => $ticket): ?>
                <tr>
                  <td><?= $index + 1 ?></td>
                  <td><?= htmlspecialchars($ticket['trip_name']) ?></td>
                  <td><?= date('d/m/Y', strtotime($ticket['trip_date'])) ?></td>
                  <td><?= htmlspecialchars($ticket['trip_time']) ?></td>
                  <td><?= htmlspecialchars($ticket['trip_location']) ?></td>
                  <td><?= number_format($ticket['amount'], 0, ',', '.') ?> ₫</td>
                  <td>
                    <?php
                    $statusText = [
                      'pending' => 'Đang chờ',
                      'confirmed' => 'Đã xác nhận',
                      'cancelled' => 'Đã hủy'
                    ];
                    echo $statusText[$ticket['status'] ?? 'Unknown'];
                    ?>
                  </td>
                  <td><?= date('d/m/Y H:i', strtotime($ticket['created_at'])) ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php else: ?>
        <div class="no-history">
          Chưa có chuyến đi nào được ghi nhận.
        </div>
      <?php endif; ?>
    </div>
  </section>

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
        <a href="#lienhe">Liên hệ</a>
      </div>
      <div class="footer-section">
        <h3>Liên hệ</h3>
        <p>Email: <a href="mailto:support@hopelink.org">support@hopelink.org</a></p>
        <p>Hotline: 1800-1234</p>
        <p>Địa chỉ: 123 Đường Từ Thiện, Hà Nội</p>
      </div>
    </div>
    <div class="footer-bottom">
      <p>© 2025 HopeLink. Mọi quyền được bảo lưu.</p>
    </div>
  </footer>

  <div id="notification-modal" class="modal">
    <div class="modal-content">
      <h3 id="modal-title"></h3>
      <p id="modal-message"></p>
      <button id="modal-close-btn" class="modal-close-btn">Đóng</button>
    </div>
  </div>

  <script>
    let donations = [];
    let trips = [];

    document.addEventListener('DOMContentLoaded', () => {
      const savedDonations = sessionStorage.getItem('donationData');
      const savedTrips = localStorage.getItem('trips');
      if (savedDonations) {
        donations = [JSON.parse(savedDonations)];
      }
      if (savedTrips) {
        trips = JSON.parse(savedTrips);
      }
      updateHistoryList();
      checkSession();

      document.getElementById('modal-close-btn').addEventListener('click', () => {
        document.getElementById('notification-modal').style.display = 'none';
      });

      const userBtn = document.querySelector('.user-btn');
      const userInfo = document.querySelector('.user-info');
      userBtn.addEventListener('click', () => {
        userInfo.classList.toggle('active');
      });

      document.addEventListener('click', (e) => {
        if (!userInfo.contains(e.target) && !userBtn.contains(e.target)) {
          userInfo.classList.remove('active');
        }
      });

      const dropdown = document.querySelector('.dropdown');
      const dropdownToggle = document.querySelector('.dropdown-toggle');
      dropdownToggle.addEventListener('click', (e) => {
        e.preventDefault();
        dropdown.classList.toggle('active');
      });
    });

    async function checkSession() {
      try {
        const response = await fetch('config.php?check_session=1');
        const result = await response.json();
        const loginBtn = document.getElementById('login-btn');
        const userInfo = document.getElementById('user-info');
        const usernameElement = document.getElementById('username');
        const dropdownUsername = document.getElementById('dropdown-username');
        const dropdownEmail = document.getElementById('dropdown-email');
        const userAvatar = document.getElementById('user-avatar');
        const adminLink = document.getElementById('admin-link');
        if (result.logged_in) {
          loginBtn.style.display = 'none';
          userInfo.style.display = 'block';
          usernameElement.textContent = result.username;
          dropdownUsername.textContent = result.username;
          dropdownEmail.textContent = result.email || 'email@example.com';
          userAvatar.textContent = result.username ? result.username.charAt(0).toUpperCase() : 'U';
          if (result.role === 'admin') {
            adminLink.style.display = 'inline-flex';
          } else {
            adminLink.style.display = 'none';
          }
        } else {
          loginBtn.style.display = 'inline-flex';
          userInfo.style.display = 'none';
          adminLink.style.display = 'none';
        }
      } catch (error) {
        console.error('Error checking session:', error);
      }
    }

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

    function updateHistoryList() {
      const historyGrid = document.getElementById('history-grid');
      const noHistory = document.getElementById('no-history');
      historyGrid.innerHTML = '';

      if (donations.length === 0) {
        noHistory.style.display = 'block';
        historyGrid.style.display = 'none';
        return;
      }

      noHistory.style.display = 'none';
      historyGrid.style.display = 'grid';

      donations.forEach((donation) => {
        const trip = trips[donation.tripIndex];
        if (trip) {
          const card = document.createElement('div');
          card.className = 'card';
          card.innerHTML = `
            <img src="${trip.image}" alt="${trip.name}">
            <div class="card-content">
              <h3>${trip.name}</h3>
              <p>Ngày: ${trip.date}</p>
              <p>Thời gian: ${trip.time}</p>
              <p>Địa điểm: ${trip.location}</p>
              <p>Họ và Tên: ${donation.userName}</p>
              <p>Email: ${donation.userEmail}</p>
              <p>Số điện thoại: ${donation.userPhone}</p>
            </div>
          `;
          historyGrid.appendChild(card);
        }
      });
    }

    function showNotification(title, message, isSuccess) {
      const modal = document.getElementById('notification-modal');
      const modalIcon = document.createElement('i');
      modalIcon.className = `modal-icon ${isSuccess ? 'success fas fa-check-circle' : 'error fas fa-exclamation-circle'}`;
      const modalTitle = document.getElementById('modal-title');
      const modalMessage = document.getElementById('modal-message');

      modalTitle.textContent = title;
      modalMessage.textContent = message;
      modal.style.display = 'flex';

      const existingIcon = modal.querySelector('.modal-icon');
      if (existingIcon) existingIcon.remove();
      modal.querySelector('.modal-content').insertBefore(modalIcon, modalTitle);
    }

    // Hàm cập nhật số dư
    async function updateUserBalance() {
      try {
        const response = await fetch('config.php?get_user_info=1');
        const result = await response.json();

        if (result.success && result.user.balance !== undefined) {
          const balanceElement = document.getElementById('user-balance');
          balanceElement.textContent = formatCurrency(result.user.balance) + ' ₫';
        }
      } catch (error) {
        console.error('Error updating balance:', error);
      }
    }

    // Hàm format tiền tệ
    function formatCurrency(amount) {
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

    // Gọi hàm khi trang được tải
    document.addEventListener('DOMContentLoaded', function () {
      updateUserBalance();

      // Cập nhật lại khi dropdown được mở
      const userBtn = document.querySelector('.user-btn');
      if (userBtn) {
        userBtn.addEventListener('click', updateUserBalance);
      }
    });

    // Xử lý hover và click cho bảng
    document.addEventListener('DOMContentLoaded', function () {
      const rows = document.querySelectorAll('.ticket-table tbody tr');

      rows.forEach(row => {
        // Hiệu ứng hover
        row.addEventListener('mouseenter', function () {
          this.style.transform = 'scale(1.01)';
          this.style.boxShadow = '0 4px 8px rgba(0,0,0,0.1)';
          this.style.transition = 'all 0.3s ease';
        });

        row.addEventListener('mouseleave', function () {
          this.style.transform = 'scale(1)';
          this.style.boxShadow = 'none';
        });

        // Xử lý click (có thể mở modal chi tiết)
        row.addEventListener('click', function () {
          // Lấy ID vé từ data attribute nếu cần
          const ticketId = this.getAttribute('data-ticket-id');
          // Hiển thị modal chi tiết
          showTicketDetails(ticketId);
        });
      });
    });

    function showTicketDetails(ticketId) {
      // Gọi AJAX để lấy chi tiết vé hoặc hiển thị thông tin đơn giản
      console.log("Xem chi tiết vé: " + ticketId);
      // Bạn có thể triển khai modal hiển thị chi tiết ở đây
    }

    // Dropdown menu functionality
    const dropdown = document.querySelector('.dropdown');
    const dropdownToggle = document.querySelector('.dropdown-toggle');
    
    if (dropdownToggle) {
      dropdownToggle.addEventListener('click', (e) => {
        if (window.innerWidth <= 768) {
          e.preventDefault();
          dropdown.classList.toggle('active');
        }
      });
    }

    // User dropdown functionality
    const userBtn = document.querySelector('.user-btn');
    const userInfo = document.querySelector('.user-info');

    if (userBtn && userInfo) {
      userBtn.addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        userInfo.classList.toggle('active');
      });

      document.addEventListener('click', function(e) {
        if (!userInfo.contains(e.target)) {
          userInfo.classList.remove('active');
        }
      });
    }
  </script>
</body>

</html>